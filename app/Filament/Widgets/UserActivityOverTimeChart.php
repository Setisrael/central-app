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

    protected function getData(): array
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

        $query = MetricUsage::query()
            ->where('timestamp', '>=', $from);

        // RBAC
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

        Log::debug('UserActivityOverTime base query', [
            'sql'          => $query->toSql(),
            'bindings'     => $query->getBindings(),
            'timeFilter'   => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
        ]);

        $rows = $query
            ->orderBy('timestamp')
            ->get(['timestamp', 'student_id_hash']);

        if ($rows->isEmpty()) {
            return [
                'labels'   => [],
                'datasets' => [],
            ];
        }

        // Gruppierung pro Tag
        $grouped = $rows->groupBy(function ($row) {
            return Carbon::parse($row->timestamp)->toDateString(); // YYYY-MM-DD
        });

        $dates            = collect($grouped->keys())->sort()->values();
        $labels           = [];
        $totalQueriesData = [];
        $uniqueStudentsData = [];

        foreach ($dates as $date) {
            $group = $grouped[$date];

            $labels[]            = Carbon::parse($date)->format('M j'); // "Jan 1"
            $totalQueriesData[]  = $group->count();
            $uniqueStudentsData[] = $group->pluck('student_id_hash')->unique()->count();
        }

        $data = [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'                     => 'Total Queries',
                    'data'                      => $totalQueriesData,
                    'borderColor'               => 'rgb(59, 130, 246)',
                    'backgroundColor'           => 'rgba(59, 130, 246, 0.1)',
                    'fill'                      => true,
                    'tension'                   => 0.4,
                    // Punkte ausblenden, nur bei Hover sichtbar
                    'pointRadius'               => 0,
                    'pointBorderWidth'          => 0,
                    'pointHoverRadius'          => 5,
                    'pointHoverBorderWidth'     => 2,
                    'pointHoverBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointHoverBorderColor'     => 'rgb(59, 130, 246)',
                ],
                [
                    'label'                     => 'Active Students',
                    'data'                      => $uniqueStudentsData,
                    'borderColor'               => 'rgb(34, 197, 94)',
                    'backgroundColor'           => 'rgba(34, 197, 94, 0.1)',
                    'fill'                      => true,
                    'tension'                   => 0.4,
                    'pointRadius'               => 0,
                    'pointBorderWidth'          => 0,
                    'pointHoverRadius'          => 5,
                    'pointHoverBorderWidth'     => 2,
                    'pointHoverBackgroundColor' => 'rgb(34, 197, 94)',
                    'pointHoverBorderColor'     => 'rgb(34, 197, 94)',
                ],
            ],
        ];

        // Empty case: alles null → lieber leeres Chart zurückgeben
        if (array_sum($totalQueriesData) === 0) {
            return [
                'labels'   => $labels,
                'datasets' => [],
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
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'scales'              => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text'    => 'Date',
                    ],
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text'    => 'Count',
                    ],
                    'beginAtZero' => true,
                ],
            ],
            'plugins'             => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                ],
                'title'  => [
                    'display' => true,
                    'text'    => 'Student Activity Over Time',
                ],
            ],
        ];
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

        return "📈 {$timeLabel} • 📚 {$moduleName}";
    }
}
