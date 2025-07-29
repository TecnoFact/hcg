<?php

// translations for EightyNine/ExcelImportAction
return [
    // Etiquetas de la acción de importación
    'import_action_heading' => 'Importar Excel',
    'import_action_description' => 'Importa datos a la base de datos desde un archivo Excel',
    'excel_data' => 'Datos de Excel',
    'download_sample_excel_file' => 'Descargar archivo Excel de muestra',

    // Mensajes de estado de importación
    'import_failed' => 'Importación fallida',
    'import_warning' => 'Advertencia de importación',
    'import_information' => 'Información de importación',
    'import_success' => 'Importación exitosa',

    // Mensajes de validación
    'validation_failed' => 'La fila :row no pasó la validación. Se devolvieron los siguientes mensajes: :messages',

    // Errores de validación de archivo
    'file_empty_error' => 'El archivo subido está vacío o no contiene datos válidos.',
    'header_read_error' => 'No se pudo leer la fila de encabezado del archivo subido.',
    'missing_headers_error' => 'Faltan encabezados requeridos: :missing. Encabezados esperados: :expected',
];
