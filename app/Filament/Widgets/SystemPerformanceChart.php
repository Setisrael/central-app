<?php

namespace App\Filament\Widgets;

use App\Models\SystemMetric;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SystemPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'System Performance';
    protected static string $color = 'info';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $timeFilter = request('timeFilter', '24hours');
        $instanceFilter = request('instanceFilter', 'all');

        $from = match ($timeFilter) {
            '1hour' => now()->subHour(),
            '6hours' => now()->subHours(6),
            '12hours' => now()->subHours(12),
            '24hours' => now()->subDay(),
            '3days' => now()->subDays(3),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'all' => now()->subYears(10),
            default => now()->subDay(),
        };

        $query = SystemMetric::query()
            ->where('timestamp', '>=', Carbon::parse($from))
            ->orderBy('timestamp');

        if ($instanceFilter !== 'all') {
            $query->where('chatbot_instance_id', $instanceFilter);
        }

        $metrics = $query->get();

        // No data → return empty chart
        if ($metrics->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'CPU Usage (%)',
                        'data' => [],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    ],
                    [
                        'label' => 'Disk Usage (%)',
                        'data' => [],
                        'borderColor' => 'rgb(245, 158, 11)',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    ],
                ],
                'labels' => [],
            ];
        }

        // Group values depending on time filter
        $groupedMetrics = $metrics->groupBy(function ($metric) use ($timeFilter) {
            $timestamp = Carbon::parse($metric->timestamp);

            return match ($timeFilter) {
                '1hour', '6hours' => $timestamp->format('H:i'),
                '12hours', '24hours' => $timestamp->format('H:00'),
                '3days', '7days' => $timestamp->format('M j H:00'),
                '30days', '90days', 'all' => $timestamp->format('M j'),
                default => $timestamp->format('H:00'),
            };
        })->map(function ($group) {
            return [
                'cpu'  => $group->avg('cpu_usage'),
                'disk' => $group->avg('disk_usage'),
            ];
        });

        $labels = $groupedMetrics->keys()->toArray();
        $cpuData = $groupedMetrics->pluck('cpu')->toArray();
        $diskData = $groupedMetrics->pluck('disk')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'CPU Usage (%)',
                    'data' => $cpuData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Disk Usage (%)',
                    'data' => $diskData,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'animation' => false,
            'elements' => [
                'line' => ['tension' => 0.3],
                'point' => ['radius' => 2],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'type' => 'category',
                    'grid' => ['drawOnChartArea' => false],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Usage %',
                    ],
                ],
            ],
        ];
    }
}
