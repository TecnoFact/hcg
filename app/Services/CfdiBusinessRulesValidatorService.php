<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Sat\CfdiErrorCatalog;
use App\Services\Exceptions\CfdiValidationException;

class CfdiBusinessRulesValidatorService
{
    protected array $errors = [];

    public function validate(array $cfdi): void
    {
        $this->errors = [];

        $this->validarUsoCfdi(
            $cfdi['uso_cfdi'] ?? '',
            $cfdi['tipo_persona_receptor'] ?? ''
        );

        // Agrega más validaciones aquí (ej. Método de pago, Régimen Fiscal...)

        $this->validarTipoComprobante($cfdi['tipo_comprobante'] ?? '');
        $this->validarMoneda($cfdi['moneda'] ?? '');
        $this->validarFormaPago($cfdi['forma_pago'] ?? '');
        $this->validarMetodoPago($cfdi['metodo_pago'] ?? '');
        $this->validarRegimenFiscalReceptor($cfdi['regimen_fiscal_receptor'] ?? '');
        $this->validarObjetoImp($cfdi['objeto_imp'] ?? '');
        $this->validarExportacion($cfdi['exportacion'] ?? '');
        $this->validarTipoRelacion($cfdi['tipo_relacion'] ?? '');
        // Validacion Cruzada de ;etodo de pago y Forma de Pago
        $this->validarRelacionMetodoYFormaPago(
            $cfdi['metodo_pago'] ?? '',
            $cfdi['forma_pago'] ?? ''
        );
        
        $this->validarConceptos($cfdi['conceptos'] ?? []);
        // Validacion Cruzada 
        $this->validarUsoCfdiContraRegimenFiscal(
            $cfdi['uso_cfdi'] ?? '',
            $cfdi['regimen_fiscal_receptor'] ?? ''
        );
        
        if (!empty($this->errors)) {
            throw new CfdiValidationException($this->errors);
        }
    }

    protected function validarUsoCfdi(string $usoCfdi, string $tipoPersonaReceptor): void
    {
        $uso = DB::table('catalogo_uso_cfdi')->where('clave', $usoCfdi)->first();

        if (!$uso) {
            $this->addError('CFDI40144');
            return;
        }

        if ($tipoPersonaReceptor === 'F' && strtolower($uso->tipo_persona) === 'm') {
            $this->addError('CFDI40145');
        }

        if ($tipoPersonaReceptor === 'M' && strtolower($uso->tipo_persona) === 'f') {
            $this->addError('CFDI40146');
        }
    }
    protected function validarTipoComprobante(string $tipo): void
    {
        if (!DB::table('catalogo_tipo_de_comprobante')->where('clave', $tipo)->exists()) {
            $this->addError('CFDI40147');
        }
    }
    
    protected function validarMoneda(string $moneda): void
    {
        if (!DB::table('catalogo_moneda')->where('clave', $moneda)->exists()) {
            $this->addError('CFDI40149');
        }
    }
    
    protected function validarFormaPago(string $formaPago): void
    {
        if (!DB::table('catalogo_forma_pago')->where('clave', $formaPago)->exists()) {
            $this->addError('CFDI40150');
        }
    }
    
    protected function validarMetodoPago(string $metodo): void
    {
        if (!DB::table('catalogo_metodo_pago')->where('clave', $metodo)->exists()) {
            $this->addError('CFDI40130');
        }
    }
    
    protected function validarRegimenFiscalReceptor(string $regimen): void
    {
        if (!DB::table('catalogo_regimen_fiscal')->where('clave', $regimen)->exists()) {
            $this->addError('CFDI40120');
        }
    }
    
    protected function validarObjetoImp(string $objeto): void
    {
        if (!DB::table('catalogo_objeto_imp')->where('clave', $objeto)->exists()) {
            $this->addError('CFDI40153');
        }
    }
    
    protected function validarExportacion(string $valor): void
    {
        if (!DB::table('catalogo_exportacion')->where('clave', $valor)->exists()) {
            $this->addError('CFDI40152');
        }
    }
    
    protected function validarTipoRelacion(string $tipoRelacion): void
    {
        if (!empty($tipoRelacion) && !DB::table('catalogo_tipo_relacion')->where('clave', $tipoRelacion)->exists()) {
            $this->addError('CFDI40154');
        }
    }
        
    protected function validarRelacionMetodoYFormaPago(string $metodoPago, string $formaPago): void
    {
        if (empty($metodoPago) || empty($formaPago)) {
            return; // Ya fue validado en pasos anteriores
        }
    
        // Ejemplo básico de incompatibilidad común
        if ($metodoPago === 'PUE' && $formaPago === '99') {
            $this->addError('CFDI40151', 'La combinación de Método de Pago PUE con Forma de Pago "Por definir" no es válida.');
        }
    
        if ($metodoPago === 'PPD' && $formaPago === '01') {
            $this->addError('CFDI40151', 'La combinación de Método de Pago PPD con Forma de Pago "Efectivo" no es válida.');
        }
    
        // Puedes agregar más combinaciones conforme al SAT
    }
    
    // Validacion de Conceptos 
    protected function validarConceptos(array $conceptos): void
    {
        foreach ($conceptos as $index => $concepto) {
            $claveProdServ = $concepto['clave_prod_serv'] ?? '';
            $claveUnidad = $concepto['clave_unidad'] ?? '';
            $unidad = $concepto['unidad'] ?? '';
    
            // Validar ClaveProdServ
            if (!DB::table('catalogo_clave_prod_serv')->where('clave', $claveProdServ)->exists()) {
                $this->addError('CFDI40155', "La ClaveProdServ '{$claveProdServ}' en el concepto {$index} no existe en el catálogo.");
            }
    
            // Validar ClaveUnidad
            if (!DB::table('catalogo_clave_unidad')->where('clave', $claveUnidad)->exists()) {
                $this->addError('CFDI40156', "La ClaveUnidad '{$claveUnidad}' en el concepto {$index} no existe en el catálogo.");
            }
    
            // Validar símbolo de unidad (opcional pero recomendado)
            if (!empty($unidad)) {
                $unidadDb = DB::table('catalogo_clave_unidad')
                    ->where('clave', $claveUnidad)
                    ->first();
    
                if ($unidadDb && $unidadDb->simbolo !== null && strtolower($unidadDb->simbolo) !== strtolower($unidad)) {
                    $this->addError('CFDI40157', "La unidad '{$unidad}' no corresponde al símbolo esperado de la ClaveUnidad '{$claveUnidad}' en el concepto {$index}.");
                }
            }
        }
    }
    
    protected function validarUsoCfdiContraRegimenFiscal(string $usoCfdi, string $regimenReceptor): void
    {
        if (empty($usoCfdi) || empty($regimenReceptor)) {
            return;
        }
    
        $registro = DB::table('catalogo_uso_cfdi')->where('clave', $usoCfdi)->first();
    
        if (!$registro || empty($registro->regimenes_fiscales)) {
            return;
        }
    
        $regimenesValidos = array_map('trim', explode(',', $registro->regimenes_fiscales));
    
        if (!in_array($regimenReceptor, $regimenesValidos)) {
            $this->addError('CFDI40145A', "El UsoCFDI '{$usoCfdi}' no es compatible con el Régimen Fiscal '{$regimenReceptor}' del receptor.");
        }
    }
    


    protected function addError(string $codigo, string $mensaje = ''): void
    {
        $this->errors[] = [
            'codigo' => $codigo,
            'mensaje' => $mensaje ?: CfdiErrorCatalog::getMessage($codigo),
        ];
    }
}

