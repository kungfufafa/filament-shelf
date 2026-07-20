<?php

namespace App\Filament\Resources;

use App\Enums\AssetCondition;
use App\Enums\AssetTransferDocumentType;
use App\Filament\Resources\AssetTransferResource\Pages;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\BusinessEntity;
use App\Models\JobTitle;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Section as ComponentSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AssetTransferResource extends Resource
{
    protected static ?string $model = AssetTransfer::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function form(Schema $form): Schema
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');

        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make('Informasi Transfer')
                            ->schema([
                                TextInput::make('letter_number')
                                    ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                                    ->extraInputAttributes(['readonly' => true]),
                                Select::make('business_entity_id')
                                    ->options(fn () => Cache::remember('business_entity_options', 300, fn () => BusinessEntity::orderBy('name')->pluck('name', 'id')->toArray()))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set(
                                        'letter_number',
                                        AssetTransfer::generateLetterNumber(BusinessEntity::find($state), null)
                                    )),
                                Select::make('from_user_id')
                                    ->relationship('fromUser', 'name')
                                    ->required()
                                    ->live()
                                    ->searchable()
                                    ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                                    ->options(function () {
                                        return User::where(fn ($query) => $query->whereNull('role')->orWhere('role', '!=', 'super_admin'))
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $set('to_user_id', null);
                                        $set('details', null);

                                        // Update the asset_id options based on the new from_user_id
                                        $fromUserId = $get('from_user_id');
                                        $assets = Asset::query();
                                        $details = [];

                                        if ($fromUserId) {
                                            $user = User::find($fromUserId);

                                            if ($user && $user->hasRole('general_affair')) {
                                                // GA can dispatch any asset that is currently available
                                                $assets->where('condition_status', AssetCondition::Available->value);
                                                $details = [['asset_id' => '', 'equipment' => '']];
                                            } else {
                                                $assets->where('recipient_id', $fromUserId)
                                                    ->whereIn('condition_status', AssetCondition::transferableValues())
                                                    ->notLockedForOpenRequest();

                                                $details = $assets->get()->map(function ($asset) {
                                                    return ['asset_id' => $asset->id, 'equipment' => ''];
                                                })->toArray();
                                            }
                                        }

                                        $set('details', $details);
                                    }),
                                Select::make('to_user_id')
                                    ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->getSearchResultsUsing(function (string $search, callable $get): array {
                                        $fromUserId = $get('from_user_id');

                                        return User::query()
                                            ->where('name', 'like', "%{$search}%")
                                            ->where(fn ($q) => $q->whereNull('role')->orWhere('role', '!=', 'super_admin'))
                                            ->when($fromUserId, fn ($q) => $q->where('id', '!=', $fromUserId))
                                            ->with('jobTitle')
                                            ->orderBy('name')
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $jobTitle = $user->jobTitle?->title ?? 'N/A';

                                                return [$user->id => "{$user->name} - {$jobTitle}"];
                                            })
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        $user = User::with('jobTitle')->find($value);
                                        if (! $user) {
                                            return null;
                                        }
                                        $jobTitle = $user->jobTitle?->title ?? 'N/A';

                                        return "{$user->name} - {$jobTitle}";
                                    })
                                    ->options(function (callable $get) {
                                        $fromUserId = $get('from_user_id');
                                        $query = User::query()
                                            ->where(fn ($q) => $q->whereNull('role')->orWhere('role', '!=', 'super_admin'))
                                            ->when($fromUserId, fn ($q) => $q->where('id', '!=', $fromUserId));

                                        return $query
                                            ->with('jobTitle')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                $jobTitle = $user->jobTitle?->title ?? 'N/A';

                                                return [$user->id => "{$user->name} - {$jobTitle}"];
                                            })
                                            ->all();
                                    })
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('business_entity_id')
                                            ->options(fn () => Cache::remember('business_entity_options', 300, fn () => BusinessEntity::orderBy('name')->pluck('name', 'id')->toArray()))
                                            ->searchable(),
                                        Select::make('job_title_id')
                                            ->options(fn () => Cache::remember('job_title_options', 300, fn () => JobTitle::orderBy('title')->pluck('title', 'id')->toArray()))
                                            ->searchable(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $user = User::create([
                                            'name' => $data['name'],
                                            'business_entity_id' => $data['business_entity_id'],
                                            'job_title_id' => $data['job_title_id'],
                                        ]);
                                        Cache::forget('user_options');

                                        return $user->id;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                DatePicker::make('transfer_date')
                                    ->native(false)
                                    ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                                    ->required(),
                            ])
                            ->columnSpan(1),
                        FileUpload::make('document')
                            ->preserveFilenames()
                            ->directory('document')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) Str::of($file->getClientOriginalName())
                                    ->prepend(mt_rand(100, 999).'-')
                            )
                            ->columnSpan(1)
                            ->hidden(fn ($context) => $context === 'create'),
                    ])
                    ->columns(1)
                    ->columnSpan(1),
                Repeater::make('details')
                    ->relationship('details')
                    ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                    ->schema([
                        Select::make('asset_id')
                            ->live()
                            ->required()
                            ->searchable()
                            ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin)
                            ->options(function (callable $get) {
                                $fromUserId = $get('../../from_user_id');
                                $selectedAssets = collect($get('../../details'))->pluck('asset_id')->filter()->all();
                                $query = Asset::query();

                                if ($fromUserId) {
                                    $user = User::find($fromUserId);
                                    if ($user && $user->hasRole('general_affair')) {
                                        $query->where('condition_status', AssetCondition::Available->value);
                                    } else {
                                        $query->where('recipient_id', $fromUserId)
                                            ->whereIn('condition_status', AssetCondition::transferableValues());
                                    }
                                }

                                // Kunci aset yang sedang diajukan penarikan/perbaikan (belum ditindak lanjuti)
                                $query->notLockedForOpenRequest();

                                // Exclude already selected assets
                                if (! empty($selectedAssets)) {
                                    $query->whereNotIn('id', $selectedAssets);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                return Asset::find($value)?->name;
                            }),
                        TextInput::make('equipment')
                            ->disabled(fn ($context) => $context === 'edit' && ! $isSuperAdmin),
                    ])
                    ->required()
                    ->hidden(fn (callable $get) => ! $get('from_user_id')) // Hide the repeater when from_user_id is not selected
                    ->columns(2)
                    ->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessEntity.name') // Mengambil nama dari relasi businessEntity
                    ->badge()
                    ->color(fn ($record) => $record->businessEntity->color)
                    ->getStateUsing(fn ($record) => $record->businessEntity->name)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors(AssetTransferDocumentType::colors())
                    ->getStateUsing(function ($record) {
                        return $record->status;
                    })
                    ->toggleable(),
                TextColumn::make('letter_number')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('fromUser.name')
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('toUser.name')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('transfer_date')->date()->toggleable(),
                TextColumn::make('document')
                    ->url(fn ($record) => $record && $record->document ? Storage::url($record->document) : null, true) // Membuat kolom URL untuk unduh
                    ->openUrlInNewTab()
                    ->getStateUsing(fn ($record) => $record && $record->document ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('businessEntity')->relationship('businessEntity', 'name'),
                SelectFilter::make('status')
                    ->options(AssetTransferDocumentType::options())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->forDocumentType($data['value'])
                        : $query),
                SelectFilter::make('fromUser')
                    ->relationship('fromUser', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('toUser')
                    ->relationship('toUser', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetTransfers::route('/'),
            'create' => Pages\CreateAssetTransfer::route('/create'),
            'edit' => Pages\EditAssetTransfer::route('/{record}/edit'),
            'view' => Pages\ViewAssetTransfer::route('/{record}'),
        ];
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                ComponentSection::make('📄 Informasi Transfer Aset')
                    ->schema([
                        ComponentsGrid::make(2) // Membuat grid dengan 2 kolom untuk tampilan yang lebih rapi
                            ->schema([
                                TextEntry::make('letter_number')
                                    ->extraAttributes([
                                        'style' => 'font-weight: bold; color: #1a202c;', // Menggunakan styling khusus
                                    ]),
                                TextEntry::make('status')
                                    ->badge() // Menambahkan Badge untuk memberikan warna berdasarkan status
                                    ->colors(AssetTransferDocumentType::colors()),
                                TextEntry::make('fromUser.name')
                                    ->icon('heroicon-o-user')
                                    ->columnSpan(1),
                                TextEntry::make('toUser.name')
                                    ->icon('heroicon-o-user')
                                    ->columnSpan(1),
                                TextEntry::make('transfer_date')
                                    ->date()
                                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('d M Y'))
                                    ->extraAttributes(['style' => 'font-weight: bold;']),
                                TextEntry::make('businessEntity.name')
                                    ->icon('heroicon-o-briefcase'),
                                TextEntry::make('document')
                                    ->url(fn ($record) => $record->document ? Storage::url($record->document) : null, true)
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-document')
                                    ->getStateUsing(fn ($record) => $record && $record->document ? 'Unduh Dokumen' : 'Tidak Ada Dokumen')
                                    ->extraAttributes(['style' => 'font-weight:bold;color:#007bff;']),
                            ]),
                    ])
                    ->columns(2) // Atur kolom agar menampilkan data dalam dua kolom
                    ->collapsible(), // Bisa diklik untuk membuka atau menutup
                ComponentSection::make('📦 Detail Aset yang Ditransfer')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->schema([
                                ComponentsGrid::make(2)  // Atur dalam 2 kolom
                                    ->schema([
                                        TextEntry::make('asset.name')
                                            ->extraAttributes(['style' => 'font-weight: bold;']),  // Font lebih tebal untuk nama aset
                                        TextEntry::make('equipment'),
                                    ]),
                            ])
                            ->columnSpan(2),  // Luaskan kolom agar detailnya rapi
                    ])
                    ->collapsible()  // Section collapsible
                    ->columns(2), // Atur agar section ditampilkan dalam 2 kolom
            ]);
    }
}
