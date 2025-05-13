<?php

namespace App\Services;

use PhpCfdi\Credentials\Certificate;

class CertificadoValidatorService
{
    /**
     * Extrae el RFC del certificado utilizando la librería oficial.
     */
    public function obtenerRfcDesdeCer(string $cerPath): ?string
    {
        try {
            $cert = Certificate::openFile($cerPath);
            return $cert->rfc();
        } catch (\Throwable $e) {
            \Log::error('Error al leer certificado', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Compara el RFC extraído del certificado con el RFC del XML.
     */
    public function validarRfcConCertificado(string $cerPath, string $rfcXml): bool
    {
        $rfcCertificado = $this->obtenerRfcDesdeCer($cerPath);
        \Log::info('RFC Certificado', ['RFC Emisor' => $rfcCertificado]);

        return strtoupper($rfcCertificado) === strtoupper($rfcXml);
    }
}
