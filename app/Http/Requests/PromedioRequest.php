<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromedioRequest extends FormRequest
{
     public function rules(): array
    {
        return [
            'tipo'  => ['required', 'string', 'max:50'],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'campo' => ['nullable', 'in:compra,venta'], // default: venta
        ];
    }
}
