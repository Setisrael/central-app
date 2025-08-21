<?php

namespace App\Filament\Pages;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
use App\Models\Module;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class MetricsDashboard extends Page
{
    protected static string $view = 'filament.pages.metrics-dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?int $navigationSort = 1;

    protected function getViewData(): array
    {
        $timeFilter = request('timeFilter', '7days');
        $moduleFilter = request('moduleFilter', 'all');

        // Fetch current and previous metrics with trends
        [$currentMetrics, $previousMetrics, $trends] = $this->getFilteredMetricsWithComparison($timeFilter, $moduleFilter);

        return [
            'metrics' => $currentMetrics,
            'metricsForTable' => $this->getFilteredMetrics($timeFilter, $moduleFilter),
            'previousMetrics' => $previousMetrics,
            'trends' => $trends,
            'modules' => $this->getModules(), // FIXED: Now uses the same logic as UserActivity
            'timeFilter' => $timeFilter,
            'moduleFilter' => $moduleFilter,
        ];
    }

    protected function getFilteredMetrics(string $timeFilter, string $moduleFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

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

        // Apply time filter
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

        $query->where('timestamp', '>=', $from);

        // Apply module filter using module_code
        if ($moduleFilter !== 'all') {
            $query->where('module_code', $moduleFilter);
        }

        return $query->get();
    }

    // FIXED: Added the same getModules() method as UserActivity page
    protected function getModules()
    {
        if (auth()->user()->is_admin) {
            // Admin sees all modules, use code as key
            $modules = Module::orderBy('name')->pluck('name', 'code')->toArray();
        } else {
            // Non-admin sees only their assigned modules
            $moduleCodes = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (!empty($moduleCodes)) {
                $modules = Module::whereIn('code', $moduleCodes)
                    ->orderBy('name')
                    ->pluck('name', 'code')
                    ->toArray();
            } else {
                $modules = [];
            }
        }

        return ['all' => 'All Modules'] + $modules;
    }

    protected function getFilteredMetricsWithComparison(string $timeFilter, string $moduleFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

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

        $now = now();
        $from = match ($timeFilter) {
            '1day' => $now->copy()->subDay(),
            '3days' => $now->copy()->subDays(3),
            '5days' => $now->copy()->subDays(5),
            '7days' => $now->copy()->subDays(7),
            '30days' => $now->copy()->subDays(30),
            '90days' => $now->copy()->subDays(90),
            '6months' => $now->copy()->subMonths(6),
            default => $now->copy()->subDays(7),
        };

        $previousFrom = match ($timeFilter) {
            '1day' => $from->copy()->subDay(),
            '3days' => $from->copy()->subDays(3),
            '5days' => $from->copy()->subDays(5),
            '7days' => $from->copy()->subDays(7),
            '30days' => $from->copy()->subDays(30),
            '90days' => $from->copy()->subDays(90),
            '6months' => $from->copy()->subMonths(6),
            default => $from->copy()->subDays(7),
        };

        // Current period
        $currentQuery = (clone $query)->where('timestamp', '>=', $from);
        if ($moduleFilter !== 'all') {
            $currentQuery->where('module_code', $moduleFilter);
        }
        $current = $currentQuery->get();

        // Previous period
        $previousQuery = (clone $query)->whereBetween('timestamp', [$previousFrom, $from]);
        if ($moduleFilter !== 'all') {
            $previousQuery->where('module_code', $moduleFilter);
        }
        $previous = $previousQuery->get();

        // Calculate trends per module using module_code
        $trends = [];
        $currentGrouped = $current->groupBy('module_code');
        $previousGrouped = $previous->groupBy('module_code');

        foreach ($currentGrouped as $moduleCode => $group) {
            $totalRequests = $group->count();
            $previousCount = $previousGrouped->get($moduleCode)?->count() ?? 0;

            $trend = match (true) {
                $previousCount === 0 && $totalRequests > 0 => 'up',
                $previousCount === 0 && $totalRequests === 0 => 'flat',
                $totalRequests === 0 && $previousCount > 0 => 'down',
                $totalRequests > $previousCount => 'up',
                $totalRequests < $previousCount => 'down',
                default => 'flat'
            };

            $percentageChange = $previousCount > 0
                ? (($totalRequests - $previousCount) / $previousCount * 100)
                : ($totalRequests > 0 ? 100 : 0);

            $trends[$moduleCode] = [
                'trend' => $trend,
                'percentage_change' => number_format($percentageChange, 1),
                'total_requests' => $totalRequests,
                'previous_count' => $previousCount,
            ];
        }

        // Include modules with data only in previous period
        foreach ($previousGrouped as $moduleCode => $group) {
            if (!isset($trends[$moduleCode])) {
                $totalRequests = 0;
                $previousCount = $group->count();

                $trend = match (true) {
                    $totalRequests === 0 && $previousCount > 0 => 'down',
                    default => 'flat'
                };

                $percentageChange = $previousCount > 0 ? -100 : 0;

                $trends[$moduleCode] = [
                    'trend' => $trend,
                    'percentage_change' => number_format($percentageChange, 1),
                    'total_requests' => $totalRequests,
                    'previous_count' => $previousCount,
                ];
            }
        }

        return [$current, $previous, $trends];
    }
}
