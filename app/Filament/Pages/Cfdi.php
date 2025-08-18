<?php

namespace App\Filament\Pages;

use App\Services\ComplementoXmlService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Pages\Page;
use App\Services\TimbradoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;


class Cfdi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.cfdi';

    protected static ?string $title =  'Subir CFDI';

      protected static ?int $navigationSort = 2;

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

     public function mount()
    {
        // Verificar si el usuario tiene el rol 'Customer'
        if (Auth::user() && !Auth::user()->hasRole('Admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Subir CFDI')
                ->schema([
                    Forms\Components\Select::make('rfc')
                        ->label('Emisor')
                        ->searchable()
                        ->options(\App\Models\Emisor::all()->pluck('rfc', 'rfc'))
                        ->placeholder('Seleccione un emisor')
                        ->getOptionLabelUsing(fn ($value) =>
                                            \App\Models\Emisor::find($value)?->rfc
                                        )
                        ->reactive()
                        ->required(),
                    Forms\Components\FileUpload::make('xml_file')
                        ->label('Archivo XML')
                        ->acceptedFileTypes(['application/xml', 'text/xml'])
                        ->disk('local')
                        ->directory(fn($get) => 'xml' . DIRECTORY_SEPARATOR . $get('rfc'))
                        ->required(),
                ]),
        ];
    }

    public function submit()
    {

        $this->validate([
            'rfc' => 'required',
            'xml_file' => 'required', // Max 2MB
        ]);


        $emisor = \App\Models\Emisor::where('rfc', $this->rfc)->first();

        if (!$emisor) {
            Notification::make()
                ->title('Emisor no encontrado')
                ->danger()
                ->body('El emisor seleccionado no existe.')
                ->send();

            session()->flash('error', 'El emisor seleccionado no existe.');
            return redirect()->back();
        }

        // Obtener el primer archivo subido
        $file = is_array($this->xml_file) ? reset($this->xml_file) : $this->xml_file;
        $pathXml = '';

        // Validar que es un TemporaryUploadedFile
        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $pathXml = $emisor->rfc . DIRECTORY_SEPARATOR . $emisor->rfc . '_' . time() . '.xml';
            $file->storeAs('cfdi', $pathXml, 'local');
        } else {
            // Manejar el caso de error
            Notification::make()
                ->title('Archivo no válido')
                ->danger()
                ->body('No se pudo procesar el archivo XML.')
                ->send();
            return redirect()->back();
        }


        try{

            $xmlPath = Storage::disk('local')->path('cfdi/' . $pathXml);

            $processXml = TimbradoService::timbraSellarXml($xmlPath, $emisor);

        }catch (\Exception $e) {
            Log::error('Error al procesar el XML: ' . $e->getMessage());
            Notification::make()
                ->title('Error al subir el CFDI')
                ->danger()
                ->body('Ocurrió un error al procesar el archivo XML: ' . $e->getMessage())
                ->send();
            session()->flash('error', 'Ocurrió un error al procesar el archivo XML: ' . $e->getMessage());
            return redirect()->back();
        }


        // Process the XML file as needed, e.g., parsing, saving to database, etc.

        Notification::make()
            ->title('CFDI Uploaded')
            ->success()
            ->body('El CFDI ha sido subido exitosamente.')
            ->send();

        session()->flash('success', 'El CFDI ha sido subido exitosamente.');

        return redirect()->route('filament.admin.pages.cfdi');
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
        $nameFile = $emisor->rfc . '_' . time() . '.xml';


        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $pathXml = $emisor->rfc . DIRECTORY_SEPARATOR . $nameFile;
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
        $registro = \App\Models\Models\Cfdi::create([
            'user_id' => Auth::id(),
            'nombre_archivo' => $nameFile,
            'ruta' => 'cfdi/' . $pathXml,
            'uuid' => "",
            'sello' => "",
            'emisor_id' => $emisor->id,
            'receptor_id' => "",
            'fecha' => now(),
            'tipo_de_comprobante' => "",
            'lugar_expedicion' => "",
            'subtotal' => "0",
            'total' => "0",
            'path_xml' => 'cfdi/' . $pathXml,
            'estatus' => 'validado',
            'status_upload' => \App\Models\Models\Cfdi::ESTATUS_SUBIDO
        ]);

        $this->cfdiArchivo = $registro;

        // crear servicio para insertar datos del xml en base de datos
        try {
            ComplementoXmlService::insertXmlToDB($this->pathXml, $registro);
            TimbradoService::createCfdiSimpleToPDF($registro);
        } catch (\Exception $e) {
            Log::error('Error al insertar datos del XML: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            Notification::make()
                ->title('Error al insertar datos del XML')
                ->danger()
                ->body('Ocurrió un error al insertar los datos del XML: ' . $e->getMessage())
                ->send();
            return;
        }

        Notification::make()
            ->title('Archivo subido')
            ->success()
            ->body('El archivo XML se subió correctamente.')
            ->send();
    }

    public function sellarXml()
    {

        try {
            $cfdiArchivo = \App\Models\Models\Cfdi::find($this->cfdiArchivo->id);

            $xmlPath = $cfdiArchivo->ruta;

            $emisor = \App\Models\Emisor::where('rfc', $cfdiArchivo->emisor->rfc)->first();

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
            $this->rfcReceptor = $processXml['rfcReceptor'] ?? '';
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
            $cfdiArchivo->status_upload = \App\Models\Models\Cfdi::ESTATUS_SELLADO;
            $cfdiArchivo->ruta = $processXml['ruta'];
            $cfdiArchivo->total = $processXml['total'];
            $cfdiArchivo->fecha = $processXml['fecha'];
            $cfdiArchivo->uuid = '';
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
            Log::error($e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage());
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
        $cfdiArchivo = \App\Models\Models\Cfdi::find($this->cfdiArchivo->id);

        $sello = $cfdiArchivo->sello;
        $xmlPath = $cfdiArchivo->ruta;

        $emisor = \App\Models\Emisor::where('rfc', $this->rfc)->first();

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

        $cfdiArchivo = \App\Models\Models\Cfdi::find($this->cfdiArchivo->id);

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

        // generate pdf from xml acuse from registro
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

        return response()->download($pdf, 'cfdi_' . $cfdiArchivo->uuid . '.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * function for test generate pdf from xml
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function convertXmlToPdf()
    {
        $ID = 62; // Cambia esto al ID del registro que deseas convertir a PDF

        $registro = \App\Models\Models\Cfdi::find($ID);

        $pdf = TimbradoService::generatePdfFromXml($registro->respuesta_sat);

        Log::info('PDF generado correctamente: ' . $pdf);

        Notification::make()
            ->title('PDF Generado')
            ->success()
            ->body('El PDF ha sido generado exitosamente.')
            ->send();

        return response()->download($pdf, 'cfdi_' . $registro->uuid . '.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

}
