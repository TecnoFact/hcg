<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Emisor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use App\Forms\Components\CertificateView;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use App\Filament\Resources\EmisorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmisorResource\RelationManagers;

class EmisorResource extends Resource
{
    protected static ?string $model = Emisor::class;

    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Emisores';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Datos del emisor')
                ->schema([

                        TextInput::make('name')
                            ->label('Nombre')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->label('Correo electronico')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rfc')
                            ->label('RFC')
                            ->unique(table: Emisor::class, column: 'rfc', ignorable: fn ($livewire) => !($livewire instanceof \App\Filament\Resources\EmisorResource\Pages\CreateEmisor) ? $livewire->record : null)
                            ->maxLength(13)
                            ->required(),

                        TextInput::make('reason_social')
                            ->label('Razon social')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->label('Sitio web')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(10),
                        ColorPicker::make('color')
                            ->label('Color PDF')
                            ->default('#000000'),
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->disk('local')
                            ->directory(fn($get) => 'certificates' . DIRECTORY_SEPARATOR . $get('rfc'))
                            ->avatar(),

                        Section::make('Domicilio')
                            ->description('Datos del domicilio del emisor')
                            ->columns(2)
                            ->schema([
                                    TextInput::make('street')
                                        ->label('Calle')
                                        ->maxLength(255),
                                    TextInput::make('number_exterior')
                                        ->label('Numero exterior')
                                        ->maxLength(10),
                                    TextInput::make('number_interior')
                                        ->label('Numero interior')
                                        ->maxLength(10),
                                    TextInput::make('colony')
                                        ->label('Colonia')
                                        ->maxLength(255),
                                    TextInput::make('postal_code')
                                        ->label('Codigo postal')
                                        ->maxLength(5)->required(),
                                    Select::make('state_id')
                                        ->label('Estado')
                                        ->relationship('state', 'nombre')
                                        ->searchable()
                                        ->preload()
                                        ->getSearchResultsUsing(fn (string $search) =>
                                            \App\Models\State::where('nombre', 'like', "%{$search}%")
                                                ->limit(20)
                                                ->pluck('nombre', 'clave')
                                        )
                                        ->reactive()
                                        ->getOptionLabelUsing(fn ($value) =>
                                            \App\Models\State::find($value)?->nombre
                                        ),
                                    Select::make('city_id')
                                        ->label('Ciudad')
                                        ->relationship('city', 'nombre')
                                        ->searchable()
                                        ->reactive()
                                        ->getSearchResultsUsing(function (string $search, $get) {
                                            $query = \App\Models\City::query();
                                            if ($get('state_id')) {
                                                $query->where('state_id', $get('state_id'));
                                            }
                                            if ($search) {
                                                $query->where('nombre', 'like', "%{$search}%");
                                            }
                                            return $query->limit(20)->pluck('nombre', 'clave');
                                        })
                                        ->getOptionLabelUsing(fn ($value) =>
                                            \App\Models\City::find($value)?->nombre
                                        ),

                                    Select::make('country_id')
                                        ->label('Pais')
                                        ->relationship('country', 'nombre')
                                        ->searchable()
                                        ->preload()
                                        ->getSearchResultsUsing(fn (string $search) =>
                                        \App\Models\Country::where('nombre', 'like', "%{$search}%")
                                            ->limit(20)
                                            ->pluck('nombre', 'clave')
                                    )
                                    ->getOptionLabelUsing(fn ($value) =>
                                        \App\Models\Country::find($value)?->nombre
                                    ),
                                ]),
                        Section::make('Certificado y llave')
                            ->description('Datos del certificado y llave del emisor')
                            ->columns(3)
                            ->schema([
                                    ViewField::make('file_certificate')->label('Certificado')->view('forms.components.certificate-view'),
                                    ViewField::make('file_key')->view('forms.components.private-key-view')->label('Llave privada'),
                                    TextInput::make('password_key')
                                        ->password()
                                        ->label('Contraseña llave')
                                        ->required(),
                            ])
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('rfc')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('reason_social')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('website')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('phone')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->sortable(),
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
            'index' => Pages\ListEmisors::route('/'),
            'create' => Pages\CreateEmisor::route('/create'),
            'edit' => Pages\EditEmisor::route('/{record}/edit'),
        ];
    }
}
