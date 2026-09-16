<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\Department;
use App\Models\RequestAuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Service Desk';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $query = ServiceRequest::whereIn('status', [
            ServiceRequest::STATUS_SUBMITTED,
            ServiceRequest::STATUS_UNDER_REVIEW,
            ServiceRequest::STATUS_IN_PROGRESS,
        ]);

        if ($user && ! $user->isAdmin()) {
            $query->where('department_id', $user->department_id);
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Request Details')
                            ->description('Provide clear and specific details about the service needed.')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Request Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. AC unit leaking in Conference Room B'),

                                Forms\Components\Textarea::make('description')
                                    ->label('Detailed Description')
                                    ->required()
                                    ->rows(4)
                                    ->placeholder('Describe the issue in detail, when it began, and any relevant circumstances...'),

                                Forms\Components\FileUpload::make('attachments')
                                    ->label('Photo or Document Attachments')
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->disk('public')
                                    ->directory('request-attachments')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Classification & Location')
                            ->schema([
                                Forms\Components\Select::make('department_id')
                                    ->label('Target Department')
                                    ->options(function () {
                                        $user = auth()->user();
                                        $query = Department::where('is_active', true);
                                        if ($user && ! $user->isAdmin() && $user->department_id) {
                                            $query->where('id', $user->department_id);
                                        }

                                        return $query->pluck('name', 'id');
                                    })
                                    ->default(fn () => auth()->user()?->isAdmin() ? null : auth()->user()?->department_id)
                                    ->disabled(fn () => ! (auth()->user()?->isAdmin() ?? false))
                                    ->dehydrated()
                                    ->required()
                                    ->live()
                                    ->searchable()
                                    ->preload()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('service_category_id', null)),

                                Forms\Components\Select::make('service_category_id')
                                    ->label('Service Category')
                                    ->options(function (Get $get): Collection {
                                        $departmentId = $get('department_id');
                                        if (! $departmentId) {
                                            return ServiceCategory::where('is_active', true)->pluck('name', 'id');
                                        }

                                        return ServiceCategory::where('department_id', $departmentId)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\Select::make('location_id')
                                    ->label('Location / Room')
                                    ->relationship('location', 'room_or_area')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->building} - {$record->floor} - {$record->room_or_area}")
                                    ->searchable(['building', 'floor', 'room_or_area'])
                                    ->preload()
                                    ->required(),
                            ])->columns(3),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Priority & Assignment')
                            ->schema([
                                Forms\Components\Select::make('priority')
                                    ->options([
                                        'low' => 'Low (Standard maintenance)',
                                        'medium' => 'Medium (Normal business priority)',
                                        'high' => 'High (Impacting team work)',
                                        'urgent' => 'Urgent (Critical facility / safety)',
                                    ])
                                    ->default('medium')
                                    ->required(),

                                Forms\Components\Select::make('requester_id')
                                    ->label('Requester')
                                    ->relationship('requester', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->id())
                                    ->disabled(fn () => ! auth()->user()?->isAdmin())
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Select::make('assigned_to_user_id')
                                    ->label('Assigned Technician')
                                    ->relationship('assignedStaff', 'name', fn (Builder $query) => $query->whereIn('role', ['admin', 'service_manager', 'technician']))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn () => auth()->user()?->canUpdateStatus() ?? false),

                                Forms\Components\DateTimePicker::make('due_date')
                                    ->label('Due Date')
                                    ->visible(fn () => auth()->user()?->canUpdateStatus() ?? false),
                            ]),

                        Forms\Components\Section::make('Workflow Status')
                            ->description('Status updates must follow the approved workflow.')
                            ->schema([
                                Forms\Components\Placeholder::make('current_status')
                                    ->label('Current Status')
                                    ->content(fn (?ServiceRequest $record): string => $record?->status ?? ServiceRequest::STATUS_SUBMITTED),

                                Forms\Components\Textarea::make('resolution_notes')
                                    ->label('Resolution Notes')
                                    ->rows(3)
                                    ->visible(fn (?ServiceRequest $record) => $record?->status === ServiceRequest::STATUS_COMPLETED || (auth()->user()?->canUpdateStatus() ?? false))
                                    ->disabled(fn () => ! (auth()->user()?->canUpdateStatus() ?? false)),

                                Forms\Components\Textarea::make('rejection_reason')
                                    ->label('Rejection Reason')
                                    ->rows(3)
                                    ->visible(fn (?ServiceRequest $record) => $record?->status === ServiceRequest::STATUS_REJECTED || (auth()->user()?->canUpdateStatus() ?? false))
                                    ->disabled(fn () => ! (auth()->user()?->canUpdateStatus() ?? false)),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Ticket number copied'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(35)
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn (ServiceRequest $record): string => $record->title),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
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

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requester')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedStaff.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Submitted' => 'Submitted',
                        'Under Review' => 'Under Review',
                        'In Progress' => 'In Progress',
                        'Completed' => 'Completed',
                        'Rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),

                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),

                Tables\Filters\SelectFilter::make('assigned_to_user_id')
                    ->label('Assignee')
                    ->relationship('assignedStaff', 'name'),
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
                                ->label('Workflow Guideline')
                                ->content("Current Status: {$record->status} -> Allowed transitions: ".implode(', ', $allowed)),

                            Forms\Components\Select::make('new_status')
                                ->label('Next Status')
                                ->options($options)
                                ->required()
                                ->live(),

                            Forms\Components\Textarea::make('notes')
                                ->label(fn (Get $get): string => match ($get('new_status')) {
                                    ServiceRequest::STATUS_REJECTED => 'Rejection Reason (Required)',
                                    ServiceRequest::STATUS_COMPLETED => 'Resolution Summary (Required)',
                                    default => 'Remarks / Notes (Optional)',
                                })
                                ->required(fn (Get $get): bool => in_array($get('new_status'), [
                                    ServiceRequest::STATUS_REJECTED,
                                    ServiceRequest::STATUS_COMPLETED,
                                ], true))
                                ->rows(3)
                                ->placeholder('Provide details regarding this status update...'),
                        ];
                    })
                    ->action(function (ServiceRequest $record, array $data): void {
                        try {
                            $record->transitionTo($data['new_status'], auth()->user(), $data['notes'] ?? null);

                            Notification::make()
                                ->title('Status Updated Successfully')
                                ->body("Request {$record->ticket_number} is now {$data['new_status']}.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Status Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('assignStaff')
                    ->label('Assign')
                    ->icon('heroicon-m-user-plus')
                    ->color('gray')
                    ->size('xs')
                    ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'service_manager'], true))
                    ->form([
                        Forms\Components\Select::make('assigned_to_user_id')
                            ->label('Assign Technician / Specialist')
                            ->options(User::whereIn('role', ['admin', 'service_manager', 'technician'])
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Assignment Note')
                            ->placeholder('Optional notes for the assigned technician...'),
                    ])
                    ->action(function (ServiceRequest $record, array $data): void {
                        $oldAssignee = $record->assignedStaff?->name ?? 'None';
                        $record->assigned_to_user_id = $data['assigned_to_user_id'];

                        $statusNote = '';
                        if ($record->status === ServiceRequest::STATUS_SUBMITTED) {
                            $record->status = ServiceRequest::STATUS_UNDER_REVIEW;
                            $statusNote = ' and status moved to Under Review';
                        }
                        $record->save();

                        $newStaff = User::find($data['assigned_to_user_id']);

                        RequestAuditLog::create([
                            'service_request_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'assigned',
                            'from_status' => $record->status,
                            'to_status' => $record->status,
                            'notes' => "Reassigned from {$oldAssignee} to {$newStaff?->name}".($data['notes'] ? ": {$data['notes']}" : ''),
                            'ip_address' => request()?->ip(),
                            'user_agent' => request()?->userAgent(),
                            'created_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Technician Assigned')
                            ->body("Assigned to {$newStaff?->name}{$statusNote}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ServiceRequest $record): bool => auth()->user()?->canUpdateStatus() ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Request Overview')
                                ->schema([
                                    Infolists\Components\TextEntry::make('ticket_number')
                                        ->label('Ticket #')
                                        ->weight('bold')
                                        ->color('primary')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                    Infolists\Components\TextEntry::make('title')
                                        ->label('Subject')
                                        ->weight('bold'),

                                    Infolists\Components\TextEntry::make('description')
                                        ->columnSpanFull(),

                                    Infolists\Components\TextEntry::make('department.name')
                                        ->label('Department')
                                        ->badge()
                                        ->color('primary'),

                                    Infolists\Components\TextEntry::make('category.name')
                                        ->label('Service Category'),

                                    Infolists\Components\TextEntry::make('location.full_location')
                                        ->label('Location'),
                                ])->columns(2),

                            Infolists\Components\Section::make('Resolution Details')
                                ->schema([
                                    Infolists\Components\TextEntry::make('resolution_notes')
                                        ->label('Resolution Notes')
                                        ->placeholder('No resolution notes recorded.')
                                        ->columnSpanFull(),

                                    Infolists\Components\TextEntry::make('rejection_reason')
                                        ->label('Rejection Reason')
                                        ->placeholder('No rejection reason recorded.')
                                        ->color('danger')
                                        ->columnSpanFull(),

                                    Infolists\Components\TextEntry::make('resolved_at')
                                        ->label('Resolved Date & Time')
                                        ->dateTime('M d, Y H:i:s')
                                        ->placeholder('Pending resolution'),
                                ])
                                ->columns(2),

                            Infolists\Components\Section::make('Audit Trail & Status History')
                                ->description('Chronological record showing who changed status, what action occurred, and when.')
                                ->schema([
                                    Infolists\Components\RepeatableEntry::make('auditLogs')
                                        ->label('')
                                        ->schema([
                                            Infolists\Components\Grid::make(4)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('created_at')
                                                        ->label('Date & Time')
                                                        ->dateTime('M d, Y H:i:s')
                                                        ->sinceTooltip(),

                                                    Infolists\Components\TextEntry::make('user.name')
                                                        ->label('Performed By')
                                                        ->weight('bold')
                                                        ->icon('heroicon-m-user')
                                                        ->placeholder('System'),

                                                    Infolists\Components\TextEntry::make('transition')
                                                        ->label('Status Change')
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
                                                        ->placeholder('-'),
                                                ]),
                                        ]),
                                ]),
                        ])->columnSpan(2),

                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Status & Meta')
                                ->schema([
                                    Infolists\Components\TextEntry::make('status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'Submitted' => 'warning',
                                            'Under Review' => 'info',
                                            'In Progress' => 'primary',
                                            'Completed' => 'success',
                                            'Rejected' => 'danger',
                                            default => 'gray',
                                        }),

                                    Infolists\Components\TextEntry::make('priority')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'medium' => 'info',
                                            'low' => 'gray',
                                            default => 'gray',
                                        }),

                                    Infolists\Components\TextEntry::make('requester.name')
                                        ->label('Requester')
                                        ->icon('heroicon-m-user'),

                                    Infolists\Components\TextEntry::make('requester.email')
                                        ->label('Contact')
                                        ->icon('heroicon-m-envelope'),

                                    Infolists\Components\TextEntry::make('assignedStaff.name')
                                        ->label('Assigned Staff')
                                        ->placeholder('Unassigned')
                                        ->icon('heroicon-m-wrench'),

                                    Infolists\Components\TextEntry::make('due_date')
                                        ->label('Target Due Date')
                                        ->dateTime('M d, Y H:i')
                                        ->placeholder('Not set'),

                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label('Submitted At')
                                        ->dateTime('M d, Y H:i'),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->where('department_id', $user->department_id);
        }

        return $query;
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
