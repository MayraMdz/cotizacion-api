<?php
namespace App\Http\Controllers;

use App\Http\Requests\ConvertirRequest;
use App\Http\Requests\GuardarCotizacionRequest;
use App\Http\Requests\PromedioRequest;
use App\Models\Cotizacion;
use App\Services\DolarApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    public function __construct(private DolarApiService $api) {}

    /**
     * GET /api/cotizaciones
     * - Por fecha + tipo: devuelve la cotización (DB o intenta API histórica si existiera)
     * - Sin fecha: devuelve última disponible (DB reciente o API)
     */
    public function show(Request $request)
    {
        $tipo  = $request->query('tipo', 'oficial');
        $fecha = $request->query('fecha'); // YYYY-MM-DD

        if ($fecha) {
            // 1) Intentamos DB
            $cot = Cotizacion::where('tipo', $tipo)->whereDate('fecha', $fecha)->first();
            if ($cot) return response()->json($cot);

            // 2) Intentamos API histórica (si existe en tu proveedor)
            $data = $this->api->getHistorica($tipo, $fecha);
            if ($data && (isset($data['venta']) || isset($data['compra']))) {
                $cot = Cotizacion::create([
                    'tipo'   => $tipo,
                    'fecha'  => $fecha,
                    'compra' => $data['compra'] ?? null,
                    'venta'  => $data['venta'] ?? null,
                    'payload'=> $data,
                ]);
                return response()->json($cot);
            }

            return response()->json([
                'error' => 'No hay cotización para esa fecha en la base y no se pudo obtener histórica.'
            ], 404);
        }

        // Sin fecha: última (prioriza API para estar al día)
        $data = $this->api->getUltimaPorTipo($tipo);
        if ($data && (isset($data['venta']) || isset($data['compra']))) {
            $hoy = now()->toDateString();
            // Upsert del día
            $cot = Cotizacion::updateOrCreate(
                ['tipo' => $tipo, 'fecha' => $hoy],
                [
                    'compra'  => $data['compra'] ?? null,
                    'venta'   => $data['venta']  ?? null,
                    'payload' => $data
                ]
            );
            return response()->json($cot);
        }

        // Fallback: última en DB
        $cot = Cotizacion::where('tipo', $tipo)->orderByDesc('fecha')->first();
        if ($cot) return response()->json($cot);

        return response()->json(['error' => 'No se pudo obtener la cotización.'], 502);
    }

    /**
     * POST /api/cotizaciones
     * Guarda/actualiza manualmente una cotización (por si el histórico no está disponible por API).
     */
    public function store(GuardarCotizacionRequest $request)
    {
        $data = $request->validated();

        $cot = Cotizacion::updateOrCreate(
            ['tipo' => $data['tipo'], 'fecha' => $data['fecha']],
            [
                'compra'  => $data['compra'] ?? null,
                'venta'   => $data['venta'] ?? null,
                'payload' => null,
            ]
        );

        return response()->json($cot, 201);
    }

    /**
     * GET /api/convertir
     * Convierte un valor en USD a ARS usando una cotización (tipo y opcionalmente fecha).
     * Si no hay fecha, usa la del día (API + guarda).
     */
    public function convertir(ConvertirRequest $request)
    {
        $valorUSD   = (float) $request->input('valor');
        $tipo       = $request->input('tipo', 'oficial');
        $fecha      = $request->input('fecha'); // YYYY-MM-DD
        $usarVenta  = filter_var($request->boolean('usar_venta', true), FILTER_VALIDATE_BOOL);

        if ($fecha) {
            $cot = Cotizacion::where('tipo', $tipo)->whereDate('fecha', $fecha)->first();
            if (!$cot) {
                // Intentar API histórica (si existiera)
                $data = $this->api->getHistorica($tipo, $fecha);
                if ($data && (isset($data['venta']) || isset($data['compra']))) {
                    $cot = Cotizacion::create([
                        'tipo'   => $tipo,
                        'fecha'  => $fecha,
                        'compra' => $data['compra'] ?? null,
                        'venta'  => $data['venta'] ?? null,
                        'payload'=> $data,
                    ]);
                }
            }
            if (!$cot) {
                return response()->json([
                    'error' => 'No hay cotización para esa fecha (ni histórica disponible).'
                ], 404);
            }
        } else {
            // Sin fecha: obtener del día desde API y guardar
            $data = $this->api->getUltimaPorTipo($tipo);
            if (!$data || (!isset($data['venta']) && !isset($data['compra']))) {
                return response()->json(['error' => 'No se pudo obtener la cotización.'], 502);
            }
            $fecha = now()->toDateString();
            $cot = Cotizacion::updateOrCreate(
                ['tipo' => $tipo, 'fecha' => $fecha],
                [
                    'compra'  => $data['compra'] ?? null,
                    'venta'   => $data['venta'] ?? null,
                    'payload' => $data,
                ]
            );
        }

        $tasa = $usarVenta ? ($cot->venta ?? null) : ($cot->compra ?? null);
        if (!$tasa) {
            return response()->json(['error' => 'La tasa solicitada no está disponible.'], 422);
        }

        $resultado = round($valorUSD * (float)$tasa, 2);

        return response()->json([
            'tipo'              => $tipo,
            'fecha'             => $fecha,
            'usar'              => $usarVenta ? 'venta' : 'compra',
            'valor_usd'         => $valorUSD,
            'tasa'              => (float)$tasa,
            'resultado_en_pesos'=> $resultado,
        ]);
    }

    /**
     * GET /api/cotizaciones/promedio
     * Calcula promedio de compra/venta entre fechas (incluidas).
     */
    public function promedio(PromedioRequest $request)
    {
        $tipo  = $request->input('tipo');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $campo = $request->input('campo', 'venta'); // default venta

        $prom = Cotizacion::where('tipo', $tipo)
            ->whereBetween('fecha', [$desde, $hasta])
            ->avg($campo);

        return response()->json([
            'tipo'   => $tipo,
            'campo'  => $campo,
            'desde'  => $desde,
            'hasta'  => $hasta,
            'promedio' => $prom ? round((float)$prom, 4) : null,
            'cantidad_registros' => Cotizacion::where('tipo', $tipo)
                ->whereBetween('fecha', [$desde, $hasta])->count(),
        ]);
    }
}
