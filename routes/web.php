<?php

use App\Http\Controllers\CfdiController;
use App\Http\Controllers\EmisionController;
use App\Http\Controllers\ProfileController;
use App\Models\CfdiArchivo;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return redirect()->to('admin/login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cfdi-continue/{id}', \App\Filament\Pages\CfdiContinue::class)->name('filament.admin.pages.cfdi-continues');

    Route::get('/descargar-xml/{factura}', [CfdiController::class, 'descargarXml'])->name('facturas.descargar-xml');
    Route::get('/descargar-pdf/{factura}', [CfdiController::class, 'descargarPdf'])->name('facturas.descargar-pdf');

    Route::get('emision/descargar-xml/{emision}', [EmisionController::class, 'descargarXmlEmision'])->name('emision.descargar-xml');
});

require __DIR__.'/auth.php';
