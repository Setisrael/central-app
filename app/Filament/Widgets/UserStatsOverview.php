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

        // Format the student ID for display
        $mostActiveStudent = $this->formatStudentId($mostActiveStudent);

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

            Stat::make('Most Queried Document ID', $mostQueriedDocument)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Helpful Ratio', $helpfulRatio . '%')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color($helpfulRatio >= 70 ? 'success' : ($helpfulRatio >= 50 ? 'warning' : 'danger')),
        ];
    }

    /**
     * Format student ID hash for display (First 6 + Last 3)
     */
    protected function formatStudentId(?string $studentHash): string
    {
        if (!$studentHash) {
            return 'None';
        }
        if (strlen($studentHash) > 9) {
            return substr($studentHash, 0, 6) . '...' . substr($studentHash, -3);
        }
        return $studentHash;
    }

    protected function getFilterDescription(): string
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

        return "📅 {$timeLabel} • 📚 {$moduleName}";
    }
}
