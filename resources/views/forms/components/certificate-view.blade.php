<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ file_certificate: '', handleFileChange(e) { const file = e.target.files[0]; this.file_certificate = file ? file.name : ''; } }" class="fi-fo-file-upload flex flex-col gap-2 filepond--root mb-5"  >
        <label class="fi-fo-file-upload-label">
            <input type="file" accept=".cer" wire:model="data.file_certificate" name="file_certificate" class="fi-fo-file-upload-input hidden" @change="handleFileChange" x-ref="file_certificate" >
            <button type="button" @click="$refs.file_certificate.click()" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action">
                Subir archivo .cer
            </button>
        </label>
        <template x-if="file_certificate">
            <span class="fi-fo-file-upload-filename text-sm text-gray-600">Archivo seleccionado: <span x-text="file_certificate"></span></span>
        </template>
    </div>
</x-dynamic-component>
