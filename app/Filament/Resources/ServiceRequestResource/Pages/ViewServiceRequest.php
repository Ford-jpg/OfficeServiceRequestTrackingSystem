<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
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
                                default => 'Status Transition Notes (Optional)',
                            })
                            ->required(fn (Get $get): bool => $get('new_status') === ServiceRequest::STATUS_REJECTED)
                            ->rows(3)
                            ->placeholder('Enter remarks or reasons for this status change...'),
                    ];
                })
                ->action(function (array $data): void {
                    try {
                        $this->record->transitionTo($data['new_status'], auth()->user(), $data['notes'] ?? null);

                        Notification::make()
                            ->title('Status Updated')
                            ->body("Request {$this->record->reference_number} is now {$data['new_status']}.")
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

            Actions\EditAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
        ];
    }
}
