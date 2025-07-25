<?php

namespace App\Filament\Resources\TaxesResource\Pages;

use App\Filament\Resources\TaxesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxes extends EditRecord
{
    protected static string $resource = TaxesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
