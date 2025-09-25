<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertirRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0'],
            'tipo'  => ['nullable', 'string', 'max:50'],
            'fecha' => ['nullable', 'date'], // YYYY-MM-DD
            'usar_venta' => ['nullable', 'boolean'], // default true
        ];
    }
}
