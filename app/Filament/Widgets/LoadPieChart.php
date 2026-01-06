<?php

namespace App\Filament\Widgets;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class LoadPieChart extends ChartWidget
{
    protected static ?string $heading = 'Server Load per Instance';
    protected static string $color = 'success';
    protected int|string|array $columnSpan = '1/2';
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $timeFilter     = request('timeFilter', '24hours');
        $instanceFilter = request('instanceFilter', 'all');

        [$from, $to] = $this->getDateRange($timeFilter);

        $query = MetricUsage::query()
            ->with('chatbotInstance')
            ->whereBetween('timestamp', [$from, $to]);

        if ($instanceFilter !== 'all') {
            $query->where('chatbot_instance_id', $instanceFilter);
        }

        $perInstance = $query
            ->selectRaw('chatbot_instance_id, COUNT(*) as request_count')
            ->groupBy('chatbot_instance_id')
            ->get();

        // Fallback bei keinen Daten
        if ($perInstance->isEmpty()) {
            return [
                'datasets' => [[
                    'label'           => 'Requests',
                    'data'            => [0],
                    'backgroundColor' => ['rgba(229, 231, 235, 0.8)'],
                    'borderColor'     => ['rgb(209, 213, 219)'],
                    'borderWidth'     => 2,
                    'borderRadius'    => 8,
                    'barThickness'    => 60,
                ]],
                'labels' => ['No data'],
            ];
        }

        $labels = $perInstance
            ->map(function ($row) {
                $instance = $row->chatbotInstance ?? null;
                if ($instance && ! empty($instance->name)) {
                    return $instance->name;
                }
                return 'Instance ' . $row->chatbot_instance_id;
            })
            ->values()
            ->toArray();

        $data = $perInstance
            ->pluck('request_count')
            ->values()
            ->toArray();

        // Generiere dynamische Farben basierend auf Anzahl der Instanzen
        $colors = $this->generateColors(count($data));

        return [
            'datasets' => [[
                'label'           => 'Requests',
                'data'            => $data,
                'backgroundColor' => $colors['background'],
                'borderColor'     => $colors['border'],
                'borderWidth'     => 2,
                'borderRadius'    => 8,
                'barThickness'    => 'flex',
                'maxBarThickness' => 80,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'titleFont' => [
                        'size' => 14,
                        'weight' => 'bold',
                    ],
                    'bodyFont' => [
                        'size' => 13,
                    ],
                    'callbacks' => [
                        'label' => "function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y + ' requests';
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                            return label + ' (' + percentage + '%)';
                        }",
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'autoSkip'    => false,
                        'maxRotation' => 45,
                        'minRotation' => 0,
                        'font' => [
                            'size' => 12,
                            'weight' => '500',
                        ],
                        'color' => '#6b7280',
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'lineWidth' => 1,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                        'color' => '#9ca3af',
                        'precision' => 0,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Number of Requests',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                        'color' => '#4b5563',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generiert harmonische Farbpalette für beliebig viele Balken
     */
    protected function generateColors(int $count): array
    {
        // Basis-Farbpalette (grün-blau-violett)
        $baseColors = [
            ['bg' => 'rgba(34, 197, 94, 0.8)',   'border' => 'rgb(34, 197, 94)'],   // green-500
            ['bg' => 'rgba(59, 130, 246, 0.8)',  'border' => 'rgb(59, 130, 246)'],  // blue-500
            ['bg' => 'rgba(168, 85, 247, 0.8)',  'border' => 'rgb(168, 85, 247)'],  // purple-500
            ['bg' => 'rgba(249, 115, 22, 0.8)',  'border' => 'rgb(249, 115, 22)'],  // orange-500
            ['bg' => 'rgba(236, 72, 153, 0.8)',  'border' => 'rgb(236, 72, 153)'],  // pink-500
            ['bg' => 'rgba(20, 184, 166, 0.8)',  'border' => 'rgb(20, 184, 166)'],  // teal-500
        ];

        $backgrounds = [];
        $borders = [];

        for ($i = 0; $i < $count; $i++) {
            $color = $baseColors[$i % count($baseColors)];
            $backgrounds[] = $color['bg'];
            $borders[] = $color['border'];
        }

        return [
            'background' => $backgrounds,
            'border' => $borders,
        ];
    }

    protected function getDateRange(string $timeFilter): array
    {
        $to = Carbon::now();

        $from = match ($timeFilter) {
            '1hour'   => $to->copy()->subHour(),
            '6hours'  => $to->copy()->subHours(6),
            '12hours' => $to->copy()->subHours(12),
            '24hours' => $to->copy()->subDay(),
            '3days'   => $to->copy()->subDays(3),
            '7days'   => $to->copy()->subDays(7),
            '30days'  => $to->copy()->subDays(30),
            '90days'  => $to->copy()->subDays(90),
            'all'     => $to->copy()->subYears(10),
            default   => $to->copy()->subDays(30),
        };

        return [$from, $to];
    }
}
