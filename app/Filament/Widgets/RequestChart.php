<?php

//namespace App\Filament\Admin\Widgets;
namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use Filament\Widgets\ChartWidget;

class RequestChart extends ChartWidget
{
    protected static ?string $heading = 'Requests Over Time';
    protected static string $color = 'primary';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $instanceFilter = request('instanceFilter', 'all');
        $from = match (request('timeFilter')) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };

        $query = MetricUsage::query()
            ->where('created_at', '>=', $from);

        if ($instanceFilter !== 'all') {
            $query->where('user_id', $instanceFilter);
        }

        $data = $query->get()
            ->groupBy(fn ($m) => $m->created_at->format('Y-m-d'))
            ->map(fn ($g) => $g->count());

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => $data->values(),
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}


