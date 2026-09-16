<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\RequestAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
        ];
    }

    protected function beforeSave(): void
    {
        // Audit log if status changed via edit form
        if ($this->record->isDirty('status')) {
            RequestAuditLog::create([
                'service_request_id' => $this->record->id,
                'user_id' => auth()->id(),
                'action' => 'status_change',
                'from_status' => $this->record->getOriginal('status'),
                'to_status' => $this->record->status,
                'notes' => 'Status updated via edit form',
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
