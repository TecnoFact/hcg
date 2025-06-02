<?php

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Resources\ReceptorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceptor extends EditRecord
{
    protected static string $resource = ReceptorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
