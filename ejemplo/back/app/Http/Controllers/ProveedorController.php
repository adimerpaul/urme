<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query();

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('carnet', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('nit', 'like', "%{$q}%")
                    ->orWhere('razon_social', 'like', "%{$q}%")
                    ->orWhere('contacto', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function show($id)
    {
        return Proveedor::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        return response()->json(Proveedor::create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $data = $request->validate($this->rules(true));
        $proveedor->update($data);

        return response()->json($proveedor);
    }

    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado correctamente']);
    }

    private function rules(bool $updating = false): array
    {
        $requiredNombre = $updating ? 'sometimes|required' : 'required';

        return [
            'nombre' => "{$requiredNombre}|string|max:255",
            'carnet' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:100',
            'direccion' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'nit' => 'nullable|string|max:100',
            'razon_social' => 'nullable|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:30',
            'observacion' => 'nullable|string',
        ];
    }
}
