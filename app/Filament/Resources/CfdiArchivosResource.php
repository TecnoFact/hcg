<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CfdiArchivosResource\Pages;
use App\Filament\Resources\CfdiResource\Pages\EditCfdi;
use App\Models\Models\Cfdi;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CfdiArchivosResource extends Resource
{
    protected static ?string $model = Cfdi::class;

    protected static ?string $label = 'CFDI Archivos';

    protected static ?string $pluralLabel = 'CFDI Archivos';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

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
                    ->label('RFC Emisor')->searchable(),
                Tables\Columns\TextColumn::make('receptor.rfc')
                    ->label('RFC Receptor')->searchable(),
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')->searchable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->dateTime()
                    ->label('Fecha'),

                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->label('Total'),
                Tables\Columns\TextColumn::make('estatus')
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('status_upload')
                    ->label('Estado Subida')
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
                ActionGroup::make([

                    Tables\Actions\EditAction::make(),

                        Action::make('descargar_xml')
                        ->label('Descargar XML')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn($record) => route('facturas.descargar-xml', $record))
                        ->color('success')
                        ->openUrlInNewTab(false)
                        ->visible(fn($record) => $record->status_upload === Cfdi::ESTATUS_TIMBRADO || $record->status_upload === Cfdi::ESTATUS_DEPOSITADO),


                        Action::make('descargar_pdf')
                        ->label('Descargar PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn($record) => route('facturas.descargar-pdf', $record))
                        ->color('success')
                        ->openUrlInNewTab(false)
                        ->visible(fn($record) => $record->status_upload === Cfdi::ESTATUS_TIMBRADO || $record->status_upload === Cfdi::ESTATUS_DEPOSITADO),


                        Action::make('continuar')
                        ->label('Continuar')
                        ->icon('heroicon-o-arrow-right')
                        ->url(fn($record) => route('filament.admin.pages.cfdi-continues', $record))
                        ->color('success')
                        ->openUrlInNewTab(false)
                        ->visible(fn($record) => $record->status_upload !== Cfdi::ESTATUS_DEPOSITADO),

                        Action::make('cancelar')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-mark')
                        ->url(fn($record) => route('filament.admin.pages.cfdi-cancel', $record))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('¿Estás seguro?')
                        ->modalSubheading('¿Deseas cancelar la creación de este Cfdi?')
                        ->openUrlInNewTab(false)
                        ->visible(fn($record) => ($record->status_upload !== Cfdi::ESTATUS_DEPOSITADO) && ($record->status_upload !== Cfdi::ESTATUS_SUBIDO)),
            ]),

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
            'index' => Pages\ListCfdiArchivos::route('/'),
            'edit' => EditCfdi::route('/{record}/edit'),
            //'create' => Pages\CreateCfdi::route('/create'),
            //'create' => \App\Filament\Pages\Cfdi::class,
           // 'edit' => Pages\EditCfdi::route('/{record}/edit'),
        ];
    }
}
