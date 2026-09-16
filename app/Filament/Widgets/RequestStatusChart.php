<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;

class RequestStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Requests by Status';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $user = auth()->user();
        $query = ServiceRequest::query();

        if ($user && ! $user->isAdmin()) {
            $query->where('department_id', $user->department_id);
        }

        $submitted = (clone $query)->where('status', ServiceRequest::STATUS_SUBMITTED)->count();
        $underReview = (clone $query)->where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count();
        $inProgress = (clone $query)->where('status', ServiceRequest::STATUS_IN_PROGRESS)->count();
        $completed = (clone $query)->where('status', ServiceRequest::STATUS_COMPLETED)->count();
        $rejected = (clone $query)->where('status', ServiceRequest::STATUS_REJECTED)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => [$submitted, $underReview, $inProgress, $completed, $rejected],
                    'backgroundColor' => [
                        '#F59E0B',
                        '#6366F1',
                        '#0EA5E9',
                        '#10B981',
                        '#F43F5E',
                    ],
                ],
            ],
            'labels' => ['Submitted', 'Under Review', 'In Progress', 'Completed', 'Rejected'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
