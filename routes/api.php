<?php

use App\Http\Controllers\Api\EmisionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CfdiController;
use App\Http\Controllers\TimbradoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->post('/cfdi', [CfdiController::class, 'store']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/timbrar', [TimbradoController::class, 'timbrar']);

//Route::middleware('auth:sanctum')->post('/cfdi-timbrado', [CfdiController::class, 'uploadAndSendSat']);


Route::middleware('auth:sanctum')->prefix('v1')->group(function () {


    // RUTA PARA SELLAR UN CFDI
    Route::post('/cfdi-sellar', [EmisionController::class, 'generateSealFromXml']);

    // RUTA PARA TIMBRAR CFDI
    Route::post('/cfdi-timbrar', [EmisionController::class, 'stampCfdiFromXml']);

    // RUTA PARA DEPOSITO
    Route::post('/cfdi-timbrado', [CfdiController::class, 'uploadAndSendSat']);

    // RUTA PARA SELLAR UN XML USANDO CERTIFICADO; KEY Y PASSWORD MAS EL XML A SELLAR
    Route::post('/sellado', [TimbradoController::class, 'sellarCfdi']);
});


Route::middleware('auth:sanctum')->get('/perfil', function (Request $request) {
    return response()->json([
        'usuario' => $request->user(),
    ]);
});

Route::middleware('auth:sanctum')->get('/debug', function (Request $request) {
    $user = $request->user();

    Log::info('Usuario autenticado desde /api/debug:', ['user' => $user]);

    return response()->json([
        'ok' => true,
        'user' => $user,
    ]);
});
