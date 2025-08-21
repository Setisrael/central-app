<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Build base query
        $query = MetricUsage::query()->where('timestamp', '>=', $from);

        // Apply user role restrictions using module_code
        if (!auth()->user()->is_admin) {
            $userModuleCodes = DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (!empty($userModuleCodes)) {
                $query->whereIn('module_code', $userModuleCodes);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Apply module filter
        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        // Calculate simple metrics
        $totalQueries = $query->count();

        if ($totalQueries === 0) {
            return [
                Stat::make('Response Success Rate', '0%')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),

               /* Stat::make('Avg Response Speed', '0s')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),*/

                Stat::make('Primary Answer Source', 'N/A')
                    ->description('No data available')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('gray'),
            ];
        }

        // 1. Success Rate (status = 'ok')
        $successfulQueries = $query->clone()->where('status', 'ok')->count();
        $successRate = round(($successfulQueries / $totalQueries) * 100, 1);

        // 2. Average Response Speed
        $avgLatency = $query->clone()->avg('latency_ms') ?? 0;
        $avgSpeed = round($avgLatency / 1000, 2); // Convert to seconds

        // 3. Answer Source Distribution
        $embeddingCount = $query->clone()->where('answer_type', 'embedding')->count();
        $llmCount = $query->clone()->where('answer_type', 'llm')->count();
        $bothCount = $query->clone()->where('answer_type', 'both')->count();

        // Find the primary source
        $sources = [
            'embedding' => $embeddingCount,
            'llm' => $llmCount,
            'both' => $bothCount,
        ];

        $primarySource = array_search(max($sources), $sources);
        $primaryPercent = round((max($sources) / $totalQueries) * 100, 1);

        $sourceLabels = [
            'embedding' => 'Knowledge Base',
            'llm' => 'AI Model',
            'both' => 'Hybrid',
        ];

        return [
            Stat::make('Response Success Rate', $successRate . '%')
                ->description($successfulQueries . ' of ' . $totalQueries . ' queries successful')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($successRate >= 80 ? 'success' : ($successRate >= 60 ? 'warning' : 'danger'))
                ->chart($this->getSuccessRateChart($query)),


            Stat::make('Primary Answer Source', $sourceLabels[$primarySource])
                ->description($primaryPercent . '% from ' . $sourceLabels[$primarySource])
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart($this->getSourceChart($query)),
        ];
    }

    private function getSuccessRateChart($query)
    {
        // Ensure query respects current filters
        $chartQuery = $query->clone();

        // Chart period based on time filter
        $days = match ($this->timeFilter) {
            '1day' => 1,
            '3days' => 3,
            '5days' => 5,
            '7days' => 7,
            '30days' => 7, // Show 7 points for 30 days (every ~4 days)
            '90days' => 7, // Show 7 points for 90 days (every ~13 days)
            '6months' => 7, // Show 7 points for 6 months (every ~26 days)
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
        // Ensure query respects current filters
        $chartQuery = $query->clone();

        // Chart period based on time filter
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
            $sourceData[] = $total > 0 ? round(($embedding / $total) * 100) : 0;
        }
        return $sourceData;
    }
}
