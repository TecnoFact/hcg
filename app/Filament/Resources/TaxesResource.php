<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxesResource\Pages;
use App\Filament\Resources\TaxesResource\RelationManagers;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxesResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Impuestos';

    protected static ?string $pluralLabel = 'Impuestos';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del impuesto')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label('Código del impuesto')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('rate')
                    ->label('Tasa del impuesto')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                Forms\Components\Select::make('tipo_factor')
                    ->label('Tipo de factor')
                    ->options([
                        'Tasa' => 'Tasa',
                        'Cuota' => 'Cuota',
                        'Exento' => 'Exento'
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Estado del impuesto')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del impuesto'),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código del impuesto'),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Tasa del impuesto'),
                Tables\Columns\TextColumn::make('tipo_factor')
                    ->label('Tipo de factor'),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Estado del impuesto'),
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTaxes::route('/create'),
            'edit' => Pages\EditTaxes::route('/{record}/edit'),
        ];
    }
}
