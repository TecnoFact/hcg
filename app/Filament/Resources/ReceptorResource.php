<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceptorResource\Pages;
use App\Filament\Resources\ReceptorResource\RelationManagers;
use App\Models\Models\CfdiReceptor;
use App\Models\Receptor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReceptorResource extends Resource
{
    protected static ?string $model = CfdiReceptor::class;

    protected static ?string $navigationGroup = 'CFDI';
    protected static ?string $navigationLabel = 'Receptores';

    protected static ?string $label = 'Receptor';

    protected static ?string $pluralLabel = 'Receptores';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfc')
                    ->label('RFC')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('domicilio_fiscal')
                    ->label('Domicilio Fiscal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('regimen_fiscal')
                    ->label('Régimen Fiscal')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListReceptors::route('/'),
            'create' => Pages\CreateReceptor::route('/create'),
            'edit' => Pages\EditReceptor::route('/{record}/edit'),
        ];
    }
}
