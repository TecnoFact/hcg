<?php

namespace App\Filament\Resources;

use App\Models\Emisor;
use Filament\Forms;
use Filament\Tables;

use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CfdiArchivo;


use App\Models\Models\Cfdi;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\CfdiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CfdiResource\RelationManagers;

class CfdiResource extends Resource
{
    protected static ?string $model = Cfdi::class;

    protected static ?string $label = 'Factura';

    protected static ?string $pluralLabel = 'Facturas';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos Generales del Comprobante')
                    ->schema([
                        Forms\Components\TextInput::make('serie')
                            ->label('Serie')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('folio')
                            ->label('Folio')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('fecha')
                            ->label('Fecha y Hora'),
                        Forms\Components\Select::make('metodo_pago')
                            ->label('Método de Pago')
                            ->options([
                                'PUE' => 'PUE - Pago en una sola exhibición',
                                'PPD' => 'PPD - Pago en parcialidades o diferido',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'PPD') {
                                    $set('forma_pago', '99');
                                }
                            })
                            ->default('PUE'),

                        Forms\Components\Select::make('forma_pago')
                            ->label('Forma de Pago')
                            ->options([
                                    '01' => '01 - Efectivo',
                                    '02' => '02 - Cheque nominativo',
                                    '03' => '03 - Transferencia electrónica',
                                    '04' => '04 - Tarjeta de crédito',
                                    '99' => '99 - Por definir',
                                ])
                            ->disabled(fn($get) => $get('metodo_pago') === 'PPD')
                            ->default('03'),

                        Forms\Components\Select::make('tipo_de_comprobante')
                            ->label('Tipo de Comprobante')
                            ->options([
                                    'I' => 'I - Ingreso',
                                    'E' => 'E - Egreso',
                                    'T' => 'T - Traslado',
                                ])
                            ->default('I'),
                        Forms\Components\TextInput::make('lugar_expedicion')
                            ->label('Código Postal')
                            ->maxLength(5),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options([
                                    'MXN' => 'MXN - Peso Mexicano',
                                    'USD' => 'USD - Dólar Americano',
                                ])
                            ->default('MXN'),
                    ])->columns(3),

                Forms\Components\Section::make('Datos del Emisor')
                    ->schema([
                            Forms\Components\Select::make('emisor_id')
                                ->label('Emisor')
                                ->searchable()
                                ->reactive()
                                ->options(DB::table('emisores')->pluck('name', 'id')->toArray())
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $emisor = Emisor::find($state);
                                        $set('emisor_rfc', $emisor->rfc);
                                        $set('emisor_nombre', $emisor->name);
                                        $set('emisor_regimen_fiscal', $emisor->reason_social);
                                        $set('lugar_expedicion', $emisor->postal_code);
                                    } else {
                                        $set('emisor_rfc', null);
                                        $set('emisor_nombre', null);
                                        $set('emisor_regimen_fiscal', null);
                                    }
                                }),
                            Forms\Components\TextInput::make('emisor_rfc')
                                ->label('RFC')
                                ->maxLength(13),
                            Forms\Components\TextInput::make('emisor_nombre')
                                ->label('Nombre o Razón Social'),
                            Forms\Components\Select::make('emisor_regimen_fiscal')
                                ->label('Régimen Fiscal')
                                ->options([
                                        '601' => '601 - General de Ley Personas Morales',
                                        '612' => '612 - Personas Físicas con Actividades Empresariales',
                                    ])
                                ->default('601'),
                        ])->columns(3),

                Forms\Components\Section::make('Datos del Receptor')
                    ->schema([
                        Forms\Components\Select::make('receptor_id')
                                ->label('Receptor')
                                ->searchable()
                                ->reactive()
                                ->options(DB::table('cfdi_receptores')->pluck('nombre', 'id')->toArray())
                                 ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $emisor = CfdiReceptor::find($state);
                                        $set('receptor_rfc', $emisor->rfc);
                                        $set('receptor_nombre', $emisor->nombre);
                                        $set('receptor_domicilio', $emisor->domicilio_fiscal);
                                        $set('receptor_regimen_fiscal', $emisor->regimen_fiscal);
                                        $set('receptor_uso_cfdi', $emisor->uso_cfdi);
                                    }
                                }),
                            Forms\Components\TextInput::make('receptor_rfc')
                                ->label('RFC')
                                ->maxLength(13),
                            Forms\Components\TextInput::make('receptor_nombre')
                                ->label('Nombre o Razón Social'),
                            Forms\Components\TextInput::make('receptor_domicilio')
                                ->label('Código Postal')
                                ->maxLength(5),
                            Forms\Components\Select::make('receptor_regimen_fiscal')
                                ->label('Régimen Fiscal')
                                ->options([
                                        '601' => '601 - General de Ley Personas Morales',
                                        '612' => '612 - Personas Físicas con Actividades Empresariales',
                                    ])
                                ->default('601'),
                            Forms\Components\Select::make('receptor_uso_cfdi')
                                ->label('Uso del CFDI')
                                ->options([
                                        'G01' => 'G01 - Adquisición de mercancías',
                                        'G03' => 'G03 - Gastos en general',
                                    ])
                                ->default('G03'),
                        ])->columns(3),

                Forms\Components\Section::make('Conceptos')
                    ->schema([
                        Forms\Components\Repeater::make('conceptos')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('no_identificacion')
                                    ->label('Número Identificación')->hidden(),
                                Forms\Components\TextInput::make('clave_prod_serv')
                                    ->label('Clave Prod/Serv')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\Select::make('clave_unidad')
                                    ->label('Clave Unidad')
                                    ->searchable()
                                    ->options(options: DB::table('catalogo_clave_unidad')->pluck('nombre', 'clave')),
                                Forms\Components\TextInput::make('unidad')
                                    ->label('Unidad')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('valor_unitario')
                                    ->label('Valor Unitario')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('tipo_impuesto')
                                    ->label('Tipo de Impuesto')
                                    ->options([
                                        'IVA' => 'IVA',
                                        'IEPS' => 'IEPS',
                                        'ISR' => 'ISR',
                                        'EXENTO' => 'EXENTO',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('importe')
                                    ->label('Importe')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(3)
                            ->createItemButtonLabel('Agregar Concepto'),
                    ]),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emisor.rfc')
                    ->label('RFC Emisor')->searchable(),
                Tables\Columns\TextColumn::make('receptor.rfc')
                    ->label('RFC Receptor')->searchable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime()
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->label('Total'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                Action::make('descargar_xml')
                ->label('Descargar XML')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn($record) => route('cfdis.descargar-xml', $record))
                ->color('success')
                ->openUrlInNewTab(false)
                 ->visible(fn($record) => $record->path_xml !== null),

                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
            Action::make('ir_a_web')
                ->label('Importar XML')
                ->icon('heroicon-o-link')
                ->url(route('filament.admin.pages.cfdi')),
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCfdis::route('/'),
            'create' => Pages\CreateCfdi::route('/create'),
            'edit' => Pages\EditCfdi::route('/{record}/edit'),
        ];
    }
}
