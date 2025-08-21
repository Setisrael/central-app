<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserActivityOverTimeChart extends ChartWidget
{
    protected static ?string $heading = 'Student Activity Over Time';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '350px';
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

        Log::debug('UserActivityOverTimeChart query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
        ]);

        // Get daily activity data
        $raw = $query->selectRaw('DATE(timestamp) as date, COUNT(*) as total_queries, COUNT(DISTINCT student_id_hash) as unique_students')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        Log::debug('UserActivityOverTimeChart raw data', $raw->toArray());

        // Generate complete date range to fill gaps
        $dates = [];
        $current = Carbon::parse($from)->startOfDay();
        $end = now()->endOfDay();

        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Fill in the data for each date
        $totalQueriesData = [];
        $uniqueStudentsData = [];
        $labels = [];

        foreach ($dates as $date) {
            $dayData = $raw->where('date', $date)->first();
            $totalQueriesData[] = $dayData ? (int) $dayData->total_queries : 0;
            $uniqueStudentsData[] = $dayData ? (int) $dayData->unique_students : 0;
            $labels[] = Carbon::parse($date)->format('M j'); // "Jan 1" format
        }

        $data = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Queries',
                    'data' => $totalQueriesData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => 'rgb(59, 130, 246)',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                ],
                [
                    'label' => 'Active Students',
                    'data' => $uniqueStudentsData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(34, 197, 94)',
                    'pointBorderColor' => 'rgb(34, 197, 94)',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                ]
            ],
        ];

        // Handle empty data case
        if (array_sum($totalQueriesData) === 0) {
            $data = [
                'labels' => [now()->format('M j')],
                'datasets' => [
                    [
                        'label' => 'No Activity',
                        'data' => [0],
                        'borderColor' => 'rgba(156, 163, 175, 0.8)',
                        'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                        'fill' => true,
                    ]
                ],
            ];
        }

        return $data;
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
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date'
                    ],
                    'grid' => [
                        'display' => false
                    ]
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Count'
                    ],
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.1)'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => 'white',
                    'bodyColor' => 'white',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1
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
