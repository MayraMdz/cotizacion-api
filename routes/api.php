<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizacionController;

// CRUD mínimo + consultas
Route::post('/cotizaciones', [CotizacionController::class, 'store']);   // guardar/actualizar
Route::get('/cotizaciones', [CotizacionController::class, 'index']);    // listar (filtros)
Route::get('/cotizaciones/promedio', [CotizacionController::class, 'promedio']); // promedio

// Conversión USD -> ARS usando última cotización del tipo
Route::get('/convertir', [CotizacionController::class, 'convertir']);
Route::post('/cotizaciones/sync', [CotizacionController::class, 'sync']); // forzar fetch y guardar