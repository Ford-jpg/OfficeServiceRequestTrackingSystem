<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Service Request')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Requests')
                ->badge(ServiceRequest::count()),

            'submitted' => Tab::make('Submitted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceRequest::STATUS_SUBMITTED))
                ->badge(ServiceRequest::where('status', ServiceRequest::STATUS_SUBMITTED)->count())
                ->badgeColor('warning'),

            'under_review' => Tab::make('Under Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceRequest::STATUS_UNDER_REVIEW))
                ->badge(ServiceRequest::where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count())
                ->badgeColor('info'),

            'in_progress' => Tab::make('In Progress')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceRequest::STATUS_IN_PROGRESS))
                ->badge(ServiceRequest::where('status', ServiceRequest::STATUS_IN_PROGRESS)->count())
                ->badgeColor('primary'),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceRequest::STATUS_COMPLETED))
                ->badge(ServiceRequest::where('status', ServiceRequest::STATUS_COMPLETED)->count())
                ->badgeColor('success'),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ServiceRequest::STATUS_REJECTED))
                ->badge(ServiceRequest::where('status', ServiceRequest::STATUS_REJECTED)->count())
                ->badgeColor('danger'),
        ];
    }
}
