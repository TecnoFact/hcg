<?php

namespace App\Services;

use DOMDocument;
use SimpleXMLElement;

class CfdiComplementValidatorService
{
    protected array $complementos = [
        'nomina12:Nomina' => 'Nomina12.xsd',
        'pago20:Pagos' => 'Pagos20.xsd',
        'cartaporte:CartaPorte' => 'CartaPorte.xsd',
        'cce11:ComercioExterior' => 'ComercioExterior11.xsd',
    ];

    public function validateComplements(SimpleXMLElement $xml): array
    {
        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('cfdi', $namespaces['cfdi'] ?? '');

        $complemento = $xml->xpath('//cfdi:Complemento');

        if (!$complemento || empty($complemento[0])) {
            return ['valido' => true, 'mensaje' => 'No contiene complementos CFDI'];
        }

        foreach ($this->complementos as $nodo => $archivoXsd) {
            [$ns, $tag] = explode(':', $nodo);
            $complemento[0]->registerXPathNamespace($ns, $namespaces[$ns] ?? '');
            $found = $complemento[0]->xpath("$ns:$tag");

            if ($found && isset($found[0])) {
                $xmlFragment = $found[0]->asXML();
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = false;

                if (!$dom->loadXML($xmlFragment)) {
                    return [
                        'valido' => false,
                        'error' => "No se pudo cargar el XML del complemento $nodo.",
                    ];
                }

                $xsdPath = resource_path("xsd/{$archivoXsd}");

                libxml_use_internal_errors(true);
                libxml_clear_errors();

                if (!$dom->schemaValidate($xsdPath)) {
                    $errors = libxml_get_errors();
                    libxml_clear_errors();

                    return [
                        'valido' => false,
                        'error' => "El complemento $nodo no cumple con su XSD.",
                        'detalles' => array_map(fn($e) => trim($e->message), $errors)
                    ];
                }
            }
        }

        return ['valido' => true];
    }
}
