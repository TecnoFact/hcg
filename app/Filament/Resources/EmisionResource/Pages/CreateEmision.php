<?php

namespace App\Filament\Resources\EmisionResource\Pages;

use App\Filament\Resources\EmisionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmision extends CreateRecord
{
    protected static string $resource = EmisionResource::class;

     protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'El Emisor ha sido creado con éxito';
    }
}
