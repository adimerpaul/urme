<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprasMayoController extends Controller
{
    /** GET /compras-mayo/sin-vincular — Lista items con observacion IS NULL (sin procesar) */
    public function sinVincular()
    {
        $rows = DB::table('compras_mayo')
            ->whereNull('observacion')
            ->orderBy('nombre')
            ->get(['id','n','nombre','unidad_medida','precio_unitario',
                   'saldo_inicial','entradas','salidas','saldo_final']);

        // Para cada fila buscar sugerencias en almacen_items normalizando espacios
        $rows = $rows->map(function ($row) {
            $palabras = collect(preg_split('/\s+/', trim($row->nombre)))
                ->filter(fn ($p) => mb_strlen($p) >= 4)
                ->take(3)
                ->values();

            $query = DB::table('almacen_items as ai')
                ->join('subpartidas as sp', 'sp.id', '=', 'ai.subpartida_id')
                ->join('partidas as pa', 'pa.id', '=', 'sp.partida_id')
                ->join('grupos as gr', 'gr.id', '=', 'pa.grupo_id')
                ->whereNull('ai.deleted_at')
                ->select('ai.id','ai.nombre','ai.unidad_medida','ai.precio_unitario',
                         'sp.id as subpartida_id','sp.nombre as subpartida_nombre',
                         'pa.id as partida_id','pa.nombre as partida_nombre',
                         'gr.id as grupo_id','gr.nombre as grupo_nombre');

            foreach ($palabras as $p) {
                $query->where('ai.nombre', 'like', "%{$p}%");
            }

            $row->sugerencias = $query->limit(5)->get();
            return $row;
        });

        return response()->json([
            'total' => $rows->count(),
            'data'  => $rows,
        ]);
    }

    /** GET /compras-mayo/sin-vincular/count — Solo el conteo para el badge */
    public function count()
    {
        return response()->json([
            'count' => DB::table('compras_mayo')->whereNull('observacion')->count(),
        ]);
    }

    /** GET /compras-mayo/buscar-item — Busca almacen_items por texto */
    public function buscarItem(Request $request)
    {
        $q = $request->input('q', '');

        $items = DB::table('almacen_items as ai')
            ->join('subpartidas as sp', 'sp.id', '=', 'ai.subpartida_id')
            ->join('partidas as pa', 'pa.id', '=', 'sp.partida_id')
            ->join('grupos as gr', 'gr.id', '=', 'pa.grupo_id')
            ->whereNull('ai.deleted_at')
            ->where(function ($query) use ($q) {
                $query->where('ai.nombre', 'like', "%{$q}%")
                      ->orWhere('ai.unidad_medida', 'like', "%{$q}%");
            })
            ->select('ai.id','ai.nombre','ai.unidad_medida','ai.precio_unitario',
                     'sp.id as subpartida_id','sp.nombre as subpartida_nombre',
                     'pa.id as partida_id','pa.nombre as partida_nombre',
                     'gr.id as grupo_id','gr.nombre as grupo_nombre')
            ->orderBy('ai.nombre')
            ->limit(20)
            ->get();

        return response()->json($items);
    }

    /** POST /compras-mayo/{id}/desvincular — Quita el vínculo para poder reasignar */
    public function desvincular(int $id)
    {
        $mayo = DB::table('compras_mayo')->where('id', $id)->first();

        if (! $mayo) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        // Eliminar compra_detalle creado por este registro si existe
        if ($mayo->almacen_item_id) {
            $compraId = DB::table('compras')
                ->where('motivo_registro', 'INVENTARIO INICIAL')
                ->value('id');

            if ($compraId) {
                DB::table('compra_detalles')
                    ->where('compra_id', $compraId)
                    ->where('producto_id', $mayo->almacen_item_id)
                    ->where('nombre', $mayo->nombre)
                    ->delete();

                DB::statement("
                    UPDATE compras SET total = (
                        SELECT COALESCE(SUM(total),0) FROM compra_detalles
                        WHERE compra_id = ? AND deleted_at IS NULL
                    ) WHERE id = ?
                ", [$compraId, $compraId]);
            }
        }

        DB::table('compras_mayo')->where('id', $id)->update([
            'almacen_item_id' => null,
            'migrado'         => false,
            'observacion'     => null,
            'updated_at'      => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /compras-mayo/{id}/vincular
     *
     * Body opción A — vincular a item existente:
     *   { "almacen_item_id": 123 }
     *
     * Body opción B — crear nuevo item:
     *   { "subpartida_id": 5, "nombre": "...", "unidad_medida": "BLOCK", "precio_unitario": 18.5 }
     */
    public function vincular(Request $request, int $id)
    {
        $mayo = DB::table('compras_mayo')->where('id', $id)->first();

        if (! $mayo) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        $now     = now();
        $userId  = $request->user()?->id ?? 1;
        $itemId  = $request->input('almacen_item_id');

        // ── Opción B: crear nuevo almacen_item ─────────────────────────
        if (! $itemId) {
            $request->validate([
                'subpartida_id' => 'required|exists:subpartidas,id',
            ]);

            $itemId = DB::table('almacen_items')->insertGetId([
                'subpartida_id'   => $request->subpartida_id,
                'nombre'          => trim($request->input('nombre', $mayo->nombre)),
                'unidad_medida'   => trim($request->input('unidad_medida', $mayo->unidad_medida)),
                'precio_unitario' => (float) $request->input('precio_unitario', $mayo->precio_unitario),
                'imagen'          => 'default.png',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        } else {
            // Actualizar unidad y precio del item existente si se enviaron
            $update = ['updated_at' => $now];
            if ($request->filled('unidad_medida')) {
                $update['unidad_medida'] = $request->unidad_medida;
            }
            if ($request->filled('precio_unitario')) {
                $update['precio_unitario'] = (float) $request->precio_unitario;
            }
            DB::table('almacen_items')->where('id', $itemId)->update($update);
        }

        // ── Crear compra_detalle en la compra INVENTARIO INICIAL ───────
        $compraId = DB::table('compras')
            ->where('motivo_registro', 'INVENTARIO INICIAL')
            ->value('id');

        if ($compraId) {
            $cantidad      = max(0, (int)$mayo->saldo_inicial + (int)$mayo->entradas);
            $cantidadVenta = max(0, (int)$mayo->salidas);
            $precio        = (float) $mayo->precio_unitario;

            DB::table('compra_detalles')->insert([
                'compra_id'      => $compraId,
                'user_id'        => $userId,
                'proveedor_id'   => null,
                'producto_id'    => $itemId,
                'nombre'         => $mayo->nombre,
                'precio'         => $precio,
                'cantidad'       => $cantidad,
                'cantidad_venta' => $cantidadVenta,
                'total'          => round($precio * $cantidad, 2),
                'estado'         => 'ACTIVO',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // Recalcular total de la compra general
            DB::statement("
                UPDATE compras SET total = (
                    SELECT COALESCE(SUM(total),0) FROM compra_detalles
                    WHERE compra_id = ? AND deleted_at IS NULL
                ) WHERE id = ?
            ", [$compraId, $compraId]);
        }

        // ── Marcar compras_mayo como vinculado y migrado ───────────────
        DB::table('compras_mayo')->where('id', $id)->update([
            'almacen_item_id' => $itemId,
            'migrado'         => true,
            'observacion'     => 'vinculado manualmente',
            'updated_at'      => $now,
        ]);

        $item = DB::table('almacen_items as ai')
            ->join('subpartidas as sp', 'sp.id', '=', 'ai.subpartida_id')
            ->join('partidas as pa', 'pa.id', '=', 'sp.partida_id')
            ->join('grupos as gr', 'gr.id', '=', 'pa.grupo_id')
            ->where('ai.id', $itemId)
            ->select('ai.id','ai.nombre','ai.unidad_medida','ai.precio_unitario',
                     'gr.nombre as grupo_nombre','pa.nombre as partida_nombre','sp.nombre as subpartida_nombre')
            ->first();

        return response()->json(['ok' => true, 'item' => $item]);
    }
}
