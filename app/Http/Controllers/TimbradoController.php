<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TimbradoController extends Controller
{
    /**
     * Simula el timbrado de un CFDI firmado.
     */
    public function timbrar(Request $request)
    {
        \Log::info('Entró al método timbrar');
        try {
            $request->validate([
                'xml' => 'required|file|mimes:xml|max:1024',
            ]);
            \Log::info('Validación de archivo pasó correctamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'mensaje' => 'La validación del archivo falló.',
                'errores' => $e->errors(),
            ], 422);
        }

        $archivo = $request->file('xml');
        $xmlContent = file_get_contents($archivo);
        // Simular UUID y fecha de timbrado
        $uuid = Str::uuid()->toString();
        $fechaTimbrado = now()->toIso8601String();

        // Agregar un timbre simulado como complemento (solo para fines de prueba)
        $timbre = <<<XML
        <cfdi:Complemento>
            <tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital"
                Version="1.1"
                UUID="{$uuid}"
                FechaTimbrado="{$fechaTimbrado}"
                RfcProvCertif="EKU9003173C9"
                SelloCFD="..."
                NoCertificadoSAT="00001000000403258748"
                SelloSAT="..." />
        </cfdi:Complemento>
        XML;
        \Log::info('Inserto timbre fiscal');
        // Insertar timbre al final antes del cierre del XML
        $xmlTimbrado = preg_replace(
            '/<\/cfdi:Comprobante>/', 
            $timbre . "
                </cfdi:Comprobante>",
            $xmlContent
        );

        // Guardar XML timbrado
        $nombre = 'cfdis/timbrado_' . $archivo->getClientOriginalName();
        Storage::disk('local')->put($nombre, $xmlTimbrado);
        \Log::info('CFDI timbrado exitosamente', [
            'uuid' => $uuid,
            'archivo' => $nombre
        ]);
        return response()->json([
            'status' => 'success',
            'mensaje' => 'El CFDI fue timbrado exitosamente.',
            'uuid' => $uuid,
            'fecha_timbrado' => $fechaTimbrado,
            'archivo_guardado' => $nombre
        ]);
        
    }
}
