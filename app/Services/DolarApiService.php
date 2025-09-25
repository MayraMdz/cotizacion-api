<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DolarApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.dolarapi.url'), '/');
    }

    /**
     * Cotización actual según tipo (blue, oficial, mep, etc.)
     */
    public function getUltimaPorTipo(string $tipo): ?array
    {
        $resp = Http::get("{$this->baseUrl}/{$tipo}");
        if ($resp->failed()) return null;
        return $resp->json();
    }

    /**
     * Cotización histórica (si la API lo soporta).
     * Si no, podés dejarlo null y usar solo la BD.
     */
    public function getHistorica(string $tipo, string $fechaYmd): ?array
    {
        // Acá depende de si la API soporta histórico.
        // Si no, devolvemos null para que tu controlador use la DB.
        return null;
    }
}
