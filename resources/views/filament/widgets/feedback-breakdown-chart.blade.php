<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ static::$heading }}
        </x-slot>

        <x-slot name="headerActions">
            <div class="flex gap-4 items-end">
                <div class="flex flex-col">
                    <label for="time-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Time Period
                    </label>
                    <select
                        id="time-filter"
                        wire:model.live="timeFilter"
                        class="fi-select-input block w-full border-gray-300 rounded-lg shadow-sm"
                    >
                        @foreach ($timeFilters as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col">
                    <label for="module-filter" class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Module
                    </label>
                    <select
                        id="module-filter"
                        wire:model.live="moduleFilter"
                        class="fi-select-input block w-full border-gray-300 rounded-lg shadow-sm"
                    >
                        @foreach ($moduleFilters as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-slot>

        <div class="relative" style="max-height: 300px;">
            <canvas
                x-data="{
                    chart: null,
                    init() {
                        this.chart = new Chart(this.$el, {
                            type: 'pie',
                            data: @js($chartData),
                            options: @js($chartOptions),
                        });

                        Livewire.on('$refresh', () => {
                            this.chart.destroy();
                            this.chart = new Chart(this.$el, {
                                type: 'pie',
                                data: @js($chartData),
                                options: @js($chartOptions),
                            });
                        });
                    }
                }"
                x-init="init()"
            ></canvas>
        </div>
    </x-filament::section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament-widgets::widget>
