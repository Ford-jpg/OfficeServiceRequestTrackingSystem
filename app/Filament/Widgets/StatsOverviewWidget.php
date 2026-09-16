<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Submitted', ServiceRequest::where('status', ServiceRequest::STATUS_SUBMITTED)->count())
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Under Review', ServiceRequest::where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count())
                ->description('Under evaluation')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('info'),

            Stat::make('In Progress', ServiceRequest::where('status', ServiceRequest::STATUS_IN_PROGRESS)->count())
                ->description('Active work')
                ->descriptionIcon('heroicon-m-wrench')
                ->color('primary'),

            Stat::make('Completed', ServiceRequest::where('status', ServiceRequest::STATUS_COMPLETED)->count())
                ->description('Resolved requests')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected', ServiceRequest::where('status', ServiceRequest::STATUS_REJECTED)->count())
                ->description('Declined requests')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Requests', ServiceRequest::count())
                ->description('Total volume')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }
}
