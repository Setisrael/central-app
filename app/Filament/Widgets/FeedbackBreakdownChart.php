<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackBreakdownChart extends ChartWidget
{
    protected static ?string $heading = 'Feedback Breakdown';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '300px';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 1;

    public string $timeFilter = '90days';
    public string $moduleFilter = 'all';

    public function mount(string $timeFilter = '90days', string $moduleFilter = 'all'): void
    {
        $this->timeFilter   = $timeFilter;
        $this->moduleFilter = $moduleFilter;
    }

    protected function getData(): array
    {
        [$from, $to] = $this->getDateRange();

        // Basis-Query mit RBAC
        $query = MetricUsage::query()
            ->whereBetween('timestamp', [$from, $to]);

        if (! auth()->user()->is_admin) {
            $userModuleCodes = DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (! empty($userModuleCodes)) {
                $query->whereIn('module_code', $userModuleCodes);
            } else {
                // Prof ohne Module → keine Daten
                $query->whereRaw('1 = 0');
            }
        }

        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        Log::debug('FeedbackBreakdownChart base query', [
            'timeFilter'    => $this->timeFilter,
            'moduleFilter'  => $this->moduleFilter,
            'from_date'     => $from->toDateTimeString(),
            'to_date'       => $to->toDateTimeString(),
            'user_id'       => auth()->id(),
            'is_admin'      => auth()->user()->is_admin,
        ]);

        // Counts robust ermitteln (ohne DB-spezifische Tricks)
        $helpful    = (int) $query->clone()->where('helpful', true)->count();
        $notHelpful = (int) $query->clone()->where('helpful', false)->count();
        $total      = (int) $query->clone()->count();
        $unrated    = max($total - $helpful - $notHelpful, 0);

        Log::debug('FeedbackBreakdownChart counts', [
            'helpful'     => $helpful,
            'notHelpful'  => $notHelpful,
            'unrated'     => $unrated,
            'total'       => $total,
        ]);

        // Fallback, wenn nichts bewertet wurde
        if ($total === 0) {
            return [
                'datasets' => [[
                    'label'           => 'Queries',
                    'data'            => [0, 0, 0],
                    'backgroundColor' => ['#e5e7eb', '#e5e7eb', '#e5e7eb'],
                ]],
                'labels' => ['Helpful', 'Not helpful', 'Unrated'],
            ];
        }

        return [
            'datasets' => [[
                'label'           => 'Queries',
                'data'            => [$helpful, $notHelpful, $unrated],
                'backgroundColor' => [
                    '#22c55e', // Helpful
                    '#ef4444', // Not helpful
                    '#9ca3af', // Unrated
                ],
            ]],
            'labels' => ['Helpful', 'Not helpful', 'Unrated'],
        ];
    }

    protected function getType(): string
    {
        // Balkendiagramm
        return 'bar';
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title'       => [
                        'display' => true,
                        'text'    => 'Number of Queries',
                    ],
                ],
            ],
        ];
    }

    protected function getDateRange(): array
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

    public function getDescription(): ?string
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

        return "📊 {$timeLabel} • 📚 {$moduleName}";
    }
}
