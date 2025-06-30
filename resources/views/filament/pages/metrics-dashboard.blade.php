<x-filament::page>
    {{-- Filters --}}
    <div class="flex space-x-4">
        <select name="timeFilter" class="border p-2 rounded"
                onchange="location.href='?timeFilter='+this.value+'&instanceFilter={{ request('instanceFilter', 'all') }}'">
            <option value="7days" @selected(request('timeFilter', '7days') === '7days')>Last 7 Days</option>
            <option value="30days" @selected(request('timeFilter') === '30days')>Last 30 Days</option>
            <option value="90days" @selected(request('timeFilter') === '90days')>Last 90 Days</option>
        </select>

        <select name="instanceFilter" class="border p-2 rounded"
                onchange="location.href='?instanceFilter='+this.value+'&timeFilter={{ request('timeFilter', '7days') }}'">
            <option value="all" @selected(request('instanceFilter', 'all') === 'all')>All Instances</option>
            @foreach ($instances as $instance)
                <option value="{{ $instance->id }}" @selected(request('instanceFilter') == $instance->id)>
                    {{ $instance->name }}
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
    <div class="grid grid-cols-3 gap-6 mt-4">
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
    <div class="grid grid-cols-2 gap-6 mt-4">
        @livewire(\App\Filament\Widgets\RequestChart::class)
        @livewire(\App\Filament\Widgets\LoadPieChart::class)
    </div>

    {{-- Metrics per Instance Table --}}
    <x-filament::card class="mt-6">
        <h3 class="text-lg font-bold mb-2">Metrics per Instance</h3>
        <table class="w-full text-sm text-left">
            <thead>
            <tr>
                <th class="p-2">Instance</th>
                <th class="p-2">Requests</th>
                <th class="p-2">Tokens</th>
                <th class="p-2">Response Time</th>
                <th class="p-2">Trend</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($metricsForTable->groupBy('user_id') as $userId => $group)
                @php
                    $instance = $group->first()->chatbotInstance ?? null;
                    $totalRequests = $group->count();
                    $totalTokens = $group->sum('prompt_tokens') + $group->sum('completion_tokens');
                    $avgDuration = $group->avg('duration_ms') / 1000;
                    $trend = $totalRequests > 10 ? 'up' : 'down';
                @endphp
                <tr>
                    <td class="p-2">{{ $instance?->name ?? 'Unknown (user_id: ' . $userId . ')' }}</td>
                    <td class="p-2">{{ $totalRequests }}</td>
                    <td class="p-2">{{ number_format($totalTokens / 1000, 2) }}K</td>
                    <td class="p-2">{{ number_format($avgDuration, 2) }}s</td>
                    <td class="p-2">
                        @if ($trend === 'up')
                            <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-green-500" />
                        @else
                            <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-red-500" />
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament::page>
