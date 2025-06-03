<?php

namespace App\Filament\Resources\EmisorResource\Pages;

use App\Filament\Resources\EmisorResource;
use CfdiUtils\OpenSSL\OpenSSL;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditEmisor extends EditRecord
{
    protected static string $resource = EmisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

     protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'El Emisor a sido creado con exito';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // subir el archivo cer y key al Storage disk local dentro de la carpeta certificates y con el nombre del rfc y que el nombre del archivo sea el rfc con su extension
        if (isset($data['file_certificate'])) {

            $data['file_certificate'] = $data['file_certificate']->storeAs(
                'certificates/' . $data['rfc'],
                $data['rfc'] . '.cer',
                'local'
            );

            $cerContents = file_get_contents(Storage::disk('local')->path($data['file_certificate']));

            $openssl = new OpenSSL();
            $nameCerPem = $data['rfc'] . DIRECTORY_SEPARATOR . $data['rfc'] . '.cer.pem';

            $pemCertificate = $openssl->derCerConvertPhp($cerContents);

            Storage::disk('local')->put('certificates/' . $nameCerPem, $pemCertificate);
        }

        if (isset($data['file_key'])) {
            $data['file_key'] = $data['file_key']->storeAs(
                'certificates/' . $data['rfc'],
                $data['rfc'] . '.key',
                'local'
            );
        }


        return $data;
    }


}
