<x-filament::page>
    {{-- Filters --}}
    <div class="flex space-x-4 mb-6">
        <select name="timeFilter" class="border p-2 rounded"
                onchange="location.href='?timeFilter='+this.value+'&instanceFilter={{ request('instanceFilter', 'all') }}'">
            @foreach ($timeOptions as $value => $label)
                <option value="{{ $value }}" @selected(request('timeFilter', '24hours') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <select name="instanceFilter" class="border p-2 rounded"
                onchange="location.href='?instanceFilter='+this.value+'&timeFilter={{ request('timeFilter', '24hours') }}'">
            <option value="all" @selected(request('instanceFilter', 'all') === 'all')>All Instances</option>
            @foreach ($instances as $instance)
                <option value="{{ $instance->id }}" @selected(request('instanceFilter', 'all') == $instance->id)>
                    {{ $instance->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Summary Statistics --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <x-filament::card>
            <div class="text-gray-600">Average CPU Usage</div>
            <div class="text-2xl font-bold" style="color: {{ $stats['avg_cpu'] > 80 ? '#dc2626' : ($stats['avg_cpu'] > 60 ? '#d97706' : '#059669') }};">
                {{ number_format($stats['avg_cpu'], 1) }}%
            </div>
            <div class="text-sm text-gray-500">Peak: {{ number_format($stats['max_cpu'], 1) }}%</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-gray-600">Average RAM Usage</div>
            <div class="text-2xl font-bold" style="color: {{ $stats['avg_ram'] > 1000 ? '#dc2626' : ($stats['avg_ram'] > 500 ? '#d97706' : '#059669') }};">
                {{ number_format($stats['avg_ram'], 0) }} MB
            </div>
            <div class="text-sm text-gray-500">Peak: {{ number_format($stats['max_ram'], 0) }} MB</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-gray-600">Average Disk Usage</div>
            <div class="text-2xl font-bold" style="color: {{ $stats['avg_disk'] > 90 ? '#dc2626' : ($stats['avg_disk'] > 80 ? '#d97706' : '#059669') }};">
                {{ number_format($stats['avg_disk'], 1) }}%
            </div>
            <div class="text-sm text-gray-500">Peak: {{ number_format($stats['max_disk'], 1) }}%</div>
        </x-filament::card>
    </div>

    {{-- Charts Section --}}
    <div class="flex flex-col lg:flex-row gap-6 mb-8">
        <div class="flex-1 w-full lg:w-1/2">
            @livewire(\App\Filament\Widgets\SystemPerformanceChart::class)
        </div>

        <div class="flex-1 w-full lg:w-1/2">
            @livewire(\App\Filament\Widgets\LoadPieChart::class)
        </div>
    </div>

    {{-- Recent System Events Table --}}
    <x-filament::card>
        <h3 class="text-lg font-bold mb-4">Recent System Metrics</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                <tr class="border-b">
                    <th class="p-3">Instance</th>
                    <th class="p-3">Timestamp</th>
                    <th class="p-3">CPU %</th>
                    <th class="p-3">RAM (MB)</th>
                    <th class="p-3">Disk %</th>
                    <th class="p-3">Uptime</th>
                    <th class="p-3">Queue</th>
                    <th class="p-3">Last Push</th>
                    <th class="p-3">Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($recentMetrics as $metric)
                    @php
                        $lastPush = \Carbon\Carbon::parse($metric->timestamp ?? $metric->created_at);
                        $isStale  = $lastPush->lt(now()->subDay()); // older than 24h
                        $isResourceHealthy = $metric->cpu_usage < 80
                            && $metric->ram_usage < 1000
                            && $metric->disk_usage < 90
                            && $metric->queue_size < 10;
                        $isHealthy = $isResourceHealthy && ! $isStale;
                    @endphp
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 font-medium">{{ $metric->chatbotInstance->name ?? 'Unknown' }}</td>
                        <td class="p-3">{{ \Carbon\Carbon::parse($metric->timestamp)->format('M j, H:i:s') }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs {{ $metric->cpu_usage > 80 ? 'bg-red-100 text-red-800' : ($metric->cpu_usage > 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                {{ number_format($metric->cpu_usage, 1) }}%
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs {{ $metric->ram_usage > 1000 ? 'bg-red-100 text-red-800' : ($metric->ram_usage > 500 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                {{ number_format($metric->ram_usage, 0) }}
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs {{ $metric->disk_usage > 90 ? 'bg-red-100 text-red-800' : ($metric->disk_usage > 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                {{ number_format($metric->disk_usage, 1) }}%
                            </span>
                        </td>
                        <td class="p-3">{{ $metric->uptime_seconds > 0 ? gmdate('H:i:s', $metric->uptime_seconds) : 'N/A' }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs {{ $metric->queue_size > 10 ? 'bg-red-100 text-red-800' : ($metric->queue_size > 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                {{ $metric->queue_size }}
                            </span>
                        </td>
                        <td class="p-3 text-xs">
                            <span class="{{ $isStale ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                {{ $lastPush->format('M j, H:i:s') }}
                                <span class="ml-1 text-[11px] {{ $isStale ? 'text-red-500' : 'text-gray-500' }}">
                                    ({{ $lastPush->diffForHumans() }})
                                </span>
                            </span>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs {{ $isHealthy ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $isHealthy ? 'Healthy' : 'Warning' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::card>
</x-filament::page>
