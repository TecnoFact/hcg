<x-filament-panels::page>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Subir de CFDI
        </h1>

    </x-slot>

    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md"
         x-data="{ subido: @entangle('subido'), sellado: @entangle('sellado'), timbrado: @entangle('timbrado'), depositado: @entangle('depositado'), xmlPath: @entangle('xmlPath'), estado: @entangle('estado') }">

        <form wire:submit.prevent="subirXml">
            {{ $this->form }}
            <div class="flex gap-4 mt-4" style="margin-top: 1rem;">
                <template  x-if="!subido">
                    <x-filament::button type="submit" color="primary" wire:loading.attr="disabled" wire:target="subirXml">
                        <span class="flex items-center" wire:loading.remove>
                             <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 10l-4-4m0 0l-4 4m4-4v12"/>
                            </svg>
                            Subir
                        </span>
                         <span wire:loading wire:target="subirXml" class="flex items-center">
                            <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Subiendo...
                        </span>
                    </x-filament::button>
                </template>


                <template x-if="subido && !sellado">
                    <form wire:submit.prevent="sellarXml">
                        <x-filament::button type="submit" color="warning" wire:loading.attr="disabled" wire:target="sellarXml">
                              <span class="flex items-center"  wire:loading.remove>
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 0a2 2 0 002 2h8a2 2 0 002-2v-4a2 2 0 00-2-2H8a2 2 0 00-2 2v4zm10-6V7a4 4 0 10-8 0v4"/>
                            </svg>
                            Sellar
                        </span>
                         <span wire:loading wire:target="subirXml" class="flex items-center">
                            <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Sellando...
                        </span>
                        </x-filament::button>
                    </form>
                </template>

                <template x-if="sellado && !timbrado">
                    <form wire:submit.prevent="timbrarXml">
                        <x-filament::button type="submit" color="success" wire:loading.attr="disabled" wire:target="timbrarXml">
                              <span class="flex items-center" wire:loading.remove>
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4-4" />
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            Timbrar
                        </span>
                            <span wire:loading wire:target="timbrarXml" class="flex items-center">
                                <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">

                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                Timbrando...
                            </span>
                        </x-filament::button>
                    </form>
                </template>



                <template x-if="timbrado && !depositado">
                    <form wire:submit.prevent="publicacion">
                        <x-filament::button type="submit" color="danger" wire:loading.attr="disabled" wire:target="publicacion">
                              <span class="flex items-center" wire:loading.remove>
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01.88-7.88A5 5 0 1117 9h-1" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v6m0 0l-3-3m3 3l3-3" />
                            </svg>
                            DEPOSITO
                        </span>
                            <span wire:loading wire:target="publicacion" class="flex items-center">
                                <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">

                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                Depositando...
                            </span>
                        </x-filament::button>
                    </form>
                </template>



                <template x-if="sellado && xmlPath">
                    <a :href="'/storage/' + xmlPath" download class="filament-button flex items-center mt-4 ">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                        </svg>
                        Descargar XML
                    </a>
                </template>

            </div>
        </form>

          <div class="mt-6 px-2 py-4">
            Estado: <span x-html="estado"></span>
          </div>

    </div>
</x-filament-panels::page>
