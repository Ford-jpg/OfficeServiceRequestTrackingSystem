<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\RequestAuditLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceRequest extends ViewRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updateStatus')
                ->label('Update Status')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->button()
                ->visible(fn (): bool => (auth()->user()?->canUpdateStatus() ?? false) && count($this->record->getNextAllowedStatuses(auth()->user())) > 0)
                ->form(function (): array {
                    $record = $this->record;
                    $allowed = $record->getNextAllowedStatuses(auth()->user());
                    $options = array_combine($allowed, $allowed);

                    return [
                        Forms\Components\Placeholder::make('workflow_info')
                            ->label('Current Status')
                            ->content("{$record->status} (Allowed next: ".implode(', ', $allowed).')'),

                        Forms\Components\Select::make('new_status')
                            ->label('Transition To')
                            ->options($options)
                            ->required()
                            ->live(),

                        Forms\Components\Textarea::make('notes')
                            ->label(fn (Get $get): string => match ($get('new_status')) {
                                ServiceRequest::STATUS_REJECTED => 'Rejection Reason (Required)',
                                ServiceRequest::STATUS_COMPLETED => 'Resolution Summary (Required)',
                                default => 'Status Transition Notes (Optional)',
                            })
                            ->required(fn (Get $get): bool => in_array($get('new_status'), [
                                ServiceRequest::STATUS_REJECTED,
                                ServiceRequest::STATUS_COMPLETED,
                            ], true))
                            ->rows(3)
                            ->placeholder('Enter remarks or reasons for this status change...'),
                    ];
                })
                ->action(function (array $data): void {
                    try {
                        $this->record->transitionTo($data['new_status'], auth()->user(), $data['notes'] ?? null);
                        $this->refreshFormData(['status', 'rejection_reason', 'resolution_notes', 'resolved_at']);

                        Notification::make()
                            ->title('Status Updated')
                            ->body("Ticket {$this->record->ticket_number} moved to {$data['new_status']}.")
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

            Actions\Action::make('assignStaff')
                ->label('Assign Technician')
                ->icon('heroicon-m-user-plus')
                ->color('gray')
                ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'service_manager'], true))
                ->form([
                    Forms\Components\Select::make('assigned_to_user_id')
                        ->label('Assign Technician / Specialist')
                        ->options(User::whereIn('role', ['admin', 'service_manager', 'technician'])
                            ->where('is_active', true)
                            ->pluck('name', 'id'))
                        ->default($this->record->assigned_to_user_id)
                        ->required()
                        ->searchable(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Assignment Note')
                        ->placeholder('Optional notes for the assigned technician...'),
                ])
                ->action(function (array $data): void {
                    $oldAssignee = $this->record->assignedStaff?->name ?? 'None';
                    $this->record->assigned_to_user_id = $data['assigned_to_user_id'];

                    if ($this->record->status === ServiceRequest::STATUS_SUBMITTED) {
                        $this->record->status = ServiceRequest::STATUS_UNDER_REVIEW;
                    }
                    $this->record->save();

                    $newStaff = User::find($data['assigned_to_user_id']);

                    RequestAuditLog::create([
                        'service_request_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'action' => 'assigned',
                        'from_status' => $this->record->status,
                        'to_status' => $this->record->status,
                        'notes' => "Assigned from {$oldAssignee} to {$newStaff?->name}".($data['notes'] ? ": {$data['notes']}" : ''),
                        'ip_address' => request()?->ip(),
                        'user_agent' => request()?->userAgent(),
                        'created_at' => now(),
                    ]);

                    $this->refreshFormData(['assigned_to_user_id', 'status']);

                    Notification::make()
                        ->title('Technician Assigned')
                        ->body("Assigned to {$newStaff?->name}.")
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canUpdateStatus() ?? false),
        ];
    }
}
