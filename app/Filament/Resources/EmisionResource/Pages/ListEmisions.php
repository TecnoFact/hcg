<?php

namespace App\Filament\Resources\EmisionResource\Pages;

use App\Filament\Resources\EmisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmisions extends ListRecords
{
    protected static string $resource = EmisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
