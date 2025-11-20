<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\Module;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class ActivityHeatmap extends ChartWidget
{
    protected static ?string $heading = 'Activity by Day and Hour';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '400px';
    protected static bool $isLazy = false;

    public string $timeFilter = '90days';
    public string $moduleFilter = 'all';

    public function mount($timeFilter = '90days', $moduleFilter = 'all'): void
    {
        $this->timeFilter = $timeFilter;
        $this->moduleFilter = $moduleFilter;
    }

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

        // Basis-Query
        $query = MetricUsage::query()->where('timestamp', '>=', $from);

        // Rollenbasierte Einschränkung per module_code
        if (! auth()->user()->is_admin) {
            $userModuleCodes = \DB::table('module_user')
                ->where('user_id', auth()->id())
                ->pluck('module_code')
                ->toArray();

            if (! empty($userModuleCodes)) {
                $query->whereIn('module_code', $userModuleCodes);
            } else {
                // User hat keine Module → leeres Resultat erzwingen
                $query->whereRaw('1 = 0');
            }
        }

        // Modulfilter
        if ($this->moduleFilter !== 'all') {
            $query->where('module_code', $this->moduleFilter);
        }

        Log::debug('ActivityHeatmap query', [
            'sql'          => $query->toSql(),
            'bindings'     => $query->getBindings(),
            'timeFilter'   => $this->timeFilter,
            'moduleFilter' => $this->moduleFilter,
        ]);

        // MariaDB-kompatibles Grouping nach Wochentag und Stunde
        $raw = $query
            ->selectRaw('DAYOFWEEK(`timestamp`) - 1 as day_of_week, HOUR(`timestamp`) as hour, COUNT(*) as count')
            ->groupBy('day_of_week', 'hour')
            ->orderBy('day_of_week')
            ->orderBy('hour')
            ->get();

        // 0 = Sonntag, 1 = Montag, ... 6 = Samstag (entsprechend DAYOFWEEK()-1)
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $hours = range(0, 23);
        $datasets = [];

        // Für jede Wochentag ein Dataset
        foreach ($days as $dayIndex => $day) {
            $data = [];

            foreach ($hours as $hour) {
                $entry = $raw
                    ->where('day_of_week', $dayIndex)
                    ->where('hour', $hour)
                    ->first();

                // Null-sicherer Zugriff
                $count = $entry?->count ?? 0;
                $data[] = (int) $count;
            }

            // Farbgenerierung pro Tag
            $hue = ($dayIndex * 51) % 360;

            $datasets[] = [
                'label'           => $day,
                'data'            => $data,
                'backgroundColor' => "hsla({$hue}, 70%, 60%, 0.7)",
                'borderColor'     => "hsla({$hue}, 70%, 50%, 1)",
                'borderWidth'     => 1,
            ];
        }

        // Wenn gar keine Datasets zustande kommen (theoretisch)
        if (empty($datasets)) {
            return [
                'labels'   => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
                'datasets' => [[
                    'label'           => 'No Activity',
                    'data'            => array_fill(0, 24, 0),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                    'borderColor'     => 'rgba(156, 163, 175, 0.5)',
                    'borderWidth'     => 1,
                ]],
            ];
        }

        // Prüfen, ob in allen Datasets nur Nullen sind
        $hasData = false;
        foreach ($datasets as $dataset) {
            if (array_sum($dataset['data']) > 0) {
                $hasData = true;
                break;
            }
        }

        if (! $hasData) {
            return [
                'labels'   => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
                'datasets' => [[
                    'label'           => 'No Activity',
                    'data'            => array_fill(0, 24, 0),
                    'backgroundColor' => 'rgba(156, 163, 175, 0.3)',
                    'borderColor'     => 'rgba(156, 163, 175, 0.5)',
                    'borderWidth'     => 1,
                ]],
            ];
        }

        return [
            'labels'   => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                        'text'    => 'Hour of Day',
                    ],
                ],
                'y' => [
                    'title' => [
                        'display' => true,
                        'text'    => 'Number of Queries',
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
                    'text'    => 'Student Activity Patterns by Day and Hour',
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $moduleName = 'All Modules';

        if ($this->moduleFilter !== 'all') {
            $module = Module::where('code', $this->moduleFilter)->first();
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
