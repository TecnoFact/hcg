<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use App\Filament\Resources\CfdiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCfdi extends EditRecord
{
    protected static string $resource = CfdiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
