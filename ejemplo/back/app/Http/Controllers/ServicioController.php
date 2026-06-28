<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\ServicioFormula;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index(Request $request)
    {
        $query = Servicio::with(['area', 'tiposMuestra.area'])->withCount('rangos')->orderBy('codigo', 'asc');

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return $query->get();
    }

    public function show($id)
    {
        return Servicio::with(['area', 'tiposMuestra.area'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'codigo' => 'nullable|integer',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'metodo' => 'nullable|string|max:255',
            'subarea' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'estado' => 'required|string|max:50',
        ]);

        $servicio = Servicio::create($data);

        return response()->json($servicio->load(['area', 'tiposMuestra.area']), 201);
    }

    public function update(Request $request, $id)
    {
        $servicio = Servicio::findOrFail($id);

        $data = $request->validate([
            'area_id' => 'sometimes|required|exists:areas,id',
            'codigo' => 'nullable|integer',
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'metodo' => 'nullable|string|max:255',
            'subarea' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'estado' => 'sometimes|required|string|max:50',
        ]);

        $servicio->update($data);

        return response()->json($servicio->load(['area', 'tiposMuestra.area']));
    }

    public function destroy($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado correctamente']);
    }

    public function getRangos($id)
    {
        $servicio = Servicio::with(['rangos' => function ($q) {
            $q->orderByPivot('orden')->orderBy('area_rangos.id');
        }])->findOrFail($id);

        return response()->json($servicio->rangos);
    }

    public function syncRangos(Request $request, $id)
    {
        $servicio = Servicio::findOrFail($id);

        $data = $request->validate([
            'rangos'                  => 'array',
            'rangos.*.area_rango_id'  => 'required|integer|exists:area_rangos,id',
            'rangos.*.nombre_variable'=> 'nullable|string|max:100',
            'rangos.*.orden'          => 'nullable|integer|min:0',
        ]);

        $sync = [];
        foreach ($data['rangos'] ?? [] as $idx => $item) {
            $sync[$item['area_rango_id']] = [
                'nombre_variable' => $item['nombre_variable'] ?? null,
                'orden'           => $item['orden'] ?? $idx + 1,
            ];
        }

        $servicio->rangos()->sync($sync);

        return response()->json($servicio->load('rangos'));
    }

    public function getFormulas($id)
    {
        $servicio = Servicio::findOrFail($id);
        return response()->json($servicio->formulas);
    }

    public function syncFormulas(Request $request, $id)
    {
        $servicio = Servicio::findOrFail($id);

        $data = $request->validate([
            'formulas'                    => 'array',
            'formulas.*.label'            => 'nullable|string|max:255',
            'formulas.*.nombre_variable'  => 'required|string|max:100',
            'formulas.*.formula'          => 'required|string',
            'formulas.*.unidad'           => 'nullable|string|max:50',
        ]);

        $servicio->formulas()->delete();

        foreach ($data['formulas'] ?? [] as $idx => $f) {
            $servicio->formulas()->create([
                'label'           => $f['label'] ?? null,
                'nombre_variable' => $f['nombre_variable'],
                'formula'         => $f['formula'],
                'unidad'          => $f['unidad'] ?? null,
                'orden'           => $idx + 1,
            ]);
        }

        return response()->json($servicio->formulas);
    }

    public function syncTiposMuestra(Request $request, $id)
    {
        $servicio = Servicio::findOrFail($id);

        $data = $request->validate([
            'area_tipo_muestra_ids' => 'array',
            'area_tipo_muestra_ids.*' => 'integer|exists:area_tipo_muestras,id',
        ]);

        $ids = collect($data['area_tipo_muestra_ids'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $invalidArea = \App\Models\AreaTipoMuestra::whereIn('id', $ids)
            ->where('area_id', '<>', $servicio->area_id)
            ->exists();

        if ($invalidArea) {
            return response()->json([
                'message' => 'Solo puede vincular tipos de muestra del mismo área del servicio.'
            ], 422);
        }

        $servicio->tiposMuestra()->sync($ids);

        return response()->json($servicio->load(['area', 'tiposMuestra.area']));
    }
}
