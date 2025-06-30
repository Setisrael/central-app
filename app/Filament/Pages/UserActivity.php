<?php

namespace App\Filament\Pages;

use App\Models\MetricUsage;
use Filament\Pages\Page;

class UserActivity extends Page
{
    protected static string $view = 'filament.pages.user-activity';
    protected static ?string $title = 'User Activities';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected function getViewData(): array
    {
        $query = MetricUsage::query();

        if (auth()->user()->isHuman() && !auth()->user()->is_admin) {
            $query->whereHas('chatbotUser', fn ($q) =>
            $q->where('module_code', auth()->user()->module_code));
        }

        $grouped = $query
            ->select('student_id_hash')
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('SUM(prompt_tokens + completion_tokens) as total_tokens')
            ->selectRaw('AVG(duration_ms) as avg_duration')
            ->groupBy('student_id_hash')
            ->get();

        return ['activities' => $grouped];
    }
}
