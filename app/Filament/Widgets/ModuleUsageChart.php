<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class ModuleUsageChart extends ChartWidget
{
    protected static ?string $heading = 'Module Usage Distribution';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '350px';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 1;

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

        // Start with base query joining with modules table
        $query = MetricUsage::query()
            ->join('modules', 'metric_usages.module_id', '=', 'modules.id')
            ->where('metric_usages.timestamp', '>=', $from);

        // Apply user role restrictions
        if (!auth()->user()->is_admin) {
            // Get user's module IDs from the module_user pivot table
            $userModuleIds = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_id')
                ->toArray();

            if (!empty($userModuleIds)) {
                $query->whereIn('metric_usages.module_id', $userModuleIds);
            } else {
                // If user has no modules, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // Apply module filter - if specific module selected, only show that one
        if ($this->moduleFilter !== 'all') {
            $query->where('metric_usages.module_id', $this->moduleFilter);
        }

        Log::debug('ModuleUsageChart query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
        ]);

        // Get module usage counts
        $raw = $query->selectRaw('modules.name as module_name, COUNT(*) as count')
            ->groupBy('modules.id', 'modules.name')
            ->orderByDesc('count')
            ->get();

        Log::debug('ModuleUsageChart raw data', $raw->toArray());

        // Generate vibrant colors for each module
        $colors = [];
        $borderColors = [];
        foreach ($raw as $index => $item) {
            $hue = ($index * 137) % 360; // Golden angle distribution for distinct colors
            $colors[] = "hsla({$hue}, 70%, 60%, 0.8)";
            $borderColors[] = "hsla({$hue}, 70%, 50%, 1)";
        }

        $data = [
            'datasets' => [
                [
                    'data' => $raw->pluck('count')->map(fn ($count) => (int) $count)->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $raw->pluck('module_name')->map(fn ($name) => (string) $name)->toArray(),
        ];

        // Handle empty data case
        if (empty($data['datasets'][0]['data']) || array_sum($data['datasets'][0]['data']) === 0) {
            $data = [
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['rgba(156, 163, 175, 0.5)'],
                    'borderColor' => ['rgba(156, 163, 175, 0.8)'],
                    'borderWidth' => 2,
                ]],
                'labels' => ['No Data Available'],
            ];
        }

        Log::debug('ModuleUsageChart final data:', $data);
        return $data;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                    ]
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => null // Use default label formatting
                    ]
                ]
            ],
            'cutout' => '50%', // Makes it a doughnut instead of pie
        ];
    }

    public function getDescription(): ?string
    {
        $moduleName = 'All Modules';
        if ($this->moduleFilter !== 'all') {
            $module = Module::find($this->moduleFilter);
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
