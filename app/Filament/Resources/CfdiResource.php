<?php

namespace App\Filament\Resources;

use App\Models\Emisor;
use App\Models\ObjImp;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Models\Cfdi;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\CfdiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CfdiResource\RelationManagers;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;
use Pelmered\FilamentMoneyField\Infolists\Components\MoneyEntry;
use Tuxones\JsMoneyField\Forms\Components\JSMoneyInput;
use Tuxones\JsMoneyField\Tables\Columns\JSMoneyColumn;
use function Filament\Support\format_money;
use function Filament\Support\format_number;

class CfdiResource extends Resource
{
    protected static ?string $model = Cfdi::class;

    protected static ?string $label = 'Factura';

    protected static ?string $pluralLabel = 'Facturas';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

     protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
          $calcularImporte = function (callable $set, callable $get) {
                $cantidad = $get('cantidad');
                $valorUnitarioRaw = $get('valor_unitario');

                // Si falta alguno, no hacemos nada
                if ($cantidad === null || $valorUnitarioRaw === null || $valorUnitarioRaw === '') {
                    return;
                }

                $cantidad = (float) $cantidad;
                $valorUnitario = (float) str_replace([',', '$', ' '], '', $valorUnitarioRaw);

                $tipoImpuesto = $get('tipo_impuesto');

                $importe = $cantidad * $valorUnitario;

                if ($tipoImpuesto) {
                    $tax = \App\Models\Tax::find($tipoImpuesto);
                    if ($tax) {
                        $importe += $importe * ($tax->rate / 100);
                    }
                }

                $set('importe', number_format($importe, 2, '.', ','));
            };

        return $form
            ->schema([
                Forms\Components\Section::make('Datos Generales del Comprobante')
                    ->schema([
                        TextInput::make('total')->hidden(),
                        TextInput::make('subtotal')->hidden(),
                        TextInput::make('status')->hidden(),
                        TextInput::make('status_upload')->hidden(),
                        Forms\Components\TextInput::make('serie')
                            ->label('Serie')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('folio')
                            ->label('Folio')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('fecha')
                            ->required()
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
                            ->required()
                            ->reactive()
                            ->options(DB::table('emisores')->pluck('name', 'id')->toArray())
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $emisor = Emisor::find($state);
                                    $set('emisor_rfc', $emisor->rfc);
                                    $set('emisor_nombre', $emisor->name);
                                    $set('emisor_regimen_fiscal', $emisor->tax_regimen_id);
                                    $set('lugar_expedicion', $emisor->postal_code);
                                } else {
                                    $set('emisor_rfc', null);
                                    $set('emisor_nombre', null);
                                    $set('emisor_regimen_fiscal', null);
                                }
                            }),
                        Forms\Components\TextInput::make('emisor_rfc')
                            ->label('RFC')
                            ->required()
                            ->maxLength(13),
                        Forms\Components\TextInput::make('emisor_nombre')
                            ->label('Nombre o Razón Social')
                            ->required(),
                        Forms\Components\Select::make('emisor_regimen_fiscal')
                            ->label('Régimen Fiscal')
                            ->getSearchResultsUsing(
                                fn(string $search) =>
                                \App\Models\RegimeFiscal::where('descripcion', 'like', "%{$search}%")
                                    ->limit(20)
                                    ->pluck('descripcion', 'clave')
                            )
                            ->options(
                                fn() => \App\Models\RegimeFiscal::pluck('descripcion', 'clave')->toArray()
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        Forms\Components\TextInput::make('lugar_expedicion')
                            ->label('Código Postal')
                            ->required()
                            ->maxLength(5),
                    ])->columns(3),

                Forms\Components\Section::make('Datos del Receptor')
                    ->schema([
                        Forms\Components\TextInput::make('receptor_rfc')
                            ->label('RFC')
                            ->required()
                            ->maxLength(13),
                        Forms\Components\TextInput::make('receptor_nombre')
                            ->label('Nombre o Razón Social')
                            ->required(),
                        Forms\Components\TextInput::make('receptor_domicilio')
                            ->label('Código Postal')
                            ->required()
                            ->maxLength(5),
                        Forms\Components\Select::make('receptor_regimen_fiscal')
                            ->label('Régimen Fiscal')
                            ->required()
                            ->options([
                                '601' => '601 - General de Ley Personas Morales',
                                '612' => '612 - Personas Físicas con Actividades Empresariales',
                            ])
                            ->default('601'),
                        Forms\Components\Select::make('receptor_uso_cfdi')
                            ->label('Uso del CFDI')
                            ->required()
                            ->options([
                                'G01' => 'G01 - Adquisición de mercancías',
                                'G03' => 'G03 - Gastos en general',
                            ])
                            ->default('G03'),
                    ])->columns(3),

                Forms\Components\Section::make('Conceptos')
                    ->schema([

                        Forms\Components\Repeater::make('conceptos')
                            ->schema([
                                Forms\Components\TextInput::make('no_identificacion')
                                    ->label('Número Identificación')->hidden(),
                                Forms\Components\TextInput::make('clave_prod_serv')
                                    ->label('Clave Prod/Serv')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\Select::make('tipo_impuesto')
                                    ->label('Tipo de Impuesto')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated($calcularImporte)
                                    ->options(Tax::pluck('name', 'id')),
                                Select::make('obj_imp_id')
                                    ->label('Objeto del Impuesto')
                                    ->options(ObjImp::pluck('descripcion', 'id')),

                                Forms\Components\Select::make('clave_unidad')
                                    ->label('Clave Unidad')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->options(options: DB::table('unit_measures')->pluck('name', 'code')),
                                Forms\Components\TextInput::make('unidad')
                                    ->label('Unidad')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->required()
                                    ->maxLength(255),
                               TextInput::make('valor_unitario')
                                    ->label('Valor Unitario')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                   ->live(debounce: 800)
                                    ->required()->afterStateUpdated($calcularImporte),
                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                   ->live(debounce: 800)
                                    ->required()->afterStateUpdated($calcularImporte),
                                Forms\Components\TextInput::make('importe')
                                            ->label('Importe')
                                            ->required()
                                            ->prefix('$')
                                            ->dehydrated(true)

                               ])
                            ->columns(3)
                            ->createItemButtonLabel('Agregar Concepto')

                            ->afterStateUpdated(function ($state, callable $set) {
                                $total = 0;
                                if (is_array($state)) {
                                    foreach ($state as $concepto) {
                                        $importe = isset($concepto['importe']) ? (float) $concepto['importe'] : 0;
                                        $total += $importe;
                                    }
                                }
                                $set('../total', $total);
                                $set('../subtotal', $total);
                            }),
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
                    TextColumn::make('subtotal')
                    ->numeric()

                    ->label('SubTotal'),
                    TextColumn::make('impuesto')
                    ->numeric()

                    ->label('Impuesto'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()

                    ->label('Total'),
            ])
            ->filters([
                //
            ])
            ->actions([
                  ActionGroup::make([
                //Tables\Actions\EditAction::make(),
                Action::make('descargar_xml')
                    ->label('Descargar XML')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn($record) => route('cfdis.descargar-xml', $record))
                    ->color('success')
                    ->openUrlInNewTab(false)
                    ->visible(fn($record) => $record->path_xml !== null),

                Action::make('descargar_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn($record) => route('cfdis.descargar-pdf', $record))
                    ->color('success')
                    ->openUrlInNewTab(false)
                    ->visible(fn($record) => $record->pdf_path !== null),

                Tables\Actions\EditAction::make(),
                  ])
            ])
            ->headerActions([
                Action::make('ir_a_web')
                    ->label('Importar XML')
                    ->icon('heroicon-o-link')
                    ->url(route('filament.admin.pages.cfdi')),

            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->hasRole('User')) {
                    $query->where('user_id', auth()->id());
                }
            })
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
