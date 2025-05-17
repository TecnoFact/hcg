<?php

namespace Tests\Unit;


use App\Services\TimbradoService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
//use PHPUnit\Framework\TestCase;
use Illuminate\Support\Str;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use Tests\TestCase; // <-- Usa el TestCase de Laravel


class ExampleTest extends TestCase
{


    public function test_authentication_cfdi_sat()
    {
           $url = 'https://recepcion.facturaelectronica.sat.gob.mx/Seguridad/Autenticacion.svc';
           $hsmUrl = 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxiSha1';

           $certificado = Storage::disk('certi')->get('certs/00001000000710051653.cer');

           if(! $certificado) {
               throw new \Exception('Certificado no encontrado');
           }


           // convertir a base64 el certificado a .pem con openssl
           // convertir certificado a formato PEM si es necesario
            $contenidoPEM = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($certificado), 64, "\n") ."-----END CERTIFICATE-----\n";

            $cert_info = openssl_x509_parse($contenidoPEM);

            $certificado = $contenidoPEM;


            $uuid = Str::uuid()->toString();
            $uuid = "uuid-$uuid-1";

           $fecha_inicial = time() - date('Z');
           $fecha_final = $fecha_inicial + (60*5);

            $created = date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial);
            $expires = date("Y-m-d\TH:i:s\.v\Z", $fecha_final);

            $data = '<u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0"><u:Created>'.$created.'</u:Created><u:Expires>'.$expires.'</u:Expires></u:Timestamp>';

            // 1. Obtener el hash SHA-512 en binario (true)
            $sha512Binary = hash('sha512', $data, true);

            // 2. Codificarlo en Base64 (como en PowerShell)
            $sha512Base64 = base64_encode($sha512Binary);

            $digestValue = $sha512Base64;



            //$dataToSign = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#"><CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod><Reference URI="#_0"><Transforms><Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></Transform></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha512"></DigestMethod><DigestValue>'.$digestValue.'</DigestValue></Reference></SignedInfo>';


            $hash = hash('sha1', $certificado, true);
            $hashBase64 = base64_encode($hash);

            Log::debug('Hash generado', ['hash_base64' => $hashBase64]);

            // Obtener sello desde HSM sin regenerar nodo

            $getHSM = TimbradoService::firmarConHSM($hsmUrl, $hashBase64);

           Log::debug('Sello HSM', ['sello' => $getHSM]);
           // $keyPEM = Storage::disk('certi')->get('app/certs/00001000000710981021.key');

               // $keyPEMGenerate = openssl_pkey_get_private(file_get_contents(storage_path('app/certs/00001000000710981021.key')), 'cPRM2379');

          //$key = Storage::disk('certi')->get('certs/ASF180914KY5.pem');

           // openssl_sign($dataToSign, $digs, $key, OPENSSL_ALGO_SHA1);

            //$hsmBase64 = base64_encode($digs);




          //  $digs = base64_encode(sha1($dataToSign, true));
            $digs = $getHSM;


            $xml = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"><s:Header><o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><u:Timestamp u:Id="_0"><u:Created>'.$created.'</u:Created><u:Expires>'.$expires.'</u:Expires></u:Timestamp><o:BinarySecurityToken u:Id="'.$uuid.'" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.base64_encode( $certificado).'</o:BinarySecurityToken><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/><Reference URI="#_0"><Transforms><Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><DigestValue>'.$digestValue.'</DigestValue></Reference></SignedInfo><SignatureValue>'. $digs .'</SignatureValue><KeyInfo><o:SecurityTokenReference><o:Reference ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" URI="#'.$uuid.'"/></o:SecurityTokenReference></KeyInfo></Signature></o:Security></s:Header><s:Body><Autentica xmlns="http://tempuri.org/" /></s:Body></s:Envelope>';



        // eel certficado tiene que ser un .pem


        // el binarysecuritytoken es el hash sha1 del certificado con un uuid generado


        // eel digest value debe ser el hash sha1 del nodo <u:Timestamp>


        $soapEnvelope = $xml;

        var_dump($soapEnvelope);


        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tempuri.org/IAutenticacion/Autentica"',
            'Content-Length: ' . strlen($soapEnvelope),
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        var_dump(" ==================== ");
        var_dump($response);



        $this->assertIsString($response);
        $this->assertEmpty($error, "cURL error: $error");
    }



    public function test_login_sat()
    {

        $fecha_inicial = time() - date('Z');
        $fecha_final = $fecha_inicial + (60*5);



        $created = date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial);
        $expires = date("Y-m-d\TH:i:s\.v\Z", $fecha_final);


        $uuid = Str::uuid()->toString();
        $uuid = "uuid-$uuid-1";
        // USANDO CERTIFICADO DE LA FIEL
        // --- CERTIFICADO DE LA FIEL
        $certificado = Storage::disk('certi')->get('certs/00001000000600268609.cer');
        $privateKeyContents = Storage::disk('certi')->get('certs/Claveprivada_FIEL_ASF180914KY5_20230518_214216.key');
        $passPhrase = 'Antares0528';

        // VALIDAR CERTIFCADO




       // dd($fiel->getCertificatePemContents());



        $credential = Credential::create($certificado, $privateKeyContents, $passPhrase);

        $certificate = self::cleanPemContents($credential->certificate()->pem());

       // $certificate =  self::cleanPemContents($fiel->getCertificatePemContents());

     //   Log::debug('Certificado', ['certificado' => $certificate]);

        $keyInfoData = <<<EOT
            <KeyInfo>
                <o:SecurityTokenReference>
                    <o:Reference URI="#$uuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3"/>
                </o:SecurityTokenReference>
            </KeyInfo>
            EOT;

        $toDigestXml = <<<EOT
            <u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0">
                <u:Created>{$created}</u:Created>
                <u:Expires>{$expires}</u:Expires>
            </u:Timestamp>
            EOT;

        $signatureData = self::createSignature($toDigestXml, '#_0', $keyInfoData, $credential);

        $xml = <<<EOT
            <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                <s:Header>
                    <o:Security xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" s:mustUnderstand="1">
                        <u:Timestamp u:Id="_0">
                            <u:Created>{$created}</u:Created>
                            <u:Expires>{$expires}</u:Expires>
                        </u:Timestamp>
                        <o:BinarySecurityToken u:Id="$uuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">
                            $certificate
                        </o:BinarySecurityToken>
                        $signatureData
                    </o:Security>
                </s:Header>
                <s:Body>
                    <Autentica xmlns="http://tempuri.org/" />
                </s:Body>
            </s:Envelope>
            EOT;

        $xml = self::nospaces($xml);

        $soapEnvelope = $xml;

        var_dump($soapEnvelope);


         $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tempuri.org/IAutenticacion/Autentica"',
            'Content-Length: ' . strlen($soapEnvelope),
        ];

        $url = 'https://recepcion.facturaelectronica.sat.gob.mx/Seguridad/Autenticacion.svc';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        var_dump(" ==================== ");
        var_dump($response);



        $this->assertIsString($response);
        $this->assertEmpty($error, "cURL error: $error");

    }

    public function test_new_login()
    {

        $fecha_inicial = time() - date('Z');
        $fecha_final = $fecha_inicial + (60*5);
        $created = date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial);
        $expires = date("Y-m-d\TH:i:s\.v\Z", $fecha_final);

        $uuid = Str::uuid()->toString();
        $uuid = "uuid-$uuid-1";

        // USANDO CERTIFICADO DE LA FIEL
        // --- CERTIFICADO DE LA FIEL -- //
      /*   $certificado = Storage::disk('certi')->get('certs/00001000000600268609.cer');
        $privateKeyContents = Storage::disk('certi')->get('certs/Claveprivada_FIEL_ASF180914KY5_20230518_214216.key');
        $passPhrase = 'Antares0528';
        $credential = Credential::create($certificado, $privateKeyContents, $passPhrase);
        $certificate = self::cleanPemContents($credential->certificate()->pem()); */

        // --- CSD CERTIFICADO -- //
         $certificado = Storage::disk('csd')->get('00001000000710051653.cer');
          $credential = new Certificate($certificado);
          $certificate = self::cleanPemContents($credential->pem());

         // convertir .cer a .pem
         // convertir certificado a formato PEM si es necesario
       // $contenidoPEM = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($certificado), 64, "\n") ."-----END CERTIFICATE-----\n";




       // $certificate =  self::cleanPemContents($fiel->getCertificatePemContents());
      //   Log::debug('Certificado', ['certificado' => $certificate]);

        $keyInfoData = <<<EOT
            <KeyInfo>
                <o:SecurityTokenReference>
                    <o:Reference URI="#$uuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3"/>
                </o:SecurityTokenReference>
            </KeyInfo>
            EOT;

        $toDigestXml = <<<EOT
            <u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0">
                <u:Created>{$created}</u:Created>
                <u:Expires>{$expires}</u:Expires>
            </u:Timestamp>
            EOT;

        $signatureData = self::createSignatureXml($toDigestXml, '#_0', $keyInfoData, $credential);

        $xml = <<<EOT
            <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                <s:Header>
                    <o:Security xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" s:mustUnderstand="1">
                        <u:Timestamp u:Id="_0">
                            <u:Created>{$created}</u:Created>
                            <u:Expires>{$expires}</u:Expires>
                        </u:Timestamp>
                        <o:BinarySecurityToken u:Id="$uuid" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">
                            $certificate
                        </o:BinarySecurityToken>
                        $signatureData
                    </o:Security>
                </s:Header>
                <s:Body>
                    <Autentica xmlns="http://tempuri.org/" />
                </s:Body>
            </s:Envelope>
            EOT;

        $xml = self::nospaces($xml);

        $soapEnvelope = $xml;

        var_dump($soapEnvelope);


         $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tempuri.org/IAutenticacion/Autentica"',
            'Content-Length: ' . strlen($soapEnvelope),
        ];

        $url = 'https://recepcion.facturaelectronica.sat.gob.mx/Seguridad/Autenticacion.svc';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        var_dump(" ==================== ");
        var_dump($response);



        $this->assertIsString($response);
        $this->assertEmpty($error, "cURL error: $error");
    }



    public static function cleanPemContents(string $pemContents): string
    {
        $filteredLines = array_filter(
            explode("\n", $pemContents),
            fn (string $line): bool => ! str_starts_with($line, '-----')
        );
        return implode('', array_map('trim', $filteredLines));
    }


    public static function nospaces(string $input): string
    {
        return preg_replace(
            [
                '/^\h*/m',      // A: remove horizontal spaces at beginning
                '/\h*\r?\n/m',  // B: remove horizontal spaces + optional CR + LF
                '/\?></',       // C: xml definition on its own line
            ],
            [
                '',             // A: remove
                '',             // B: remove
                "?>\n<",        // C: insert LF
            ],
            $input
        ) ?? '';
    }

    private function createSignature(string $toDigest, string $signedInfoUri = '', string $keyInfo = '', $certificate): string
    {

            $toDigest = self::nospaces($toDigest);
            $digested = base64_encode(sha1($toDigest, true));
            $signedInfo = self::createSignedInfoCanonicalExclusive($digested, $signedInfoUri);
            $signatureValue = base64_encode($certificate->sign($signedInfo, OPENSSL_ALGO_SHA1));
            $signedInfo = str_replace('<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">', '<SignedInfo>', $signedInfo);

            if ('' === $keyInfo) {
                $keyInfo = self::createKeyInfoData($certificate);
            }

            return <<<EOT
                <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
                    $signedInfo
                    <SignatureValue>$signatureValue</SignatureValue>
                    $keyInfo
                </Signature>
                EOT;
    }


        private function createSignedInfoCanonicalExclusive(string $digested, string $uri = ''): string
        {
            // see https://www.w3.org/TR/xmlsec-algorithms/ to understand the algorithm
            // http://www.w3.org/2001/10/xml-exc-c14n# - Exclusive Canonicalization XML 1.0 (omit comments)
            $xml = <<<EOT
                <SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
                    <CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod>
                    <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>
                    <Reference URI="$uri">
                        <Transforms>
                            <Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></Transform>
                        </Transforms>
                        <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod>
                        <DigestValue>$digested</DigestValue>
                    </Reference>
                </SignedInfo>
                EOT;

                return self::nospaces($xml);
        }


         private function createKeyInfoData($certificate): string
        {
            $fiel = $certificate;
            $certificate = self::cleanPemContents($fiel->certificate()->pem());
            $serial = $fiel->certificate()->getCertificateSerial();
            $issuerName = $this->parseXml($fiel->certificate()->getCertificateIssuerName());


            return <<<EOT
                <KeyInfo>
                    <X509Data>
                        <X509IssuerSerial>
                            <X509IssuerName>$issuerName</X509IssuerName>
                            <X509SerialNumber>$serial</X509SerialNumber>
                        </X509IssuerSerial>
                        <X509Certificate>$certificate</X509Certificate>
                    </X509Data>
                </KeyInfo>
                EOT;
        }

          private function createSignatureXml(string $toDigest, string $signedInfoUri = '', string $keyInfo = '', $certificate): string
          {

            $hsmUrl = 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxiSha1';
            $toDigest = self::nospaces($toDigest);
            $digested = base64_encode(sha1($toDigest, true));
            $signedInfo = self::createSignedInfoCanonicalExclusive($digested, $signedInfoUri);
            $shaSign = base64_encode(sha1($signedInfo, true));
            $signatureValue =  TimbradoService::firmarConHSM($hsmUrl,  $shaSign);
            $signedInfo = str_replace('<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">', '<SignedInfo>', $signedInfo);


            if ('' === $keyInfo)
            {
                $keyInfo = self::createKeyInfoData($certificate);
            }

            return <<<EOT
                <Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
                    $signedInfo
                    <SignatureValue>$signatureValue</SignatureValue>
                    $keyInfo
                </Signature>
                EOT;
        }



         public function test_conexion_blob(): void
        {
             try {
                $disk = Storage::disk('azure');
                $archivos = $disk->allFiles(); // Esto lanza error si no conecta

            Log::debug('Conexión exitosa. Se encontraron ' . count($archivos) . ' archivos.');

                $this->assertNotEmpty($archivos, 'No se encontraron archivos en el contenedor.');
                $this->assertIsArray($archivos, 'El resultado no es un array.');
            } catch (\Exception $e) {
                Log::debug('Error en la conexión: ' . $e->getMessage());
            }
        }


        public function test_leer_xml()
        {
            $file_xml = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><AcuseRecepcion xmlns="http://recibecfdi.sat.gob.mx"><AcuseRecepcionCFDI UUID="F2A24651-DFBE-4EEE-A76D-A032497F4DCA" CodEstatus="Comprobante rechazado" Fecha="2025-05-16T15:43:09.0702032" NoCertificadoSAT="00001000000710051653"><Incidencia><MensajeIncidencia>Autenticación no válida</MensajeIncidencia><NoCertificadoPac/><CodigoError>501</CodigoError><RfcEmisor>PUL230626UV4</RfcEmisor><IdIncidencia>f42cc4ee-6aed-4ebe-8da1-2baf56379c29</IdIncidencia><Uuid>f2a24651-dfbe-4eee-a76d-a032497f4dca</Uuid><WorkProcessId>057e277d-4fde-4a46-a50c-43711f713ebd</WorkProcessId><FechaRegistro>2025-05-16T15:43:09.0702032</FechaRegistro></Incidencia></AcuseRecepcionCFDI></AcuseRecepcion></s:Body></s:Envelope>';

            $xml = simplexml_load_string($file_xml);

            $namespaces = $xml->getNamespaces(true);

            $body = $xml->children($namespaces['s'])->Body;

                // Acceder al nodo AcuseRecepcionCFDI
                $acuse = $body->children('http://recibecfdi.sat.gob.mx')->AcuseRecepcion->AcuseRecepcionCFDI;

                $incidencia = $acuse->Incidencia;
                $mensaje = $incidencia->MensajeIncidencia;
                $codigoError = $incidencia->CodigoError;
                $rfcEmisor = $incidencia->RfcEmisor;
                $idIncidencia = $incidencia->IdIncidencia;
                $uuid = $incidencia->Uuid;

                dd($codigoError);
        }
}
