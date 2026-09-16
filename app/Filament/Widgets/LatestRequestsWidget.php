<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Recent Service Requests';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceRequest::query()->latest()->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('title')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('department.name')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Submitted' => 'warning',
                        'Under Review' => 'info',
                        'In Progress' => 'primary',
                        'Completed' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (ServiceRequest $record): string => ServiceRequestResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->size('xs'),
            ]);
    }
}
