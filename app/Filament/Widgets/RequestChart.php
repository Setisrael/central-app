<?php

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

        // Apply user role restrictions using module_code
        if (!auth()->user()->is_admin) {
            // Get user's module codes from the module_user pivot table
            $userModuleCodes = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (!empty($userModuleCodes)) {
                $query->whereIn('module_code', $userModuleCodes);
            } else {
                // If user has no modules, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // Apply module filter using module_code
        if ($moduleFilter !== 'all') {
            $query->where('module_code', $moduleFilter);
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
