<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\RequestAuditLog;
use App\Models\ServiceRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Service Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->placeholder('Auto-generated on submission')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?ServiceRequest $record) => $record !== null),

                        Forms\Components\Select::make('user_id')
                            ->label('Requesting Employee')
                            ->relationship('requester', 'name')
                            ->default(fn () => auth()->id())
                            ->disabled(fn () => ! auth()->user()?->isAdmin())
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('department_id')
                            ->label('Office or Unit')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('service_category_id')
                            ->label('Request Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options(ServiceRequest::PRIORITIES)
                            ->default(ServiceRequest::PRIORITY_MEDIUM)
                            ->required(),

                        Forms\Components\Placeholder::make('status')
                            ->label('Current Status')
                            ->content(fn (?ServiceRequest $record): string => $record?->status ?? ServiceRequest::STATUS_SUBMITTED)
                            ->visible(fn (?ServiceRequest $record) => $record !== null),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requesting Employee')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Office or Unit')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Request Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Current Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Submitted' => 'warning',
                        'Under Review' => 'info',
                        'In Progress' => 'primary',
                        'Completed' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(array_combine(ServiceRequest::STATUSES, ServiceRequest::STATUSES)),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(ServiceRequest::PRIORITIES),

                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Office or Unit')
                    ->relationship('department', 'name'),

                Tables\Filters\SelectFilter::make('service_category_id')
                    ->label('Request Category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('updateStatus')
                    ->label('Update Status')
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->button()
                    ->size('xs')
                    ->visible(fn (ServiceRequest $record): bool => (auth()->user()?->canUpdateStatus() ?? false) && count($record->getNextAllowedStatuses(auth()->user())) > 0)
                    ->form(function (ServiceRequest $record): array {
                        $allowed = $record->getNextAllowedStatuses(auth()->user());
                        $options = array_combine($allowed, $allowed);

                        return [
                            Forms\Components\Placeholder::make('workflow_info')
                                ->label('Current Status')
                                ->content("{$record->status} (Allowed next: ".implode(', ', $allowed).')'),

                            Forms\Components\Select::make('new_status')
                                ->label('Next Status')
                                ->options($options)
                                ->required()
                                ->live(),

                            Forms\Components\Textarea::make('notes')
                                ->label(fn (Forms\Get $get): string => match ($get('new_status')) {
                                    ServiceRequest::STATUS_REJECTED => 'Rejection Reason (Required)',
                                    default => 'Status Transition Notes (Optional)',
                                })
                                ->required(fn (Forms\Get $get): bool => $get('new_status') === ServiceRequest::STATUS_REJECTED)
                                ->rows(3)
                                ->placeholder('Enter remarks or reason for this status update...'),
                        ];
                    })
                    ->action(function (ServiceRequest $record, array $data): void {
                        try {
                            $record->transitionTo($data['new_status'], auth()->user(), $data['notes'] ?? null);

                            Notification::make()
                                ->title('Status Updated')
                                ->body("Request {$record->reference_number} is now {$data['new_status']}.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Office Service Request')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference_number')
                            ->label('Reference Number')
                            ->weight('bold')
                            ->color('primary')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Current Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Submitted' => 'warning',
                                'Under Review' => 'info',
                                'In Progress' => 'primary',
                                'Completed' => 'success',
                                'Rejected' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('requester.name')
                            ->label('Requesting Employee')
                            ->icon('heroicon-m-user'),

                        Infolists\Components\TextEntry::make('department.name')
                            ->label('Office or Unit')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Request Category'),

                        Infolists\Components\TextEntry::make('priority')
                            ->label('Priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'urgent' => 'danger',
                                'high' => 'warning',
                                'medium' => 'info',
                                'low' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date Submitted')
                            ->dateTime('M d, Y H:i:s'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Status Audit Trail')
                    ->description('Record showing who changed the status and when.')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('auditLogs')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Timestamp')
                                            ->dateTime('M d, Y H:i:s'),

                                        Infolists\Components\TextEntry::make('user.name')
                                            ->label('Changed By')
                                            ->icon('heroicon-m-user')
                                            ->weight('bold')
                                            ->placeholder('System'),

                                        Infolists\Components\TextEntry::make('transition')
                                            ->label('Status Transition')
                                            ->state(function (RequestAuditLog $record): string {
                                                if ($record->from_status && $record->to_status) {
                                                    return "{$record->from_status} → {$record->to_status}";
                                                }

                                                return $record->to_status ?? ucfirst($record->action);
                                            })
                                            ->badge()
                                            ->color('info'),

                                        Infolists\Components\TextEntry::make('notes')
                                            ->label('Notes / Reason')
                                            ->placeholder('None'),
                                    ]),
                            ]),
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
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'view' => Pages\ViewServiceRequest::route('/{record}'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
