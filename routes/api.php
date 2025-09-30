<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizacionController;

Route::get('/convertir', [CotizacionController::class, 'convertir']);