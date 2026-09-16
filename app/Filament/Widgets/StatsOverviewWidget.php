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
        $user = auth()->user();
        $query = ServiceRequest::query();

        if ($user && ! $user->isAdmin()) {
            $query->where('department_id', $user->department_id);
        }

        $submittedCount = (clone $query)->where('status', ServiceRequest::STATUS_SUBMITTED)->count();
        $underReviewCount = (clone $query)->where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count();
        $inProgressCount = (clone $query)->where('status', ServiceRequest::STATUS_IN_PROGRESS)->count();
        $completedCount = (clone $query)->where('status', ServiceRequest::STATUS_COMPLETED)->count();
        $rejectedCount = (clone $query)->where('status', ServiceRequest::STATUS_REJECTED)->count();
        $totalCount = (clone $query)->count();

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
