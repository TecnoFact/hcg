<?php

namespace App\Filament\Resources\CfdiArchivosResource\Pages;

use App\Filament\Resources\CfdiArchivosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCfdiArchivos extends EditRecord
{
    protected static string $resource = CfdiArchivosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
