<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CfdiResource\Pages;
use App\Filament\Resources\CfdiResource\RelationManagers;

use App\Models\Models\Cfdi;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CfdiResource extends Resource
{
    protected static ?string $model = Cfdi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emisor.rfc')
                    ->label('RFC Emisor'),
                Tables\Columns\TextColumn::make('receptor.rfc')
                    ->label('RFC Receptor'),
                Tables\Columns\TextColumn::make('serie')
                    ->label('Serie'),
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio'),
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime()
                    ->label('Fecha'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->numeric()
                    ->label('Subtotal'),
                Tables\Columns\TextColumn::make('descuento')
                    ->numeric()
                    ->label('Descuento'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->label('Total'),
                Tables\Columns\TextColumn::make('forma_pago')
                    ->label('Forma de Pago'),
                Tables\Columns\TextColumn::make('metodo_pago')
                    ->label('Método de Pago'),
                Tables\Columns\TextColumn::make('moneda')
                    ->label('Moneda'),
                Tables\Columns\TextColumn::make('tipo_de_comprobante')
                    ->label('Tipo de Comprobante'),
                Tables\Columns\TextColumn::make('exportacion')
                    ->label('Exportación'),
                Tables\Columns\TextColumn::make('lugar_expedicion')
                    ->label('Lugar de Expedición'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCfdis::route('/'),
            //'create' => Pages\CreateCfdi::route('/create'),
            //'create' => \App\Filament\Pages\Cfdi::class,
           // 'edit' => Pages\EditCfdi::route('/{record}/edit'),
        ];
    }
}
