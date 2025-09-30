<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CotizacionController extends Controller
{
    /* =================== Helpers API =================== */

    /**
     * Llama a DólarAPI para un tipo y devuelve ['fecha'=>'Y-m-d','valor'=>float] o null si falla.
     */
    private function fetchFromApi(string $tipo): ?array
    {
        // Map de slugs (ajustá si tu API usa otros nombres)
        $slugMap = [
            'oficial' => 'oficial',
            'blue'    => 'blue',
            'mep'     => 'mep',
            'ccl'     => 'ccl',       // intento 1
            'tarjeta' => 'tarjeta',
        ];

        $slugs = [];
        $slugs[] = $slugMap[$tipo] ?? $tipo;

        // Alternativa para CCL según algunos proveedores
        if ($tipo === 'ccl') {
            $slugs[] = 'contadoconliqui';
            $slugs[] = 'contado-con-liquidacion';
        }

        $json = null;
        foreach ($slugs as $slug) {
            $url = "https://dolarapi.com/v1/dolares/{$slug}";
            try {
                $resp = Http::timeout(10)->acceptJson()->get($url);
                if ($resp->ok()) {
                    $json = $resp->json();
                    break;
                }
            } catch (\Throwable $e) {
                // Silenciamos y probamos el siguiente slug
            }
        }

        if (!$json || !is_array($json)) {
            return null;
        }

        // Tomar el mejor campo de precio disponible
        $valor = $json['venta'] ?? $json['promedio'] ?? $json['valor'] ?? $json['compra'] ?? null;
        if (!is_numeric($valor)) return null;

        // Tomar fecha ISO si viene, si no fecha de hoy
        $fechaRaw = $json['fecha'] ?? $json['fechaActualizacion'] ?? $json['last_update'] ?? null;
        $fecha = $fechaRaw ? Carbon::parse($fechaRaw)->toDateString() : now()->toDateString();

        return [
            'fecha' => $fecha,
            'valor' => (float)$valor,
        ];
    }

    /**
     * Devuelve la cotización del día desde BD o, si falta (o live=1), la trae de API y la cachea.
     */
    private function getTodayOrFetch(string $tipo, bool $forceLive = false): ?Cotizacion
    {
        $hoy = now()->toDateString();

        // Si NO forzamos live, probamos tomar la del día desde BD
        if (!$forceLive) {
            $cot = Cotizacion::where('tipo', $tipo)->whereDate('fecha', $hoy)->first();
            if ($cot) return $cot;
        }

        // Traer de API
        $api = $this->fetchFromApi($tipo);
        if ($api) {
            // upsert por (fecha,tipo)
            return Cotizacion::updateOrCreate(
                ['fecha' => $api['fecha'], 'tipo' => $tipo],
                ['valor' => $api['valor'], 'source' => 'api']
            );
        }

        // Último recurso: devolver la más reciente guardada
        return Cotizacion::where('tipo', $tipo)->orderBy('fecha', 'desc')->first();
    }

    /* =================== Endpoints =================== */

    // POST /api/cotizaciones
    // Body JSON: { "fecha":"2025-09-20", "tipo":"oficial", "valor": 987.50, "source":"manual" }
    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'  => ['required', 'date'],
            'tipo'   => ['nullable', 'string', 'max:50'],
            'valor'  => ['required', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $data['tipo'] = $data['tipo'] ?? 'oficial';

        $cot = Cotizacion::updateOrCreate(
            ['fecha' => $data['fecha'], 'tipo' => $data['tipo']],
            ['valor' => $data['valor'], 'source' => $data['source'] ?? 'manual']
        );

        return response()->json([
            'message' => 'Cotización guardada',
            'data' => $cot
        ], 201);
    }

    // GET /api/cotizaciones?tipo=oficial&desde=YYYY-MM-DD&hasta=YYYY-MM-DD&include_live=1
    public function index(Request $request)
    {
        $tipo  = $request->query('tipo', 'oficial');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $includeLive = (bool)$request->boolean('include_live', false);

        $q = Cotizacion::where('tipo', $tipo);
        if ($desde) $q->whereDate('fecha', '>=', $desde);
        if ($hasta) $q->whereDate('fecha', '<=', $hasta);

        $items = $q->orderBy('fecha','desc')->limit($desde || $hasta ? 1000 : 30)->get();

        // Si no hay datos y el usuario quiere live, traemos 1 del día y lo agregamos
        if ($items->isEmpty() && $includeLive) {
            if ($cot = $this->getTodayOrFetch($tipo, true)) {
                $items = collect([$cot]);
            }
        }

        return response()->json([
            'count' => $items->count(),
            'tipo'  => $tipo,
            'desde' => $desde,
            'hasta' => $hasta,
            'data'  => $items,
        ]);
    }

    // GET /api/cotizaciones/promedio?tipo=oficial&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
    public function promedio(Request $request)
    {
        $data = $request->validate([
            'tipo'  => ['nullable', 'string', 'max:50'],
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);

        $tipo  = $data['tipo'] ?? 'oficial';
        $desde = $data['desde'];
        $hasta = $data['hasta'];

        // El promedio SIEMPRE es sobre datos persistidos (requisito del profe).
        // Si querés, antes de calcular podés llamar a /api/cotizaciones/sync diariamente.
        $q = Cotizacion::where('tipo', $tipo)->whereBetween('fecha', [$desde, $hasta]);

        $avg = $q->avg('valor');
        $count = $q->count();

        return response()->json([
            'tipo'     => $tipo,
            'desde'    => $desde,
            'hasta'    => $hasta,
            'cantidad' => $count,
            'promedio' => $avg ? round((float)$avg, 2) : null,
        ]);
    }

    // GET /api/convertir?tipo=oficial&monto=120.5&live=1
    public function convertir(Request $request)
    {
        $data = $request->validate([
            'tipo'  => ['nullable', 'string', 'max:50'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'live'  => ['nullable'], // si viene, forzamos API
        ]);

        $tipo  = $data['tipo'] ?? 'oficial';
        $monto = (float)$data['monto'];
        $forceLive = (bool)$request->boolean('live', false);

        $cot = $this->getTodayOrFetch($tipo, $forceLive);

        if (!$cot) {
            return response()->json([
                'message' => "No se pudo obtener cotización para el tipo '{$tipo}'."
            ], 404);
        }

        $ars = round($monto * (float)$cot->valor, 2);

        return response()->json([
            'tipo'        => $tipo,
            'monto_usd'   => $monto,
            'valor_usd'   => (float)$cot->valor,
            'fecha'       => Carbon::parse($cot->fecha)->toDateString(),
            'total_ars'   => $ars,
            'source'      => $cot->source ?? 'desconocido',
        ]);
    }

    // POST /api/cotizaciones/sync  (opcional)  Body: { "tipo":"oficial" }  ó { "tipo":"all" }
    public function sync(Request $request)
    {
        $tipo = $request->input('tipo', 'oficial');

        $tipos = $tipo === 'all'
            ? ['oficial','blue','mep','ccl','tarjeta']
            : [$tipo];

        $insertadas = [];
        foreach ($tipos as $t) {
            if ($data = $this->fetchFromApi($t)) {
                $cot = Cotizacion::updateOrCreate(
                    ['fecha' => $data['fecha'], 'tipo' => $t],
                    ['valor' => $data['valor'], 'source' => 'api']
                );
                $insertadas[] = $cot;
            }
        }

        return response()->json([
            'message' => 'Sync completado',
            'cantidad' => count($insertadas),
            'data' => $insertadas,
        ]);
    }
}
