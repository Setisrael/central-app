<?php

namespace App\Filament\Pages;

use App\Models\SystemMetric;
use App\Models\ChatbotInstance;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class SystemMetrics extends Page
{
    protected static string $view = 'filament.pages.system-metrics';
    protected static ?string $title = 'System Metrics';
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?int $navigationSort = 3;

    protected function getViewData(): array
    {
        $timeFilter = request('timeFilter', '24hours');
        $instanceFilter = request('instanceFilter', 'all');

        // Get time range
        $from = match ($timeFilter) {
            '1hour' => now()->subHour(),
            '6hours' => now()->subHours(6),
            '12hours' => now()->subHours(12),
            '24hours' => now()->subDay(),
            '3days' => now()->subDays(3),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'all' => now()->subYears(10), // Show all data
            default => now()->subDay(),
        };

        // Base query
        $query = SystemMetric::query()
            ->with('chatbotInstance')
            ->where('timestamp', '>=', $from);

        // Apply instance filter
        if ($instanceFilter !== 'all') {
            $query->where('chatbot_instance_id', $instanceFilter);
        }

        $metrics = $query->orderBy('timestamp', 'desc')->get();

        // Get latest metrics for current status
        $latestMetrics = SystemMetric::query()
            ->with('chatbotInstance')
            ->when($instanceFilter !== 'all', fn($q) => $q->where('chatbot_instance_id', $instanceFilter))
            ->orderBy('timestamp', 'desc')
            ->get()
            ->groupBy('chatbot_instance_id')
            ->map(fn($group) => $group->first());

        // Get recent metrics for table - one latest per instance
        $recentMetrics = SystemMetric::query()
            ->with('chatbotInstance')
            ->when($instanceFilter !== 'all', fn($q) => $q->where('chatbot_instance_id', $instanceFilter))
            ->orderBy('timestamp', 'desc')
            ->get()
            ->groupBy('chatbot_instance_id')
            ->map(fn($group) => $group->first())
            ->sortByDesc(fn($metric) => $metric->timestamp)
            ->take(20);

        // Calculate averages and stats
        $stats = [
            'avg_cpu' => $metrics->avg('cpu_usage'),
            'avg_ram' => $metrics->avg('ram_usage'),
            'avg_disk' => $metrics->avg('disk_usage'),
            'max_cpu' => $metrics->max('cpu_usage'),
            'max_ram' => $metrics->max('ram_usage'),
            'max_disk' => $metrics->max('disk_usage'),
            'total_records' => $metrics->count(),
        ];

        // Get instances for filter
        $instances = ChatbotInstance::orderBy('name')->get();

        return [
            'metrics' => $metrics,
            'latestMetrics' => $latestMetrics,
            'recentMetrics' => $recentMetrics,
            'stats' => $stats,
            'instances' => $instances,
            'timeFilter' => $timeFilter,
            'instanceFilter' => $instanceFilter,
            'timeOptions' => [
                '1hour' => 'Last 1 Hour',
                '6hours' => 'Last 6 Hours',
                '12hours' => 'Last 12 Hours',
                '24hours' => 'Last 24 Hours',
                '3days' => 'Last 3 Days',
                '7days' => 'Last 7 Days',
                '30days' => 'Last 30 Days',
                '90days' => 'Last 90 Days',
                'all' => 'All Time',
            ],
        ];
    }
}
