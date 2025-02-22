<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StoreResource\Pages;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $modelLabel = 'Tiendas';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('url')
                    ->maxLength(255),

                Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('certificate_of_incorporation_path')
                    ->label('Certificate of Incorporation')
                    ->maxSize(1024)
                    ->maxFiles(1)
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/*']),

                Forms\Components\FileUpload::make('rif_path')
                    ->label('RIF')
                    ->maxSize(1024)
                    ->maxFiles(1)
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/*']),

                Forms\Components\Select::make('owner_id')
                    ->label('Owner')
                    ->options(function () {
                        return User::role('owner_store') // Filtra los usuarios que tienen el rol 'owner_store'
                            ->get() // Obtiene la colección de usuarios
                            ->pluck('name', 'id'); // Utiliza el accesor 'name' para obtener el nombre completo
                    })
                    ->required()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('Url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propietario')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('verified')
                    ->label('Verificada')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modificación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
