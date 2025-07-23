<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class UserStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    public string $timeFilter = '90days';
    public string $moduleFilter = 'all';

    public function mount($timeFilter = '90days', $moduleFilter = 'all'): void
    {
        $this->timeFilter = $timeFilter;
        $this->moduleFilter = $moduleFilter;
    }

    protected function getStats(): array
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

        // Apply user role restrictions
        if (!auth()->user()->is_admin) {
            // Get user's module IDs from the module_user pivot table
            $userModuleIds = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_id')
                ->toArray();

            Log::debug('Non-admin user module access', [
                'user_id' => auth()->id(),
                'user_module_ids' => $userModuleIds,
            ]);

            if (!empty($userModuleIds)) {
                $query->whereIn('module_id', $userModuleIds);
            } else {
                // If user has no modules, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        // Apply module filter using direct module_id
        if ($this->moduleFilter !== 'all') {
            $query->where('module_id', $this->moduleFilter);
        }

        Log::debug('UserStatsOverview query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'timeFilter' => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
            'from_date' => $from->toDateTimeString(),
            'user_id' => auth()->id(),
            'is_admin' => auth()->user()->is_admin,
        ]);

        // Calculate metrics
        $activeStudents = (int) $query->clone()->distinct('student_id_hash')->count('student_id_hash');
        $totalQueries = (int) $query->clone()->count();
        $avgQueries = $activeStudents > 0 ? round($totalQueries / $activeStudents, 1) : 0.0;

        // Most active student
        $mostActiveStudent = $query->clone()
            ->selectRaw('student_id_hash, count(*) as query_count')
            ->groupBy('student_id_hash')
            ->orderByDesc('query_count')
            ->first()?->student_id_hash ?? 'None';

        // Most queried document
        $mostQueriedDocument = $query->clone()
            ->selectRaw('document_id, count(*) as query_count')
            ->groupBy('document_id')
            ->orderByDesc('query_count')
            ->first()?->document_id ?? 'None';

        // Helpful ratio - handle PostgreSQL boolean field properly
        $helpfulCount = (int) $query->clone()->where('helpful', true)->count();
        $notHelpfulCount = (int) $query->clone()->where('helpful', false)->count();
        $ratedCount = $helpfulCount + $notHelpfulCount;
        $helpfulRatio = $ratedCount > 0 ? round(($helpfulCount / $ratedCount) * 100, 1) : 0.0;

        Log::debug('UserStatsOverview counts', [
            'activeStudents' => $activeStudents,
            'totalQueries' => $totalQueries,
            'helpfulCount' => $helpfulCount,
            'notHelpfulCount' => $notHelpfulCount,
            'ratedCount' => $ratedCount,
            'helpfulRatio' => $helpfulRatio,
        ]);

        return [
            Stat::make('Active Students', $activeStudents)
                ->description($this->getFilterDescription())
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Avg Queries per Student', $avgQueries)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Most Active Student', $mostActiveStudent)
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Most Queried Document', $mostQueriedDocument)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Helpful Ratio', $helpfulRatio . '%')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color($helpfulRatio >= 70 ? 'success' : ($helpfulRatio >= 50 ? 'warning' : 'danger')),
        ];
    }

    protected function getFilterDescription(): string
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

        return "📅 {$timeLabel} • 📚 {$moduleName}";
    }
}
/*namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null; // Disable polling

    protected function getStats(): array
    {
        $timeFilter = $this->filters['timeFilter'] ?? '90days';
        $instanceFilter = auth()->user()->is_admin ? ($this->filters['instanceFilter'] ?? 'all') : 'all';

        $from = match ($timeFilter) {
            '1day' => now()->subDay(),
            '3days' => now()->subDays(3),
            '5days' => now()->subDays(5),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '6months' => now()->subMonths(6),
            default => now()->subDays(90),
        };

        $cacheKey = "user_stats_overview_{$timeFilter}_{$instanceFilter}_" . auth()->id();
        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($from, $instanceFilter) {
            // Create a fresh query for each metric to avoid state issues
            $baseQuery = MetricUsage::query()->where('timestamp', '>=', $from);
            if (!auth()->user()->is_admin) {
                \Log::debug('Non-admin user ID:', [auth()->id()]);
                $baseQuery->whereHas('chatbotInstance.modules.users', function ($q) {
                    $q->where('users.id', auth()->id());
                    \Log::debug('whereHas query:', [$q->toSql(), $q->getBindings()]);
                });
            }
            if ($instanceFilter !== 'all' && auth()->user()->is_admin) {
                $baseQuery->where('chatbot_instance_id', $instanceFilter);
            }

            \Log::debug('UserStatsOverview query:', [$baseQuery->toSql(), $baseQuery->getBindings()]);
            \Log::debug('MetricUsage raw data:', [$baseQuery->get()->toArray()]);
            \Log::debug('ModuleUser pivot data:', [DB::table('module_user')->where('user_id', auth()->id())->get()->toArray()]);

            $activeStudents = (int) $baseQuery->clone()->distinct('student_id_hash')->count('student_id_hash');
            $totalQueries = (int) $baseQuery->clone()->count('*');
            $avgQueries = $activeStudents > 0 ? round($totalQueries / $activeStudents, 1) : 0.0;

            $mostActiveStudent = MetricUsage::query()
                ->where('timestamp', '>=', $from)
                ->when(!auth()->user()->is_admin, fn ($q) => $q->whereHas('chatbotInstance.modules.users', fn ($q) => $q->where('users.id', auth()->id())))
                ->when($instanceFilter !== 'all' && auth()->user()->is_admin, fn ($q) => $q->where('chatbot_instance_id', $instanceFilter))
                ->selectRaw('student_id_hash, count(*) as query_count')
                ->groupBy('student_id_hash')
                ->orderByDesc('query_count')
                ->orderBy('student_id_hash')
                ->first()?->student_id_hash ?? 'None';

            $mostQueriedDocument = MetricUsage::query()
                ->where('timestamp', '>=', $from)
                ->when(!auth()->user()->is_admin, fn ($q) => $q->whereHas('chatbotInstance.modules.users', fn ($q) => $q->where('users.id', auth()->id())))
                ->when($instanceFilter !== 'all' && auth()->user()->is_admin, fn ($q) => $q->where('chatbot_instance_id', $instanceFilter))
                ->selectRaw('document_id, count(*) as query_count')
                ->groupBy('document_id')
                ->orderByDesc('query_count')
                ->orderBy('document_id')
                ->first()?->document_id ?? 'None';

            $helpfulCount = (int) $baseQuery->clone()->where('helpful', true)->count();
            $notHelpfulCount = (int) $baseQuery->clone()->where('helpful', false)->count();
            $ratedCount = $helpfulCount + $notHelpfulCount;
            $helpfulRatio = $ratedCount > 0 ? round(($helpfulCount / $ratedCount) * 100, 1) : 0.0;

            \Log::debug('UserStatsOverview counts:', compact('activeStudents', 'totalQueries', 'helpfulCount', 'notHelpfulCount', 'ratedCount'));

            return compact('activeStudents', 'avgQueries', 'mostActiveStudent', 'mostQueriedDocument', 'helpfulRatio');
        });

        \Log::debug('UserStatsOverview data:', $stats);

        return [
            Stat::make('Active Students', $stats['activeStudents']),
            Stat::make('Avg Queries per Student', $stats['avgQueries']),
            Stat::make('Most Active Student', $stats['mostActiveStudent']),
            Stat::make('Most Queried Document', $stats['mostQueriedDocument']),
            Stat::make('Helpful Ratio', $stats['helpfulRatio'] . '%'),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'timeFilter' => [
                'label' => 'Time Range',
                'options' => [
                    '1day' => 'Last 1 Day',
                    '3days' => 'Last 3 Days',
                    '5days' => 'Last 5 Days',
                    '7days' => 'Last 7 Days',
                    '30days' => 'Last 30 Days',
                    '90days' => 'Last 90 Days',
                    '6months' => 'Last 6 Months',
                ],
                'default' => '90days',
                'query' => true,
            ],
            'instanceFilter' => [
                'label' => 'Chatbot Instance',
                'options' => ['all' => 'All'] + \App\Models\ChatbotInstance::pluck('name', 'id')->toArray(),
                'default' => 'all',
                'query' => true,
                'visible' => fn () => auth()->user()->is_admin,
            ],
        ];
    }
}*/
