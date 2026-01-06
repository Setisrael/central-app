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

    protected function getStats(): array
    {
        $from = match ($this->timeFilter) {
            '1day'    => now()->subDay(),
            '3days'   => now()->subDays(3),
            '5days'   => now()->subDays(5),
            '7days'   => now()->subDays(7),
            '30days'  => now()->subDays(30),
            '90days'  => now()->subDays(90),
            '6months' => now()->subMonths(6),
            default   => now()->subDays(90),
        };

        // Basis-Query
        $query = MetricUsage::query()
            ->where('timestamp', '>=', $from);

        // RBAC: nur Module anzeigen, die dem User zugeordnet sind
        if (! auth()->user()->is_admin) {
            $userModuleCodes = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (! empty($userModuleCodes)) {
                $query->whereIn('module_code', $userModuleCodes);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Modulfilter
        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        Log::debug('UserStatsOverview base query', [
            'sql'          => $query->toSql(),
            'bindings'     => $query->getBindings(),
            'timeFilter'   => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
            'user_id'      => auth()->id(),
            'is_admin'     => auth()->user()->is_admin,
        ]);

        // Calculate metrics
        $activeStudents = (int) $query->clone()->distinct('student_id_hash')->count('student_id_hash');
        $totalQueries   = (int) $query->clone()->count();
        $avgQueries     = $activeStudents > 0 ? round($totalQueries / $activeStudents, 1) : 0.0;

        // Per-student distribution (Most Active + Top-X Share)
        $studentStats = $query->clone()
            ->selectRaw('student_id_hash, COUNT(*) as query_count')
            ->groupBy('student_id_hash')
            ->orderByDesc('query_count')
            ->get();

        // Most active student (pseudonymisiert)
        $mostActiveStudentHash = $studentStats->first()?->student_id_hash ?? 'None';
        $mostActiveStudent     = $this->formatStudentId($mostActiveStudentHash);

        // Top-X-Share: wie viel % machen die Top X Studierenden aus?
        $topN            = min(10, $studentStats->count());
        $topStudentsShare = 0.0;

        if ($topN > 0 && $totalQueries > 0) {
            $topQueries       = $studentStats->take($topN)->sum('query_count');
            $topStudentsShare = round(($topQueries / $totalQueries) * 100, 1);
        }

        // Most queried document
        $mostQueriedDocument = $query->clone()
            ->selectRaw('document_id, count(*) as query_count')
            ->groupBy('document_id')
            ->orderByDesc('query_count')
            ->first()?->document_id ?? 'None';

        // Helpful ratio - funktioniert für MariaDB & PostgreSQL
        $helpfulCount    = (int) $query->clone()->where('helpful', true)->count();
        $notHelpfulCount = (int) $query->clone()->where('helpful', false)->count();
        $ratedCount      = $helpfulCount + $notHelpfulCount;
        $helpfulRatio    = $ratedCount > 0 ? round(($helpfulCount / $ratedCount) * 100, 1) : 0.0;

        Log::debug('UserStatsOverview counts', [
            'activeStudents'   => $activeStudents,
            'totalQueries'     => $totalQueries,
            'helpfulCount'     => $helpfulCount,
            'notHelpfulCount'  => $notHelpfulCount,
            'ratedCount'       => $ratedCount,
            'helpfulRatio'     => $helpfulRatio,
            'topStudentsShare' => $topStudentsShare,
            'topStudentsN'     => $topN,
        ]);

        $topLabel = $topN > 0
            ? "Top {$topN} Students Share"
            : 'Top Students Share';

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

            Stat::make($topLabel, $topStudentsShare . '%')
                ->description('Share of all queries')
                ->descriptionIcon('heroicon-m-fire')
                ->color('info'),

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
        if (! $studentHash) {
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
            $module     = Module::where('code', $this->moduleFilter)->first();
            $moduleName = $module ? $module->name : 'Unknown Module';
        }

        $timeLabel = match ($this->timeFilter) {
            '1day'    => 'Last 1 Day',
            '3days'   => 'Last 3 Days',
            '5days'   => 'Last 5 Days',
            '7days'   => 'Last 7 Days',
            '30days'  => 'Last 30 Days',
            '90days'  => 'Last 90 Days',
            '6months' => 'Last 6 Months',
            default   => 'Last 90 Days',
        };

        return "📅 {$timeLabel} • 📚 {$moduleName}";
    }
}
