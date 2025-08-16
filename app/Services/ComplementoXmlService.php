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

        if (!file_exists($xml)) {
            throw new Exception("El archivo XML no existe");
        }
        $xmlContent = file_get_contents($xml);
        if ($xmlContent === false || trim($xmlContent) === '') {
            throw new Exception("El archivo XML está vacío");
        }

        if (!$dom->loadXML($xmlContent)) {
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
            'TipoCambio' => '1',
            'Exportacion' => '01'
        ];
        $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);

        $comprobante = $creator->comprobante();

        // No agrego (aunque puedo) el Rfc y Nombre porque uso los que están establecidos en el certificado
        $nameEmisor = Str::upper($emisor->name);
        $comprobante->addEmisor([
            'Rfc' => $emisor->rfc,
            'Nombre' => $nameEmisor,
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
           $conceptos['ValorUnitario'] = number_format((float) str_replace([',', ' '], '', $concepto->valor_unitario), 6, '.', '');
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
                    $attributesTaxes['TasaOCuota'] = number_format((float) str_replace([',', ' '], '', ($taxs->rate / 100)), 6, '.', '');
                    $attributesTaxes['Base'] = number_format((float) str_replace([',', ' '], '', $traslado->base), 6, '.', '');
                    $attributesTaxes['Impuesto'] = $taxs->code; // 002 = IVA, 003 = IEPS
                    $attributesTaxes['TipoFactor'] = 'Tasa';
                    $conceptoCfdi->addTraslado($attributesTaxes);
                }
            }

            if($taxs->code === '001')
            {
                // retenciones
                foreach($concepto->retenciones as $items)
                {
                    $attributesTaxes['Importe'] = number_format((float) str_replace([',', ' '], '', $items->importe), 2, '.', '');
                    $attributesTaxes['TasaOCuota'] = number_format((float) str_replace([',', ' '], '', ($taxs->rate / 100)), 6, '.', '');
                    $attributesTaxes['Base'] = number_format((float) str_replace([',', ' '], '', $items->base), 6, '.', '');
                    $attributesTaxes['Impuesto'] = $taxs->code; // 002 = IVA, 003 = IEPS
                    $attributesTaxes['TipoFactor'] = 'Tasa';
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

        if (!file_exists($keyPemFileUnprotected)) {
            // Convertir clave DER a PEM
            $openssl->derKeyConvert($keyDerFile, $keyDerPass, $keyPemFileUnprotected);
        }

        if (empty(file_get_contents($keyPemFileUnprotected))) {
            // Convertir clave DER a PEM
            $openssl->derKeyConvert($keyDerFile, $keyDerPass, $keyPemFileUnprotected);
        }


//        // método de ayuda para generar el sello (obtener la cadena de origen y firmar con la llave privada)
        $creator->addSello('file://' . $keyPemFileUnprotected, $emisor->password_key);

        // método de ayuda para mover las declaraciones de espacios de nombre al nodo raíz
        $creator->moveSatDefinitionsToComprobante();

        // método de ayuda para validar usando las validaciones estándar de creación de la librería
        $asserts = $creator->validate();

        $arrayErrorFromXml = [
            'TFDSELLO01',
            'TFDVERSION01',
            'SELLO08'
        ];

        if ($asserts->hasErrors()) { // contiene si hay o no errores
             //throw new Exception(implode("\n", $asserts->errors()));
            $errorData = [];
            foreach ($asserts as $assert) {
                $errorData[] = $assert->getExplanation();
                 Log::debug($assert->getExplanation());
                 Log::debug( $assert->getCode());

                 if(!in_array($assert->getCode(), $arrayErrorFromXml)) {
                     // Manejar el error específico de TFDSELLO01
                     $errorData[] = $assert->getExplanation();

                 }
            }

            if(count($errorData) > 0)
            {
               // throw new \InvalidArgumentException(implode("\n", $errorData));
            }
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
                'postal_code' => $comprobante['LugarExpedicion'],
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

        $impuestos = 0;

        $impuestosTraslados = (float)$comprobante->impuestos['totalImpuestosTrasladados'];
        $impuestosRetenidos = (float)$comprobante->impuestos['totalImpuestosRetenidos'];

        $impuestos = $impuestosTraslados + $impuestosRetenidos;

              $cfdiArchivo->update([
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
                'cfdi_archivos_id' => null,
                'impuesto' => $impuestos
            ]);

         // insertar si hay conceptos en la bsee de datos
            $contador = 1;
            foreach($conceptos() as $concepto)
            {
                // Asignar NoIdentificacion con 3 dígitos (ejemplo: 001, 002, ...)

                $noIdentification = str_pad($contador, 3, '0', STR_PAD_LEFT);

                $objImp = ObjImp::where('clave', $concepto['ObjetoImp'])->first();


                $cfdiConcepto = \App\Models\Models\CfdiConcepto::create([
                    'clave_prod_serv' => $concepto['ClaveProdServ'],
                    'cfdi_id' => $cfdiArchivo->id,
                    'no_identificacion' => $noIdentification,
                    'cantidad' => $concepto['Cantidad'],
                    'clave_unidad' => $concepto['ClaveUnidad'],
                    'unidad' => $concepto['Unidad'],
                    'descripcion' => $concepto['Descripcion'],
                    'valor_unitario' => $concepto['ValorUnitario'],
                    'importe' => $concepto['Importe'],
                    'obj_imp_id' => $objImp ? $objImp->id : null,
                ]);

                // TRASLADOS
                foreach(($concepto->impuestos->traslados)() as $traslado)
                {
                    TrasladoCfdi::create([
                        'concepto_id' => $cfdiConcepto->id,
                        'importe' => $traslado['importe'],
                        'tasa' => $traslado['TasaOCuota'],
                        'base' => $traslado['base'],
                        'impuesto' => $traslado['Impuesto'], // 002 = IVA, 003 = IEPS
                        'tipo_factor' => $traslado['TipoFactor'],
                    ]);
                    $taxs = Tax::where('code', $traslado['Impuesto'])->first();
                    $cfdiConcepto->tax_id = $taxs ? $taxs->id : null;
                    $cfdiConcepto->tipo_impuesto = $taxs ? $taxs->id : null;
                    $cfdiConcepto->save();
                }

                foreach(($concepto->impuestos->retenciones)() as $retencion) {
                          RetencionCfdi::create([
                                'concepto_id' => $cfdiConcepto->id,
                                'importe' => $retencion['importe'],
                                'tasa' => $retencion['TasaOCuota'],
                                'base' => $retencion['base'],
                                'impuesto' => $retencion['Impuesto'], // 002 = IVA, 003 = IEPS
                                'tipo_factor' => $retencion['TipoFactor'],
                        ]);

                    $taxs = Tax::where('code', $retencion['Impuesto'])->first();
                    $cfdiConcepto->tax_id = $taxs ? $taxs->id : null;
                    $cfdiConcepto->tipo_impuesto = $taxs ? $taxs->id : null;
                    $cfdiConcepto->save();

                }


                $contador++;
         }
    }

}
