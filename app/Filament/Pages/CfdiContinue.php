<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use App\Models\CfdiArchivo;
use App\Services\TimbradoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class CfdiContinue extends Page
{
    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.pages.cfdi-continue';


    protected static ?string $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;



    public static function getUriKey(): string
    {
        return 'cfdi-continue';
    }

    public static function getParameters(): array
    {
        return [
            'id' => ['required'],
        ];
    }


    public $rfc;
    public $xml_file;

    public $pdf_file;

    public $subido = false;

    public $sellado = false;
    public $timbrado = false;
    public $depositado = false;

    public $xmlPath = '';
    public $pathXml = '';

    public $sello = '';

    public $rfcReceptor = '';

    public $registerFirst = null;

    public $estado = '
        <span style="background-color: #f3f4f6; color: #6b7280; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
            -
        </span>';

    public $cfdiArchivo = null;

    public function mount($id)
    {
        if (Auth::user() && !Auth::user()->hasRole('Admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        $this->cfdiArchivo = CfdiArchivo::find($id);
        if($this->cfdiArchivo->status_upload == CfdiArchivo::ESTATUS_SUBIDO){
            $this->subido = true;
            $this->estado = '
            <span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Subido
            </span>
            ';
        }
        if($this->cfdiArchivo->status_upload == CfdiArchivo::ESTATUS_SELLADO){
            $this->sellado = true;
            $this->estado = '
            <span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Sellado
            </span>
            ';
        }
        if($this->cfdiArchivo->status_upload == CfdiArchivo::ESTATUS_TIMBRADO){
            $this->timbrado = true;
            $this->estado = '
            <span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Timbrado
            </span>
            ';
        }
        if($this->cfdiArchivo->status_upload == CfdiArchivo::ESTATUS_DEPOSITADO){
            $this->depositado = true;
            $this->estado = '
            <span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Depositado
            </span>
            ';
        }
        Log::info("LOADED CFDI ARCHIVO: " . json_encode($this->cfdiArchivo));
    }


    public function subirXml()
    {
        // Aquí va la lógica para subir el XML (puedes reutilizar tu lógica actual de submit)
        $this->validate([
            'rfc' => 'required',
            'xml_file' => 'required',
        ]);



        $emisor = \App\Models\Emisor::where('rfc', $this->rfc)->first();

        if (!$emisor) {
            Notification::make()
                ->title('Emisor no encontrado')
                ->danger()
                ->body('El emisor seleccionado no existe.')
                ->send();
            return;
        }

        $file = is_array($this->xml_file) ? reset($this->xml_file) : $this->xml_file;


        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $pathXml = $emisor->rfc . DIRECTORY_SEPARATOR . $emisor->rfc . '_' . time() . '.xml';
            $file->storeAs('cfdi', $pathXml, 'local');
           $this->pathXml = Storage::disk('local')->path('cfdi/' . $pathXml);
        } else {
            // Manejar el caso de error
            Notification::make()
                ->title('Archivo no válido')
                ->danger()
                ->body('No se pudo procesar el archivo XML.')
                ->send();

            return;
        }

        // Emitir evento para Alpine.js
        $this->subido = true;

       $this->estado = '
            <span style="background-color: #d1fae5; color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Subido
            </span>
        ';

        // guardar el registro de subir xml a cfdiArchivo
        $registro = CfdiArchivo::create([
            'user_id' => Auth::id(),
            'nombre_archivo' => "",
            'ruta' => $this->pathXml,
            'uuid' => "",
            'sello' => "",
            'rfc_emisor' => $emisor->rfc,
            'rfc_receptor' => "",
            'total' => "",
            'fecha' => "",
            'tipo_comprobante' => "",
            'status_upload' => CfdiArchivo::ESTATUS_SUBIDO
        ]);

        $this->cfdiArchivo = $registro;

        Notification::make()
            ->title('Archivo subido')
            ->success()
            ->body('El archivo XML se subió correctamente.')
            ->send();
    }

    public function sellarXml()
    {

        try {
            $cfdiArchivo = CfdiArchivo::find($this->cfdiArchivo->id);

              $xmlPath = $cfdiArchivo->ruta;

            $emisor = \App\Models\Emisor::where('rfc', $cfdiArchivo->rfc_emisor)->first();

            if(file_exists(Storage::disk('local')->path($xmlPath)) === false) {
                Notification::make()
                    ->title('Archivo no encontrado')
                    ->danger()
                    ->body('El archivo XML no existe en la ruta especificada.')
                    ->send();
                return;
            }

             $contentXml = Storage::disk('local')->path($xmlPath);

            $comprobante = \CfdiUtils\Cfdi::newFromString(file_get_contents($contentXml))->getQuickReader();

              $segundos = Carbon::parse($comprobante['Fecha'])->format('s');

           if ($segundos === '00') {
                // Generar un segundo aleatorio entre 1 y 59 (diferente de 00)
                $segundoRandom = str_pad(strval(random_int(1, 59)), 2, '0', STR_PAD_LEFT);
                $fechaOriginal = $comprobante['Fecha'];
                // Reemplazar los segundos en la fecha original por el valor aleatorio
                $nuevaFecha = preg_replace('/:\d{2}$/', ':' . $segundoRandom, $fechaOriginal);

                $xmlPathAbs = Storage::disk('local')->path($cfdiArchivo->ruta);
                $xmlContent = file_get_contents($xmlPathAbs);
                // Reemplaza solo la primera ocurrencia de la fecha original
                $xmlContent = preg_replace('/Fecha="' . preg_quote($fechaOriginal, '/') . '"/', 'Fecha="' . $nuevaFecha . '"', $xmlContent, 1);
                file_put_contents($xmlPathAbs, $xmlContent);

                $contentXml = $xmlPathAbs;
            }
            // Continúa con el proceso normalmente
            $xml = file_get_contents($contentXml);

            $processXml = TimbradoService::sellarCfdi($xml, $emisor);

            Log::info('XML sellado correctamente: ' . json_encode($processXml));


            $this->sello = $processXml['sello'];
            $this->rfcReceptor = $processXml['rfc_receptor'] ?? '';
            $this->sellado = true;
            $this->estado = '
            <span style="background-color:rgb(250, 209, 209); color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Sellado
            </span>
            ';
            $this->xmlPath = $processXml['ruta'];

            $cfdiArchivo->sello = $processXml['sello'];
            $cfdiArchivo->rfc_receptor = $processXml['rfc_receptor'] ?? '';
            $cfdiArchivo->status_upload = CfdiArchivo::ESTATUS_SELLADO;
            $cfdiArchivo->ruta = $processXml['ruta'];
            $cfdiArchivo->total = $processXml['total'];
            $cfdiArchivo->fecha = $processXml['fecha'];
            $cfdiArchivo->uuid = $processXml['uuid'];
            $cfdiArchivo->estatus  = 'validado';
            $cfdiArchivo->save();

            $this->cfdiArchivo = $cfdiArchivo;

            Notification::make()
                ->title('XML Sellado')
                ->success()
                ->body('El XML ha sido sellado exitosamente.')
                ->send();

        } catch (\Exception $e) {
            Log::error('Error al procesar el XML: ' . $e->getMessage());
            Log::error($e->getFile() . ':' . $e->getLine());
            Notification::make()
                ->title('Error al sellar el XML')
                ->danger()
                ->body('Ocurrió un error al procesar el archivo XML: ' . $e->getMessage())
                ->send();
            return;
        }

    }

    public function timbrarXml()
    {
        $cfdiArchivo = CfdiArchivo::find($this->cfdiArchivo->id);

        $sello = $cfdiArchivo->sello;
        $xmlPath = $cfdiArchivo->ruta;

        $emisor = \App\Models\Emisor::where('rfc', $cfdiArchivo->rfc_emisor)->first();

        if (!$emisor) {
            Notification::make()
                ->title('Emisor no encontrado')
                ->danger()
                ->body('El emisor seleccionado no existe.')
                ->send();
            return;
        }

        $xmlFile = $xmlPath;
        $xmlPath = Storage::disk('public')->get($xmlPath);


        $comprobante = \CfdiUtils\Cfdi::newFromString($xmlPath)->getQuickReader();

        //dd($comprobante);
/*
        // vaalidr si la fecha del $comprobante es un dia lunes, de ser asi devuelve un notification de error
        $fechaEmision = Carbon::parse($comprobante['Fecha'])->format('l');
        if ($fechaEmision !== 'Tuesday') {
            Notification::make()
                ->title('Error al timbrar el XML')
                ->danger()
                ->body('La fecha de emisión debe ser un martes.')
                ->send();
            return;
        }

        // valida que la fecha del comprobante no sea segundo :00 si es asi devuelve un notification de error
        $fechaEmision = Carbon::parse($comprobante['Fecha'])->format('s');
        if ($fechaEmision === '00') {
            Notification::make()
                ->title('Error al timbrar el XML')
                ->danger()
                ->body('La fecha de emisión no puede tener segundos en 00.')
                ->send();
            return;
        }
            */

        $fechaCfdi = Carbon::parse($comprobante['Fecha'])->utc();

        $certificado = Storage::disk('local')->path($emisor->file_certificate);

        list($inicioVigencia, $finVigencia) = TimbradoService::obtenerFechasVigenciaCertificado( $certificado);

        Log::info('Fecha CFDI: ' . $fechaCfdi);
        Log::info('Inicio Vigencia: ' . $inicioVigencia);
        Log::info('Fin Vigencia: ' . $finVigencia);


        if ($fechaCfdi->lt($inicioVigencia) || $fechaCfdi->gt($finVigencia)) {
              Notification::make()
                ->title('Error al timbrar el XML')
                ->danger()
                ->body('La fecha de emisión no está dentro de la vigencia del CSD del Emisor')
                ->send();
            return;
        }


        $processXml = TimbradoService::timbraXML(
            $xmlFile,
            $emisor,
            $sello,
            $cfdiArchivo
        );
        if (!$processXml) {
            Notification::make()
                ->title('Error al timbrar el XML')
                ->danger()
                ->body('Ocurrió un error al timbrar el archivo XML.')
                ->send();
            return;
        }

        $this->registerFirst = $processXml['id'];
        $this->timbrado = true;
        $this->xmlPath = $processXml['ruta'];

        $this->estado = '
            <span style="background-color:rgb(249, 250, 209); color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Timbrado
            </span>
        ';


        Notification::make()
            ->title('XML Timbrado')
            ->success()
            ->body('El XML ha sido timbrado exitosamente.')
            ->send();


        TimbradoService::createCfdiToPDF($cfdiArchivo);

        $this->pdf_file = Storage::disk('public')->url($cfdiArchivo->pdf_path);


    }

    public function publicacion()
    {

        $cfdiArchivo = CfdiArchivo::find($this->cfdiArchivo->id);

        if( !$cfdiArchivo) {
            Notification::make()
                ->title('Registro no encontrado')
                ->danger()
                ->body('No hay un registro para timbrar.')
                ->send();
            return;
        }


        $data = TimbradoService::envioxml($cfdiArchivo);


        if($data['status'] !== 'success') {
            Notification::make()
                ->title('Error al timbrar')
                ->danger()
                ->body('Ocurrió un error al timbrar el XML: ' . $data['message'])
                ->send();
            return;
        }

        if(!$cfdiArchivo->respuesta_sat) {
            Notification::make()
                ->title('Error al timbrar')
                ->danger()
                ->body('No se pudo obtener la respuesta del SAT.')
                ->send();
            return;
        }

        // generate pdf from xml  from registro
        $pdf = TimbradoService::generatePdfFromXml($cfdiArchivo->respuesta_sat);

        $this->depositado = true;
        $this->estado = '
            <span style="background-color:rgb(209, 212, 250) 209, 250); color: #065f46; font-size: 0.75rem; font-weight: 500; border-radius: 0.25rem; padding: 0.25rem 0.5rem; display: inline-flex; align-items: center;">
                <svg class="w-4 h-4 mr-1" style="color: #10b981;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Depositado
            </span>
        ';
        // Aquí va la lógica para timbrar el XML
        Notification::make()
            ->title('Timbrado')
            ->success()
            ->body('El XML ha sido subido correctamente y enviado.')
            ->send();

        return response()->download($pdf, 'cfdi_' . $cfdiArchivo->uuid . '.pdf');
    }

    /**
     * function for test generate pdf from xml
     * @return void
     */
    public function convertXmlToPdf()
    {
        $ID = 62; // Cambia esto al ID del registro que deseas convertir a PDF

        $registro = CfdiArchivo::find($ID);

        $pdf = TimbradoService::generatePdfFromXml($registro->respuesta_sat);

        Log::info('PDF generado correctamente: ' . $pdf);

        Notification::make()
            ->title('PDF Generado')
            ->success()
            ->body('El PDF ha sido generado exitosamente.')
            ->send();

        return response()->download($pdf, 'cfdi_' . $registro->uuid . '.pdf');
    }
}
