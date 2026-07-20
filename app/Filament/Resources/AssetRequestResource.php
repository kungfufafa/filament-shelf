<?php

namespace App\Filament\Resources;

use App\Enums\AssetRequestType;
use App\Enums\RequestStatus;
use App\Filament\Resources\AssetRequestResource\Pages;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\AssetRequest;
use App\Models\AssetRequestApproval;
use App\Models\AssetRequestItem;
use App\Models\BusinessEntity;
use App\Models\JobTitle;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Grid as InfolistGrid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class AssetRequestResource extends Resource
{
    protected static ?string $model = AssetRequest::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static function lifecycleSummaryHtml(AssetRequest $record): HtmlString
    {
        $record->loadMissing(['asset', 'assetTransfer', 'createdAssets']);

        $rows = [
            '<div><strong>Tahap:</strong> '.e($record->lifecycleStageLabel()).'</div>',
            '<div><strong>Langkah berikutnya:</strong> '.e($record->nextStepLabel()).'</div>',
            '<div><strong>Item pengajuan:</strong> '.e($record->itemSummaryLabel()).'</div>',
            '<div><strong>Link publik:</strong> <a href="'.e($record->publicProgressUrl()).'" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline;">Buka progress pengajuan</a></div>',
        ];

        if ($record->asset) {
            $assetUrl = AssetResource::getUrl('view', ['record' => $record->asset]);
            $assetLabel = e($record->asset->name);
            $conditionLabel = e($record->asset->condition_status?->label() ?? '-');
            $rows[] = "<div><strong>Aset terkait:</strong> <a href=\"{$assetUrl}\" style=\"color: #2563eb; text-decoration: underline;\">{$assetLabel}</a> ({$conditionLabel})</div>";
        }

        if ($record->type === AssetRequestType::Pengadaan && $record->createdAssets->isNotEmpty()) {
            $assetsLinks = $record->createdAssets
                ->map(function ($asset): string {
                    $url = AssetResource::getUrl('view', ['record' => $asset]);
                    $name = e($asset->name);

                    return "<a href=\"{$url}\" style=\"color: #2563eb; text-decoration: underline;\">{$name}</a>";
                })
                ->implode(', ');

            $rows[] = "<div><strong>Aset dibuat:</strong> {$assetsLinks}</div>";
        }

        if ($record->assetTransfer) {
            $transferUrl = AssetTransferResource::getUrl('view', ['record' => $record->assetTransfer]);
            $letterNumber = e($record->assetTransfer->letter_number ?? 'Transfer');
            $rows[] = "<div><strong>BA Pengembalian:</strong> <a href=\"{$transferUrl}\" style=\"color: #2563eb; text-decoration: underline;\">{$letterNumber}</a></div>";
        }

        return new HtmlString('<div class="space-y-1 text-sm">'.implode('', $rows).'</div>');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Left Column (width 1): Informasi Pemohon
                        Section::make('Informasi Pemohon')
                            ->schema([
                                Forms\Components\TextInput::make('reference_number')
                                    ->placeholder('REQ-YYYY-XXX')
                                    ->readOnly()
                                    ->disabledOn('create'),
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                                    ->default(fn () => auth()->id())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                    ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable())
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('business_entity_id')
                                            ->options(fn () => Cache::remember('business_entity_options', 300, fn () => BusinessEntity::orderBy('name')->pluck('name', 'id')->toArray()))
                                            ->searchable(),
                                        Forms\Components\Select::make('job_title_id')
                                            ->options(fn () => Cache::remember('job_title_options', 300, fn () => JobTitle::orderBy('title')->pluck('title', 'id')->toArray()))
                                            ->searchable()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                $jobTitle = JobTitle::create([
                                                    'title' => $data['title'],
                                                ]);
                                                Cache::forget('job_title_options');

                                                return $jobTitle->id;
                                            }),
                                        Forms\Components\TextInput::make('whatsapp_number')
                                            ->placeholder('Contoh: 08123456789')
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $user = User::create([
                                            'name' => $data['name'],
                                            'business_entity_id' => $data['business_entity_id'] ?? null,
                                            'job_title_id' => $data['job_title_id'] ?? null,
                                            'whatsapp_number' => $data['whatsapp_number'] ?? null,
                                        ]);
                                        Cache::forget('user_options');

                                        return $user->id;
                                    }),
                                Forms\Components\Select::make('division_id')
                                    ->relationship('division', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                    ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable()),
                                Forms\Components\Select::make('asset_location_id')
                                    ->relationship('assetLocation', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                    ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable())
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('address')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $location = AssetLocation::create([
                                            'name' => $data['name'],
                                            'address' => $data['address'] ?? null,
                                            'description' => $data['description'] ?? null,
                                        ]);
                                        Cache::forget('asset_location_options');

                                        return $location->id;
                                    }),
                            ])
                            ->columns(1)
                            ->columnSpan(1),

                        // Right Column (width 2): Detail Pengajuan, Lampiran, Status & Catatan
                        Grid::make(1)
                            ->schema([
                                Section::make('Detail Pengajuan')
                                    ->schema([
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'penarikan' => 'Penarikan Aset',
                                                'perbaikan' => 'Perbaikan Aset',
                                                'pengadaan' => 'Pengadaan Aset Baru',
                                            ])
                                            ->default('pengadaan')
                                            ->required()
                                            ->live()
                                            ->columnSpanFull()
                                            ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                            ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable()),
                                        Forms\Components\Repeater::make('request_items')
                                            ->schema([
                                                Forms\Components\Select::make('asset_id')
                                                    ->relationship('asset', 'name', modifyQueryUsing: fn ($query, Get $get) => $query->with('recipient')->eligibleForPenarikanOrPerbaikan()->orderBy('name')->where('recipient_id', $get('../../user_id') ?: -1))
                                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}".($record->serial_number ? " (SN: {$record->serial_number})" : '').($record->recipient ? " - Pemegang: {$record->recipient->name}" : ' - (Di GA / Tidak Digunakan)'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->distinct()
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                    ->visible(fn (Get $get) => in_array($get('../../type'), ['penarikan', 'perbaikan']))
                                                    ->columnSpanFull()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        if ($state) {
                                                            $asset = Asset::with('recipient')->find($state);
                                                            if ($asset && $asset->recipient_id) {
                                                                $set('../../user_id', $asset->recipient_id);
                                                            }
                                                        }
                                                    }),
                                                Forms\Components\TextInput::make('item_name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->visible(fn (Get $get) => $get('../../type') === 'pengadaan'),
                                                Forms\Components\TextInput::make('qty')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->minValue(1)
                                                    ->visible(fn (Get $get) => $get('../../type') === 'pengadaan'),
                                            ])
                                            ->minItems(1)
                                            ->columnSpanFull()
                                            ->columns(2)
                                            // Item hanya bisa diubah saat status Pending. Setelah disetujui,
                                            // mengubah item akan merusak tracking fulfillment (fulfilled_asset_id
                                            // dst.) dan mengubah scope pengajuan tanpa re-approval.
                                            ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                            ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable()),
                                        Forms\Components\Textarea::make('description')
                                            ->maxLength(65535)
                                            ->columnSpanFull()
                                            ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                            ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable()),
                                    ])
                                    ->columns(2),

                                Section::make('Lampiran')
                                    ->schema([
                                        Forms\Components\FileUpload::make('attachment')
                                            ->directory('asset-requests')
                                            ->visibility('public')
                                            ->multiple()
                                            ->required()
                                            ->minFiles(1)
                                            ->columnSpanFull()
                                            ->disabled(fn (?AssetRequest $record): bool => $record !== null && ! $record->isMaterialScopeEditable())
                                            ->dehydrated(fn (?AssetRequest $record): bool => $record === null || $record->isMaterialScopeEditable()),
                                    ]),
                                Section::make('Status & Catatan')
                                    ->visible(fn ($record) => $record !== null)
                                    ->schema([
                                        Forms\Components\TextInput::make('status')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->formatStateUsing(fn ($record) => $record?->status?->label()),
                                        Forms\Components\Textarea::make('notes')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(2),
                    ]),

                Section::make('Lifecycle Pengajuan')
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Forms\Components\Placeholder::make('lifecycle_summary')
                            ->content(fn (AssetRequest $record) => self::lifecycleSummaryHtml($record)),
                    ]),

                Section::make('Alur Persetujuan (Approval Tracking)')
                    ->visible(fn ($record) => $record !== null && $record->approvals()->exists())
                    ->schema([
                        Forms\Components\Repeater::make('approvals')
                            ->relationship('approvals')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->disabled(),
                                Forms\Components\Placeholder::make('job_title')
                                    ->content(fn (?AssetRequestApproval $record): string => $record?->user?->jobTitle?->title ?? '—'),
                                Forms\Components\TextInput::make('status')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => is_object($state) && method_exists($state, 'label') ? $state->label() : (is_string($state) ? ucfirst($state) : $state)),
                                Forms\Components\Textarea::make('notes')
                                    ->disabled()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->columns([
                'lg' => 3,
            ])
            ->schema([
                InfolistGrid::make(1)
                    ->schema([
                        InfolistSection::make('Ringkasan Pengajuan')
                            ->schema([
                                InfolistGrid::make(3)
                                    ->schema([
                                        TextEntry::make('reference_number')
                                            ->icon('heroicon-o-hashtag')
                                            ->copyable()
                                            ->placeholder('—'),
                                        TextEntry::make('type')
                                            ->icon('heroicon-o-clipboard-document-list')
                                            ->formatStateUsing(fn ($state): string => $state instanceof AssetRequestType ? $state->label() : ucfirst((string) $state))
                                            ->badge()
                                            ->color(fn ($state): string => $state instanceof AssetRequestType ? $state->color() : 'gray'),
                                        TextEntry::make('status')
                                            ->icon('heroicon-o-shield-check')
                                            ->formatStateUsing(fn ($state): string => self::formatRequestStatus($state))
                                            ->badge()
                                            ->color(fn ($state): string => self::requestStatusColor($state)),
                                        TextEntry::make('lifecycle_stage_label')
                                            ->icon('heroicon-o-arrow-path-rounded-square')
                                            ->state(fn (AssetRequest $record): string => $record->lifecycleStageLabel())
                                            ->badge()
                                            ->color(fn (AssetRequest $record): string => $record->lifecycleStageColor()),
                                        TextEntry::make('created_at')
                                            ->icon('heroicon-o-calendar')
                                            ->dateTime('d M Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('updated_at')
                                            ->icon('heroicon-o-clock')
                                            ->dateTime('d M Y H:i')
                                            ->placeholder('—'),
                                    ]),
                            ])
                            ->collapsible(),

                        InfolistSection::make('Pemohon & Kebutuhan')
                            ->schema([
                                InfolistGrid::make(3)
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),
                                        TextEntry::make('user.email')
                                            ->icon('heroicon-o-envelope')
                                            ->placeholder('—'),
                                        TextEntry::make('user.whatsapp_number')
                                            ->icon('heroicon-o-device-phone-mobile')
                                            ->placeholder('—'),
                                        TextEntry::make('division.name')
                                            ->icon('heroicon-o-building-office-2')
                                            ->placeholder('—'),
                                        TextEntry::make('assetLocation.name')
                                            ->icon('heroicon-o-map-pin')
                                            ->placeholder('—'),
                                        TextEntry::make('description')
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->collapsible(),

                        InfolistSection::make('Item Pengajuan')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->schema([
                                        InfolistGrid::make(3)
                                            ->schema([
                                                TextEntry::make('display_name')
                                                    ->state(fn (AssetRequestItem $record): string => $record->asset?->name ?? $record->item_name ?? '—')
                                                    ->icon('heroicon-o-cube')
                                                    ->columnSpan([
                                                        'default' => 'full',
                                                        'lg' => 2,
                                                    ]),
                                                TextEntry::make('qty')
                                                    ->badge()
                                                    ->color('gray')
                                                    ->placeholder('1'),
                                                TextEntry::make('asset.serial_number')
                                                    ->icon('heroicon-o-identification')
                                                    ->placeholder('—')
                                                    ->columnSpan([
                                                        'default' => 'full',
                                                        'lg' => 1,
                                                    ]),
                                                TextEntry::make('asset.recipient.name')
                                                    ->icon('heroicon-o-user')
                                                    ->placeholder('—')
                                                    ->columnSpan([
                                                        'default' => 'full',
                                                        'lg' => 2,
                                                    ]),
                                            ]),
                                    ])
                                    ->columns(1),
                            ])
                            ->collapsible(),

                        InfolistSection::make('Lampiran & Catatan')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('attachment')
                                            ->icon('heroicon-o-paper-clip')
                                            ->state(fn (AssetRequest $record): string => self::attachmentSummary($record))
                                            ->placeholder('—'),
                                        TextEntry::make('notes')
                                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                            ->placeholder('—'),
                                    ]),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 2,
                    ]),

                InfolistGrid::make(1)
                    ->schema([
                        InfolistSection::make('Approval Tracking')
                            ->schema([
                                RepeatableEntry::make('approvals')
                                    ->schema([
                                        InfolistGrid::make(2)
                                            ->schema([
                                                TextEntry::make('status')
                                                    ->icon('heroicon-o-check-circle')
                                                    ->formatStateUsing(fn ($state): string => self::formatRequestStatus($state))
                                                    ->badge()
                                                    ->color(fn ($state): string => self::requestStatusColor($state)),
                                                TextEntry::make('user.name')
                                                    ->icon('heroicon-o-user')
                                                    ->formatStateUsing(function ($state, ?AssetRequestApproval $record): string {
                                                        return $record?->user?->nameWithJobTitle() ?? ($state ?: '—');
                                                    })
                                                    ->placeholder('—')
                                                    ->columnSpanFull(),
                                                TextEntry::make('public_token')
                                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                                    ->formatStateUsing(fn (): string => 'Buka halaman approval')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->url(fn ($state, ?AssetRequestApproval $record): ?string => $record?->status === RequestStatus::Pending ? $record->publicApprovalUrl() : null, true)
                                                    ->visible(fn ($state, ?AssetRequestApproval $record): bool => $record?->status === RequestStatus::Pending)
                                                    ->columnSpanFull(),
                                                TextEntry::make('decidedBy.name')
                                                    ->icon('heroicon-o-user-circle')
                                                    ->placeholder('—'),
                                                TextEntry::make('decided_at')
                                                    ->icon('heroicon-o-calendar-days')
                                                    ->dateTime('d M Y H:i')
                                                    ->placeholder('—'),
                                                TextEntry::make('notes')
                                                    ->icon('heroicon-o-document-text')
                                                    ->placeholder('—')
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->columns(1),
                            ])
                            ->collapsible(),

                        InfolistSection::make('Tindak Lanjut Operasional')
                            ->schema([
                                InfolistGrid::make(1)
                                    ->schema([
                                        TextEntry::make('next_step_label')
                                            ->icon('heroicon-o-forward')
                                            ->state(fn (AssetRequest $record): string => $record->nextStepLabel()),
                                        TextEntry::make('fulfilled_status')
                                            ->icon('heroicon-o-check-badge')
                                            ->state(fn (AssetRequest $record): string => $record->isFulfilled() ? 'Selesai' : 'Belum selesai')
                                            ->badge()
                                            ->color(fn (AssetRequest $record): string => $record->isFulfilled() ? 'success' : 'warning'),
                                        TextEntry::make('fulfilledBy.name')
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),
                                        TextEntry::make('fulfilled_at')
                                            ->icon('heroicon-o-calendar')
                                            ->dateTime('d M Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('assetTransfer.letter_number')
                                            ->icon('heroicon-o-arrow-uturn-left')
                                            ->placeholder('—')
                                            ->url(fn (AssetRequest $record): ?string => $record->assetTransfer ? AssetTransferResource::getUrl('view', ['record' => $record->assetTransfer]) : null),
                                    ]),
                            ])
                            ->collapsible(),

                        InfolistSection::make('Link Publik')
                            ->schema([
                                InfolistGrid::make(1)
                                    ->schema([
                                        TextEntry::make('public_token')
                                            ->icon('heroicon-o-arrow-top-right-on-square')
                                            ->state(fn (): string => 'Buka progress publik')
                                            ->badge()
                                            ->color('primary')
                                            ->url(fn (AssetRequest $record): string => $record->publicProgressUrl(), true)
                                            ->copyable()
                                            ->copyableState(fn (AssetRequest $record): string => $record->publicProgressUrl()),
                                        TextEntry::make('current_approval_link')
                                            ->icon('heroicon-o-paper-airplane')
                                            ->state(fn (AssetRequest $record): string => $record->currentPendingApproval() ? 'Buka approval pending' : 'Tidak ada approval pending')
                                            ->badge()
                                            ->color(fn (AssetRequest $record): string => $record->currentPendingApproval() ? 'warning' : 'gray')
                                            ->url(fn (AssetRequest $record): ?string => $record->currentPendingApproval()?->publicApprovalUrl(), true)
                                            ->copyable(fn (AssetRequest $record): bool => $record->currentPendingApproval() !== null)
                                            ->copyableState(fn (AssetRequest $record): ?string => $record->currentPendingApproval()?->publicApprovalUrl()),
                                    ]),
                            ])
                            ->compact()
                            ->collapsible(),
                    ])
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 1,
                    ]),
            ]);
    }

    protected static function formatRequestStatus(mixed $state): string
    {
        if ($state instanceof RequestStatus) {
            return $state->label();
        }

        return filled($state) ? ucfirst((string) $state) : '—';
    }

    protected static function requestStatusColor(mixed $state): string
    {
        if ($state instanceof RequestStatus) {
            return $state->color();
        }

        return match ((string) $state) {
            RequestStatus::Approved->value => 'success',
            RequestStatus::Rejected->value => 'danger',
            RequestStatus::Pending->value => 'warning',
            default => 'gray',
        };
    }

    protected static function attachmentSummary(AssetRequest $record): string
    {
        $attachments = collect(is_array($record->attachment) ? $record->attachment : ($record->attachment ? [$record->attachment] : []))
            ->filter()
            ->values();

        if ($attachments->isEmpty()) {
            return '—';
        }

        if ($attachments->count() === 1) {
            return basename((string) $attachments->first());
        }

        return $attachments->count().' lampiran';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->stackedOnMobile()
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->description(fn (AssetRequest $record): string => $record->type->label())
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (AssetRequestType $state): string => $state->color())
                    ->formatStateUsing(fn (AssetRequestType $state): string => $state->label())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->description(fn (AssetRequest $record): string => collect([
                        $record->division?->name,
                        $record->assetLocation?->name,
                    ])->filter()->implode(' · '))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('division.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assetLocation.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('item_name')
                    ->getStateUsing(fn (AssetRequest $record): string => $record->itemSummaryLabel())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('item_name', 'like', "%{$search}%")
                            ->orWhereHas('asset', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('items', fn ($q) => $q->where('item_name', 'like', "%{$search}%")
                                ->orWhereHas('asset', fn ($assetQuery) => $assetQuery->where('name', 'like', "%{$search}%")));
                    })
                    ->limit(42)
                    ->tooltip(fn (AssetRequest $record): string => $record->itemSummaryLabel())
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty')
                    ->numeric()
                    ->getStateUsing(fn (AssetRequest $record): int => $record->itemQuantityTotal())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (RequestStatus $state): string => $state->color())
                    ->formatStateUsing(fn (RequestStatus $state): string => $state->label())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lifecycle_stage')
                    ->badge()
                    ->getStateUsing(fn (AssetRequest $record): string => $record->lifecycleStageLabel())
                    ->color(fn (AssetRequest $record): string => $record->lifecycleStageColor())
                    ->description(fn (AssetRequest $record): string => 'Approval: '.$record->status->label()),
                Tables\Columns\TextColumn::make('next_step')
                    ->getStateUsing(fn (AssetRequest $record): string => $record->nextStepLabel())
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'penarikan' => 'Penarikan',
                        'perbaikan' => 'Perbaikan',
                        'pengadaan' => 'Pengadaan',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options(RequestStatus::options()),
                Tables\Filters\SelectFilter::make('division')
                    ->relationship('division', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->button()
                    ->visible(fn (?AssetRequest $record): bool => $record !== null
                        && $record->status === RequestStatus::Pending
                        && (auth()->user()?->can('approve', $record) ?? false))
                    ->url(fn (AssetRequest $record): string => static::getUrl('view', ['record' => $record])),

                Action::make('fulfillPengadaan')
                    ->label('Buat Aset')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (?AssetRequest $record): bool => $record !== null
                        && $record->status === RequestStatus::Approved
                        && ! $record->is_fulfilled
                        && $record->type === AssetRequestType::Pengadaan)
                    ->url(fn (AssetRequest $record): string => AssetResource::getUrl('create', array_filter([
                        'asset_request_id' => $record->id,
                        'asset_request_item_id' => $record->nextUnfulfilledPengadaanItem()?->id,
                    ]))),

                Action::make('fulfillPenarikan')
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->button()
                    ->tooltip('Buat BA')
                    ->visible(fn (?AssetRequest $record): bool => $record !== null
                        && $record->status === RequestStatus::Approved
                        && ! $record->is_fulfilled
                        && $record->type === AssetRequestType::Penarikan)
                    ->url(fn (AssetRequest $record): string => AssetTransferResource::getUrl('create', [
                        'asset_request_id' => $record->id,
                    ])),

                Action::make('fulfillPerbaikan')
                    ->label('')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->button()
                    ->tooltip('Proses Perbaikan')
                    ->requiresConfirmation()
                    ->modalHeading('Proses aset untuk perbaikan?')
                    ->modalDescription('Status aset akan menjadi Rusak dan NBH menjadi Pending. Pengajuan ditandai selesai setelah proses ini dijalankan.')
                    ->modalSubmitActionLabel('Ya, proses perbaikan')
                    ->visible(fn (?AssetRequest $record): bool => $record !== null
                        && $record->status === RequestStatus::Approved
                        && ! $record->is_fulfilled
                        && $record->type === AssetRequestType::Perbaikan
                        && $record->requestedAssetIds() !== [])
                    ->action(function (AssetRequest $record): void {
                        $asset = $record->fulfillPerbaikan(auth()->user());

                        Notification::make()
                            ->title('Aset masuk proses perbaikan')
                            ->body("Aset \"{$asset->name}\" sekarang Rusak, NBH Pending.")
                            ->warning()
                            ->send();
                    }),

                ViewAction::make()
                    ->label('')
                    ->button()
                    ->tooltip('Lihat detail'),

                ActionGroup::make([
                    EditAction::make()
                        ->label('Ubah data'),

                    Action::make('openPublicProgress')
                        ->label('Buka Progress Publik')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (AssetRequest $record): string => $record->publicProgressUrl())
                        ->openUrlInNewTab(),

                    Action::make('resendApprovalNotification')
                        ->label('Kirim Ulang ke Approver')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim ulang notifikasi ke approver?')
                        ->modalDescription('Pengingat hanya dikirim ke approver yang sedang menunggu. Keputusan approval tidak berubah.')
                        ->modalSubmitActionLabel('Kirim notifikasi')
                        ->visible(fn (?AssetRequest $record): bool => $record !== null
                            && $record->status === RequestStatus::Pending
                            && $record->currentPendingApproval() !== null)
                        ->action(function (AssetRequest $record): void {
                            try {
                                $result = $record->sendCurrentApprovalReminder();

                                Notification::make()
                                    ->title('Notifikasi approver dikirim ulang')
                                    ->body('Dikirim ke '.$result['recipient']->name.'.')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Gagal mengirim ulang notifikasi approver')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('resendRequesterNotification')
                        ->label('Kirim Ulang ke Pengaju')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim ulang progress ke pengaju?')
                        ->modalDescription('Pengaju akan menerima status terbaru dan link progress publik. Status pengajuan tidak berubah.')
                        ->modalSubmitActionLabel('Kirim notifikasi')
                        ->visible(fn (?AssetRequest $record): bool => $record !== null
                            && $record->user()->exists())
                        ->action(function (AssetRequest $record): void {
                            try {
                                $result = $record->sendRequesterProgressReminder();

                                Notification::make()
                                    ->title('Notifikasi pengaju dikirim ulang')
                                    ->body('Dikirim ke '.$result['recipient']->name.'.')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Gagal mengirim ulang notifikasi pengaju')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    DeleteAction::make()
                        ->label('Hapus pengajuan'),
                ])
                    ->label('')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->tooltip('Aksi lainnya'),
            ])
            ->recordActionsColumnLabel('Tindak Lanjut')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAssetRequests::route('/'),
            'create' => Pages\CreateAssetRequest::route('/create'),
            'view' => Pages\ViewAssetRequest::route('/{record}'),
            'edit' => Pages\EditAssetRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            // Hindari N+1 pada list: kolom/visible memakai items.asset, user, division,
            // dan approvals (currentPendingApproval / can('approve')).
            ->with([
                'items.asset',
                'user',
                'division',
                'approvals' => fn ($query) => $query->orderBy('level')->with('user.jobTitle'),
            ]);
    }
}
