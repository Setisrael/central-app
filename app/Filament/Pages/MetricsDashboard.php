<?php

namespace App\Filament\Pages;

use App\Models\MetricUsage;
use App\Models\ChatbotInstance;
use Filament\Pages\Page;

class MetricsDashboard extends Page
{
    protected static string $view = 'filament.pages.metrics-dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?int $navigationSort = 1;

    protected function getViewData(): array
    {
        $timeFilter = request('timeFilter', '7days');
        $instanceFilter = request('instanceFilter', 'all');

        return [
            'metrics' => $this->getFilteredMetrics($timeFilter, $instanceFilter),
            'metricsForTable' => $this->getFilteredMetrics($timeFilter), // NO instanceFilter for Metrics per Instance
            'instances' => $this->getInstances(),
            'timeFilter' => $timeFilter,
            'instanceFilter' => $instanceFilter,
        ];
    }

    // Used for cards & charts
    protected function getFilteredMetrics(string $timeFilter, string $instanceFilter = 'all')
    {
        $query = MetricUsage::query()->with(['chatbotUser', 'chatbotInstance']);

        // Limit by module for profs
        if (auth()->user()->isHuman() && !auth()->user()->is_admin) {
            $query->whereHas('chatbotUser', function ($q) {
                $q->where('is_chatbot', true)
                    ->where('module_code', auth()->user()->module_code);
            });
        }

        $from = match ($timeFilter) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };

        $query->where('created_at', '>=', $from);

        if (auth()->user()->is_admin && $instanceFilter !== 'all') {
            $query->whereHas('chatbotInstance', function ($q) use ($instanceFilter) {
                $q->where('id', $instanceFilter);
            });
        }

        return $query->get();
    }

    protected function getInstances()
    {
        $query = ChatbotInstance::query()->with('user');

        if (auth()->user()->isHuman() && !auth()->user()->is_admin) {
            $query->whereHas('user', function ($q) {
                $q->where('is_chatbot', true)
                    ->where('module_code', auth()->user()->module_code);
            });
        }

        return $query->get();
    }
}
