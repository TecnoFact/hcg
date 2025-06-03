<x-filament-panels::page>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Subir de CFDI
        </h1>
    </x-slot>

    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md"
         x-data="{ subido: @entangle('subido'), xmlPath: @entangle('xmlPath') }">
        <form wire:submit.prevent="subirXml">
            {{ $this->form }}
            <div class="flex gap-4 mt-4">
                <x-filament::button type="submit" x-bind:disabled="subido">
                    Subir XML
                </x-filament::button>
                <template x-if="subido">
                    <form wire:submit.prevent="timbrarXml">
                        <x-filament::button type="submit">
                            Enviar XML
                        </x-filament::button>
                    </form>
                </template>
                <template x-if="subido && xmlPath">
                    <a :href="'/storage/' + xmlPath" download class="filament-button flex items-center mt-4">
                        Descargar XML
                    </a>
                </template>
            </div>
        </form>
    </div>
</x-filament-panels::page>
