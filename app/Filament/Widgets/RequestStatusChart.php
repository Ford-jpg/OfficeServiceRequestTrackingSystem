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
        $submitted = ServiceRequest::where('status', ServiceRequest::STATUS_SUBMITTED)->count();
        $underReview = ServiceRequest::where('status', ServiceRequest::STATUS_UNDER_REVIEW)->count();
        $inProgress = ServiceRequest::where('status', ServiceRequest::STATUS_IN_PROGRESS)->count();
        $completed = ServiceRequest::where('status', ServiceRequest::STATUS_COMPLETED)->count();
        $rejected = ServiceRequest::where('status', ServiceRequest::STATUS_REJECTED)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => [$submitted, $underReview, $inProgress, $completed, $rejected],
                    'backgroundColor' => [
                        '#F59E0B', // Amber (Submitted)
                        '#6366F1', // Indigo (Under Review)
                        '#0EA5E9', // Sky (In Progress)
                        '#10B981', // Emerald (Completed)
                        '#F43F5E', // Rose (Rejected)
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
