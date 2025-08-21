<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class ActivityHeatmap extends ChartWidget
{
    protected static ?string $heading = 'Activity by Day and Hour';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '400px';
    protected static bool $isLazy = false;

    public string $timeFilter = '90days';
    public string $moduleFilter = 'all';

    public function mount($timeFilter = '90days', $moduleFilter = 'all'): void
    {
        $this->timeFilter = $timeFilter;
        $this->moduleFilter = $moduleFilter;
    }

    protected function getData(): array
    {
        $from = match ($this->timeFilter) {
            '1day' => now()->subDay(),
            '3days' => now()->subDays(3),
            '5days' => now()->subDays(5),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            default => now()->subDays(90),
        };

        // Start with base query
        $query = MetricUsage::query()->where('timestamp', '>=', $from);

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
        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        Log::debug('ActivityHeatmap query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
        ]);

        // Get activity data grouped by day of week and hour
        $raw = $query->selectRaw('EXTRACT(DOW FROM timestamp) as day_of_week, EXTRACT(HOUR FROM timestamp) as hour, COUNT(*) as count')
            ->groupBy('day_of_week', 'hour')
            ->orderBy('day_of_week')
            ->orderBy('hour')
            ->get();

        // Days of week (0 = Sunday, 1 = Monday, etc.)
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $hours = range(0, 23);
        $datasets = [];

        // Create a dataset for each day of the week
        foreach ($days as $dayIndex => $day) {
            $data = [];
            foreach ($hours as $hour) {
                $count = $raw->where('day_of_week', $dayIndex)->where('hour', $hour)->first()->count ?? 0;
                $data[] = (int) $count;
            }

            // Generate distinct colors for each day
            $hue = ($dayIndex * 51) % 360; // Spread colors across hue spectrum
            $datasets[] = [
                'label' => $day,
                'data' => $data,
                'backgroundColor' => "hsla({$hue}, 70%, 60%, 0.7)",
                'borderColor' => "hsla({$hue}, 70%, 50%, 1)",
                'borderWidth' => 1,
            ];
        }

        // If no data, return empty visualization
        if (empty($datasets)) {
            return [
                'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
                'datasets' => [[
                    'label' => 'No Activity',
                    'data' => array_fill(0, 24, 0),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                    'borderColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderWidth' => 1,
                ]],
            ];
        }

        // Check if all datasets have zero data
        $hasData = false;
        foreach ($datasets as $dataset) {
            if (array_sum($dataset['data']) > 0) {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            return [
                'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
                'datasets' => [[
                    'label' => 'No Activity',
                    'data' => array_fill(0, 24, 0),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                    'borderColor' => 'rgba(156, 163, 175, 0.5)',
                    'borderWidth' => 1,
                ]],
            ];
        }

        return [
            'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Hour of Day'
                    ]
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Queries'
                    ],
                    'beginAtZero' => true
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top'
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Student Activity Patterns by Day and Hour'
                ]
            ]
        ];
    }

    public function getDescription(): ?string
    {
        $moduleName = 'All Modules';
        if ($this->moduleFilter !== 'all') {
            $module = Module::where('code', $this->moduleFilter)->first();
            $moduleName = $module ? $module->name : 'Unknown Module';
        }

        $timeLabel = match ($this->timeFilter) {
            '1day' => 'Last 1 Day',
            '3days' => 'Last 3 Days',
            '5days' => 'Last 5 Days',
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
            '90days' => 'Last 90 Days',
            '6months' => 'Last 6 Months',
            default => 'Last 90 Days',
        };

        return "📊 {$timeLabel} • 📚 {$moduleName}";
    }
}
