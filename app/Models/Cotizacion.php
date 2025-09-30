<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';
    protected $fillable = ['fecha','tipo','valor'];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'valor' => 'decimal:2',
    ];
}
