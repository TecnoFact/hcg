<?php

namespace App\Filament\Resources\CfdiArchivosResource\Pages;

use App\Filament\Resources\CfdiArchivosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCfdiArchivos extends ListRecords
{
    protected static string $resource = CfdiArchivosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
