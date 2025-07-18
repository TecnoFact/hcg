<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Emision;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\EmisionResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmisionResource\RelationManagers;
use App\Filament\Resources\EmisionResource\RelationManagers\EmisionesRelationManager;

class EmisionResource extends Resource
{
    protected static ?string $model = Emision::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Emisiones';

    protected static ?string $pluralModelLabel = 'Emision';





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

                        TextInput::make('sub_total')
                            ->label('Subtotal')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('iva')
                            ->label('IVA')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

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

                        Forms\Components\Select::make('tipo_comprobante')
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
                        Forms\Components\Repeater::make('detalles')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('clave_prod_serv')
                                    ->label('Clave Prod/Serv')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('numero_identificacion')
                                    ->label('Número Identificación')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\Select::make('clave_unidad')
                                    ->label('Clave Unidad')
                                    ->searchable()
                                    ->options(DB::table('catalogo_clave_unidad')->pluck('nombre', 'clave')),
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
                    Tables\Columns\TextColumn::make('serie')
                        ->label('Serie'),
                    Tables\Columns\TextColumn::make('folio')
                        ->label('Folio'),
                    Tables\Columns\TextColumn::make('fecha')
                        ->label('Fecha'),
                    Tables\Columns\TextColumn::make('forma_pago')
                        ->label('Forma de Pago'),
                    Tables\Columns\TextColumn::make('metodo_pago')
                        ->label('Método de Pago'),
                    Tables\Columns\TextColumn::make('tipo_comprobante')
                        ->label('Tipo de Comprobante')->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('lugar_expedicion')
                        ->label('Lugar de Expedición')->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('moneda')
                        ->label('Moneda')->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('emisor_rfc')
                        ->label('RFC del Emisor'),
                    Tables\Columns\TextColumn::make('emisor_nombre')
                        ->label('Nombre del Emisor'),
                    Tables\Columns\TextColumn::make('receptor_rfc')
                        ->label('RFC del Receptor'),
                    Tables\Columns\TextColumn::make('receptor_nombre')
                        ->label('Nombre del Receptor'),
                    Tables\Columns\TextColumn::make('sub_total')->numeric()
                        ->label('Subtotal')->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('iva')->numeric()
                        ->label('IVA')->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('total')->numeric()
                        ->label('Total'),
                    Tables\Columns\TextColumn::make('estado')
                        ->label('Estado'),
                ])
            ->filters([
                    //
                ])
            ->actions([
                    Tables\Actions\EditAction::make(),

                    Action::make('descargar_xml')
                        ->label('Descargar XML')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn($record) => route('emision.descargar-xml', $record))
                        ->color('success')
                        ->openUrlInNewTab(false)
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
           // EmisionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmisions::route('/'),
            'create' => Pages\CreateEmision::route('/create'),
            'edit' => Pages\EditEmision::route('/{record}/edit'),
        ];
    }
}
