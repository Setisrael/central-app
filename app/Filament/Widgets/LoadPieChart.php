<?php

namespace App\Filament\Widgets;

use App\Models\SystemMetric;
use Filament\Widgets\ChartWidget;

class LoadPieChart extends ChartWidget
{
    protected static ?string $heading = 'Server Load per Instance';
    protected static string $color = 'success';
    protected int|string|array $columnSpan = '1/2';

    protected function getData(): array
    {
        $latestMetrics = SystemMetric::query()
            ->latest('timestamp')
            ->get()
            ->groupBy('chatbot_instance_id')
            ->map(fn($group) => $group->first());

        $labels = $latestMetrics->map(fn ($metric) =>
            $metric->chatbotInstance?->name ?? "Unknown ({$metric->chatbot_instance_id})"
        );

        $data = $latestMetrics->pluck('cpu_usage');

        // Generate distinct colors for each instance
        $colors = [];
        $borderColors = [];
        foreach ($latestMetrics as $index => $metric) {
            $hue = ($index * 137) % 360; // Golden angle for distinct colors
            $colors[] = "hsla({$hue}, 70%, 60%, 0.8)";
            $borderColors[] = "hsla({$hue}, 70%, 50%, 1)";
        }

        return [
            'datasets' => [
                [
                    'label' => 'CPU Load',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
