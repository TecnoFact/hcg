<?php

namespace App\Filament\Resources\EmisorResource\Pages;

use App\Filament\Resources\EmisorResource;
use CfdiUtils\Certificado\Certificado;
use CfdiUtils\OpenSSL\OpenSSL;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Storage;

class CreateEmisor extends CreateRecord
{
    protected static string $resource = EmisorResource::class;

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
        $data['name'] = $data['reason_social'];

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

            $fileCertificatePath = Storage::disk('local')->path($data['file_certificate']);

            // obtener la fecha de vencimiento del certificado
            $certificado = new Certificado($fileCertificatePath);

            $fecha = date('Y-m-d H:i:s', $certificado->getValidTo());
            $fechaDesde = date('Y-m-d H:i:s', $certificado->getValidFrom());

            $data['date_from'] = $fechaDesde;

            $data['due_date'] = $fecha;



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
