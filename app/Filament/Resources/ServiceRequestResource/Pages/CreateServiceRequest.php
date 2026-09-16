<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceCategory;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['requester_id'])) {
            $data['requester_id'] = auth()->id();
        }

        if (empty($data['department_id']) && ! empty($data['service_category_id'])) {
            $category = ServiceCategory::find($data['service_category_id']);
            if ($category) {
                $data['department_id'] = $category->department_id;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
