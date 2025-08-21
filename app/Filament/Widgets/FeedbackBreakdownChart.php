<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class FeedbackBreakdownChart extends ChartWidget
{
    protected static ?string $heading = 'Feedback Breakdown';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 1;
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

            Log::debug('Non-admin user module access', [
                'user_id' => auth()->id(),
                'user_module_codes' => $userModuleCodes,
            ]);

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

        Log::debug('FeedbackBreakdownChart query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
            'from_date' => $from->toDateTimeString(),
            'user_id' => auth()->id(),
            'is_admin' => auth()->user()->is_admin,
        ]);

        // Get the actual counts - PostgreSQL safe approach
        $helpful = (int)$query->clone()->where('helpful', true)->count();
        $notHelpful = (int)$query->clone()->where('helpful', false)->count();

        // Calculate unrated by subtracting rated from total
        $totalRecords = $query->clone()->count();
        $totalUnrated = $totalRecords - $helpful - $notHelpful;

        Log::debug('FeedbackBreakdownChart counts', [
            'helpful' => $helpful,
            'notHelpful' => $notHelpful,
            'totalUnrated' => $totalUnrated,
            'totalRecords' => $totalRecords,
            'sum_check' => $helpful + $notHelpful + $totalUnrated,
        ]);

        // If no data, return empty chart
        if ($totalRecords === 0) {
            return [
                'datasets' => [
                    [
                        'label' => 'Feedback',
                        'data' => [0, 0, 0],
                        'backgroundColor' => ['#e5e7eb', '#e5e7eb', '#e5e7eb'],
                        'borderColor' => ['#d1d5db', '#d1d5db', '#d1d5db'],
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => ['No Data', 'No Data', 'No Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Feedback',
                    'data' => [$helpful, $notHelpful, $totalUnrated],
                    'backgroundColor' => ['#22c55e', '#ef4444', '#6b7280'],
                    'borderColor' => ['#16a34a', '#dc2626', '#4b5563'],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['Helpful', 'Not Helpful', 'Unrated'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'color' => '#374151',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'backgroundColor' => '#1f2937',
                    'titleFont' => ['size' => 14],
                    'bodyFont' => ['size' => 12],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
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
