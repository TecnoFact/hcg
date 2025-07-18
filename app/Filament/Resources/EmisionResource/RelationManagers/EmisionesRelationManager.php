<?php

namespace App\Filament\Resources\EmisionResource\RelationManagers;

use DB;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmisionesRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';

    public function form(Form $form): Form
    {
        return $form
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                Tables\Columns\TextColumn::make('clave_prod_serv')
                    ->label('Clave Prod/Serv'),
                Tables\Columns\TextColumn::make('numero_identificacion')
                    ->label('Número Identificación'),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad'),
                Tables\Columns\TextColumn::make('clave_unidad')
                    ->label('Clave Unidad'),
                Tables\Columns\TextColumn::make('unidad')
                    ->label('Unidad'),
                Tables\Columns\TextColumn::make('valor_unitario')
                    ->label('Valor Unitario')
                    ->numeric(),
                Tables\Columns\TextColumn::make('tipo_impuesto')
                    ->label('Tipo de Impuesto'),
                Tables\Columns\TextColumn::make('importe')
                    ->label('Importe')
                    ->numeric(),
                Tables\Columns\TextColumn::make('descripcion'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
