<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleChecksheetResource\Pages;
use App\Models\AssetAttribute;
use App\Models\VehicleChecksheet;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VehicleChecksheetResource extends Resource
{
    protected static ?string $model = VehicleChecksheet::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Informasi Kendaraan
                Section::make('Informasi Kendaraan')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->default(function () {
                                return VehicleChecksheetResource::generateReferenceNumber();
                            }),
                        Forms\Components\Select::make('license_plate')
                            ->options(function () {
                                // Ambil data dari AssetAttribute yang terkait dengan CustomAssetAttribute "Plat Nomor"
                                return AssetAttribute::whereHas('customAttribute', function ($query) {
                                    $query->where('name', 'Plat Nomor');
                                })->pluck('attribute_value', 'attribute_value')->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih Plat Nomor')
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('pic')
                            ->maxLength(255)
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Contoh: Depo 1, Workshop, dll.')
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\TextInput::make('destination')
                            ->maxLength(255)
                            ->placeholder('Contoh: Depo 1, Workshop, dll.')
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated(),
                    ]),

                // Informasi Keberangkatan
                Section::make('Informasi Keberangkatan')
                    ->schema([
                        Forms\Components\TextInput::make('start_km')
                            ->required()
                            ->numeric()
                            ->placeholder('Masukkan KM awal')
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\DateTimePicker::make('departure_time')
                            ->required()
                            ->default(now())
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\FileUpload::make('departure_photo')
                            ->required()
                            ->disk('public')
                            ->directory('vehiclechecksheet')
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->visibility('public')
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->deletable(fn (string $operation): bool => $operation !== 'edit')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file, Get $get, $record) => self::vehicleChecksheetUploadFilename(
                                    $file,
                                    'departure_photo',
                                    $record?->reference_number ?? $get('reference_number')
                                )
                            ),
                        Forms\Components\FileUpload::make('departure_damage_report')
                            ->required()
                            ->disk('public')
                            ->directory('vehiclechecksheet')
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->visibility('public')
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->deletable(fn (string $operation): bool => $operation !== 'edit')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file, Get $get, $record) => self::vehicleChecksheetUploadFilename(
                                    $file,
                                    'departure_damage_report',
                                    $record?->reference_number ?? $get('reference_number')
                                )
                            ),
                    ]),

                // Informasi Pengembalian
                Section::make('Informasi Pengembalian')
                    ->schema([
                        Forms\Components\TextInput::make('end_km')
                            ->required()
                            ->numeric()
                            ->placeholder('Masukkan KM akhir'),
                        Forms\Components\DateTimePicker::make('return_time')
                            ->required(),
                        Forms\Components\FileUpload::make('return_photo')
                            ->required()
                            ->disk('public')
                            ->directory('vehiclechecksheet')
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->visibility('public')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file, Get $get, $record) => self::vehicleChecksheetUploadFilename(
                                    $file,
                                    'return_photo',
                                    $record?->reference_number ?? $get('reference_number')
                                )
                            ),
                        Forms\Components\FileUpload::make('return_damage_report')
                            ->required()
                            ->disk('public')
                            ->directory('vehiclechecksheet')
                            ->previewable()
                            ->imagePreviewHeight('250')
                            ->visibility('public')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file, Get $get, $record) => self::vehicleChecksheetUploadFilename(
                                    $file,
                                    'return_damage_report',
                                    $record?->reference_number ?? $get('reference_number')
                                )
                            ),
                    ])
                    ->hidden(fn ($livewire) => $livewire instanceof CreateRecord),
                // Informasi Tambahan
                Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('rental_duration')
                            ->numeric()
                            ->disabled(), // Set as read-only
                        Forms\Components\TextInput::make('distance_traveled')
                            ->numeric()
                            ->default(0.00)
                            ->disabled(), // Set as read-only
                        Forms\Components\Textarea::make('remarks')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pic')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('license_plate')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_km')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('departure_time')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('departure_photo')
                    ->checkFileExistence(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('departure_damage_report')
                    ->checkFileExistence(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_km')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('return_time')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('return_photo')
                    ->checkFileExistence(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('return_damage_report')
                    ->checkFileExistence(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rental_duration')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('distance_traveled')
                    ->numeric()
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
                SelectFilter::make('license_plate')
                    ->options(fn () => VehicleChecksheet::query()
                        ->orderBy('license_plate')
                        ->distinct()
                        ->pluck('license_plate', 'license_plate')
                        ->all()),
                SelectFilter::make('pic')
                    ->options(fn () => VehicleChecksheet::query()
                        ->orderBy('pic')
                        ->distinct()
                        ->pluck('pic', 'pic')
                        ->all()),
                SelectFilter::make('location')
                    ->options(fn () => VehicleChecksheet::query()
                        ->orderBy('location')
                        ->distinct()
                        ->pluck('location', 'location')
                        ->all()),
            ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Panggil fungsi generateReferenceNumber untuk menghasilkan nomor referensi
        $data['reference_number'] = self::generateReferenceNumber();

        return $data;
    }

    public static function generateReferenceNumber(): string
    {
        $year = date('Y');
        $prefix = "GA-{$year}-";

        $lastNumber = VehicleChecksheet::where('reference_number', 'like', $prefix.'%')
            ->pluck('reference_number')
            ->map(fn (string $ref): int => (int) Str::afterLast($ref, '-'))
            ->max() ?? 0;

        $newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}{$newNumber}";
    }

    protected static function vehicleChecksheetUploadFilename(
        TemporaryUploadedFile $file,
        string $field,
        ?string $referenceNumber = null,
    ): string {
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $reference = $referenceNumber ?: self::generateReferenceNumber();

        return sprintf(
            'scale_%s_%s_%s.%s',
            $reference,
            $field,
            now()->format('Ymd_His'),
            $extension
        );
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
            'index' => Pages\ListVehicleChecksheets::route('/'),
            'create' => Pages\CreateVehicleChecksheet::route('/create'),
            'edit' => Pages\EditVehicleChecksheet::route('/{record}/edit'),
        ];
    }
}
