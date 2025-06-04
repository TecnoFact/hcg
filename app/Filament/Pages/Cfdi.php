<?php

namespace App\Filament\Pages;

use App\Models\CfdiArchivo;
use App\Services\TimbradoService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\Log;
use Storage;


class Cfdi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.cfdi';

    protected static ?string $title =  'Subir CFDI';

    public $rfc;
    public $xml_file;

    public $subido = false;

    public $xmlPath = '';

    public $registerFirst = null;

     public function mount()
    {
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

            return;
        }


        try {
            $xmlPath = Storage::disk('local')->path('cfdi/' . $pathXml);
            $processXml = TimbradoService::timbraSellarXml($xmlPath, $emisor);

            Log::info('XML procesado correctamente: ' . $processXml);

            $this->xmlPath = $processXml->ruta;
            $this->registerFirst = $processXml->id;


        } catch (\Exception $e) {
            Log::error('Error al procesar el XML: ' . $e->getMessage());
            Notification::make()
                ->title('Error al subir el CFDI')
                ->danger()
                ->body('Ocurrió un error al procesar el archivo XML: ' . $e->getMessage())
                ->send();
            return;
        }

        // Emitir evento para Alpine.js
        $this->subido = true;

        Notification::make()
            ->title('Archivo subido')
            ->success()
            ->body('El archivo XML se subió correctamente.')
            ->send();
    }

    public function timbrarXml()
    {
        if( !$this->registerFirst) {
            Notification::make()
                ->title('Registro no encontrado')
                ->danger()
                ->body('No hay un registro para timbrar.')
                ->send();
            return;
        }


        $registro = CfdiArchivo::find($this->registerFirst);

        $data = TimbradoService::envioxml($registro);


        if($data['status'] !== 'success') {
            Notification::make()
                ->title('Error al timbrar')
                ->danger()
                ->body('Ocurrió un error al timbrar el XML: ' . $data['message'])
                ->send();
            return;
        }

        if(!$registro->respuesta_sat) {
            Notification::make()
                ->title('Error al timbrar')
                ->danger()
                ->body('No se pudo obtener la respuesta del SAT.')
                ->send();
            return;
        }

        // generate pdf from xml  from registro
        $pdf = TimbradoService::generatePdfFromXml($registro->respuesta_sat);

        // Aquí va la lógica para timbrar el XML
        Notification::make()
            ->title('Timbrado')
            ->success()
            ->body('El XML ha sido timbrado (simulado).')
            ->send();

        return response()->download($pdf, 'cfdi_' . $registro->uuid . '.pdf');
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
