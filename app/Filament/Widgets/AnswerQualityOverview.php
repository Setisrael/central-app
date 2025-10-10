<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AnswerQualityOverview extends BaseWidget
{
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';

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

        // Base query (time window)
        $query = MetricUsage::query()->where('timestamp', '>=', $from);

        // Restrict by role (modules a prof can see) via module_code
        if (!auth()->user()->is_admin) {
            $userModuleCodes = DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (!empty($userModuleCodes)) {
                $query->whereIn('module_code', $userModuleCodes);
            } else {
                // User has no module access -> show no data
                $query->whereRaw('1 = 0');
            }
        }

        // Apply explicit module filter
        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        $totalQueries = $query->count();

        if ($totalQueries === 0) {
            return [
                Stat::make('Response Success Rate', '0%')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),
                Stat::make('Primary Model', 'N/A')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),
            ];
        }

        // 1) Success Rate
        $successfulQueries = $query->clone()->where('status', 'ok')->count();
        $successRate = round(($successfulQueries / $totalQueries) * 100, 1);

        // 2) Primary Answer Source (kept for charts; not displayed as a Stat label anymore)
        $embeddingCount = $query->clone()->where('answer_type', 'embedding')->count();
        $llmCount       = $query->clone()->where('answer_type', 'llm')->count();
        $bothCount      = $query->clone()->where('answer_type', 'both')->count();

        $sources = [
            'embedding' => $embeddingCount,
            'llm'       => $llmCount,
            'both'      => $bothCount,
        ];
        $primarySource   = array_search(max($sources), $sources);
        $primaryPercent  = round((max($sources) / $totalQueries) * 100, 1);

        // 3) Primary Model (among llm/both)
        $llmQuery = $query->clone()
            ->whereIn('answer_type', ['llm', 'both'])
            ->whereNotNull('model')
            ->where('model', '!=', '');

        $llmTotal = $llmQuery->count();

        $topModelRow = $llmQuery
            ->select('model', DB::raw('COUNT(*) as cnt'))
            ->groupBy('model')
            ->orderByDesc('cnt')
            ->first();

        $primaryModel = $topModelRow->model ?? 'N/A';

        // Show percentage out of ALL queries (consistent with previous card wording)
        $primaryModelPercent = $topModelRow
            ? round(($topModelRow->cnt / $totalQueries) * 100, 1)
            : 0.0;

        // If there are zero LLM answers, fall back gracefully
        if ($llmTotal === 0) {
            $primaryModel = 'N/A';
            $primaryModelPercent = 0.0;
        }

        // Labels for the (hidden) source chart still useful for the sparkline
        $sourceLabels = [
            'embedding' => 'Knowledge Base',
            'llm'       => 'AI Model',
            'both'      => 'Hybrid',
        ];

        return [
            Stat::make('Response Success Rate', $successRate . '%')
                ->description($successfulQueries . ' of ' . $totalQueries . ' queries successful')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($successRate >= 80 ? 'success' : ($successRate >= 60 ? 'warning' : 'danger'))
                ->chart($this->getSuccessRateChart($query)),

            // Replaced "Primary Answer Source" with "Primary Model"
            Stat::make('Primary Model', $primaryModel)
               // ->description($primaryModelPercent . '% of answers used ' . $primaryModel)
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('info')
                ->chart($this->getSourceChart($query)), // keep the small sparkline for continuity
        ];
    }

    private function getSuccessRateChart($query)
    {
        $chartQuery = $query->clone();

        $days = match ($this->timeFilter) {
            '1day' => 1,
            '3days' => 3,
            '5days' => 5,
            '7days' => 7,
            '30days' => 7,
            '90days' => 7,
            '6months' => 7,
            default => 7,
        };

        $interval = match ($this->timeFilter) {
            '30days' => 4,
            '90days' => 13,
            '6months' => 26,
            default => 1,
        };

        $dailySuccess = [];
        for ($i = ($days - 1); $i >= 0; $i--) {
            $date = now()->subDays($i * $interval)->startOfDay();
            $dayTotal = $chartQuery->clone()->whereDate('timestamp', $date)->count();
            $daySuccess = $chartQuery->clone()->whereDate('timestamp', $date)->where('status', 'ok')->count();
            $dailySuccess[] = $dayTotal > 0 ? round(($daySuccess / $dayTotal) * 100) : 0;
        }
        return $dailySuccess;
    }

    private function getSourceChart($query)
    {
        // Keep a compact sparkline showing share of KB vs LLM across time.
        $chartQuery = $query->clone();

        $days = match ($this->timeFilter) {
            '1day' => 1,
            '3days' => 3,
            '5days' => 5,
            '7days' => 7,
            '30days' => 7,
            '90days' => 7,
            '6months' => 7,
            default => 7,
        };

        $interval = match ($this->timeFilter) {
            '30days' => 4,
            '90days' => 13,
            '6months' => 26,
            default => 1,
        };

        $sourceData = [];
        for ($i = ($days - 1); $i >= 0; $i--) {
            $date = now()->subDays($i * $interval)->startOfDay();
            $embedding = $chartQuery->clone()->whereDate('timestamp', $date)->where('answer_type', 'embedding')->count();
            $llm = $chartQuery->clone()->whereDate('timestamp', $date)->where('answer_type', 'llm')->count();
            $total = $embedding + $llm;
            $sourceData[] = $total > 0 ? round(($llm / $total) * 100) : 0; // % that used LLM that day
        }
        return $sourceData;
    }
}
