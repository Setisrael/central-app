<?php

/*
namespace App\Filament\Pages;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
use Filament\Pages\Page;

class MetricsDashboard extends Page
{
    protected static string $view = 'filament.pages.metrics-dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?int $navigationSort = 1;

    protected function getViewData(): array
    {
        $tf = request('timeFilter', '7days');
        $if = request('instanceFilter', 'all');

        return [
            'metrics' => $this->getFilteredMetrics($tf, $if),
            'metricsForTable' => $this->getFilteredMetrics($tf),
            'instances' => $this->getInstances(),
            'timeFilter' => $tf,
            'instanceFilter' => $if,
        ];
    }

    protected function getFilteredMetrics(string $timeFilter, string $instanceFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

        if (!auth()->user()->is_admin) {
            $query->whereHas('chatbotInstance.modules.users', function ($q) {
                $q->where('id', auth()->id());
            });
        }

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

        if (auth()->user()->is_admin && $instanceFilter !== 'all') {
            $query->where('chatbot_instance_id', $instanceFilter);
        }

        return $query->get();
    }


    protected function getInstances()
    {
        $query = ChatbotInstance::query()->with('modules.users');

        if (!auth()->user()->is_admin) {
            $query->whereHas('modules.users', function ($q) {
                $q->where('id', auth()->id());
            });
        }

        return $query->get();
    }

    protected function getFilteredMetricsWithComparison(string $timeFilter, string $instanceFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

        if (!auth()->user()->is_admin) {
            $query->whereHas('chatbotInstance.modules.users', fn ($q) =>
            $q->where('id', auth()->id()));
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
            '30days' => $from->copy()->copy()->subDays(30),
            '90days' => $from->copy()->subDays(90),
            '6months' => $from->copy()->subMonths(6),
            default => $from->copy()->subDays(7),
        };

        // current period
        $current = (clone $query)
            ->where('timestamp', '>=', $from)
            ->when($instanceFilter !== 'all', fn ($q) =>
            $q->where('chatbot_instance_id', $instanceFilter)
            )
            ->get();

        // previous period
        $previous = (clone $query)
            ->whereBetween('timestamp', [$previousFrom, $from])
            ->when($instanceFilter !== 'all', fn ($q) =>
            $q->where('chatbot_instance_id', $instanceFilter)
            )
            ->get();

        return [$current, $previous];
    }

}*/


/*namespace App\Filament\Pages;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
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
        $tf = request('timeFilter', '7days');
        $if = request('instanceFilter', 'all');

        // Fetch current and previous metrics with trends
        [$currentMetrics, $previousMetrics, $trends] = $this->getFilteredMetricsWithComparison($tf, $if);

        return [
            'metrics' => $currentMetrics,
            'metricsForTable' => $this->getFilteredMetrics($tf),
            'previousMetrics' => $previousMetrics,
            'trends' => $trends, // Pass trends to Blade
            'instances' => $this->getInstances(),
            'timeFilter' => $tf,
            'instanceFilter' => $if,
        ];
    }

    protected function getFilteredMetrics(string $timeFilter, string $instanceFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

        if (!auth()->user()->is_admin) {
            $query->whereHas('chatbotInstance.modules.users', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

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

        if (auth()->user()->is_admin && $instanceFilter !== 'all') {
            $query->where('chatbot_instance_id', $instanceFilter);
        }

        return $query->get();
    }

    protected function getInstances()
    {
        $query = ChatbotInstance::query()->with('modules.users');

        if (!auth()->user()->is_admin) {
            $query->whereHas('modules.users', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        return $query->get();
    }

    protected function getFilteredMetricsWithComparison(string $timeFilter, string $instanceFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

        if (!auth()->user()->is_admin) {
            $query->whereHas('chatbotInstance.modules.users', function ($q) {
                $q->where('users.id', auth()->id());
            });
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
        $currentQuery = (clone $query)
            ->where('timestamp', '>=', $from);

        if (auth()->user()->is_admin && $instanceFilter !== 'all') {
            $currentQuery->where('chatbot_instance_id', $instanceFilter);
        }

        $current = $currentQuery->get();

        // Previous period
        $previousQuery = (clone $query)
            ->whereBetween('timestamp', [$previousFrom, $from]);

        if (auth()->user()->is_admin && $instanceFilter !== 'all') {
            $previousQuery->where('chatbot_instance_id', $instanceFilter);
        }

        $previous = $previousQuery->get();

        // Calculate trends per instance
        $trends = [];
        $currentGrouped = $current->groupBy('chatbot_instance_id');
        $previousGrouped = $previous->groupBy('chatbot_instance_id');

        foreach ($currentGrouped as $instanceId => $group) {
            $totalRequests = $group->count();
            $previousCount = $previousGrouped->get($instanceId)?->count() ?? 0;

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

            $trends[$instanceId] = [
                'trend' => $trend,
                'percentage_change' => number_format($percentageChange, 1),
                'total_requests' => $totalRequests,
                'previous_count' => $previousCount,
            ];
        }

        // Include instances with data only in previous period
        foreach ($previousGrouped as $instanceId => $group) {
            if (!isset($trends[$instanceId])) {
                $totalRequests = 0;
                $previousCount = $group->count();

                $trend = match (true) {
                    $totalRequests === 0 && $previousCount > 0 => 'down',
                    default => 'flat'
                };

                $percentageChange = $previousCount > 0 ? -100 : 0;

                $trends[$instanceId] = [
                    'trend' => $trend,
                    'percentage_change' => number_format($percentageChange, 1),
                    'total_requests' => $totalRequests,
                    'previous_count' => $previousCount,
                ];
            }
        }

        return [$current, $previous, $trends];
    }
}*/


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
            'modules' => $this->getModules(),
            'timeFilter' => $timeFilter,
            'moduleFilter' => $moduleFilter,
        ];
    }

    protected function getFilteredMetrics(string $timeFilter, string $moduleFilter = 'all')
    {
        $query = MetricUsage::query()->with('chatbotInstance');

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

        // Apply module filter
        if ($moduleFilter !== 'all') {
            $query->where('module_id', $moduleFilter);
        }

        return $query->get();
    }

    protected function getModules()
    {
        if (auth()->user()->is_admin) {
            // Admin sees all modules
            $modules = Module::orderBy('name')->pluck('name', 'id')->toArray();
        } else {
            // Non-admin sees only their assigned modules
            $moduleIds = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_id')
                ->toArray();

            if (!empty($moduleIds)) {
                $modules = Module::whereIn('id', $moduleIds)
                    ->orderBy('name')
                    ->pluck('name', 'id')
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
            $currentQuery->where('module_id', $moduleFilter);
        }
        $current = $currentQuery->get();

        // Previous period
        $previousQuery = (clone $query)->whereBetween('timestamp', [$previousFrom, $from]);
        if ($moduleFilter !== 'all') {
            $previousQuery->where('module_id', $moduleFilter);
        }
        $previous = $previousQuery->get();

        // Calculate trends per instance (but filtered by modules)
        $trends = [];
        $currentGrouped = $current->groupBy('module_id');
        $previousGrouped = $previous->groupBy('module_id');

        foreach ($currentGrouped as $moduleId=> $group) {
            $totalRequests = $group->count();
            $previousCount = $previousGrouped->get($moduleId)?->count() ?? 0;

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

            $trends[$moduleId] = [
                'trend' => $trend,
                'percentage_change' => number_format($percentageChange, 1),
                'total_requests' => $totalRequests,
                'previous_count' => $previousCount,
            ];
        }

        // Include instances with data only in previous period
        foreach ($previousGrouped as $moduleId => $group) {
            if (!isset($trends[$moduleId])) {
                $totalRequests = 0;
                $previousCount = $group->count();

                $trend = match (true) {
                    $totalRequests === 0 && $previousCount > 0 => 'down',
                    default => 'flat'
                };

                $percentageChange = $previousCount > 0 ? -100 : 0;

                $trends[$moduleId] = [
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


