<?php

namespace App\Filament\Resources\EmisorResource\Pages;

use App\Filament\Resources\EmisorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmisors extends ListRecords
{
    protected static string $resource = EmisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
