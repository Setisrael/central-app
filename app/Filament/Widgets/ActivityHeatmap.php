<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityHeatmap extends Widget
{
    protected static string $view = 'filament.pages.activity-heatmap';

    public string $timeFilter = '90days';
    public string $moduleFilter = 'all';

    protected function getViewData(): array
    {
        [$weeks, $monthLabels, $maxCount, $hasData] = $this->buildCalendarData();

        return [
            'weeks'       => $weeks,
            'monthLabels' => $monthLabels,
            'maxCount'    => $maxCount,
            'hasData'     => $hasData,
            'description' => $this->getDescription(),
        ];
    }

    /**
     * GitHub-artiger Kalender mit DYNAMISCHEM Zeitraum:
     * - Zeigt nur Wochen von erster bis letzter Aktivität (keine leeren Monate!)
     * - Adaptive Farbskala: Dunkler bei niedrigem maxCount
     * - Korrekte Monatszuordnung: Labels basieren auf Montag der Woche
     *
     * Wissenschaftliche Fundierung:
     * - Cleveland & McGill (1984): Heatmaps brauchen Datendichte
     * - Tufte (2001): Maximize Data-Ink Ratio, minimize empty space
     * - Ifenthaler et al. (2017): Temporal Activity Patterns in Learning Analytics
     */
    protected function buildCalendarData(): array
    {
        $baseQuery = $this->getBaseQuery();

        $firstActivity = (clone $baseQuery)->min('timestamp');
        $lastActivity  = (clone $baseQuery)->max('timestamp');

        if (!$firstActivity || !$lastActivity) {
            return [[], [], 0, false];
        }

        [$filterFrom, $filterTo] = $this->getDateRangeFromFilter();

        $from = Carbon::parse($firstActivity)->max($filterFrom);
        $to   = Carbon::parse($lastActivity)->min($filterTo);

        $rows = (clone $baseQuery)
            ->whereBetween('timestamp', [$from, $to])
            ->get(['timestamp']);

        Log::debug('ActivityHeatmap calendar query', [
            'firstActivity' => $firstActivity,
            'lastActivity'  => $lastActivity,
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
            'rowCount'      => $rows->count(),
            'timeFilter'    => $this->timeFilter,
            'moduleFilter'  => $this->moduleFilter,
        ]);

        $countsByDate = [];
        foreach ($rows as $row) {
            $date = Carbon::parse($row->timestamp)->toDateString();
            $countsByDate[$date] = ($countsByDate[$date] ?? 0) + 1;
        }

        $maxCount = !empty($countsByDate) ? max($countsByDate) : 0;

        $startDate = $from->copy()->startOfWeek(Carbon::MONDAY);
        $endDate   = $to->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks         = [];
        $monthLabels   = [];
        $current       = $startDate->copy();
        $weekIndex     = 0;
        $previousMonth = null;

        while ($current <= $endDate) {
            $week = [];

            // KORRIGIERT: Prüfe ERSTEN TAG der Woche (Montag!)
            $firstDayOfWeek = $current->copy();

            // KORRIGIERT: Label nur wenn DIESER Montag in neuem Monat ist!
            if ($firstDayOfWeek->month !== $previousMonth) {
                $monthLabels[$weekIndex] = $firstDayOfWeek->format('M'); // "Jun", "Jul", etc.
                $previousMonth = $firstDayOfWeek->month;
            }

            for ($i = 0; $i < 7; $i++) {
                $date    = $current->copy();
                $dateStr = $date->toDateString();
                $count   = $countsByDate[$dateStr] ?? 0;
                $color   = $this->colorClass($count, $maxCount);
                $tooltip = $this->formatTooltip($date, $count);

                $week[] = [
                    'date'    => $dateStr,
                    'day_iso' => $date->dayOfWeekIso,
                    'count'   => $count,
                    'color'   => $color,
                    'tooltip' => $tooltip,
                ];

                $current->addDay();
            }

            $weeks[] = $week;
            $weekIndex++;
        }

        Log::debug('ActivityHeatmap result', [
            'weekCount'   => count($weeks),
            'monthLabels' => $monthLabels,
            'firstWeek'   => $weeks[0][0]['date'] ?? null,
            'lastWeek'    => $weeks[count($weeks)-1][0]['date'] ?? null,
        ]);

        return [$weeks, $monthLabels, $maxCount, true];
    }

    protected function getBaseQuery()
    {
        $query = MetricUsage::query();

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

        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        return $query;
    }

    protected function getDateRangeFromFilter(): array
    {
        $to = now()->endOfDay();

        $from = match ($this->timeFilter) {
            '1day'    => $to->copy()->subDays(1),
            '3days'   => $to->copy()->subDays(3),
            '5days'   => $to->copy()->subDays(5),
            '7days'   => $to->copy()->subDays(7),
            '30days'  => $to->copy()->subDays(30),
            '90days'  => $to->copy()->subDays(90),
            '6months' => $to->copy()->subMonths(6),
            default   => $to->copy()->subDays(90),
        };

        return [$from->startOfDay(), $to];
    }

    /**
     * INLINE-STYLES VERSION: Returns HEX colors instead of Tailwind classes
     * This bypasses Tailwind purge issues completely!
     *
     * ADAPTIVE Farbklasse abhängig von Aktivität + maxCount.
     * Für niedrige Counts (< 10): Dunklere Farben für bessere Sichtbarkeit
     * Für hohe Counts (>= 10): GitHub-Style Quartile
     */
    protected function colorClass(int $count, int $maxCount): string
    {
        if ($count === 0 || $maxCount === 0) {
            return '#e5e7eb'; // gray-200
        }

        // ADAPTIVE FARBSKALA für niedrige Counts
        if ($maxCount < 10) {
            if ($count === 1) {
                return '#86efac'; // green-300
            }

            $ratio = $count / $maxCount;

            if ($ratio < 0.33) {
                return '#4ade80'; // green-400
            }
            if ($ratio < 0.66) {
                return '#16a34a'; // green-600
            }
            return '#14532d'; // green-800
        }

        // Standard GitHub-Style Quartile für hohe Counts
        if ($maxCount <= 1) {
            return '#86efac'; // green-300
        }

        $ratio = $count / $maxCount;

        if ($ratio < 0.25) {
            return '#dcfce7'; // green-100
        }
        if ($ratio < 0.5) {
            return '#86efac'; // green-300
        }
        if ($ratio < 0.75) {
            return '#22c55e'; // green-500
        }
        return '#15803d'; // green-700
    }

    protected function formatTooltip(Carbon $date, int $count): string
    {
        $formattedDate = $date->toFormattedDateString();

        if ($count === 0) {
            return "No queries on {$formattedDate}";
        }

        if ($count === 1) {
            return "1 query on {$formattedDate}";
        }

        return "{$count} queries on {$formattedDate}";
    }

    protected function getDescription(): string
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
