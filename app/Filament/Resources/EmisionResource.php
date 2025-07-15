<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmisionResource\Pages;
use App\Filament\Resources\EmisionResource\RelationManagers;
use App\Filament\Resources\EmisionResource\RelationManagers\EmisionesRelationManager;
use App\Models\Emision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmisionResource extends Resource
{
    protected static ?string $model = Emision::class;

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
                    Forms\Components\Select::make('forma_pago')
                        ->label('Forma de Pago')
                        ->options([
                            '01' => '01 - Efectivo',
                            '02' => '02 - Cheque nominativo',
                            '03' => '03 - Transferencia electrónica',
                            '04' => '04 - Tarjeta de crédito',
                        ])
                        ->default('03'),
                    Forms\Components\Select::make('metodo_pago')
                        ->label('Método de Pago')
                        ->options([
                            'PUE' => 'PUE - Pago en una sola exhibición',
                            'PPD' => 'PPD - Pago en parcialidades o diferido',
                        ])
                        ->default('PUE'),
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serie'),
                Tables\Columns\TextColumn::make('folio'),
                Tables\Columns\TextColumn::make('fecha'),
                Tables\Columns\TextColumn::make('forma_pago'),
                Tables\Columns\TextColumn::make('metodo_pago'),
                Tables\Columns\TextColumn::make('tipo_comprobante'),
                Tables\Columns\TextColumn::make('lugar_expedicion'),
                Tables\Columns\TextColumn::make('moneda'),
                Tables\Columns\TextColumn::make('emisor_rfc'),
                Tables\Columns\TextColumn::make('emisor_nombre'),
                Tables\Columns\TextColumn::make('receptor_rfc'),
                Tables\Columns\TextColumn::make('receptor_nombre'),
                Tables\Columns\TextColumn::make('sub_total')->numeric(),
                Tables\Columns\TextColumn::make('iva')->numeric(),
                Tables\Columns\TextColumn::make('total')->numeric(),
                Tables\Columns\TextColumn::make('estado')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            EmisionesRelationManager::class,
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
