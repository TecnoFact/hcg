<x-filament-panels::page>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Continuar la Subida de CFDI
        </h1>

    </x-slot>

    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md"
    x-data="{ cfdiArchivo: @entangle('cfdiArchivo'), subido: @entangle('subido'), sellado: @entangle('sellado'), timbrado: @entangle('timbrado'), depositado: @entangle('depositado'), xmlPath: @entangle('xmlPath'), estado: @entangle('estado') }">


    <div class="flex items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Continuar la Subida de CFDI {{ $cfdiArchivo->id }}
        </h1>
     </div>


    <div class="flex justify-between " style="margin-top: 1rem;">



        <!-- Paso 1: Subir XML -->
        <div class="relative">
            <form wire:submit.prevent="subirXml">
                <x-filament::button
                    type="submit"
                    x-bind:color="subido ? 'success' : 'primary'"
                    x-bind:disabled="true"
                    wire:loading.attr="disabled"
                    wire:target="subirXml"
                    class="transition-all duration-300 opacity-50 cursor-not-allowed"
                >
                    <span class="flex items-center" wire:loading.remove>
                        <template x-if="!subido">
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 10l-4-4m0 0l-4 4m4-4v12"/>
                            </svg>
                        </template>
                        <template x-if="subido">
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <span x-text="subido ? 'Subido ✓' : 'Subir'"></span>
                    </span>
                    <span wire:loading wire:target="subirXml" class="flex items-center">
                        <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        Subiendo...
                    </span>
                </x-filament::button>
            </form>
            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 text-xs text-gray-500 dark:text-gray-400">
                Paso 1
            </div>
        </div>

            <!-- Paso 2: Sellar XML -->
            <div class="relative">
                <form wire:submit.prevent="sellarXml">
                    <x-filament::button
                        type="submit"
                        x-bind:color="sellado ? 'success' : 'warning'"
                        x-bind:disabled="!subido || sellado"
                        wire:loading.attr="disabled"
                        wire:target="sellarXml"
                        class="transition-all duration-300"
                        x-bind:class="!subido ? 'opacity-50 cursor-not-allowed' : (sellado ? 'opacity-75' : '')"
                    >
                        <span class="flex items-center" wire:loading.remove>
                            <template x-if="!sellado">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 0a2 2 0 002 2h8a2 2 0 002-2v-4a2 2 0 00-2-2H8a2 2 0 00-2 2v4zm10-6V7a4 4 0 10-8 0v4"/>
                                </svg>
                            </template>
                            <template x-if="sellado">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <span x-text="sellado ? 'Sellado ✓' : 'Sellar'"></span>
                        </span>
                        <span wire:loading wire:target="sellarXml" class="flex items-center">
                            <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Sellando...
                        </span>
                    </x-filament::button>
                </form>
                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 text-xs text-gray-500 dark:text-gray-400">
                    Paso 2
                </div>
            </div>

            <!-- Paso 3: Timbrar XML -->
            <div class="relative">
                <form wire:submit.prevent="timbrarXml">
                    <x-filament::button
                        type="submit"
                        x-bind:color="timbrado ? 'success' : 'success'"
                        x-bind:disabled="!sellado || timbrado"
                        wire:loading.attr="disabled"
                        wire:target="timbrarXml"
                        class="transition-all duration-300"
                        x-bind:class="(!sellado || timbrado) ? 'opacity-50 cursor-not-allowed' : (timbrado ? 'opacity-75' : '')"
                    >
                        <span class="flex items-center" wire:loading.remove>
                            <template x-if="!timbrado">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4-4" />
                                </svg>
                            </template>
                            <template x-if="timbrado">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <span x-text="timbrado ? 'Timbrado ✓' : 'Timbrar'"></span>
                        </span>
                        <span wire:loading wire:target="timbrarXml" class="flex items-center">
                            <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Timbrando...
                        </span>
                    </x-filament::button>
                </form>
                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 text-xs text-gray-500 dark:text-gray-400">
                    Paso 3
                </div>
            </div>

            <!-- Paso 4: Depositar -->
            <div class="relative">
                <form wire:submit.prevent="publicacion">
                    <x-filament::button
                        type="submit"
                        x-bind:color="depositado ? 'success' : 'danger'"
                        x-bind:disabled="!timbrado || depositado"
                        wire:loading.attr="disabled"
                        wire:target="publicacion"
                        class="transition-all duration-300"
                        x-bind:class="!timbrado ? 'opacity-50 cursor-not-allowed' : (depositado ? 'opacity-75' : '')"
                    >
                        <span class="flex items-center" wire:loading.remove>
                            <template x-if="!depositado">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01.88-7.88A5 5 0 1117 9h-1" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v6m0 0l-3-3m3 3l3-3" />
                                </svg>
                            </template>
                            <template x-if="depositado">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <span x-text="depositado ? 'Depositado ✓' : 'Depositar'"></span>
                        </span>
                        <span wire:loading wire:target="publicacion" class="flex items-center">
                            <svg class="animate-spin w-5 h-5 mr-2 -ml-1 text-white" fill="none" viewBox="0 0 24 24">
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Depositando...
                        </span>
                    </x-filament::button>
                </form>
                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 text-xs text-gray-500 dark:text-gray-400">
                    Paso 4
                </div>
            </div>



        <!-- Botón de descarga -->
        <div class="relative">
            <template x-if="sellado && xmlPath">
                <a :href="'/storage/' + xmlPath" download class="filament-button flex items-center mt-4 ">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                    </svg>
                    Descargar XML
                </a>
            </template>
        </div>
    </div>

      <div class="mt-6 px-2 py-4">
        Estado: <span x-html="estado"></span>
      </div>
    </div>
</x-filament-panels::page>
