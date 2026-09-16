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
        $submittedCount = ServiceRequest::where('status', ServiceRequest::STATUS_SUBMITTED)->count();
        $underReviewCount = ServiceRequest::where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count();
        $inProgressCount = ServiceRequest::where('status', ServiceRequest::STATUS_IN_PROGRESS)->count();
        $completedCount = ServiceRequest::where('status', ServiceRequest::STATUS_COMPLETED)->count();
        $rejectedCount = ServiceRequest::where('status', ServiceRequest::STATUS_REJECTED)->count();
        $totalCount = ServiceRequest::count();

        return [
            Stat::make('Submitted', $submittedCount)
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Under Review', $underReviewCount)
                ->description('In evaluation')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('info'),

            Stat::make('In Progress', $inProgressCount)
                ->description('Active technician work')
                ->descriptionIcon('heroicon-m-wrench')
                ->color('primary'),

            Stat::make('Completed', $completedCount)
                ->description('Successfully resolved')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected', $rejectedCount)
                ->description('Declined requests')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Requests', $totalCount)
                ->description('All time volume')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }
}
