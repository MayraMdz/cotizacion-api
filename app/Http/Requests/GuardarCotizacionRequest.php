<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarCotizacionRequest extends FormRequest
{
        public function rules(): array
    {
        return [
            'tipo'   => ['required', 'string', 'max:50'],
            'fecha'  => ['required', 'date'],
            'compra' => ['nullable', 'numeric', 'gt:0'],
            'venta'  => ['nullable', 'numeric', 'gt:0'],

        ];
    }
}
