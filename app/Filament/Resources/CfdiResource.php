<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CfdiResource\Pages;
use App\Filament\Resources\CfdiResource\RelationManagers;

use App\Models\CfdiArchivo;
use App\Models\Models\Cfdi;
use Filament\Tables\Actions\Action;


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
    protected static ?string $model = CfdiArchivo::class;

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
                Tables\Columns\TextColumn::make('rfc_emisor')
                    ->label('RFC Emisor'),
                Tables\Columns\TextColumn::make('rfc_receptor')
                    ->label('RFC Receptor'),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID'),
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime()
                    ->label('Fecha'),

                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->label('Total'),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estatus'),
                Tables\Columns\TextColumn::make('status_upload')
                    ->label('Status Upload')
                    ->badge(fn($record): string => match ($record->status_upload) {
                        'subido' => 'success',
                        'sellado' => 'warning',
                        'timbrado' => 'info',
                        'depositado' => 'success',
                        default => 'danger',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                Action::make('descargar_xml')
                ->label('Descargar XML')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn($record) => route('facturas.descargar-xml', $record))
                ->color('success')
                ->openUrlInNewTab(false)
                ->visible(fn($record) => !empty($record->ruta)),

                Action::make('continuar')
                ->label('Continuar')
                ->icon('heroicon-o-arrow-right')
                ->url(fn($record) => route('filament.admin.pages.cfdi-continues', $record))
                ->color('success')
                ->openUrlInNewTab(false)
                ->visible(fn($record) => $record->status_upload !== 'depositado'),


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
