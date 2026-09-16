<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\RequestAuditLog;
use App\Models\ServiceRequest;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                Section::make('Request Information')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->placeholder('Auto-generated on submission')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?ServiceRequest $record) => $record !== null),

                        Select::make('user_id')
                            ->label('Requesting Employee')
                            ->relationship('requester', 'name')
                            ->default(fn () => auth()->id())
                            ->disabled(fn () => ! auth()->user()?->isAdmin())
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('department_id')
                            ->label('Office or Unit')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('service_category_id')
                            ->label('Request Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('priority')
                            ->label('Priority')
                            ->options(ServiceRequest::PRIORITIES)
                            ->default(ServiceRequest::PRIORITY_MEDIUM)
                            ->required(),

                        Placeholder::make('status')
                            ->label('Current Status')
                            ->content(fn (?ServiceRequest $record): string => $record?->status ?? ServiceRequest::STATUS_SUBMITTED)
                            ->visible(fn (?ServiceRequest $record) => $record !== null),

                        Textarea::make('description')
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
                TextColumn::make('reference_number')
                    ->label('Reference #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('requester.name')
                    ->label('Requesting Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Office or Unit')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Request Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('priority')
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

                TextColumn::make('status')
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

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(array_combine(ServiceRequest::STATUSES, ServiceRequest::STATUSES)),

                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(ServiceRequest::PRIORITIES),

                SelectFilter::make('department_id')
                    ->label('Office or Unit')
                    ->relationship('department', 'name'),

                SelectFilter::make('service_category_id')
                    ->label('Request Category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Action::make('updateStatus')
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
                            Placeholder::make('workflow_info')
                                ->label('Current Status')
                                ->content("{$record->status} (Allowed next: ".implode(', ', $allowed).')'),

                            Select::make('new_status')
                                ->label('Next Status')
                                ->options($options)
                                ->required()
                                ->live(),

                            Textarea::make('notes')
                                ->label(fn (Get $get): string => match ($get('new_status')) {
                                    ServiceRequest::STATUS_REJECTED => 'Rejection Reason (Required)',
                                    default => 'Status Transition Notes (Optional)',
                                })
                                ->required(fn (Get $get): bool => $get('new_status') === ServiceRequest::STATUS_REJECTED)
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
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Office Service Request')
                    ->schema([
                        TextEntry::make('reference_number')
                            ->label('Reference Number')
                            ->weight('bold')
                            ->color('primary')
                            ->size(TextEntrySize::Large),

                        TextEntry::make('status')
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

                        TextEntry::make('requester.name')
                            ->label('Requesting Employee')
                            ->icon('heroicon-m-user'),

                        TextEntry::make('department.name')
                            ->label('Office or Unit')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('category.name')
                            ->label('Request Category'),

                        TextEntry::make('priority')
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

                        TextEntry::make('created_at')
                            ->label('Date Submitted')
                            ->dateTime('M d, Y H:i:s'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                InfolistSection::make('Status Audit Trail')
                    ->description('Record showing who changed the status and when.')
                    ->schema([
                        RepeatableEntry::make('auditLogs')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Timestamp')
                                            ->dateTime('M d, Y H:i:s'),

                                        TextEntry::make('user.name')
                                            ->label('Changed By')
                                            ->icon('heroicon-m-user')
                                            ->weight('bold')
                                            ->placeholder('System'),

                                        TextEntry::make('transition')
                                            ->label('Status Transition')
                                            ->state(function (RequestAuditLog $record): string {
                                                if ($record->from_status && $record->to_status) {
                                                    return "{$record->from_status} → {$record->to_status}";
                                                }

                                                return $record->to_status ?? ucfirst($record->action);
                                            })
                                            ->badge()
                                            ->color('info'),

                                        TextEntry::make('notes')
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
