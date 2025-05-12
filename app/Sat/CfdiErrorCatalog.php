<?php

namespace App\Sat;

class CfdiErrorCatalog
{
    /**
     * Devuelve el mensaje oficial para un código de error del SAT.
     */
    public static function getMessage(string $codigo): string
    {
        $errores = [
            'CFDI40144' => 'El campo UsoCFDI debe encontrarse en el catálogo c_UsoCFDI.',
            'CFDI40145' => 'El UsoCFDI seleccionado no aplica para personas físicas.',
            'CFDI40146' => 'El UsoCFDI seleccionado no aplica para personas morales.',
            'CFDI40130' => 'El Método de Pago no existe en el catálogo c_MetodoPago.',
            'CFDI40120' => 'El Régimen Fiscal del receptor no existe en el catálogo c_RegimenFiscal.',
            // Aquí se agregarán más códigos oficiales del SAT
        ];

        return $errores[$codigo] ?? 'Error desconocido según catálogo del SAT.';
    }
}
