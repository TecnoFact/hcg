<?php

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Resources\ReceptorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceptors extends ListRecords
{
    protected static string $resource = ReceptorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
