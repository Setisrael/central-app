<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\AccountWidget;

use App\Models\SystemMetric;
use App\Models\MetricUsage;
use App\Models\PushLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.dashboard';

    public function getSystemStats()
    {
        return SystemMetric::select('user_id', DB::raw('MAX(timestamp) as last_reported'),
            DB::raw('AVG(cpu_usage) as avg_cpu'),
            DB::raw('AVG(ram_usage) as avg_ram'),
            DB::raw('AVG(disk_usage) as avg_disk'),
            DB::raw('AVG(queue_size) as avg_queue'))
            ->groupBy('user_id')
            ->with('user')
            ->get();
    }

    public function getUsageStats()
    {
        return MetricUsage::select('user_id',
            DB::raw('COUNT(*) as usage_count'),
            DB::raw('SUM(prompt_tokens + completion_tokens) as total_tokens'),
            DB::raw('AVG(latency_ms) as avg_latency'),
            DB::raw('AVG(duration_ms) as avg_duration'))
            ->groupBy('user_id')
            ->with('user')
            ->get();
    }


}
