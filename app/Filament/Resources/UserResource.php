<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\RelationManagers\AssetTransfersRelationManager;
use App\Filament\Resources\UserResource\Pages;
use App\Models\BusinessEntity;
use App\Models\JobTitle;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $form): Schema
    {
        $isSuperAdmin = Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin');

        return $form
            ->schema([
                Section::make('Informasi Pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('business_entity_id')
                            ->options(BusinessEntity::orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable(),
                        Select::make('job_title_id')
                            ->options(JobTitle::orderBy('title')->pluck('title', 'id')->toArray())
                            ->searchable(),
                        TextInput::make('whatsapp_number')
                            ->tel()
                            ->placeholder('081234567890')
                            ->helperText('Dipakai untuk pengingat aset via WhatsApp/Fonnte.')
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? preg_replace('/[^\d+]/', '', (string) $state) : null)
                            ->maxLength(32),
                    ]),
                Section::make('Akses & Keamanan')
                    ->schema([
                        TextInput::make('username')
                            ->maxLength(255)
                            ->unique(User::class, 'username', ignoreRecord: true)
                            ->visible($isSuperAdmin),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->rules(['not_regex:/[\r\n]/'])
                            ->visible($isSuperAdmin),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->visible($isSuperAdmin),
                        DateTimePicker::make('email_verified_at')
                            ->visible($isSuperAdmin),
                        Select::make('role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'admin' => 'Admin',
                                'general_affair' => 'General Affair',
                                'staff' => 'Staff',
                            ])
                            ->preload()
                            ->searchable()
                            ->visible($isSuperAdmin),
                    ])->visible($isSuperAdmin),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSuperAdmin = Auth::user()->hasRole('super_admin');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('businessEntity.name')
                    ->badge()
                    ->color(fn ($record) => $record->businessEntity->color)
                    ->getStateUsing(fn ($record) => $record->businessEntity->name ?? null)
                    ->toggleable(),
                TextColumn::make('jobTitle.title')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('whatsapp_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($isSuperAdmin),
            ])
            ->filters([
                SelectFilter::make('businessEntity')
                    ->relationship('businessEntity', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('jobTitle')
                    ->relationship('jobTitle', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        'general_affair' => 'General Affair',
                        'staff' => 'Staff',
                    ])
                    ->searchable()
                    ->preload()
                    ->visible($isSuperAdmin),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->actions([
                EditAction::make(),
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
            AssetTransfersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        if (Auth::user()->hasRole('super_admin')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->where(function (Builder $query): void {
            $query->whereNull('role')->orWhere('role', '!=', 'super_admin');
        });
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('User Information')
                    ->description('Details about the user')
                    ->schema([
                        Grid::make(2) // 2-column layout for better readability
                            ->schema([
                                TextEntry::make('name')
                                    ->columnSpan(1),
                                TextEntry::make('email')
                                    ->columnSpan(1),
                                TextEntry::make('whatsapp_number')
                                    ->placeholder('-')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Professional Information')
                    ->description('Business and Job details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('businessEntity.name')
                                    ->columnSpan(1),
                                TextEntry::make('jobTitle.title')
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Timestamps')
                    ->description('Creation and update times')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->columnSpan(2),
                    ]),
            ]);
    }
}
