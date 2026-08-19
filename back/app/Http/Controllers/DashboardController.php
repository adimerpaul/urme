<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Internacion;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Solicitude;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->can('Ver Dashboard')) {
            abort(403, 'No tiene permiso para ver el panel general');
        }

        $dias = (int) $request->input('dias', 30);
        $dias = max(7, min($dias, 365));

        $hoy = Carbon::today();
        $desde = $hoy->copy()->subDays($dias - 1);
        $inicioMes = $hoy->copy()->startOfMonth();
        $inicioMesAnterior = $inicioMes->copy()->subMonth();

        $verVentas = $user->can('Ver Ventas');
        $verCompras = $user->can('Ver Compras');
        $verProductos = $user->can('Ver Productos');
        $verPacientes = $user->can('Ver Pacientes');
        $verLaboratorio = $user->can('Ver Solicitudes Laboratorio');
        $verInternaciones = $user->can('Ver Internaciones');

        return response()->json([
            'rango' => [
                'dias' => $dias,
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hoy->format('Y-m-d'),
            ],
            'permisos' => [
                'ventas' => $verVentas,
                'compras' => $verCompras,
                'productos' => $verProductos,
                'pacientes' => $verPacientes,
                'laboratorio' => $verLaboratorio,
                'internaciones' => $verInternaciones,
            ],
            'resumen' => $this->resumen($hoy, $inicioMes, $inicioMesAnterior, $verVentas, $verCompras, $verProductos, $verPacientes, $verLaboratorio, $verInternaciones),
            'serie_dias' => $verVentas || $verCompras ? $this->serieDias($desde, $hoy, $verVentas, $verCompras) : [],
            'serie_meses' => $verVentas || $verCompras ? $this->serieMeses($hoy, $verVentas, $verCompras) : [],
            'tipo_pago' => $verVentas ? $this->tipoPago($desde) : [],
            'top_productos' => $verVentas ? $this->topProductos($desde) : [],
            'por_tipo_producto' => $verVentas ? $this->porTipoProducto($desde) : [],
            'top_vendedores' => $verVentas ? $this->topVendedores($desde) : [],
            'ventas_por_hora' => $verVentas ? $this->ventasPorHora($desde) : [],
            'solicitudes_estado' => $verLaboratorio ? $this->solicitudesPorEstado($desde) : [],
            'vencimientos' => $verProductos ? $this->vencimientos($hoy) : [],
            'stock_critico' => $verProductos ? $this->stockCritico() : [],
        ]);
    }

    // ── Resumen (KPIs) ────────────────────────────────────────────

    private function resumen(Carbon $hoy, Carbon $inicioMes, Carbon $inicioMesAnterior, bool $verVentas, bool $verCompras, bool $verProductos, bool $verPacientes, bool $verLaboratorio, bool $verInternaciones): array
    {
        $resumen = [];

        if ($verVentas) {
            $ventasHoy = Venta::where('estado', 'ACTIVO')->whereDate('fecha_hora', $hoy);
            $ventasMes = Venta::where('estado', 'ACTIVO')->whereDate('fecha_hora', '>=', $inicioMes);
            $ventasMesAnterior = Venta::where('estado', 'ACTIVO')
                ->whereDate('fecha_hora', '>=', $inicioMesAnterior)
                ->whereDate('fecha_hora', '<', $inicioMes);

            $totalMes = (float) (clone $ventasMes)->sum('total');
            $cantidadMes = (clone $ventasMes)->count();
            $totalMesAnterior = (float) $ventasMesAnterior->sum('total');

            $resumen['ventas_hoy'] = (float) (clone $ventasHoy)->sum('total');
            $resumen['ventas_hoy_cantidad'] = (clone $ventasHoy)->count();
            $resumen['ventas_mes'] = $totalMes;
            $resumen['ventas_mes_cantidad'] = $cantidadMes;
            $resumen['ticket_promedio'] = $cantidadMes > 0 ? round($totalMes / $cantidadMes, 2) : 0;
            $resumen['ventas_variacion'] = $totalMesAnterior > 0
                ? round((($totalMes - $totalMesAnterior) / $totalMesAnterior) * 100, 1)
                : null;
            $resumen['ventas_pendientes'] = (float) Venta::where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->sum('total');
            $resumen['ventas_pendientes_cantidad'] = Venta::where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->count();
        }

        if ($verCompras) {
            $resumen['compras_mes'] = (float) Compra::where('estado', 'ACTIVO')
                ->whereDate('fecha_hora', '>=', $inicioMes)
                ->sum('total');
        }

        if ($verProductos) {
            $resumen['productos'] = Producto::count();
            $resumen['stock_valorizado'] = $this->stockValorizado();
            $resumen['por_vencer'] = $this->stockQuery()
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '>=', $hoy)
                ->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addDays(30))
                ->count();
            $resumen['vencidos'] = $this->stockQuery()
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '<', $hoy)
                ->count();
        }

        if ($verPacientes) {
            $resumen['pacientes'] = Paciente::count();
            $resumen['pacientes_mes'] = Paciente::whereDate('created_at', '>=', $inicioMes)->count();
        }

        if ($verLaboratorio) {
            $resumen['solicitudes_mes'] = Solicitude::whereDate('fecha_solicitud', '>=', $inicioMes)->count();
            $resumen['solicitudes_pendientes'] = Solicitude::whereNotIn('estado', ['FINALIZADO', 'ANULADO'])->count();
        }

        if ($verInternaciones) {
            $resumen['internados'] = Internacion::whereNull('fecha_alta')->count();
        }

        return $resumen;
    }

    // ── Series temporales ─────────────────────────────────────────

    private function serieDias(Carbon $desde, Carbon $hoy, bool $verVentas, bool $verCompras): array
    {
        $ventas = $verVentas
            ? Venta::where('estado', 'ACTIVO')
                ->whereDate('fecha_hora', '>=', $desde)
                ->whereDate('fecha_hora', '<=', $hoy)
                ->selectRaw('DATE(fecha_hora) as dia, SUM(total) as total, COUNT(*) as cantidad')
                ->groupBy('dia')
                ->get()
                ->keyBy(fn ($fila) => (string) $fila->dia)
            : collect();

        $compras = $verCompras
            ? Compra::where('estado', 'ACTIVO')
                ->whereDate('fecha_hora', '>=', $desde)
                ->whereDate('fecha_hora', '<=', $hoy)
                ->selectRaw('DATE(fecha_hora) as dia, SUM(total) as total')
                ->groupBy('dia')
                ->get()
                ->keyBy(fn ($fila) => (string) $fila->dia)
            : collect();

        $serie = [];
        for ($fecha = $desde->copy(); $fecha->lte($hoy); $fecha->addDay()) {
            $clave = $fecha->format('Y-m-d');
            $serie[] = [
                'fecha' => $clave,
                'ventas' => (float) ($ventas[$clave]->total ?? 0),
                'cantidad' => (int) ($ventas[$clave]->cantidad ?? 0),
                'compras' => (float) ($compras[$clave]->total ?? 0),
            ];
        }

        return $serie;
    }

    private function serieMeses(Carbon $hoy, bool $verVentas, bool $verCompras): array
    {
        $desde = $hoy->copy()->startOfMonth()->subMonths(11);

        $ventas = $verVentas
            ? Venta::where('estado', 'ACTIVO')
                ->whereDate('fecha_hora', '>=', $desde)
                ->selectRaw("DATE_FORMAT(fecha_hora, '%Y-%m') as mes, SUM(total) as total, COUNT(*) as cantidad")
                ->groupBy('mes')
                ->get()
                ->keyBy(fn ($fila) => (string) $fila->mes)
            : collect();

        $compras = $verCompras
            ? Compra::where('estado', 'ACTIVO')
                ->whereDate('fecha_hora', '>=', $desde)
                ->selectRaw("DATE_FORMAT(fecha_hora, '%Y-%m') as mes, SUM(total) as total")
                ->groupBy('mes')
                ->get()
                ->keyBy(fn ($fila) => (string) $fila->mes)
            : collect();

        $serie = [];
        for ($fecha = $desde->copy(); $fecha->lte($hoy); $fecha->addMonth()) {
            $clave = $fecha->format('Y-m');
            $serie[] = [
                'mes' => $clave,
                'etiqueta' => mb_strtoupper($fecha->locale('es')->isoFormat('MMM YY')),
                'ventas' => (float) ($ventas[$clave]->total ?? 0),
                'cantidad' => (int) ($ventas[$clave]->cantidad ?? 0),
                'compras' => (float) ($compras[$clave]->total ?? 0),
            ];
        }

        return $serie;
    }

    private function ventasPorHora(Carbon $desde): array
    {
        $filas = Venta::where('estado', 'ACTIVO')
            ->whereDate('fecha_hora', '>=', $desde)
            ->selectRaw('HOUR(fecha_hora) as hora, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('hora')
            ->get()
            ->keyBy(fn ($fila) => (int) $fila->hora);

        $serie = [];
        for ($hora = 0; $hora < 24; $hora++) {
            $serie[] = [
                'hora' => sprintf('%02d:00', $hora),
                'cantidad' => (int) ($filas[$hora]->cantidad ?? 0),
                'total' => (float) ($filas[$hora]->total ?? 0),
            ];
        }

        return $serie;
    }

    // ── Distribuciones ────────────────────────────────────────────

    private function tipoPago(Carbon $desde): array
    {
        $filas = Venta::where('estado', 'ACTIVO')
            ->whereDate('fecha_hora', '>=', $desde)
            ->selectRaw('COALESCE(tipo_pago, ?) as tipo_pago, SUM(total) as total, COUNT(*) as cantidad', ['SIN DEFINIR'])
            ->groupBy('tipo_pago')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => [
                'tipo_pago' => (string) $fila->tipo_pago,
                'total' => (float) $fila->total,
                'cantidad' => (int) $fila->cantidad,
            ]);

        return $this->plegarEnOtros($filas, 'tipo_pago');
    }

    private function topProductos(Carbon $desde): array
    {
        return VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->whereNull('ventas.deleted_at')
            ->where('ventas.estado', 'ACTIVO')
            ->whereDate('ventas.fecha_hora', '>=', $desde)
            ->selectRaw('venta_detalles.nombre as nombre, SUM(venta_detalles.cantidad) as cantidad, SUM(venta_detalles.total) as total')
            ->groupBy('venta_detalles.nombre')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => (string) $fila->nombre,
                'cantidad' => (float) $fila->cantidad,
                'total' => (float) $fila->total,
            ])
            ->all();
    }

    private function porTipoProducto(Carbon $desde): array
    {
        $filas = VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos', 'productos.id', '=', 'venta_detalles.producto_id')
            ->leftJoin('tipo_productos', 'tipo_productos.id', '=', 'productos.tipo_producto_id')
            ->whereNull('ventas.deleted_at')
            ->where('ventas.estado', 'ACTIVO')
            ->whereDate('ventas.fecha_hora', '>=', $desde)
            ->selectRaw('COALESCE(tipo_productos.nombre, ?) as tipo, SUM(venta_detalles.total) as total', ['SIN CATEGORÍA'])
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => [
                'tipo' => (string) $fila->tipo,
                'total' => (float) $fila->total,
            ]);

        return $this->plegarEnOtros($filas, 'tipo');
    }

    /**
     * Las donas se pintan con la paleta categórica en orden por tamaño, así que
     * cualquier par de porciones puede quedar contiguo. Solo cuatro ranuras
     * superan la validación con todos los pares, de modo que el excedente se
     * pliega en OTROS en lugar de reciclar colores.
     */
    private function plegarEnOtros($filas, string $campo, int $limite = 3): array
    {
        if ($filas->count() <= $limite + 1) {
            return $filas->values()->all();
        }

        $resto = $filas->slice($limite);
        $otros = [$campo => 'OTROS', 'total' => round((float) $resto->sum('total'), 2)];

        if (array_key_exists('cantidad', $filas->first())) {
            $otros['cantidad'] = (int) $resto->sum('cantidad');
        }

        return $filas->take($limite)->push($otros)->values()->all();
    }

    private function topVendedores(Carbon $desde): array
    {
        return Venta::query()
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->where('ventas.estado', 'ACTIVO')
            ->whereDate('ventas.fecha_hora', '>=', $desde)
            ->selectRaw('users.name as nombre, SUM(ventas.total) as total, COUNT(*) as cantidad')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($fila) => [
                'nombre' => (string) $fila->nombre,
                'total' => (float) $fila->total,
                'cantidad' => (int) $fila->cantidad,
            ])
            ->all();
    }

    private function solicitudesPorEstado(Carbon $desde): array
    {
        return Solicitude::query()
            ->whereDate('fecha_solicitud', '>=', $desde)
            ->selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->orderByDesc('cantidad')
            ->get()
            ->map(fn ($fila) => [
                'estado' => (string) $fila->estado,
                'cantidad' => (int) $fila->cantidad,
            ])
            ->all();
    }

    // ── Inventario ────────────────────────────────────────────────

    private function vencimientos(Carbon $hoy): array
    {
        $tramos = [
            ['clave' => 'VENCIDOS', 'etiqueta' => 'Vencidos', 'desde' => null, 'hasta' => -1],
            ['clave' => 'D30', 'etiqueta' => '≤ 30 días', 'desde' => 0, 'hasta' => 30],
            ['clave' => 'D90', 'etiqueta' => '31 a 90 días', 'desde' => 31, 'hasta' => 90],
            ['clave' => 'SEGURO', 'etiqueta' => '> 90 días', 'desde' => 91, 'hasta' => null],
        ];

        $resultado = [];
        foreach ($tramos as $tramo) {
            $query = $this->stockQuery()->whereNotNull('fecha_vencimiento');

            if ($tramo['desde'] !== null) {
                $query->whereDate('fecha_vencimiento', '>=', $hoy->copy()->addDays($tramo['desde']));
            }
            if ($tramo['hasta'] !== null) {
                $query->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addDays($tramo['hasta']));
            }

            $resultado[] = [
                'clave' => $tramo['clave'],
                'etiqueta' => $tramo['etiqueta'],
                'cantidad' => $query->count(),
            ];
        }

        return $resultado;
    }

    private function stockCritico(): array
    {
        $existencia = $this->existenciaSql();

        return CompraDetalle::query()
            ->join('productos', 'productos.id', '=', 'compra_detalles.producto_id')
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->whereNull('compra_detalles.deleted_at')
            ->selectRaw("productos.id as producto_id, productos.nombre as nombre, productos.codigo as codigo, SUM($existencia) as existencia")
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo')
            ->havingRaw('existencia > 0 AND existencia <= 10')
            ->orderBy('existencia')
            ->limit(8)
            ->get()
            ->map(fn ($fila) => [
                'producto_id' => (int) $fila->producto_id,
                'nombre' => (string) $fila->nombre,
                'codigo' => (string) $fila->codigo,
                'existencia' => (float) $fila->existencia,
            ])
            ->all();
    }

    private function stockValorizado(): float
    {
        $existencia = $this->existenciaSql();

        $total = CompraDetalle::query()
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->whereNull('compra_detalles.deleted_at')
            ->selectRaw("COALESCE(SUM($existencia * COALESCE(compra_detalles.precio_venta, compra_detalles.precio, 0)), 0) as total")
            ->value('total');

        return round((float) $total, 2);
    }

    /**
     * Detalles de compra que todavía tienen saldo disponible en almacén.
     */
    private function stockQuery(): Builder
    {
        return CompraDetalle::query()
            ->whereHas('compra', fn (Builder $compra) => $compra->where('estado', 'ACTIVO'))
            ->whereRaw('compra_detalles.cantidad > (
                SELECT COALESCE(SUM(vd.cantidad), 0)
                FROM venta_detalles vd
                INNER JOIN ventas v ON v.id = vd.venta_id
                WHERE vd.compra_detalle_id = compra_detalles.id
                  AND vd.deleted_at IS NULL
                  AND v.deleted_at IS NULL
                  AND v.estado != ?
            )', ['ANULADO']);
    }

    /**
     * Expresión SQL con el saldo restante de un compra_detalles (nunca negativo).
     */
    private function existenciaSql(): string
    {
        return "GREATEST(compra_detalles.cantidad - (
            SELECT COALESCE(SUM(vd.cantidad), 0)
            FROM venta_detalles vd
            INNER JOIN ventas v ON v.id = vd.venta_id
            WHERE vd.compra_detalle_id = compra_detalles.id
              AND vd.deleted_at IS NULL
              AND v.deleted_at IS NULL
              AND v.estado != 'ANULADO'
        ), 0)";
    }
}
