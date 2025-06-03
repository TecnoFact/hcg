<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms;


class Cfdi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.cfdi';

    protected static ?string $title =  'Subir CFDI';

    public $emisor_id;
    public $xml_file;

     public function mount()
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Subir CFDI')
                ->schema([
                    Forms\Components\Select::make('emisor_id')
                        ->label('Emisor')
                        ->options(\App\Models\Emisor::all()->pluck('name', 'id'))
                        ->required(),
                    Forms\Components\FileUpload::make('xml_file')
                        ->label('Archivo XML')
                        ->acceptedFileTypes(['application/xml', 'text/xml'])
                        ->required(),
                ]),
        ];
    }

    public function submit()
    {
        $this->validate([
            'emisor_id' => 'required|exists:emisores,id',
            'xml_file' => 'required|file|mimes:xml|max:2048', // Max 2MB
        ]);

        $emisor = \App\Models\Emisor::find($this->emisor_id);
        $xmlPath = $this->xml_file->storeAs('cfdi', $emisor->rfc . '_' . time() . '.xml');

        // Process the XML file as needed, e.g., parsing, saving to database, etc.

        Notification::make()
            ->title('CFDI Uploaded')
            ->success()
            ->body('El CFDI ha sido subido exitosamente.')
            ->send();
        //session()->flash('success', 'CFDI uploaded successfully.');

        return redirect()->route('filament.pages.cfdi');
    }


}
