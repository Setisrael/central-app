@php
    // Erwartet:
    // $weeks: Array von Wochen, jede Woche = 7 Zellen (Mon–Sun)
    // $monthLabels: [weekIndex => 'Jan', ...]
    // $description: Text unter der Überschrift
    // $maxCount: Höchster Count
    // $hasData: boolean - ob überhaupt Daten vorhanden
@endphp

<x-filament::widget>
    <x-filament::card>
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold">Activity Heatmap</h3>
                <p class="text-sm text-gray-500">
                    {{ $description }}
                </p>
            </div>
        </div>

        @if (!$hasData || empty($weeks))
            {{-- Fallback: Keine Daten --}}
            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <p class="text-sm font-medium">No activity data available</p>
                <p class="text-xs mt-1">Try selecting a different time range or module</p>
            </div>
        @else
            {{-- Heatmap mit VOLLER BREITE --}}
            <div class="w-full">
                {{-- Grau-Container VOLLE BREITE --}}
                <div class="w-full p-3 rounded-lg" style="background-color: #f3f4f6;">
                    {{-- Monatslabels oben (aligned mit Grid) --}}
                    <div class="flex text-xs text-gray-600 font-medium mb-2 ml-12">
                        @foreach ($weeks as $weekIndex => $week)
                            <div class="flex-1 flex items-center justify-start">
                                {{ $monthLabels[$weekIndex] ?? '' }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Grid: Wochentag-Labels + Zellen (VOLLE BREITE) --}}
                    <div class="flex gap-3 w-full">
                        {{-- Wochentagslabels links --}}
                        <div class="flex flex-col justify-start text-xs text-gray-600 font-medium" style="width: 32px; flex-shrink: 0;">
                            <div style="height: 16px; line-height: 16px; margin-bottom: 4px;" class="text-right pr-1">Mon</div>
                            <div style="height: 16px; margin-bottom: 4px;"></div>
                            <div style="height: 16px; line-height: 16px; margin-bottom: 4px;" class="text-right pr-1">Wed</div>
                            <div style="height: 16px; margin-bottom: 4px;"></div>
                            <div style="height: 16px; line-height: 16px; margin-bottom: 4px;" class="text-right pr-1">Fri</div>
                            <div style="height: 16px; margin-bottom: 4px;"></div>
                            <div style="height: 16px; margin-bottom: 4px;"></div>
                        </div>

                        {{-- Grid mit Zellen (FLEX-GROW für volle Breite!) --}}
                        <div class="flex gap-1 flex-grow">
                            @foreach ($weeks as $week)
                                <div class="flex flex-col gap-1 flex-1">
                                    @foreach ($week as $cell)
                                        <div
                                            class="rounded transition-all duration-150 hover:ring-2 hover:ring-offset-1 hover:ring-blue-500 cursor-pointer w-full"
                                            style="height: 16px; background-color: {{ $cell['color'] }};"
                                            title="{{ $cell['tooltip'] }}"
                                        ></div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Legende --}}
                <div class="flex items-center justify-end mt-4 text-xs text-gray-500 gap-2">
                    <span>Less</span>
                    <span class="rounded border-2 border-gray-500" style="width: 16px; height: 16px; background-color: #e5e7eb;"></span>
                    <span class="rounded border-2 border-green-400" style="width: 16px; height: 16px; background-color: #dcfce7;"></span>
                    <span class="rounded border-2 border-green-500" style="width: 16px; height: 16px; background-color: #86efac;"></span>
                    <span class="rounded border-2 border-green-600" style="width: 16px; height: 16px; background-color: #22c55e;"></span>
                    <span class="rounded border-2 border-green-800" style="width: 16px; height: 16px; background-color: #15803d;"></span>
                    <span>More</span>
                </div>

                {{-- Debug Info --}}
                @if (config('app.debug'))
                    <div class="mt-3 text-xs text-gray-400 space-y-1">
                        <div>Max count: {{ $maxCount }} | Weeks: {{ count($weeks) }}</div>
                        @if(isset($weeks[0][0]))
                            <div>First cell: {{ $weeks[0][0]['date'] }} ({{ $weeks[0][0]['count'] }} queries)</div>
                        @endif
                        @if(isset($weeks[count($weeks)-1][0]))
                            <div>Last cell: {{ $weeks[count($weeks)-1][0]['date'] }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>
