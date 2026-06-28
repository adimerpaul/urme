<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaTipoMuestra;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        // si quieres incluir servicios: ->with('servicios')
        return Area::orderBy('id', 'asc')->with('servicios.tiposMuestra')->get();
    }
    function areasCreateSolicitud(Request $request){
        $user = $request->user();
        if ($user->role == 'Administrador') {
            return Area::orderBy('id', 'asc')->with('servicios.tiposMuestra')->get();
        }
        $area = $user->area;
        $idBiologiaMolecular = 7;
        error_log('area id: '.$area->id);
        if($area->id == $idBiologiaMolecular){
            return Area::where('id',$idBiologiaMolecular)->with('servicios.tiposMuestra')->get();
        }else{
            return Area::where('id','<>',$idBiologiaMolecular)->with('servicios.tiposMuestra')->get();
        }
    }

    public function show($id)
    {
        return Area::with('servicios.tiposMuestra')->findOrFail($id);
    }

    public function store(Request $request)
    {
        // sin validaciones fuertes, toma todo el request
        $area = Area::create($request->all());
        return response()->json($area, 201);
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);
        $area->update($request->all());
        return response()->json($area);
    }

    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json(['message' => 'Área eliminada correctamente']);
    }
    function tipoMuestras(){
        $areaTipoMuestras = AreaTipoMuestra::all();
        return response()->json($areaTipoMuestras);
    }
}
