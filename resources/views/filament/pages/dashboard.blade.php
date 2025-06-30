<x-filament::page>
    <x-filament::section heading=" System Health Summary">
        <div class="overflow-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2">Chatbot</th>
                    <th class="px-4 py-2">CPU (%)</th>
                    <th class="px-4 py-2">RAM (MB)</th>
                    <th class="px-4 py-2">Disk (%)</th>
                    <th class="px-4 py-2">Queue Size</th>
                    <th class="px-4 py-2">Last Report</th>
                </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                @foreach($this->getSystemStats() as $stat)
                    <tr>
                        <td class="px-4 py-2">{{ $stat->user->name ?? 'Unknown' }}</td>
                        <td class="px-4 py-2">{{ number_format($stat->avg_cpu, 1) }}</td>
                        <td class="px-4 py-2">{{ number_format($stat->avg_ram, 1) }}</td>
                        <td class="px-4 py-2">{{ number_format($stat->avg_disk, 1) }}</td>
                        <td class="px-4 py-2">{{ $stat->avg_queue }}</td>
                        <td class="px-4 py-2">{{ $stat->last_reported }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section heading="Chatbot Usage Summary">
        <div class="overflow-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-2">Chatbot</th>
                    <th class="px-4 py-2">Total Requests</th>
                    <th class="px-4 py-2">Total Tokens</th>
                    <th class="px-4 py-2">Avg Latency</th>
                    <th class="px-4 py-2">Avg Duration</th>
                </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                @foreach($this->getUsageStats() as $usage)
                    <tr>
                        <td class="px-4 py-2">{{ $usage->user->name ?? 'Unknown' }}</td>
                        <td class="px-4 py-2">{{ $usage->usage_count }}</td>
                        <td class="px-4 py-2">{{ $usage->total_tokens }}</td>
                        <td class="px-4 py-2">{{ number_format($usage->avg_latency, 1) }}ms</td>
                        <td class="px-4 py-2">{{ number_format($usage->avg_duration, 1) }}ms</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament::page>
