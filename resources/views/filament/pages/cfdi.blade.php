<x-filament-panels::page>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Subir de CFDI
        </h1>
    </x-slot>

    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <form wire:submit.prevent="submit">
            {{ $this->form }}
            <x-filament::button type="submit" class="mt-4">
                Subir
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
