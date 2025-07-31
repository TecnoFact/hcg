<?php

namespace App\Filament\Resources\CfdiResource\Pages;

use App\Filament\Resources\CfdiResource;
use App\Imports\CfdiImport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Actions\Action;


class ListCfdis extends ListRecords
{
    protected static string $resource = CfdiResource::class;

    protected function getHeaderActions(): array
    {
        return [
             \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Importar Excel')
                ->color("primary")
                ->use(CfdiImport::class)
                ->sampleFileExcel(
                    url: url('excel/template_cfdi_import.xlsx'),
                    sampleButtonLabel: 'Descargar ejemplo',
                    customiseActionUsing: fn(Action $action) => $action->color('secondary')
                        ->icon('heroicon-m-clipboard')
                        ->requiresConfirmation(),
                ),
            Actions\CreateAction::make(),
        ];
    }
}
