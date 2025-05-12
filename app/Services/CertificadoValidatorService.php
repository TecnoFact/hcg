<?php

namespace App\Services;

class CertificadoValidatorService
{
    /**
     * Extrae el RFC del certificado usando 'openssl x509 -text'.
     */
    public function obtenerRfcDesdeCer(string $cerPath): ?string
    {
        $cmd = 'openssl x509 -in "' . $cerPath . '" -inform DER -noout -text';
        $output = shell_exec($cmd);

        if (!$output) {
            return null;
        }

        // Buscar RFC tipo EKU9003173C9 o similar (12 o 13 caracteres)
        if (preg_match('/([A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3})/', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Compara el RFC extraído del certificado con el RFC del XML.
     */
    public function validarRfcConCertificado(string $cerPath, string $rfcXml): bool
    {
        $rfcCertificado = $this->obtenerRfcDesdeCer($cerPath);
        return strtoupper($rfcCertificado) === strtoupper($rfcXml);
    }
}