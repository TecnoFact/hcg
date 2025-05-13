<?php

return [

    // RFC del PAC autorizado
    'Rfc' => 'ASF180914KY5',

    // URL del servicio para firmar la cadena con SHA1 usando HSM
    'FirmaSha1Url' => 'http://35.208.215.143/akval-firma/api/FirmaHsm/FirmaCxi',

    // Endpoint de autenticación del SAT
    'EndpointAuth' => 'https://recepcion.facturaelectronica.sat.gob.mx/Seguridad/Autenticacion.svc',

    // Endpoint para enviar CFDI al SAT
    'EndpointEnviarCfdi' => 'https://recepcion.facturaelectronica.sat.gob.mx/Recepcion/CFDI40/RecibeCFDIService.svc?singleWsdl',

    // Contenedor de Azure donde se suben los CFDI
    'ContainerName' => 'asf180914ky5',

    // Firma de acceso compartido (SAS) para Azure Blob Storage
    'SharedAccesSignature' => '?sv=2023-01-03&si=WriteOnly&sr=c&sig=uc9fSFKyGwiDLT5MQ6u4roh4AeZ17gXBZE71WYwMHyk%3D',

    // URL base del Blob Storage
    'BlobStorageEndpoint' => 'https://cfdipac08.blob.core.windows.net/',
];
