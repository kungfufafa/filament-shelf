<?php

namespace App\Filament\Resources\VehicleChecksheetResource\Pages;

use App\Filament\Resources\VehicleChecksheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleChecksheets extends ListRecords
{
    protected static string $resource = VehicleChecksheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
