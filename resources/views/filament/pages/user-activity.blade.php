<x-filament::page>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4">User Activity Summary in Arbeit (activitätsdiagram?)</h2>
        <table class="w-full text-sm text-left">
            <thead>
            <tr>
                <th class="p-2">Student Hash</th>
                <th class="p-2">Total Requests</th>
                <th class="p-2">Total Tokens</th>
                <th class="p-2">Avg. Duration (s)</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($activities as $activity)
                <tr>
                    <td class="p-2 font-mono">{{ Str::limit($activity->student_id_hash, 12) }}</td>
                    <td class="p-2">{{ $activity->total_requests }}</td>
                    <td class="p-2">{{ number_format($activity->total_tokens / 1000, 2) }}K</td>
                    <td class="p-2">{{ number_format($activity->avg_duration / 1000, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament::page>
