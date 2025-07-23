<?php

// app/Filament/Widgets/LoadPieChart.php

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
       /* $from = now()->subDays(7);
        $from = match (request('timeFilter')) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
           // default => now()->subDays(7),
        };*/

        $latestMetrics = SystemMetric::query()
            //->where('timestamp', '>=', $from)
            ->latest('timestamp')
            ->get()
            ->groupBy('chatbot_instance_id')
            ->map(fn($group) => $group->first());

        $labels = $latestMetrics->map(fn ($metric) =>
            $metric->chatbotInstance?->name ?? "Unknown ({$metric->chatbot_instance_id})"
        );

        $data = $latestMetrics->pluck('cpu_usage');

        return [
            'datasets' => [
                [
                    'label' => 'CPU Load',
                    'data' => $data->values()->toArray(),
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

