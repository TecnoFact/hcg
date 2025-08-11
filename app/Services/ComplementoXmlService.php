<?php

namespace App\Services;

use App\Models\Emisor;
use App\Models\ObjImp;
use App\Models\RegimeFiscal;
use App\Models\RetencionCfdi;
use App\Models\Tax;
use App\Models\TrasladoCfdi;
use DOMXPath;
use Exception;
use DOMDocument;
use App\Models\Models\Cfdi;
use App\Models\Models\CfdiReceptor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Str;

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

    /*
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
        $cfdi->setAttribute('SubTotal', number_format($datos['cfdi']['subtotal'], 2, '.', ''));
        $cfdi->setAttribute('Moneda', $datos['cfdi']['moneda']);
        $cfdi->setAttribute('Total', number_format($datos['cfdi']['total'], 2, '.', ''));
        $cfdi->setAttribute('TipoDeComprobante', $datos['cfdi']['tipo_de_comprobante']);
        $cfdi->setAttribute('MetodoPago', $datos['cfdi']['metodo_pago']);
        $cfdi->setAttribute('LugarExpedicion', $datos['cfdi']['lugar_expedicion']);

        // Emisor
        $emisorFind = Emisor::find($datos['cfdi']['emisor_id']);
        $regimenFiscal = RegimeFiscal::find($emisorFind->tax_regimen_id);
        $emisor = $doc->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $emisorFind->rfc);
        $emisor->setAttribute('Nombre', $emisorFind->name);
        $emisor->setAttribute('RegimenFiscal', $regimenFiscal ? $regimenFiscal->clave : '01');
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
            $objeImp = ObjImp::find($c['obj_imp_id']);

            $concepto = $doc->createElement('cfdi:Concepto');
            $concepto->setAttribute('ClaveProdServ', $c['clave_prod_serv']);
            $concepto->setAttribute('Cantidad', number_format((float) str_replace([',', ' '], '', $c['cantidad']), 6, '.', ''));
            $concepto->setAttribute('ClaveUnidad', $c['clave_unidad']);
            $concepto->setAttribute('Unidad', $c['unidad']);
            $concepto->setAttribute('Descripcion', $c['descripcion']);
            $concepto->setAttribute('ValorUnitario', number_format((float) str_replace([',', ' '], '', $c['valor_unitario']), 2, '.', ''));
            $concepto->setAttribute('Importe', number_format((float) str_replace([',', ' '], '', $c['importe']), 2, '.', ''));
            $concepto->setAttribute('ObjetoImp', $objeImp ? $objeImp->clave : '01');

            // Nodo de impuestos por concepto
            if (!empty($c['traslados']) || !empty($c['retenciones'])) {
                $impuestosConcepto = $doc->createElement('cfdi:Impuestos');

                // Traslados
                if (!empty($c['traslados'])) {
                    $traslados = $doc->createElement('cfdi:Traslados');
                    foreach ($c['traslados'] as $t) {
                        $traslado = $doc->createElement('cfdi:Traslado');
                        $traslado->setAttribute('Base', number_format($t['base'], 2, '.', ''));
                        $traslado->setAttribute('Impuesto', $t['impuesto']); // Ej: 002 = IVA
                        $traslado->setAttribute('TipoFactor', $t['tipo_factor']); // Ej: Tasa
                        $traslado->setAttribute('TasaOCuota', number_format($t['tasa'], 6, '.', ''));
                        $traslado->setAttribute('Importe', number_format($t['importe'], 2, '.', ''));
                        $traslados->appendChild($traslado);
                    }
                    $impuestosConcepto->appendChild($traslados);
                }

                // Retenciones
                if (!empty($c['retenciones'])) {
                    $retenciones = $doc->createElement('cfdi:Retenciones');
                    foreach ($c['retenciones'] as $r) {
                        $retencion = $doc->createElement('cfdi:Retencion');
                        $retencion->setAttribute('Base', number_format($r['base'], 2, '.', ''));
                        $retencion->setAttribute('Impuesto', $r['impuesto']);
                        $retencion->setAttribute('TipoFactor', $r['tipo_factor']);
                        $retencion->setAttribute('TasaOCuota', number_format($r['tasa'], 6, '.', ''));
                        $retencion->setAttribute('Importe', number_format($r['importe'], 2, '.', ''));
                        $retenciones->appendChild($retencion);
                    }
                    $impuestosConcepto->appendChild($retenciones);
                }

                $concepto->appendChild($impuestosConcepto);
            }

            $conceptos->appendChild($concepto);
        }

        $cfdi->appendChild($conceptos);

        // Nodo global de impuestos
        $impuestosGlobal = $doc->createElement('cfdi:Impuestos');

        if (!empty($datos['impuestos']['retenciones'])) {
            $retencionesGlobal = $doc->createElement('cfdi:Retenciones');
            foreach ($datos['impuestos']['retenciones'] as $r) {
                $retencion = $doc->createElement('cfdi:Retencion');
                $retencion->setAttribute('Impuesto', $r['impuesto']);
                $retencion->setAttribute('Importe', number_format($r['importe'], 2, '.', ''));
                $retencionesGlobal->appendChild($retencion);
            }
            $impuestosGlobal->appendChild($retencionesGlobal);
        }

        if (!empty($datos['impuestos']['traslados'])) {
            $trasladosGlobal = $doc->createElement('cfdi:Traslados');
            foreach ($datos['impuestos']['traslados'] as $t) {
                $traslado = $doc->createElement('cfdi:Traslado');
                $traslado->setAttribute('Base', number_format($t['base'], 2, '.', ''));
                $traslado->setAttribute('Impuesto', $t['impuesto']);
                $traslado->setAttribute('TipoFactor', $t['tipo_factor']);
                $traslado->setAttribute('TasaOCuota', number_format($t['tasa'], 6, '.', ''));
                $traslado->setAttribute('Importe', number_format($t['importe'], 2, '.', ''));
                $trasladosGlobal->appendChild($traslado);
            }
            $impuestosGlobal->appendChild($trasladosGlobal);
        }

        if (isset($datos['impuestos']['total_retenciones'])) {
            $impuestosGlobal->setAttribute('TotalImpuestosRetenidos', number_format($datos['impuestos']['total_retenciones'], 2, '.', ''));
        }
        if (isset($datos['impuestos']['total_traslados'])) {
            $impuestosGlobal->setAttribute('TotalImpuestosTrasladados', number_format($datos['impuestos']['total_traslados'], 2, '.', ''));
        }

        $cfdi->appendChild($impuestosGlobal);

        $doc->appendChild($cfdi);

        return $doc->saveXML();

    }
    */

    public static function buildXmlCfdiFromDatabase(Cfdi $datos)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $cfdi = $doc->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Comprobante');
        $cfdi->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $cfdi->setAttribute('xsi:schemaLocation', 'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd');

        $cfdi->setAttribute('Version', '4.0');
        $cfdi->setAttribute('Serie', $datos->serie);
        $cfdi->setAttribute('Folio', $datos->folio);
        $cfdi->setAttribute('Fecha', $datos->fecha);
        $cfdi->setAttribute('FormaPago', $datos->forma_pago);
        $cfdi->setAttribute('NoCertificado', "");
        $cfdi->setAttribute('SubTotal', number_format($datos->sub_total, 2, '.', ''));
        $cfdi->setAttribute('Moneda', $datos->moneda);
        $cfdi->setAttribute('Total', number_format($datos->total, 2, '.', ''));
        $cfdi->setAttribute('TipoDeComprobante', $datos->tipo_de_comprobante);
        $cfdi->setAttribute('MetodoPago', $datos->metodo_pago);
        $cfdi->setAttribute('LugarExpedicion', $datos->lugar_expedicion);
        //$cfdi->setAttribute('Exportacion', '01');

        // Emisor

        $emisorFind = Emisor::find( $datos->emisor_id);
        $regimenFiscal = RegimeFiscal::find($emisorFind->tax_regimen_id);
        $emisor = $doc->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $emisorFind->rfc);
        $emisor->setAttribute('Nombre', $emisorFind->name);
        $emisor->setAttribute('RegimenFiscal', $regimenFiscal ? $regimenFiscal->clave : '01');
        $cfdi->appendChild($emisor);

        // Receptor
        $receptorFind = CfdiReceptor::find($datos->receptor_id);
        $receptor = $doc->createElement('cfdi:Receptor');
        $receptor->setAttribute('Rfc', $receptorFind->rfc);
        $receptor->setAttribute('Nombre', $receptorFind->nombre);
        $receptor->setAttribute('DomicilioFiscalReceptor', $receptorFind->domicilio_fiscal);
        $receptor->setAttribute('RegimenFiscalReceptor', $receptorFind->regimen_fiscal);
        $receptor->setAttribute('UsoCFDI', $receptorFind->uso_cfdi);
        $cfdi->appendChild($receptor);

        // Conceptos
        $conceptos = $doc->createElement('cfdi:Conceptos');

        foreach ($datos->conceptos as $c) {
             $objeImp = ObjImp::find($c['obj_imp_id']);
            $concepto = $doc->createElement('cfdi:Concepto');
            $concepto->setAttribute('ClaveProdServ', $c['clave_prod_serv']);
            $concepto->setAttribute('Cantidad', number_format($c['cantidad'], 6, '.', ''));
            $concepto->setAttribute('ClaveUnidad', $c['clave_unidad']);
            $concepto->setAttribute('Unidad', $c['unidad']);
            $concepto->setAttribute('Descripcion', $c['descripcion']);
            $concepto->setAttribute('ValorUnitario', number_format($c['valor_unitario'], 2, '.', ''));
            $concepto->setAttribute('Importe', number_format($c['importe'], 2, '.', ''));
            $concepto->setAttribute('ObjetoImp', $objeImp ? $objeImp->clave : '01'); // No objeto de impuesto
            $conceptos->appendChild($concepto);
        }

        $cfdi->appendChild($conceptos);

        $doc->appendChild($cfdi);

        return $doc->saveXML();
    }

    /**
     * Genera el XML del CFDI a partir de un array de datos.
     *
     * @param array $datos
     * @return string
     */
    public static function buildXmlCfdi(array $datos): string
    {
        //dd($datos);
        if (empty($datos)) {
            throw new \InvalidArgumentException('Los datos son requeridos para generar el CFDI.');
        }

        $emisor = Emisor::find($datos['cfdi']->emisor_id);

        if (!$emisor) {
            throw new \InvalidArgumentException('El emisor no existe.');
        }

        $certificatePath = Storage::disk('local')->path($emisor->file_certificate);
        $fileKeyPath = Storage::disk('local')->path($emisor->file_key);

        if (!file_exists($certificatePath)) {
            throw new \InvalidArgumentException("El certificado del emisor $emisor->rfc no existe.");
        }

        $certificado = new \CfdiUtils\Certificado\Certificado($certificatePath);


        $comprobanteAtributos = [
            'Serie' => $datos['cfdi']->serie ?? null,
            'Folio' => $datos['cfdi']->folio ?? null,
            'Fecha' => str_replace(' ', 'T', $datos['cfdi']->fecha ?? ''),
            'FormaPago' => $datos['cfdi']->forma_pago ?? null,
            'MetodoPago' => $datos['cfdi']->metodo_pago ?? null,
            'TipoDeComprobante' => $datos['cfdi']->tipo_de_comprobante ?? null,
            'LugarExpedicion' => $datos['cfdi']->lugar_expedicion ?? null,
            'Moneda' => 'MXN',
            'TipoCambio' => '1'
        ];
        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();

        // No agrego (aunque puedo) el Rfc y Nombre porque uso los que están establecidos en el certificado
        $comprobante->addEmisor([
            'Rfc' => $emisor->rfc,
            'Nombre' => Str::upper($emisor->name),
            'RegimenFiscal' => $emisor->regimenFiscal->clave, // General de Ley Personas Morales
        ]);

        $receptor = CfdiReceptor::find($datos['cfdi']->receptor_id);

        if (!$receptor) {
            throw new \InvalidArgumentException('El receptor no existe.');
        }

        $comprobante->addReceptor([
            'Rfc' => $receptor->rfc,
            'Nombre' => Str::upper($receptor->nombre),
            'DomicilioFiscalReceptor' => $receptor->domicilio_fiscal,
            'RegimenFiscalReceptor' => $receptor->regimen_fiscal,
            'UsoCFDI' => $receptor->uso_cfdi,
        ]);


        $conceptos = [];

        foreach($datos['cfdi']->conceptos as $concepto)
        {


           $objeImp = ObjImp::find($concepto->obj_imp_id);

           $conceptos['ClaveProdServ'] = $concepto->clave_prod_serv;
           $conceptos['Cantidad'] = number_format((float) str_replace([',', ' '], '', $concepto->cantidad), 6, '.', '');
           $conceptos['ClaveUnidad'] = $concepto->clave_unidad;
           $conceptos['Unidad'] = $concepto->unidad;
           $conceptos['Descripcion'] = $concepto->descripcion;
           $conceptos['ValorUnitario'] = number_format((float) str_replace([',', ' '], '', $concepto->valor_unitario), 2, '.', '');
           $conceptos['Importe'] = number_format((float) str_replace([',', ' '], '', $concepto->importe), 2, '.', '');
           $conceptos['ObjetoImp'] = $objeImp ? $objeImp->clave : '01';

           $conceptoCfdi = $comprobante->addConcepto($conceptos);

            // buscar el impuesto
            $taxs = $concepto->impuestos ?? Tax::find((int)$concepto->tipo_impuesto);

            $attributesTaxes = [];
            if ($taxs && ($taxs->code === '002' || $taxs->code === '003')) {
                // TRASLADOS
                foreach($concepto->traslados as $traslado)
                {
                    $attributesTaxes['Importe'] = number_format((float) str_replace([',', ' '], '', $traslado->importe), 2, '.', '');
                    $attributesTaxes['TasaOCuota'] = number_format((float) str_replace([',', ' '], '', ($taxs->rate / 100)), 2, '.', '');
                    $attributesTaxes['Base'] = number_format((float) str_replace([',', ' '], '', $traslado->base), 2, '.', '');
                    $attributesTaxes['Impuesto'] = $taxs->code; // 002 = IVA, 003 = IEPS
                    $attributesTaxes['TipoFactor'] = 'tasa';
                    $conceptoCfdi->addTraslado($attributesTaxes);
                }
            }

            if($taxs->code === '001')
            {
                // retenciones
                foreach($concepto->retenciones as $items)
                {
                    $attributesTaxes['Importe'] = number_format((float) str_replace([',', ' '], '', $items->importe), 2, '.', '');
                    $attributesTaxes['TasaOCuota'] = number_format((float) str_replace([',', ' '], '', ($taxs->rate / 100)), 2, '.', '');
                    $attributesTaxes['Base'] = number_format((float) str_replace([',', ' '], '', $items->base), 2, '.', '');
                    $attributesTaxes['Impuesto'] = $taxs->code; // 002 = IVA, 003 = IEPS
                    $attributesTaxes['TipoFactor'] = 'tasa';
                    $conceptoCfdi->addRetencion($attributesTaxes);
                }
            }
        }



        // método de ayuda para establecer las sumas del comprobante e impuestos
        // con base en la suma de los conceptos y la agrupación de sus impuestos
        $creator->addSumasConceptos(null, 2);

            $keyDerFile = $fileKeyPath;
            $keyPemFileUnprotected = $keyDerFile . '.unprotected.pem';
            $keyDerPass = $emisor->password_key;

            $openssl = new \CfdiUtils\OpenSSL\OpenSSL();

                if( !file_exists($keyPemFileUnprotected)) {
                    // Convertir clave DER a PEM
                    $openssl->derKeyConvert($keyDerFile, $keyDerPass, $keyPemFileUnprotected);
                }

        // método de ayuda para generar el sello (obtener la cadena de origen y firmar con la llave privada)
        $creator->addSello('file://' . $keyPemFileUnprotected, $emisor->password_key);

        // método de ayuda para mover las declaraciones de espacios de nombre al nodo raíz
        $creator->moveSatDefinitionsToComprobante();

        // método de ayuda para validar usando las validaciones estándar de creación de la librería
        $asserts = $creator->validate();

        if ($asserts->hasErrors()) { // contiene si hay o no errores
            Log::debug($asserts->errors());
            //throw new Exception(implode("\n", $asserts->errors()));
        }

        // método de ayuda para generar el xml y guardar los contenidos en un archivo
        //$creator->saveXml(Storage::disk('local')->path('cfdi/'. $emisor->rfc . '/' . $datos['cfdi']['uuid'] . '.xml'));

        // método de ayuda para generar el xml y retornarlo como un string
        return $creator->asXml();
    }

    /**
     * Inserta el XML en la base de datos.
     *
     * @param string $xml Ruta al archivo XML.
     * @return void
     */
    public static function insertXmlToDB(string $xml, Cfdi $cfdiArchivo): void
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
       $emisorData = Emisor::updateOrCreate(
            ['rfc' => $emisor['Rfc']],
            [
                'name' => $emisor['Nombre'],
                'tax_regimen_id' => $emisor['RegimenFiscal'] ?? null,
                'postal_code' => null,
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

             $cfdi = $cfdiArchivo->update([
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
                'cfdi_archivos_id' => null
            ]);



         // insertar si hay conceptos en la bsee de datos
         foreach($conceptos() as $concepto)
         {
            $objImp = ObjImp::where('clave', $concepto['ObjetoImp'])->first();
            $taxs = Tax::where('code', $concepto['Impuesto'])->first();

            $cfdiConcepto = new \App\Models\Models\CfdiConcepto();
            $cfdiConcepto->cfdi_id = $cfdiArchivo->id;
            $cfdiConcepto->clave_prod_serv = $concepto['ClaveProdServ'];
            $cfdiConcepto->no_identificacion = $concepto['NoIdentificacion'];
            $cfdiConcepto->cantidad = $concepto['Cantidad'];
            $cfdiConcepto->clave_unidad = $concepto['ClaveUnidad'];
            $cfdiConcepto->unidad = $concepto['Unidad'];
            $cfdiConcepto->descripcion = $concepto['Descripcion'];
            $cfdiConcepto->valor_unitario = $concepto['ValorUnitario'];
            $cfdiConcepto->importe = $concepto['Importe'];
            $cfdiConcepto->obj_imp_id = $objImp ? $objImp->id : null;
            $cfdiConcepto->tax_id = $taxs ? $taxs->id : null;
            $cfdiConcepto->tipo_impuesto = $taxs ? $taxs->id : null;
            $cfdiConcepto->save();


            if ($taxs && ($taxs->code === '002' || $taxs->code === '003')) {
                // TRASLADOS
                foreach($concepto->traslados as $traslado)
                {
                    TrasladoCfdi::create([
                        'concepto_id' => $cfdiConcepto->id,
                        'importe' => number_format((float) str_replace([',', ' '], '', $traslado->importe), 2, '.', ''),
                        'tasa' => number_format((float) str_replace([',', ' '], '', ($taxs->rate / 100)), 2, '.', ''),
                        'base' => number_format((float) str_replace([',', ' '], '', $traslado->base), 2, '.', ''),
                        'impuesto' => $taxs->code, // 002 = IVA, 003 = IEPS
                    ]);

                }
            }

            if($taxs->code === '001')
            {
                // retenciones
                    RetencionCfdi::create([
                        'concepto_id' => $cfdiConcepto->id,
                        'importe' => number_format((float) str_replace([',', ' '], '', $traslado->importe), 2, '.', ''),
                        'tasa' => number_format((float) str_replace([',', ' '], '', ($taxs->rate / 100)), 2, '.', ''),
                        'base' => number_format((float) str_replace([',', ' '], '', $traslado->base), 2, '.', ''),
                        'impuesto' => $taxs->code, // 002 = IVA, 003 = IEPS
                    ]);
            }
         }
    }

}
