<?php

namespace App\Http\Controllers;

use App\Models\AlmacenItem;
use App\Models\HerramientaAlmacen;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    public function index(Request $request)
    {

        $query = Pedido::with(['user:id,name', 'unidad:id,nombre', 'detalles.producto:id,nombre,imagen'])
            ->withCount('detalles');

        $isAdmin = $this->isAdmin();
        if (! $isAdmin) {
            $query->deUsuario();
        }

        $this->applyFilters($query, $request);

        $rowsPerPage = (int) $request->input('rowsPerPage', 15);
        $rowsPerPage = $rowsPerPage > 0 ? $rowsPerPage : 15;

        $summaryQuery = Pedido::query();
        if (! $isAdmin) {
            $summaryQuery->deUsuario();
        }
        $this->applyFilters($summaryQuery, $request);
        $summary = [
            'total_pendientes' => (float) (clone $summaryQuery)->where('estado', 'PENDIENTE')->sum('total'),
            'total_aceptados' => (float) (clone $summaryQuery)->where('estado', 'ACEPTADO')->sum('total'),
            'total_rechazados' => (float) (clone $summaryQuery)->where('estado', 'RECHAZADO')->sum('total'),
            'cantidad' => (int) (clone $summaryQuery)->count(),
        ];

        $paginated = $query
            ->orderBy('fecha_hora', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    public function show($id)
    {
        $pedido = Pedido::with(['user:id,name,firma,sello,mostrar_firma,mostrar_sello', 'unidad:id,nombre', 'detalles.producto'])->findOrFail($id);

        if (! $this->isAdmin() && $pedido->user_id !== auth()->id()) {
            abort(403, 'No autorizado para ver este pedido');
        }

        $productoIds = $pedido->detalles->pluck('producto_id')->filter()->unique()->toArray();
        if (! empty($productoIds)) {
            $stocks = AlmacenItem::stockByIds($productoIds);
            $pedido->detalles->each(function ($det) use ($stocks) {
                if ($det->producto) {
                    $det->producto->cantidad = $stocks[$det->producto_id] ?? 0;
                }
            });
        }

        return $pedido;
    }

    public function store(Request $request)
    {

        $data = $this->validateData($request);

        $requester = $request->user();
        $esEmergencia = $request->filled('user_id') && (int) $request->user_id !== $requester->id;

        if ($esEmergencia) {
            if (! (method_exists($requester, 'hasPermissionTo') && $requester->hasPermissionTo('Crear Pedidos de Emergencia'))
                && ($requester->role ?? null) !== 'Administrador') {
                return response()->json(['message' => 'No autorizado para crear pedidos de emergencia.'], 403);
            }
            $targetUser = \App\Models\User::findOrFail((int) $request->user_id);
        } else {
            $targetUser = $requester;
        }

        if (! $esEmergencia && ! HerramientaAlmacen::pedidosHabilitados()) {
            return response()->json([
                'message' => 'Los pedidos no están habilitados. Configura una fecha de inicio y una fecha de finalización activas en Herramientas de Almacén.',
            ], 422);
        }

        if (! $esEmergencia) {
            $maxPedidos = (int) ($targetUser->max_pedidos ?? 1);
            $pedidosMes = Pedido::where('user_id', $targetUser->id)
                ->where('estado', '!=', 'ANULADO')
                ->whereYear('fecha_hora', now()->year)
                ->whereMonth('fecha_hora', now()->month)
                ->count();

            if ($pedidosMes >= $maxPedidos) {
                return response()->json([
                    'message' => "Has alcanzado el límite de {$maxPedidos} pedido(s) permitido(s) este mes. Contacta al administrador para ampliar tu límite.",
                    'limite_alcanzado' => true,
                ], 422);
            }
        }

        return DB::transaction(function () use ($data, $targetUser) {
            $productos = AlmacenItem::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');
            $total = collect($data['items'])->sum(function ($item) use ($productos) {
                $precio = (float) ($item['precio_unitario'] ?? $productos[$item['producto_id']]->precio_unitario ?? 0);

                return $precio * (float) $item['cantidad'];
            });

            $nombreUsuario = $targetUser->username ?: $targetUser->name;

            $pedido = Pedido::create([
                'user_id' => $targetUser->id,
                'unidad_id' => $targetUser->unidad_id,
                'fecha_hora' => now(),
                'nombre_usuario' => $nombreUsuario,
                'comentario' => $data['comentario'] ?? null,
                'estado' => 'PENDIENTE',
                'total' => $total,
                'modificado' => false,
            ]);

            foreach ($data['items'] as $item) {
                $producto = $productos[$item['producto_id']];
                $precio = (float) ($item['precio_unitario'] ?? $producto->precio_unitario ?? 0);
                $cantidad = (float) $item['cantidad'];
                $subtotal = round($precio * $cantidad, 2);

                $pedido->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }

            return response()->json($pedido->load(['user:id,name', 'detalles.producto']), 201);
        });
    }

    public function update(Request $request, $id)
    {

        $pedido = Pedido::with('detalles.producto:id,nombre')->findOrFail($id);

        if (! $this->isAdmin() && $pedido->user_id !== auth()->id()) {
            abort(403, 'No autorizado para modificar este pedido');
        }

        // Cambio de estado solamente (acción rápida desde el listado)
        if ($request->has('estado') && ! $request->has('items')) {
            $request->validate([
                'estado' => ['required', Rule::in(['PENDIENTE', 'ACEPTADO', 'RECHAZADO'])],
            ]);

            if ($pedido->estado !== 'PENDIENTE') {
                return response()->json([
                    'message' => 'Solo se puede cambiar el estado cuando el pedido está PENDIENTE.',
                ], 422);
            }

            $pedido->update([
                'estado' => $request->estado,
            ]);

            return response()->json($pedido->load(['user:id,name', 'detalles.producto']));
        }

        // Edición completa: solo permitida cuando está PENDIENTE
        if ($pedido->estado !== 'PENDIENTE') {
            return response()->json([
                'message' => 'Solo se pueden modificar pedidos en estado PENDIENTE.',
            ], 422);
        }

        $data = $this->validateData($request);

        return DB::transaction(function () use ($pedido, $data) {
            $productos = AlmacenItem::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');
            $total = collect($data['items'])->sum(function ($item) use ($productos) {
                $precio = (float) ($item['precio_unitario'] ?? $productos[$item['producto_id']]->precio_unitario ?? 0);

                return $precio * (float) $item['cantidad'];
            });

            $modificacionDetalle = $this->calcularDiff($pedido->detalles, $data['items'], $productos);

            $pedido->update([
                'comentario' => $data['comentario'] ?? $pedido->comentario,
                'total' => $total,
                'modificado' => true,
                'modificacion_detalle' => $modificacionDetalle,
            ]);

            $pedido->detalles()->delete();

            foreach ($data['items'] as $item) {
                $producto = $productos[$item['producto_id']];
                $precio = (float) ($item['precio_unitario'] ?? $producto->precio_unitario ?? 0);
                $cantidad = (float) $item['cantidad'];
                $subtotal = round($precio * $cantidad, 2);

                $pedido->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }

            return response()->json($pedido->load(['user:id,name', 'unidad:id,nombre', 'detalles.producto']));
        });
    }

    public function destroy($id)
    {

        $pedido = Pedido::with('detalles')->findOrFail($id);

        if (! $this->isAdmin() && $pedido->user_id !== auth()->id()) {
            abort(403, 'No autorizado para anular este pedido');
        }

        if ($pedido->estado !== 'PENDIENTE') {
            return response()->json([
                'message' => 'Solo se pueden anular pedidos en estado PENDIENTE.',
            ], 422);
        }

        $pedido->update(['estado' => 'ANULADO']);

        return response()->json($pedido->load(['user:id,name', 'detalles.producto']));
    }

    public function printPdf($id)
    {
        //        if (auth()->check() && method_exists(auth()->user(), 'can') && !auth()->user()->can('Imprimir Pedidos')) {
        //            abort(403, 'No autorizado para imprimir este pedido');
        //        }

        $pedido = Pedido::with(['user:id,name', 'unidad:id,nombre', 'detalles.producto'])->findOrFail($id);

        if (! $this->isAdmin() && $pedido->user_id !== auth()->id()) {
            abort(403, 'No autorizado para imprimir este pedido');
        }

        $pdf = Pdf::loadView('reportes.pedido_detalle', [
            'pedido' => $pedido,
        ])->setPaper('letter', 'portrait');

        $filename = 'pedido_'.$pedido->id.'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($filename);
    }

    public function cambiarUnidad(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        if (! $this->isAdmin() && $pedido->user_id !== auth()->id()) {
            abort(403, 'No autorizado para modificar este pedido');
        }

        $request->validate(['unidad_id' => 'required|exists:unidades,id']);

        $pedido->update(['unidad_id' => $request->unidad_id]);

        return response()->json($pedido->load(['user:id,name', 'unidad:id,nombre', 'detalles.producto']));
    }

    public function limiteMensual(Request $request)
    {
        $user = $request->user();
        $maxPedidos = (int) ($user->max_pedidos ?? 1);
        $pedidosMes = Pedido::where('user_id', $user->id)
            ->where('estado', '!=', 'ANULADO')
            ->whereYear('fecha_hora', now()->year)
            ->whereMonth('fecha_hora', now()->month)
            ->count();

        return response()->json([
            'max_pedidos'  => $maxPedidos,
            'pedidos_mes'  => $pedidosMes,
            'bloqueado'    => $pedidosMes >= $maxPedidos,
        ]);
    }

    private function calcularDiff($detallesOriginales, array $itemsNuevos, $productos): string
    {
        $originales = $detallesOriginales->keyBy('producto_id');
        $nuevos = collect($itemsNuevos)->keyBy('producto_id');
        $cambios = [];

        foreach ($originales as $pid => $det) {
            $nombre = $det->producto?->nombre ?? "Producto #{$pid}";
            if (! $nuevos->has($pid)) {
                $cambios[] = "Se quitó {$nombre} x{$det->cantidad}";
            } elseif ((float) $nuevos[$pid]['cantidad'] !== (float) $det->cantidad) {
                $antes = (float) $det->cantidad;
                $despues = (float) $nuevos[$pid]['cantidad'];
                $cambios[] = "{$nombre}: {$antes}→{$despues}";
            }
        }

        foreach ($nuevos as $pid => $item) {
            if (! $originales->has($pid)) {
                $nombre = $productos[$pid]?->nombre ?? "Producto #{$pid}";
                $cambios[] = "Se agregó {$nombre} x{$item['cantidad']}";
            }
        }

        return empty($cambios) ? 'Sin cambios en productos' : implode('; ', $cambios);
    }

    private function isAdmin(): bool
    {
        $user = auth()->user();

        return ($user->role ?? null) === 'Administrador'
            || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'jefe-almacen']))
            || (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('Ver todos los pedidos'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'comentario' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:almacen_items,id',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('producto_id')) {
            $productoId = $request->input('producto_id');
            $query->whereHas('detalles', function ($query) use ($productoId) {
                $query->where('producto_id', $productoId);
            })->with(['detalles' => function ($query) use ($productoId) {
                $query->where('producto_id', $productoId)
                    ->with('producto:id,nombre,imagen');
            }]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha_hora', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('fecha_hora', '<=', $request->date_to);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('nombre_usuario', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$q}%"));
            });
        }
    }
}
