<x-filament::page>
    {{-- Filters --}}
    <div class="flex space-x-4">
        <select name="timeFilter" class="border p-2 rounded"
                onchange="location.href='?timeFilter='+this.value+'&moduleFilter={{ request('moduleFilter', 'all') }}'">
            <option value="1day" @selected(request('timeFilter') === '1day')>Last 1 Day</option>
            <option value="3days" @selected(request('timeFilter') === '3days')>Last 3 Days</option>
            <option value="5days" @selected(request('timeFilter') === '5days')>Last 5 Days</option>
            <option value="7days" @selected(request('timeFilter', '7days') === '7days')>Last 7 Days</option>
            <option value="30days" @selected(request('timeFilter') === '30days')>Last 30 Days</option>
            <option value="90days" @selected(request('timeFilter') === '90days')>Last 90 Days</option>
            <option value="6months" @selected(request('timeFilter') === '6months')>Last 6 Months</option>
        </select>

        <select name="moduleFilter" class="border p-2 rounded"
                onchange="location.href='?moduleFilter='+this.value+'&timeFilter={{ request('timeFilter', '7days') }}'">
            @foreach ($modules as $id => $name)
                <option value="{{ $id }}" @selected(request('moduleFilter', 'all') == $id)>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Summary Cards --}}
    @php
        $totalRequests = $metrics->count();
        $totalTokens = $metrics->sum('prompt_tokens') + $metrics->sum('completion_tokens');
        $avgDuration = $metrics->avg('duration_ms') / 1000;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-4">
        <x-filament::card>
            <div class="text-gray-600">Total Requests</div>
            <div class="text-2xl font-bold">{{ $totalRequests }}</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-gray-600">Token Usage</div>
            <div class="text-2xl font-bold">{{ number_format($totalTokens / 1000, 2) }}K</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-gray-600">Avg. Response Time</div>
            <div class="text-2xl font-bold">{{ number_format($avgDuration, 2) }}s</div>
        </x-filament::card>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div class="overflow-x-auto">
            @livewire(\App\Filament\Widgets\RequestChart::class)
        </div>
        <div>
            @livewire(\App\Filament\Widgets\LoadPieChart::class)
        </div>
    </div>

    {{-- Metrics per Instance Table --}}
    <x-filament::card class="mt-6">
        <h3 class="text-lg font-bold mb-2">Metrics per Module</h3>
        <table class="w-full text-sm text-left">
            <thead>
            <tr>
                <th class="p-2">Module</th>
                <th class="p-2">Requests</th>
                <th class="p-2">Tokens</th>
                <th class="p-2">Response Time</th>
                <th class="p-2">Trend</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($metricsForTable->groupBy('module_id') as $moduleId => $group)
                @php
                    //$module = $group->first()->chatbotInstance ?? null;
                    $module = \App\Models\Module::find($moduleId);
                    $totalRequests = $group->count();
                    $totalTokens = $group->sum('prompt_tokens') + $group->sum('completion_tokens');
                    $avgDuration = $group->avg('duration_ms') / 1000;
                    $trendData = $trends[$moduleId] ?? [
                        'trend' => 'flat',
                        'percentage_change' => '0',
                        'total_requests' => $totalRequests,
                        'previous_count' => 0,
                    ];
                @endphp
                <tr>
                    <td class="p-2">{{ $module?->name ?? 'Unknown (ID: ' . $moduleId . ')' }}</td>
                    <td class="p-2">{{ $totalRequests }}</td>
                    <td class="p-2">{{ number_format($totalTokens / 1000, 2) }}K</td>
                    <td class="p-2">{{ number_format($avgDuration, 2) }}s</td>
                    <td class="p-2">
                        @if ($trendData['trend'] === 'up')
                            <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-green-500 inline" />
                            <span>{{ $trendData['percentage_change'] > 0 ? '+' : '' }}{{ $trendData['percentage_change'] }}%</span>
                        @elseif ($trendData['trend'] === 'down')
                            <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-red-500 inline" />
                            <span>{{ $trendData['percentage_change'] }}%</span>
                        @else
                            <x-heroicon-o-minus class="w-5 h-5 text-gray-400 inline" />
                            <span>0%</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament::page>
