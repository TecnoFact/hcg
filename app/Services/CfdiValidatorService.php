<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\Facades\Log;

class CfdiValidatorService
{
    protected string $mainXsd;

    public function __construct()
    {
        // Ruta al XSD principal descargado del SAT
        $this->mainXsd = resource_path('xsd/cfdv40.xsd');
    }

    public function validate(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xmlContent)) {
            $errors = libxml_get_errors();
            return [
                'valido' => false,
                'error' => 'El archivo XML no se pudo cargar.',
                'detalle' => $this->formatErrors($errors),
            ];
        }

        if (!$dom->schemaValidate($this->mainXsd)) {
            $errors = libxml_get_errors();
            return [
                'valido' => false,
                'error' => 'El XML no cumple con el XSD del SAT.',
                'detalle' => $this->formatErrors($errors),
            ];
        }

        return ['valido' => true];
    }

    protected function formatErrors(array $errors): array
    {
        return array_map(function ($error) {
            return trim($error->message);
        }, $errors);
    }
}
