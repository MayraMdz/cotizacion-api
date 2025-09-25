<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $fillable = ['tipo', 'fecha', 'compra', 'venta', 'payload'];

    protected $casts = [
        'fecha'   => 'date',
        'payload' => 'array',
    ];
}
