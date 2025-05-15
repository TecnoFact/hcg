<?php

namespace Tests\Unit;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
//use PHPUnit\Framework\TestCase;
use Illuminate\Support\Str;
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
        //$url = 'https://recepcion.facturaelectronica.sat.gob.mx/Seguridad/Autenticacion.svc';

        $url = 'https://sc1-cfd-cert-cses-cancela.southcentralus.cloudapp.azure.com/Autenticacion/Autenticacion.svc';


           $certificado = Storage::disk('certi')->get('certs/00001000000710981021.cer');


           if(! $certificado) {
               throw new \Exception('Certificado no encontrado');
           }


            $uuid = Str::uuid()->toString();
            $uuid = "uuid-$uuid-1";
            // zone horaria mexico
           $fecha_inicial = time() - date('Z');
           $fecha_final = $fecha_inicial + (60*5);

          // date_default_timezone_set('America/Mexico_City');

                $data = '<u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0"><u:Created>'.date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial).'</u:Created><u:Expires>'.date("Y-m-d\TH:i:s\.v\Z", $fecha_final).'</u:Expires></u:Timestamp>';
                $digestValue = base64_encode(sha1($data, true));

                $dataToSign = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#"><CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod><Reference URI="#_0"><Transforms><Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></Transform></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod><DigestValue>'.$digestValue.'</DigestValue></Reference></SignedInfo>';

            //$hsmUrl = 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxiSha1';

           // $hash = hash('sha1', $certificado, true);
            //$hashBase64 = base64_encode($hash);

            //Log::debug('Hash generado', ['hash_base64' => $hashBase64]);

            // Obtener sello desde HSM sin regenerar nodo

            //$getHSM = TimbradoService::firmarConHSM($hsmUrl, $hashBase64);

           // Log::debug('Sello HSM', ['sello' => $getHSM]);
           // $keyPEM = Storage::disk('certi')->get('app/certs/00001000000710981021.key');

          //      $keyPEMGenerate = openssl_pkey_get_private(file_get_contents(storage_path('app/certs/00001000000710981021.pem')), 'cPRM2379');

         openssl_sign($dataToSign, $digs, file_get_contents(storage_path('app/certs/00001000000710981021.pem')), OPENSSL_ALGO_SHA1);

            $hsmBase64 = base64_encode($digs);




           // $digs = base64_encode(sha1($dataToSign, true));
           //$digs = $getHSM;


            $xml = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"><s:Header><o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><u:Timestamp u:Id="_0"><u:Created>'.date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial).'</u:Created><u:Expires>'.date("Y-m-d\TH:i:s\.v\Z", $fecha_final).'</u:Expires></u:Timestamp><o:BinarySecurityToken u:Id="'.$uuid.'" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">'.base64_encode( $certificado).'</o:BinarySecurityToken><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/><Reference URI="#_0"><Transforms><Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><DigestValue>'.$digestValue.'</DigestValue></Reference></SignedInfo><SignatureValue>'. $hsmBase64 .'</SignatureValue><KeyInfo><o:SecurityTokenReference><o:Reference ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" URI="#'.$uuid.'"/></o:SecurityTokenReference></KeyInfo></Signature></o:Security></s:Header><s:Body><Autentica xmlns="http://tempuri.org/" /></s:Body></s:Envelope>';



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

        date_default_timezone_set('America/Mexico_City');

        $created = date("Y-m-d\TH:i:s\.v\Z", $fecha_inicial);
        $expires = date("Y-m-d\TH:i:s\.v\Z", $fecha_final);


        $uuid = Str::uuid()->toString();
        $uuid = "uuid-$uuid-1";

        $certificado = Storage::disk('certi')->get('certs/00001000000710981021.cer');

        $privateKeyContents = Storage::disk('certi')->get('certs/00001000000710981021.key');
        $passPhrase = 'cPRM2379';

        $credential = Credential::create($certificado, $privateKeyContents, $passPhrase);

        $certificate = self::cleanPemContents($credential->certificate()->pem());



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
                    <Autentica xmlns="http://tempuri.org"/>
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

       // $url = 'https://sc1-cfd-cert-cses-cancela.southcentralus.cloudapp.azure.com/Autenticacion/Autenticacion.svc';
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
        $certificado = Storage::disk('certi')->path('certs/00001000000710981021.cer');
        $privateKeyContents = Storage::disk('certi')->path('certs/00001000000710981021.key');
        $passPhrase = 'cPRM2379';

        $fiel = Fiel::create(
            file_get_contents($certificado),
            file_get_contents($privateKeyContents),
            $passPhrase
        );

        $webClient = new GuzzleWebClient();

        // creación del objeto encargado de crear las solicitudes firmadas usando una FIEL
        $requestBuilder = new FielRequestBuilder($fiel);

        // Creación del servicio
        $service = new Service($requestBuilder, $webClient);

        dd($service->obtainCurrentToken());

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
           // dd($certificate);
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
}
