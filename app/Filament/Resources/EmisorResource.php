<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use App\Models\Emisor;
use Filament\Forms\Form;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
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

    protected static ?string $pluralLabel = 'Emisores';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Datos del emisor')
                ->schema([
                        TextInput::make('reason_social')
                            ->label('Razon social')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('name', $state);
                            })
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->maxLength(255)
                            ->hidden(),

                        TextInput::make('rfc')
                            ->label('RFC')
                            ->unique(table: Emisor::class, column: 'rfc', ignorable: fn ($livewire) => !($livewire instanceof \App\Filament\Resources\EmisorResource\Pages\CreateEmisor) ? $livewire->record : null)
                            ->maxLength(13)
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->label('Correo electronico')
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
                            Select::make('tax_regimen_id')
                            ->label('Régimen fiscal')
                            ->relationship('regimenFiscal', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->getSearchResultsUsing(fn (string $search) =>
                                \App\Models\RegimeFiscal::where('descripcion', 'like', "%{$search}%")
                                    ->limit(20)
                                    ->pluck('descripcion', 'clave')
                            )
                            ->reactive(),
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
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('city_id', null);
                                        })
                                        ->getOptionLabelUsing(fn ($value) =>
                                            \App\Models\State::find($value)?->nombre
                                        ),
                                    Select::make('city_id')
                                        ->label('Ciudad')
                                        ->searchable()
                                        ->reactive()
                                        ->preload()
                                        ->options(function ($get) {
                                            $stateId = $get('state_id');

                                            if ($stateId) {

                                               $cities = \App\Models\City::where('id_estado', $stateId)
                                                    ->orderBy('descripcion')
                                                    ->pluck('descripcion', 'id')
                                                    ->toArray();
                                            }

                                            return $cities ?? [];
                                        })
                                        ->getOptionLabelUsing(fn ($value) =>
                                            \App\Models\City::find($value)?->descripcion
                                        ),

                                    TextInput::make('country_id')->default('MEX')->hidden(),
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
                Stack::make([

                    Tables\Columns\ImageColumn::make('logo')
                        ->disk('local')
                        ->visibility('private')
                        ->defaultImageUrl(url('/image/avaatar.png'))
                        ->circular(),

                    Tables\Columns\TextColumn::make('rfc')
                        ->formatStateUsing(fn($state) => 'RFC: ' . $state)
                        ->searchable(),
                    Tables\Columns\TextColumn::make('reason_social')
                        ->formatStateUsing(fn($state) => 'Razón Social: ' . $state)
                        ->searchable(),
                    Tables\Columns\TextColumn::make('regimenFiscal.descripcion')
                        ->formatStateUsing(fn($state) => 'Régimen Fiscal: ' . $state)
                        ->searchable(),
                    Tables\Columns\TextColumn::make('date_from')
                        ->formatStateUsing(fn($state) => 'Fecha de inicio: ' . $state)
                        ->icon('heroicon-o-calendar')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('due_date')
                        ->formatStateUsing(fn($state) => 'Fecha de vencimiento: ' . $state)
                        ->icon('heroicon-o-calendar')
                        ->searchable(),
                ]),
            ])
        ->contentGrid([
            'md' => 2,
            'xl' => 3,
        ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]) ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->hasRole('Customer')) {
                    $query->where('user_id', auth()->id());
                }
            });
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
