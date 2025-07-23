<?php
/*
//namespace App\Filament\Admin\Widgets;

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RequestChart extends ChartWidget
{
    protected static ?string $heading = 'Requests Over Time';
    protected static string $color = 'primary';

    // 🛠️ Display side-by-side in dashboard layout
    protected int|string|array $columnSpan = '1/2';

    protected function getData(): array
    {
        $instanceFilter = request('instanceFilter', 'all');

        // 🛠️ Use selected time range
        $from = match (request('timeFilter')) {
            '30days' => now()->subDays(30)->startOfDay(),
            '90days' => now()->subDays(90)->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        $query = MetricUsage::query()->where('timestamp', '>=', $from);

        if ($instanceFilter !== 'all') {
            $query->where('chatbot_instance_id', $instanceFilter);
        }

        // Group actual metric data by day
        $rawData = $query->get()
            ->groupBy(fn ($m) => $m->timestamp->format('Y-m-d'))
            ->map(fn ($group) => $group->count());

        // Pad with 0s for missing days
        $dateRange = collect();
        $current = $from->copy();
        $today = now()->startOfDay();

        while ($current <= $today) {
            $dateRange->put($current->format('Y-m-d'), 0);
            $current->addDay();
        }

        $paddedData = $dateRange->merge($rawData)->sortKeys();

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => $paddedData->values()->toArray(),
                ],
            ],
            'labels' => $paddedData->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): ?array
    {
        return [
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
            ],
        ];

    }
}*/


/*namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RequestChart extends ChartWidget
{
    protected static ?string $heading = 'Requests Over Time';
    protected static string $color = 'primary';
    protected int|string|array $columnSpan = '1/2';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $filter = request('timeFilter', '7days');

        $from = match ($filter) {
            '1day' => now()->subDay(),
            '3days' => now()->subDays(3),
            '5days' => now()->subDays(5),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
           // default => now()->subDays(7),
        };

        $query = MetricUsage::query()
            ->where('timestamp', '>=', Carbon::parse($from));

        if (($instance = request('instanceFilter', 'all')) !== 'all') {
            $query->where('chatbot_instance_id', $instance);
        }

        $raw = $query->get()->groupBy(function ($m) use ($filter) {
            return match ($filter) {
                '1day' => $m->timestamp->format('H:00'),
                '3days', '5days' => $m->timestamp->floorMinute(180)->format('d M H:00'),
                '7days', '30days' => $m->timestamp->format('Y-m-d'),
                '90days', '6months' => $m->timestamp->startOfWeek()->format('Y-W'),
                default => $m->timestamp->format('Y-m-d'),
            };
        })
            ->map(fn($g) => $g->count());

        $labels = $raw->keys()->sort()->values();
        $data = $raw->values()->toArray();

        return [
            'datasets' => [['label' => 'Requests', 'data' => $data]],
            'labels' => $labels->toArray(),
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
                'point' => ['radius' => 3],
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
                    'offset' => false, // 👈 important to avoid trimming
                    'grid' => ['drawOnChartArea' => false],
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'align' => 'start', // 👈 keep left label visible
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

}*/


namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RequestChart extends ChartWidget
{
    protected static ?string $heading = 'Requests Over Time';
    protected static string $color = 'primary';
    protected int|string|array $columnSpan = '1/2';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $timeFilter = request('timeFilter', '7days');
        $moduleFilter = request('moduleFilter', 'all');

        $from = match ($timeFilter) {
            '1day' => now()->subDay(),
            '3days' => now()->subDays(3),
            '5days' => now()->subDays(5),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            default => now()->subDays(7),
        };

        $query = MetricUsage::query()->where('timestamp', '>=', Carbon::parse($from));

        // Apply user role restrictions
        if (!auth()->user()->is_admin) {
            // Get user's module IDs from the module_user pivot table
            $userModuleIds = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_id')
                ->toArray();

            if (!empty($userModuleIds)) {
                $query->whereIn('module_id', $userModuleIds);
            } else {
                // If user has no modules, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // Apply module filter
        if ($moduleFilter !== 'all') {
            $query->where('module_id', $moduleFilter);
        }

        $raw = $query->get()->groupBy(function ($m) use ($timeFilter) {
            return match ($timeFilter) {
                '1day' => $m->timestamp->format('H:00'),
                '3days', '5days' => $m->timestamp->floorMinute(180)->format('d M H:00'),
                '7days', '30days' => $m->timestamp->format('Y-m-d'),
                '90days', '6months' => $m->timestamp->startOfWeek()->format('Y-W'),
                default => $m->timestamp->format('Y-m-d'),
            };
        })
            ->map(fn($g) => $g->count());

        $labels = $raw->keys()->sort()->values();
        $data = $raw->values()->toArray();

        return [
            'datasets' => [['label' => 'Requests', 'data' => $data]],
            'labels' => $labels->toArray(),
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
                'point' => ['radius' => 3],
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
                    'offset' => false,
                    'grid' => ['drawOnChartArea' => false],
                    'ticks' => [
                        'autoSkip' => false,
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'align' => 'start',
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}

