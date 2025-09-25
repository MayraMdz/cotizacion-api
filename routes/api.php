<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizacionController;

Route::get('/cotizaciones', [CotizacionController::class, 'show']);          // listar/obtener por fecha
Route::post('/cotizaciones', [CotizacionController::class, 'store']);        // guardar manual
Route::get('/convertir',     [CotizacionController::class, 'convertir']);    // convertir USD->ARS
Route::get('/cotizaciones/promedio', [CotizacionController::class, 'promedio']); // promedio entre fechas
