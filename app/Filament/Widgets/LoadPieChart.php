<?php

// app/Filament/Widgets/LoadPieChart.php

namespace App\Filament\Widgets;

use App\Models\SystemMetric;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class LoadPieChart extends ChartWidget
{
    protected static ?string $heading = 'Server Load per Instance';
    protected static string $color = 'success';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $from = now()->subDays(7);

        $latestMetrics = SystemMetric::query()
            ->where('created_at', '>=', $from)
            ->latest('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn($group) => $group->first());

        $labels = $latestMetrics->map(fn ($metric) =>
            $metric->chatbotInstance?->name ?? "Unknown ({$metric->user_id})"
        );

        $data = $latestMetrics->pluck('cpu_usage');

        return [
            'datasets' => [
                [
                    'label' => 'CPU Load',
                    'data' => $data,
                ],
            ],
            'labels' => $labels->values(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
