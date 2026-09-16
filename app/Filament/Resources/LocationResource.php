<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Settings & Organization';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\TextInput::make('building')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Main Tower / HQ'),

                        Forms\Components\TextInput::make('floor')
                            ->maxLength(255)
                            ->placeholder('e.g. 3rd Floor'),

                        Forms\Components\TextInput::make('room_or_area')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Executive Boardroom / Desk 12A'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Location')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(2)
                            ->placeholder('Special instructions, access card required, etc.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('building')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('floor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room_or_area')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests')
                    ->counts('serviceRequests')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Locations')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
