<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

abstract class CustomChartWidget extends ChartWidget
{
    protected function getOptions(): ?array
    {
        $options = [];
        Log::debug(static::class . ' options:', $options);
        return $options;
    }

    protected function getCachedData(): array
    {
        $data = parent::getCachedData();
        Log::debug(static::class . ' cachedData:', [$data]);
        return $data ?? [];
    }
}
