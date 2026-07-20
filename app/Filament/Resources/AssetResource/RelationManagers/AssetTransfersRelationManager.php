<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

use App\Enums\AssetTransferDocumentType;
use App\Filament\Resources\AssetTransferResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AssetTransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'assetTransferDetails';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('assetTransfer.letter_number'),
                TextInput::make('assetTransfer.fromUser.name'),
                TextInput::make('assetTransfer.toUser.name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('letter_number')
            ->columns([
                TextColumn::make('assetTransfer.letter_number')
                    ->badge(),
                TextColumn::make('assetTransfer.fromUser.name')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('assetTransfer.toUser.name')
                    ->badge()
                    ->color('success'),
                TextColumn::make('status')
                    ->badge()
                    ->colors(AssetTransferDocumentType::colors())
                    ->getStateUsing(function ($record) {
                        return $record->assetTransfer->status;
                    }),
                TextColumn::make('assetTransfer.document')
                    ->url(fn ($record) => $record && $record->assetTransfer && $record->assetTransfer->document ? Storage::url($record->assetTransfer->document) : null, true)
                    ->openUrlInNewTab()
                    ->getStateUsing(fn ($record) => $record->assetTransfer && $record->assetTransfer->document ? 'Dokumen' : '-')
                    ->icon('heroicon-o-document-text'),
                TextColumn::make('assetTransfer.created_at')
                    ->date()
                    ->label(__('Created at')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('createAssetTransfer')
                    ->label('Transfer Asset')
                    ->url(AssetTransferResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->color('success'),
            ])
            ->actions([
                Action::make('removeTransferDetail')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Riwayat Transfer')
                    ->modalDescription('Apakah Anda yakin ingin menghapus riwayat transfer ini dari aset? Hanya link ke aset ini yang dihapus, data transfer utama tidak terpengaruh.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->action(fn ($record) => $record->delete()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
