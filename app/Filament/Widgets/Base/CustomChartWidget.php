<?php

namespace App\Filament\Widgets\Base;

use Filament\Widgets\ChartWidget;

abstract class CustomChartWidget extends ChartWidget
{
    public array $filters = [];

    public function updatedFilters(): void
    {
        $this->dispatch('$refresh');
    }

    public function getActiveFilters(): array
    {
        return $this->filters;
    }

    public function getFilterValue(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }
}
