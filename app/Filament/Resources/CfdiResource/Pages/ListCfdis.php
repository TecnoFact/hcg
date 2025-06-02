<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use App\Filament\Resources\CfdiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCfdis extends ListRecords
{
    protected static string $resource = CfdiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
