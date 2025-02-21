<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms\Form;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $modelLabel = 'Tiendas';

    public static function canCreate(): bool
    {
        return false;
    }

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
                TextColumn::make('name')
                    ->label('Nombre'),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('No disponible'),

                TextColumn::make('owner.name')
                    ->label('Propietario')
                    ->placeholder('No disponible'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Información de la Tienda')
                    ->tabs([
                        Tab::make('Información General')
                            ->schema([
                                ImageEntry::make('logoUrl')
                                    ->label('Logo')
                                    ->circular()
                                    ->placeholder('No disponible'),

                                Group::make()
                                    ->columnSpan(2)
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nombre de la Tienda')
                                            ->placeholder('No disponible'),

                                        TextEntry::make('verified')
                                            ->label('Verificada')
                                            ->formatStateUsing(fn ($record) => $record->verified ? 'Sí' : 'No')
                                            ->badge()
                                            ->color(fn ($record) => $record->verified ? 'success' : 'danger'),
                                    ]),
                            ])->columnSpanFull(),

                        Tab::make('Direcciones')
                            ->schema([
                                RepeatableEntry::make('addresses')
                                    ->label('Direcciones')
                                    ->schema([
                                        TextEntry::make('branch')
                                            ->label('Sucursal')
                                            ->placeholder('No disponible'),
                                        TextEntry::make('location')
                                            ->label('Ubicación')
                                            ->placeholder('No disponible'),
                                    ])
                                    ->columnSpanFull()
                                    ->grid(2),
                            ])->columnSpanFull(),
                    ])->columnSpanFull(),
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
            'view' => Pages\ViewStore::route('/{record}'),
        ];
    }
}
