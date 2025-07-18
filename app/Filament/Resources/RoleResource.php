<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

     protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 2;



    public static function getPluralModelLabel(): string
    {
        return "Roles";
    }

    public static function getModelLabel(): string
    {
        return "Rol";
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make()
            ->schema([
                Forms\Components\TextInput::make('name')->label('Nombre')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
               Forms\Components\Select::make('guard_name')->label('Guard')
                   ->required()
                   ->options([
                       'web' => 'Web',
                       'api' => 'API',
                   ])
                   ->default('web'),
                Select::make('permissions')
                    ->multiple()
                    ->label("Permisos")
                    ->relationship(
                        name: 'permissions',
                        titleAttribute: 'name'
                    )
                    ->preload(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')->label('Guard')
                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->label("Permisos")
                    ->badge()
                    ->counts('permissions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar'),
                ])->label('Eliminar'),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
