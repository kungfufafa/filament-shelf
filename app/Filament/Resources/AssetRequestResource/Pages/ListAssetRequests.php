<?php

namespace App\Filament\Resources\AssetRequestResource\Pages;

use App\Filament\Resources\AssetRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetRequests extends ListRecords
{
    protected static string $resource = AssetRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
