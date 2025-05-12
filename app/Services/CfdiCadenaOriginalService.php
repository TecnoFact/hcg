<?php

namespace App\Services;

use DOMDocument;
use XSLTProcessor;
use Exception;

class CfdiCadenaOriginalService
{
    protected string $xsltPath;

    public function __construct()
    {
        $this->xsltPath = resource_path('xslt/cadenaoriginal_4_0.xslt');
    }

    public function generar(string $xmlContent): string
    {
        libxml_use_internal_errors(true);

        $xml = new DOMDocument();
        if (!$xml->loadXML($xmlContent)) {
            throw new Exception("No se pudo cargar el XML para generar la cadena original.");
        }

        $xsl = new DOMDocument();
        if (!$xsl->load($this->xsltPath)) {
            throw new Exception("No se pudo cargar el archivo XSLT de cadena original.");
        }

        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);

        $resultado = $proc->transformToXML($xml);

        if ($resultado === false) {
            throw new Exception("No se pudo transformar el XML a cadena original.");
        }

        return trim($resultado);
    }
}
