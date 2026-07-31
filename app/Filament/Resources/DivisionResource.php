<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DivisionResource\Pages;
use App\Models\Division;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DivisionResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),

                Forms\Components\Repeater::make('approvers')
                    ->helperText('Tambahkan approver sesuai jabatan yang harus menyetujui. Seret untuk mengatur alur.')
                    ->relationship('approvers')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name', function ($query, $get, $state) {
                                $selectedUsers = collect($get('../../approvers'))
                                    ->pluck('user_id')
                                    ->filter()
                                    ->all();

                                return $query
                                    ->with('jobTitle')
                                    ->when($selectedUsers, function ($q) use ($selectedUsers, $state) {
                                        $q->whereNotIn('id', array_diff($selectedUsers, [$state]));
                                    })
                                    ->orderBy('name');
                            })
                            ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->nameWithJobTitle())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Hidden::make('level')
                            ->default(fn ($livewire, $get) => count($get('../../approvers') ?? []) + 1),
                    ])
                    ->columns(1)
                    ->defaultItems(1)
                    ->reorderable('level')
                    ->orderColumn('level')
                    ->itemLabel(function (array $state): ?string {
                        if (! isset($state['user_id'])) {
                            return 'Approver baru';
                        }

                        $user = User::with('jobTitle')->find($state['user_id']);

                        return $user?->nameWithJobTitle() ?? 'Approver';
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvers')
                    ->getStateUsing(function (Division $record) {
                        $record->loadMissing('approvers.user.jobTitle');

                        return $record->approvers
                            ->map(fn ($approver) => $approver->user?->nameWithJobTitle() ?? 'Unknown')
                            ->implode(' ➔ ');
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index' => Pages\ManageDivisions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
