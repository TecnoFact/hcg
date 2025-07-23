<?php

namespace App\Services;

use DOMXPath;
use Exception;
use DOMDocument;
use App\Models\Emision;
use App\Models\CfdiArchivo;
use App\Models\Models\Cfdi;
use App\Models\Models\CfdiEmisor;
use App\Models\Models\CfdiReceptor;
use Illuminate\Support\Facades\Storage;

class ComplementoXmlService
{
    public function insertarTimbreFiscalDigital(string $xml, string $timbreXml): string
    {
        // Cargar el XML del CFDI
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $xml = file_get_contents($xml);

        if (!$dom->loadXML($xml)) {
            throw new Exception("No se pudo cargar el CFDI XML");
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xpath->registerNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $comprobante = $xpath->query('/cfdi:Comprobante')->item(0);
        if (!$comprobante) {
            throw new Exception('No se encontró el nodo <cfdi:Comprobante>');
        }

        // Buscar o crear <cfdi:Complemento>
        $complemento = $xpath->query('cfdi:Complemento', $comprobante)->item(0);
        if (!$complemento) {
            $complemento = $dom->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Complemento');
            $comprobante->appendChild($complemento);
        }

        // Eliminar timbres previos
        $timbres = $xpath->query('tfd:TimbreFiscalDigital', $complemento);
        foreach ($timbres as $oldTimbre) {
            $complemento->removeChild($oldTimbre);
        }

        // Cargar el TFD firmado exactamente como fue generado
        $timbreDoc = new DOMDocument();
        $timbreDoc->preserveWhiteSpace = false;
        $timbreDoc->formatOutput = false;

        if (!$timbreDoc->loadXML($timbreXml)) {
            throw new Exception("No se pudo cargar el XML del TimbreFiscalDigital");
        }

        // ✅ No eliminar atributos. Se preserva el nodo exactamente como fue firmado.
        $tfdElement = $timbreDoc->documentElement;
        $timbreImportado = $dom->importNode($tfdElement, true);
        $complemento->appendChild($timbreImportado);

        return $dom->saveXML();
    }

    public static function buildXmlCfdi(array $datos): string
    {

         $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $cfdi = $doc->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Comprobante');
        $cfdi->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $cfdi->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');

        $cfdi->setAttribute('Version', '4.0');
        $cfdi->setAttribute('Serie', $datos['cfdi']['serie']);
        $cfdi->setAttribute('Folio', $datos['cfdi']['folio']);
        $cfdi->setAttribute('Fecha', $datos['cfdi']['fecha']);
        $cfdi->setAttribute('FormaPago', $datos['cfdi']['forma_pago']);
        $cfdi->setAttribute('NoCertificado', "");
        $cfdi->setAttribute('SubTotal', number_format($datos['cfdi']['sub_total'], 2, '.', ''));
        $cfdi->setAttribute('Moneda', $datos['cfdi']['moneda']);
        $cfdi->setAttribute('Total', number_format($datos['cfdi']['total'], 2, '.', ''));
        $cfdi->setAttribute('TipoDeComprobante', $datos['cfdi']['tipo_de_comprobante']);
        $cfdi->setAttribute('MetodoPago', $datos['cfdi']['metodo_pago']);
        $cfdi->setAttribute('LugarExpedicion', $datos['cfdi']['lugar_expedicion']);
        //$cfdi->setAttribute('Exportacion', '01');

        // Emisor

        $emisorFind = CfdiEmisor::find( $datos['cfdi']['emisor_id']);
        $emisor = $doc->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $emisorFind->rfc);
        $emisor->setAttribute('Nombre', $emisorFind->nombre);
        $emisor->setAttribute('RegimenFiscal', $emisorFind->regimen_fiscal);
        $cfdi->appendChild($emisor);

        // Receptor
        $receptorFind = CfdiReceptor::find($datos['cfdi']['receptor_id']);
        $receptor = $doc->createElement('cfdi:Receptor');
        $receptor->setAttribute('Rfc', $receptorFind->rfc);
        $receptor->setAttribute('Nombre', $receptorFind->nombre);
        $receptor->setAttribute('DomicilioFiscalReceptor', $receptorFind->domicilio_fiscal);
        $receptor->setAttribute('RegimenFiscalReceptor', $receptorFind->regimen_fiscal);
        $receptor->setAttribute('UsoCFDI', $receptorFind->uso_cfdi);
        $cfdi->appendChild($receptor);

        // Conceptos
        $conceptos = $doc->createElement('cfdi:Conceptos');

        foreach ($datos['conceptos'] as $c) {
            $concepto = $doc->createElement('cfdi:Concepto');
            $concepto->setAttribute('ClaveProdServ', $c['clave_prod_serv']);
            $concepto->setAttribute('Cantidad', number_format($c['cantidad'], 6, '.', ''));
            $concepto->setAttribute('ClaveUnidad', $c['clave_unidad']);
            $concepto->setAttribute('Unidad', $c['unidad']);
            $concepto->setAttribute('Descripcion', $c['descripcion']);
            $concepto->setAttribute('ValorUnitario', number_format($c['valor_unitario'], 2, '.', ''));
            $concepto->setAttribute('Importe', number_format($c['importe'], 2, '.', ''));
            //$concepto->setAttribute('ObjetoImp', '01'); // No objeto de impuesto
            $conceptos->appendChild($concepto);
        }

        $cfdi->appendChild($conceptos);

        $doc->appendChild($cfdi);

        return $doc->saveXML();

    }

    /**
     * Inserta el XML en la base de datos.
     *
     * @param string $xml Ruta al archivo XML.
     * @return void
     */
    public static function insertXmlToDB(string $xml, CfdiArchivo $cfdiArchivo): void
    {
        // Aquí va la lógica para insertar el XML en la base de datos
        $comprobante = \CfdiUtils\Cfdi::newFromString(file_get_contents($xml))
            ->getQuickReader();

        $comprobantes = $comprobante();
        $emisor = $comprobantes[0];
        $conceptos = $comprobante->conceptos;


        if(!$emisor) {
            throw new Exception("No se pudo obtener el emisor del CFDI");
        }

        // Aquí puedes guardar el emisor en la base de datos
       $emisorData = CfdiEmisor::updateOrCreate(
            ['rfc' => $emisor['Rfc']],
            [
                'nombre' => $emisor['Nombre'],
                'regimen_fiscal' => $emisor['RegimenFiscal'] ?? null,
                'domicilio_fiscal' => null,
            ]
        );

        $receptor = $comprobantes[1] ?? null;

        if (!$receptor) {
            throw new Exception("No se pudo obtener el receptor del CFDI");
        }

        // Aquí puedes guardar el receptor en la base de datos
        $receptorData = CfdiReceptor::updateOrCreate(
            ['rfc' => $receptor['Rfc']],
            [
                'nombre' => $receptor['Nombre'],
                'domicilio_fiscal' => $receptor['DomicilioFiscalReceptor'] ?? null,
                'regimen_fiscal' => $receptor['RegimenFiscalReceptor'] ?? null,
                'uso_cfdi' => $receptor['UsoCFDI'] ?? null,
            ]
        );

             $cfdi = Cfdi::create([
                'emisor_id' => $emisorData->id,
                'receptor_id' => $receptorData->id,
                'uuid' => $comprobante['UUID'],
                'serie' => $comprobante['Serie'],
                'folio' => $comprobante['Folio'],
                'fecha' => $comprobante['Fecha'],
                'forma_pago' => $comprobante['FormaPago'],
                'no_certificado' => $comprobante['NoCertificado'],
                'subtotal' => number_format($comprobante['SubTotal'], 2, '.', ''),
                'moneda' => $comprobante['Moneda'],
                'total' => number_format($comprobante['Total'], 2, '.', ''),
                'tipo_de_comprobante' => $comprobante['TipoDeComprobante'],
                'metodo_pago' => $comprobante['MetodoPago'],
                'lugar_expedicion' => $comprobante['LugarExpedicion'],
                'user_id' => auth()->id(),
                'cfdi_archivos_id' => $cfdiArchivo->id
            ]);

            $cfdiArchivo->rfc_receptor = $receptor['Rfc'];
            $cfdiArchivo->total = number_format($comprobante['Total'], 2, '.', '');
            $cfdiArchivo->fecha = $comprobante['Fecha'];
            $cfdiArchivo->tipo_comprobante = $comprobante['TipoDeComprobante'];
            $cfdiArchivo->save();

         // insertar si hay conceptos en la bsee de datos
         foreach($conceptos() as $concepto)
         {
            $cfdiConcepto = new \App\Models\Models\CfdiConcepto();
            $cfdiConcepto->cfdi_id = $cfdi->id;
            $cfdiConcepto->clave_prod_serv = $concepto['ClaveProdServ'];
            $cfdiConcepto->no_identificacion = $concepto['NoIdentificacion'];
            $cfdiConcepto->cantidad = $concepto['Cantidad'];
            $cfdiConcepto->clave_unidad = $concepto['ClaveUnidad'];
            $cfdiConcepto->unidad = $concepto['Unidad'];
            $cfdiConcepto->descripcion = $concepto['Descripcion'];
            $cfdiConcepto->valor_unitario = $concepto['ValorUnitario'];
            $cfdiConcepto->importe = $concepto['Importe'];
            $cfdiConcepto->save();
         }
    }

}
