<x-filament::page>
    <div class="max-w-2xl">
        <form wire:submit="updatePassword">
            {{ $this->form }}

            <div class="mt-6">
                {{ $this->getFormActions()[0] }}
            </div>
        </form>
    </div>
</x-filament::page>
