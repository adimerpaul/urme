<?php

namespace App\Http\Controllers;

use App\Models\Internacion;
use App\Models\Paciente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InternacionController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Internaciones');

        $q = $request->input('q', '');
        $perPage = $request->input('per_page');

        $query = Internacion::with(['paciente:id,nombre_completo', 'seguro:id,nombre'])->orderByDesc('fecha_ingreso');
        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('codigo_hc', 'like', "%$q%")
                    ->orWhere('sala', 'like', "%$q%")
                    ->orWhereHas('paciente', function ($pq) use ($q) {
                        $pq->where('nombre_completo', 'like', "%$q%");
                    });
            });
        }

        if ($perPage) {
            return response()->json($query->paginate((int) $perPage));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Internaciones');
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'seguro_id' => 'nullable|exists:seguros,id',
            'fecha_ingreso' => 'nullable|date',
            'fecha_alta' => 'nullable|date',
        ]);
        $datos = $request->only(['paciente_id', 'seguro_id', 'fecha_ingreso', 'tipo_paciente', 'fecha_alta', 'codigo_hc', 'sala']);
        if (! $request->exists('seguro_id')) {
            $datos['seguro_id'] = Paciente::findOrFail($request->paciente_id)->seguro_id;
        }
        $internacion = Internacion::create($datos);

        return response()->json($internacion->load(['paciente:id,nombre_completo', 'seguro:id,nombre']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->req($request, 'Editar Internaciones');
        $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'seguro_id' => 'nullable|exists:seguros,id',
            'fecha_ingreso' => 'nullable|date',
            'fecha_alta' => 'nullable|date',
        ]);
        $internacion = Internacion::findOrFail($id);
        $internacion->update($request->only(['paciente_id', 'seguro_id', 'fecha_ingreso', 'tipo_paciente', 'fecha_alta', 'codigo_hc', 'sala']));

        return response()->json($internacion->load(['paciente:id,nombre_completo', 'seguro:id,nombre']));
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Internaciones');
        Internacion::findOrFail($id)->delete();

        return response()->json(['message' => 'Internación eliminada']);
    }

    /**
     * Planilla de seguimiento del seguro: entrega de informe, respuesta de
     * auditoría, facturación, cancelación y tipo de pago de la internación.
     */
    public function updateSeguimiento(Request $request, $id)
    {
        $this->req($request, 'Editar Seguros');

        $request->validate([
            'entrega_informe' => 'nullable|date',
            'respuesta_auditoria' => 'nullable|date',
            'fecha_facturacion' => 'nullable|date',
            'monto_facturado' => 'nullable|numeric|min:0',
            'fecha_cancelacion' => 'nullable|date',
            'tipo_pago' => 'nullable|string|max:30',
            'observacion_seguro' => 'nullable|string',
        ]);

        $internacion = Internacion::findOrFail($id);
        $internacion->update([
            'entrega_informe' => $request->entrega_informe ?: null,
            'respuesta_auditoria' => $request->respuesta_auditoria ?: null,
            'fecha_facturacion' => $request->fecha_facturacion ?: null,
            'monto_facturado' => $request->monto_facturado !== null && $request->monto_facturado !== ''
                ? $request->monto_facturado
                : null,
            'fecha_cancelacion' => $request->fecha_cancelacion ?: null,
            'tipo_pago' => $request->tipo_pago ?: null,
            'observacion_seguro' => $request->observacion_seguro ?: null,
        ]);

        return response()->json($internacion->load(['paciente:id,nombre_completo,ci', 'items:id,internacion_id,nombre,cantidad,precio,total']));
    }

    public function pdf(Request $request, $id)
    {
        $this->req($request, 'Ver Internaciones');
        $internacion = Internacion::with(['paciente', 'seguro:id,nombre', 'items.producto:id,nombre', 'items.user:id,name'])->findOrFail($id);
        $total = $internacion->items->sum('total');

        $pdf = Pdf::loadView('reportes.internacion', [
            'internacion' => $internacion,
            'total' => $total,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('proforma_'.$internacion->id.'_'.now()->format('Ymd_His').'.pdf');
    }

    private function req(Request $request, string|array $permission): void
    {
        $user = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
