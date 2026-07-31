<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Umum')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required(),

                                TextInput::make('cost')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required(),
                            ]),

                        DateTimePicker::make('work_timestamp')
                            ->native(false)
                            ->default(now())
                            ->required(),

                        Textarea::make('description')
                            ->required(),

                        TextInput::make('location')
                            ->required(),

                        Select::make('business_entity_id')
                            ->relationship('businessEntity', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id())
                            ->dehydrated(true),
                    ]),

                // Vendor Information Section
                Section::make('Informasi Vendor')
                    ->schema([
                        Select::make('vendor_id')
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),

                                TextInput::make('last_price')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->required()
                                    ->placeholder('Masukkan harga terakhir'),
                            ]),
                    ]),

                // Attachment Section
                Section::make('Lampiran')
                    ->schema([
                        FileUpload::make('document_upload')
                            ->directory('documents') // Define a different directory for documents
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) // Allow only document types
                            ->maxSize(5120) // Set a max size of 5MB
                            ->required() // Optionally, limit the number of files (example: 5)
                            ->hiddenOn('create'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('businessEntity.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('vendor.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('cost')
                    ->money('IDR')
                    ->toggleable(),
                TextColumn::make('location')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge() // Enables badge display
                    ->colors([
                        'danger' => 'open',         // Red badge for 'open' status
                        'warning' => 'in_progress', // Yellow badge for 'in_progress' status
                        'success' => 'completed',   // Green badge for 'completed' status
                    ])
                    ->toggleable(),
                TextColumn::make('document_upload')
                    ->url(fn ($record) => $record && $record->document_upload ? Storage::url($record->document_upload) : null, true) // Membuat kolom URL untuk unduh
                    ->openUrlInNewTab()
                    ->getStateUsing(fn ($record) => $record && $record->document_upload ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('businessEntity')->preload()->searchable()->relationship('businessEntity', 'name'),
                SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->actions([
                // Group the custom actions together
                ActionGroup::make([
                    // Edit Action (with pencil icon)
                    EditAction::make()
                        ->icon('heroicon-o-pencil') // Use the pencil icon for edit
                        ->visible(fn ($record) => ! in_array($record->status, ['in_progress', 'completed'])),

                    // Custom Process Action (color: blue, with play icon)
                    Action::make('process')
                        ->icon('heroicon-o-play') // Use the play icon for process
                        ->color('primary') // Use 'primary' for blue
                        ->visible(fn ($record) => $record->status === 'open')
                        ->action(function ($record) {
                            $record->update(['status' => 'in_progress']);
                        }),

                    // Custom Complete Action (color: green, with check icon)
                    Action::make('complete')
                        ->icon('heroicon-o-check-circle') // Use the check circle icon for complete
                        ->color('success') // Use 'success' for green
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->form([
                            FileUpload::make('attachment')
                                ->directory('task') // Define the directory to store images
                                ->image() // Only allow image uploads
                                ->maxSize(2048) // Maximum size (optional)
                                ->required()
                                ->multiple() // Enable multiple file uploads
                                ->maxFiles(5), // Optionally, limit the number of files (example: 5)
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'status' => 'completed',
                                'attachment' => $data['attachment'],
                            ]);
                        }),

                    Action::make('upload')
                        ->icon('heroicon-o-check-circle') // Use the check circle icon for complete
                        ->color('success') // Use 'success' for green
                        ->visible(fn ($record) => $record->status === 'completed' && is_null($record->document_upload))
                        ->form([
                            FileUpload::make('document_upload')
                                ->directory('documents') // Define a different directory for documents
                                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) // Allow only document types
                                ->maxSize(5120) // Set a max size of 5MB
                                ->required(),
                        ])
                        ->action(function ($record, $data) {
                            $record->update([
                                'document_upload' => $data['document_upload'], // Save the uploaded document
                            ]);
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                // General Information Section
                Section::make('Informasi Umum')
                    ->description('Detail penting mengenai tugas dan entitas terkait.')
                    ->schema([
                        Grid::make(2) // Two-column grid layout for better spacing
                            ->schema([
                                TextEntry::make('name')
                                    ->placeholder('Tidak ada nama tugas'),

                                TextEntry::make('vendor.name')
                                    ->placeholder('Tidak ada vendor yang ditugaskan'),

                                TextEntry::make('businessEntity.name')
                                    ->placeholder('Tidak ada badan usaha terkait'),

                                TextEntry::make('code')
                                    ->placeholder('Kode tugas belum dibuat'),
                            ]),
                    ])
                    ->columns(1) // Single column for easier readability
                    ->collapsible(), // Allow section to be collapsible for a cleaner UI

                // Status Section
                Section::make('Status Pekerjaan')
                    ->description('Periksa status terbaru dari tugas ini.')
                    ->schema([
                        TextEntry::make('status')
                            ->placeholder('Status belum diperbarui'),
                    ])
                    ->columns(1)
                    ->collapsible(), // Make it collapsible

                // Attachments Section
                Section::make('Lampiran')
                    ->description('Lampiran terkait tugas ini.')
                    ->schema([
                        TextEntry::make('attachment')
                            ->formatStateUsing(function ($state) {
                                $baseUrl = asset('storage'); // Path dasar untuk storage

                                // Jika state adalah JSON-encoded string, ubah menjadi array
                                if (is_string($state) && str_starts_with($state, '[')) {
                                    $state = json_decode($state, true); // Decode JSON string to array
                                }

                                // Jika state adalah array, tampilkan gambar
                                if (is_array($state)) {
                                    return "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>"
                                        .collect($state)->map(function ($image) use ($baseUrl) {
                                            return "<img src='{$baseUrl}/{$image}' alt='Lampiran' style='max-width: 100px; border-radius: 5px;'>";
                                        })->implode('').
                                        '</div>';
                                }

                                // Jika hanya satu gambar
                                if (is_string($state) && ! empty($state)) {
                                    return "<img src='{$baseUrl}/{$state}' alt='Lampiran' style='max-width: 100px; border-radius: 5px;'>";
                                }

                                return 'Tidak ada lampiran';
                            })
                            ->html(), // Enable HTML rendering for images
                    ])
                    ->collapsible() // Allow section to be collapsible
                    ->columns(1),

                // Timestamps Section
                Section::make('Tanggal')
                    ->description('Waktu pembuatan dan pembaruan tugas ini.')
                    ->schema([
                        Grid::make(2) // Two-column grid for created and updated timestamps
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder('Tanggal pembuatan belum tersedia'),

                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder('Tanggal pembaruan belum tersedia'),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(), // Make this section collapsible too
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
