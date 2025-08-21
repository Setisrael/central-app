<x-filament-panels::page>
    {{-- Hero Section with Filters --}}
    <div class="mb-8">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-6 border border-gray-200 dark:border-gray-600">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        📊 Analytics Dashboard
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Monitor user activity and feedback across your modules
                    </p>
                </div>

                {{-- Filters Section --}}
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            📅 Time Period
                        </label>
                        <select
                            wire:model.live="timeFilter"
                            class="block w-full sm:w-48 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                        >
                            @foreach ($timeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            📚 Module
                        </label>
                        <select
                            wire:model.live="moduleFilter"
                            class="block w-full sm:w-48 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                        >
                            @foreach ($modules as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Overview Widget --}}
    <div class="mb-8">
        @livewire(\App\Filament\Widgets\UserStatsOverview::class, ['timeFilter' => $timeFilter, 'moduleFilter' => $moduleFilter], key('stats-'.$timeFilter.'-'.$moduleFilter))
    </div>

    {{-- Charts Section - Top Row Side by Side --}}
    <div class="flex flex-col lg:flex-row gap-6 mb-8">
        {{-- Feedback Chart --}}
        <div class="flex-1 w-full lg:w-1/2">
            @livewire(\App\Filament\Widgets\FeedbackBreakdownChart::class, ['timeFilter' => $timeFilter, 'moduleFilter' => $moduleFilter], key('feedback-'.$timeFilter.'-'.$moduleFilter))
        </div>

        {{-- Module Usage Chart --}}
        <div class="flex-1 w-full lg:w-1/2">
            @livewire(\App\Filament\Widgets\ModuleUsageChart::class, ['timeFilter' => $timeFilter, 'moduleFilter' => $moduleFilter], key('module-usage-'.$timeFilter.'-'.$moduleFilter))
        </div>
    </div>

    {{-- Additional Analytics - Full Width Below --}}
    <div class="space-y-6 mb-8">
        {{-- User Activity Over Time --}}
        <div>
            @livewire(\App\Filament\Widgets\UserActivityOverTimeChart::class, ['timeFilter' => $timeFilter, 'moduleFilter' => $moduleFilter], key('activity-time-'.$timeFilter.'-'.$moduleFilter))
        </div>

        {{-- Activity Heatmap --}}
        <div>
            @livewire(\App\Filament\Widgets\ActivityHeatmap::class, ['timeFilter' => $timeFilter, 'moduleFilter' => $moduleFilter], key('heatmap-'.$timeFilter.'-'.$moduleFilter))
        </div>
    </div>

    {{-- Table Section --}}
    <x-filament::card class="overflow-hidden">
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📋 Detailed User Activity</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Individual student metrics with independent filtering
                    </p>
                </div>
                <div class="hidden sm:flex items-center text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 px-3 py-1 rounded-full">
                    <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                    Table has separate filters
                </div>
            </div>
        </x-slot>

        {{ $this->table }}
    </x-filament::card>

    {{-- Loading States --}}
    <div wire:loading wire:target="timeFilter,moduleFilter" class="fixed inset-0 bg-black bg-opacity-20 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Updating analytics...</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
