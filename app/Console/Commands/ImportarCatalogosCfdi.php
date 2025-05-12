<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\CatalogoCsvLoaderService;

class ImportarCatalogosCfdi extends Command
{
    protected $signature = 'catalogos:importar';
    protected $description = 'Importa todos los catálogos CFDI desde CSVs locales';

    public function handle()
    {
        // ==========================
        // Catálogos de Emision
        // ==========================
        
        $ruta = storage_path('app/catalogos/cfdi40');

        if (!File::exists($ruta)) {
            $this->error("No se encontró la carpeta: $ruta");
            return;
    }

        
        $archivos = File::files($ruta);

        foreach ($archivos as $archivo) {
            $nombreArchivo = $archivo->getFilename();

            if (!str_ends_with($nombreArchivo, '.csv')) {
                continue;
    }

            // Elimina prefijo 'c_' y convierte CamelCase a snake_case (ej: FormaPago → forma_pago)
            $nombreBase = pathinfo($nombreArchivo, PATHINFO_FILENAME); // Ej: c_RegimenFiscal
            $nombreBase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('c_', '', $nombreBase))); // Ej: regimen_fiscal
            $nombreTabla = 'catalogo_' . $nombreBase;

            $this->info("Importando: $nombreArchivo => tabla $nombreTabla");

            try {
                // Definir headerOffset según catálogo
                $headerOffsets = [

                    'aduana' => 3,
                    'estado' => 3,
                    'tipofactor' => 3,
    ];

                $offset = $headerOffsets[$nombreBase] ?? 3;

                $loader = new CatalogoCsvLoaderService(
                    $nombreTabla,
                    $archivo->getRealPath(),
                    $offset,
                    $this->getTransformCallback($nombreBase)
                );

                $loader->load();
    } catch (\Throwable $e) {
                $this->error("Error al importar $nombreArchivo: " . $e->getMessage());
    }
    }
        // ==========================
        // Catálogos de Nómina
        // ==========================
        $rutaNomina = storage_path('app/catalogos/nomina');

        if (!File::exists($rutaNomina)) {
            $this->warn("No se encontró la carpeta de nómina: $rutaNomina");
    } else {
            $archivosNomina = File::files($rutaNomina);

            foreach ($archivosNomina as $archivo) {
                $nombreArchivo = $archivo->getFilename();

                if (!str_ends_with($nombreArchivo, '.csv')) {
                    continue;
    }

                $nombreBase = pathinfo($nombreArchivo, PATHINFO_FILENAME);
                $nombreBase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('c_', '', $nombreBase)));
                $nombreTabla = 'catalogo_' . $nombreBase;

                $this->info("Importando (nómina): $nombreArchivo => tabla $nombreTabla");

                try {
                    $headerOffsets = [
                    'estado' => 3,
                    'tipofactor' => 3,
                    ];

                    $offset = $headerOffsets[$nombreBase] ?? 3;

                    $loader = new \App\Services\CatalogoCsvLoaderService(
                        $nombreTabla,
                        $archivo->getRealPath(),
                        $offset,
                        $this->getTransformCallback($nombreBase)
                    );

                    $loader->load();
                    } catch (\Throwable $e) {
                    $this->error("Error al importar $nombreArchivo: " . $e->getMessage());
                    }
                }
    }


        $this->info("✅ Proceso de importación finalizado.");
    }

    private function getTransformCallback(string $catalogo): \Closure
    {
        return match ($catalogo) {
            // ===============================

        
            // Transform: forma_pago

        
            // ===============================

        
            'forma_pago' => function($record) {
            if (!isset($record['c_FormaPago']) || empty($record['Descripción'])) return null;

            return [
                'match' => ['clave' => $record['c_FormaPago']],
                'data' => [
                    'descripcion' => $record['Descripción'],
                    'bancarizado' => strtolower($record['Bancarizado']) === 'sí',
                    'requiere_numero_operacion' => strtolower($record['Número de operación']) === 'sí',
                    'requiere_rfc_emisor_cuenta_ordenante' => strtolower($record['RFC del Emisor de la cuenta ordenante']) === 'sí',
                    'requiere_cuenta_ordenante' => strtolower($record['Cuenta Ordenante']) === 'sí',
                    'patron_cuenta_ordenante' => $record['Patrón para cuenta ordenante'] ?? null,
                    'requiere_rfc_emisor_cuenta_beneficiario' => strtolower($record['RFC del Emisor Cuenta de Beneficiario']) === 'sí',
                    'requiere_cuenta_beneficiario' => strtolower($record['Cuenta de Benenficiario']) === 'sí',
                    'patron_cuenta_beneficiario' => $record['Patrón para cuenta Beneficiaria'] ?? null,
                    'requiere_tipo_cadena_pago' => strtolower($record['Tipo Cadena Pago']) === 'sí',
                    'nombre_banco_extranjero' => $record['Nombre del Banco emisor de la cuenta ordenante en caso de extranjero'] ?? null,
                    'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                    'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];
    },
            
            

            
            // ===============================

            
            // Transform: metodopago

            
            // ===============================

            
            'metodo_pago' => function ($record) {
                // Filtro estricto para omitir encabezados o basura
                if (
                    !isset($record['c_MetodoPago']) ||
                    $record['c_MetodoPago'] === 'c_MetodoPago' ||                   // encabezado real
                    str_starts_with($record['c_MetodoPago'], 'Versión') ||         // línea basura
                    !preg_match('/^[A-Z]{3}$/', $record['c_MetodoPago'])            // clave válida PUE / PPD
                ) {
                    return null;
                }

                return [
                    'match' => ['clave' => $record['c_MetodoPago']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                        'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },
            // puedes agregar más aquí:

                


                // ===============================


                // Transform: regimen_fiscal


                // ===============================


                'regimen_fiscal' => function($record) {
                    if (
                        !isset($record['c_RegimenFiscal']) ||
                        !is_numeric($record['c_RegimenFiscal']) ||
                        empty($record['Descripción'])
                    ) {
                        return null;
    }

                    return [
                        'match' => ['clave' => $record['c_RegimenFiscal']],
                        'data' => 
                        [
                            'descripcion' => $record['Descripción'],
                            'persona_fisica' => strtolower($record['Física']) === 'sí',
                            'persona_moral' => strtolower($record['Moral']) === 'sí',
                            'vigencia_desde' => $record['Fecha de inicio de vigencia'] ?? null,
                            'vigencia_hasta' => $record['Fecha de fin de vigencia'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    ];
    },

                


                // ===============================


                // Transform: uso_cfdi


                // ===============================


                'uso_cfdi' => function($record) {
                    if (!isset($record['c_UsoCFDI']) || empty($record['Descripción'])) return null;

                    return [
                        'match' => ['clave' => $record['c_UsoCFDI']],
                        'data' => 
                        [
                            'descripcion' => $record['Descripción'],
                            'tipo_persona' => $record['Aplica para tipo persona'] ?? '',
                            'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                            'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                            'regimenes_fiscales' => $record['Régimen Fiscal Receptor'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    ];
    },
           
                

           
                // ===============================

           
                // Transform: tipo_relacion

           
                // ===============================

           
                'tipo_relacion' => function($record) {
                    if (!preg_match('/^[0-9]{2}$/', $record['c_TipoRelacion'])) return null;

                    return [
                        'match' => ['clave' => $record['c_TipoRelacion']],
                        'data' => [
                        'descripcion' => $record['Descripción'],
                        'vigencia_desde' => $record['Fecha inicio de vigencia'] ?? null,
                        'vigencia_hasta' => $record['Fecha fin de vigencia'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        ]
                    ];
    },

            


            // ===============================


            // Transform: clave_unidad


            // ===============================


            'clave_unidad' => function($record) {
                if (!preg_match('/^[0-9A-Z]{1,10}$/', $record['c_ClaveUnidad'])) return null;


             return [
                 'match' => ['clave' => $record['c_ClaveUnidad']],
                    'data' => [
                    'nombre' => $record['Nombre'],
                    'descripcion' => $record['Descripción'],
                    'nota' => $record['Nota'] ?? null,
                    'vigencia_desde' => empty($record['Fecha de inicio de vigencia']) ? null : $record['Fecha de inicio de vigencia'],
                    'vigencia_hasta' => empty($record['Fecha de fin de vigencia']) ? null : $record['Fecha de fin de vigencia'],
                    'simbolo' => $record['Símbolo'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];
    },
          
            

          
            // ===============================

          
            // Transform: clave_prod_serv

          
            // ===============================

          
            'clave_prod_serv' => function($record) {
                if (!preg_match('/^[0-9]{8}$/', $record['c_ClaveProdServ'])) return null;


                return [
                    'match' => ['clave' => $record['c_ClaveProdServ']],
                    'data' => 
                    [
                        'descripcion' => $record['Descripción'],
                        'incluir_iva_trasladado' => strtolower($record['Incluir IVA trasladado']) === 'sí',
                        'incluir_ieps_trasladado' => strtolower($record['Incluir IEPS trasladado']) === 'sí',
                        'complemento' => $record['Complemento que debe incluir'] ?? null,
                        'vigencia_desde' => empty($record['FechaInicioVigencia']) ? null : $record['FechaInicioVigencia'],
                        'vigencia_hasta' => empty($record['FechaFinVigencia']) ? null : $record['FechaFinVigencia'],

                        'estimulo_franja_fronteriza' => $record['Estímulo Franja Fronteriza'] ?? null,
                        'palabras_similares' => $record['Palabras similares'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
    },


            



            // ===============================



            // Transform: tipo_de_comprobante



            // ===============================



                    'tipo_de_comprobante' => function($record) {
                        if (!preg_match('/^[A-Z]$/', $record['c_TipoDeComprobante'])) return null;

                        return [
                            'match' => ['clave' => $record['c_TipoDeComprobante']],
                            'data' => [
                                'descripcion' => $record['Descripción'],
                                'valor_maximo' => is_numeric($record['Valor máximo']) ? $record['Valor máximo'] : null,
                                'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                                'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        ];
            },

            'colonia' => function ($record) {
                if (!isset($record['c_Colonia']) || empty($record['Descripción'])) return null;

                return [
                    'match' => ['clave' => $record['c_Colonia']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'localidad' => function ($record) {
                if (!isset($record['c_Localidad']) || empty($record['Descripción'])) return null;

                return [
                    'match' => ['clave' => $record['c_Localidad']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'municipio' => function ($record) {
                if (!isset($record['c_Municipio']) || empty($record['Descripción'])) return null;

                return [
                    'match' => ['clave' => $record['c_Municipio']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },
    



               
            //Importacion de catalogos de nomina
            

            // ===============================

            // Transform: tipo_contrato

            // ===============================

            'tipo_contrato' => function($record) {
                if (
                        !isset($record['c_TipoContrato']) ||
                        !is_numeric($record['c_TipoContrato']) ||
                        empty($record['Descripción'])
                ) {
                return null;
                }

                return [
                        'match' => ['clave' => $record['c_TipoContrato']],
                        'data' => [
                            'descripcion' => $record['Descripción'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    ];
            },
            
            'aduana' => function ($record) {
                if (!isset($record['c_Aduana']) || !is_numeric($record['c_Aduana'])) return null;

                return [
                    'match' => ['clave' => $record['c_Aduana']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                        'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },
 
            'codigo_postal' => function ($record) {
                if (!isset($record['c_CodigoPostal']) || !is_numeric($record['c_CodigoPostal'])) return null;

                return [
                    'match' => ['clave' => $record['c_CodigoPostal']],
                    'data' => [
                        'estado' => $record['c_Estado'] ?? null,
                        'municipio' => $record['c_Municipio'] ?? null,
                        'localidad' => $record['c_Localidad'] ?? null,
                        'estimulo_franja_fronteriza' => strtolower($record['Estímulo Franja Fronteriza'] ?? '') === 'sí',
                        'vigencia_desde' => empty(trim($record['Fecha inicio de vigencia'])) ? null : trim($record['Fecha inicio de vigencia']),
                        'vigencia_hasta' => empty(trim($record['Fecha fin de vigencia'])) ? null : trim($record['Fecha fin de vigencia']),
                        'referencias_huso_horario' => $record['Referencias del Huso Horario'] ?? null,
                        'descripcion_huso_horario' => $record['Descripción del Huso Horario'] ?? null,
                        'mes_inicio_horario_verano' => $record['Mes_Inicio_Horario_Verano'] ?? null,
                        'dia_inicio_horario_verano' => $record['Día_Inicio_Horario_Verano'] ?? null,
                        'mes_inicio_horario_invierno' => $record['Mes_Inicio_Horario_Invierno'] ?? null,
                        'dia_inicio_horario_invierno' => $record['Día_Inicio_Horario_Invierno'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'estado' => function ($record) {
                if (!isset($record['c_Estado']) || empty($record['Nombre del estado'])) return null;

                return [
                    'match' => ['clave' => $record['c_Estado']],
                    'data' => [
                        'pais' => $record['c_Pais'],
                        'nombre' => $record['Nombre del estado'],
                        'vigencia_desde' => $record['Fecha inicio de vigencia'] ?? null,
                        'vigencia_hasta' => $record['Fecha fin de vigencia'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'exportacion' => function ($record) {
                if (!isset($record['c_Exportacion']) || empty($record['Descripción'])) return null;
                return [
                    'match' => ['clave' => $record['c_Exportacion']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'impuesto' => function ($record) {
                if (!isset($record['c_Impuesto']) || empty($record['Descripción'])) return null;
                return [
                    'match' => ['clave' => $record['c_Impuesto']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'retencion' => strtolower($record['Retención'] ?? '') === 'sí',
                        'traslado' => strtolower($record['Traslado'] ?? '') === 'sí',
                        'ambito' => $record['Ámbito'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'meses' => function ($record) {
                if (!isset($record['c_Mes'])) return null;
                return [
                    'match' => ['clave' => $record['c_Mes']],
                    'data' => [
                        'nombre' => $record['Nombre'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'metodo_pago' => function ($record) {
                if (!isset($record['c_MetodoPago']) || empty($record['Descripción'])) return null;

                return [
                    'match' => ['clave' => $record['c_MetodoPago']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'vigencia_desde' => $record['Fecha inicio de vigencia'] ?? null,
                        'vigencia_hasta' => $record['Fecha fin de vigencia'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            'moneda' => function ($record) {
                if (!isset($record['c_Moneda']) || !is_string($record['Descripción']) || !is_numeric($record['Decimales'])) return null;


                return [
                    'match' => ['clave' => $record['c_Moneda']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'decimales' => is_numeric($record['Decimales']) ? (int)$record['Decimales'] : null,
                        'por_defecto' => 0, // Aquí podrías poner una lógica si hay columna para ello
                        'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                        'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            
            'num_pedimento_aduana' => function ($record) {
                if (!isset($record['c_NumPedimentoAduana'])) return null;
                return [
                    'match' => ['clave' => $record['c_NumPedimentoAduana']],
                    'data' => [
                        'aduana' => $record['Aduana'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            
            'objeto_imp' => function ($record) {
                if (!isset($record['c_ObjetoImp']) || empty($record['Descripción'])) return null;

                return [
                    'match' => ['clave' => $record['c_ObjetoImp']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            
            'pais' => function ($record) {
                if (!isset($record['c_Pais']) || empty($record['Codigo del país'])) return null;

                return [
                    'match' => ['clave' => $record['c_Pais']],
                    'data' => [
                        'nombre' => $record['Nombre del país'],
                        'nacionalidad' => $record['Nacionalidad'] ?? null,
                        'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                        'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },
            
            'patente_aduanal' => function ($record) {
                if (!isset($record['c_PatenteAduanal'])) return null;
                return [
                    'match' => ['clave' => $record['c_PatenteAduanal']],
                    'data' => [
                        'nombre' => $record['Nombre'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            
            'periodicidad' => function ($record) {
                // Validar que el registro tiene clave y descripción
                if (!isset($record['c_Periodicidad']) || empty($record['Descripción'])) return null;

                return [
                    'match' => ['clave' => $record['c_Periodicidad']],
                    'data' => [
                        'descripcion' => $record['Descripción'],
                        'equivalencia' => $record['Equivalencia'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            
            'tasa_o_cuota' => function ($record) {
                if (!isset($record['Rango']) && !isset($record['c_TasaOCuota'])) return null;
                return [
                    'match' => ['rango' => $record['Rango'] ?? $record['c_TasaOCuota']],
                    'data' => [
                        'valor_minimo' => isset($record['Valor mínimo']) ? (float)$record['Valor mínimo'] : null,
                        'valor_maximo' => isset($record['Valor máximo']) ? (float)$record['Valor máximo'] : null,
                        'traslado' => strtolower($record['Traslado'] ?? '') === 'sí',
                        'retencion' => strtolower($record['Retención'] ?? '') === 'sí',
                        'tipo_factor' => $record['Tipo Factor'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            
            'tipo_factor' => function ($record) {
                if (
                    !isset($record['c_TipoFactor']) ||
                    $record['c_TipoFactor'] === 'c_TipoFactor' || // encabezado
                    $record['c_TipoFactor'] === 'Versión CFDI' || // basura
                    !preg_match('/^[A-Za-z]+$/', $record['c_TipoFactor']) // clave esperada
                ) {
                    return null;
                }

                return [
                    'match' => ['clave' => $record['c_TipoFactor']],
                    'data' => [
                        'vigencia_desde' => empty($record['Fecha inicio de vigencia']) ? null : $record['Fecha inicio de vigencia'],
                        'vigencia_hasta' => empty($record['Fecha fin de vigencia']) ? null : $record['Fecha fin de vigencia'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ];
            },

            default => function () {
                return null;
            }
        };
    }
}

