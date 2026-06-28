<?php

namespace App\Http\Controllers;

use App\Models\CultivoAntibiograma;
use App\Models\Solicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CultivoAntibiogramaController extends Controller
{
    public function showBySolicitude($id)
    {
        $solicitud = Solicitude::with(['paciente', 'doctor'])->findOrFail($id);

        $cultivo = CultivoAntibiograma::firstOrNew(['solicitude_id' => $id]);

        if (empty($cultivo->antibiograma)) {
            $cultivo->antibiograma = [
                ['antibiotico' => 'Amikacina', 'estado' => ''],
                ['antibiotico' => 'Cefotaxima', 'estado' => ''],
                ['antibiotico' => 'Trimetoprima/Sulfametoxazol', 'estado' => ''],
                ['antibiotico' => 'Amoxicilina/Ácido clavulánico', 'estado' => ''],
                ['antibiotico' => 'Levofloxacina', 'estado' => ''],
            ];
        }

        return response()->json([
            'solicitud' => $solicitud,
            'cultivo'   => $cultivo,
        ]);
    }

    public function upsert(Request $request, $id)
    {
        $data = $request->all();
        $data['solicitude_id'] = $id;

        // Normaliza estructura del antibiograma
        $ab = $request->input('antibiograma', []);
        $ab = is_array($ab) ? array_values(array_filter($ab, function ($x) {
            return is_array($x) && !empty(trim($x['antibiotico'] ?? ''));
        })) : [];

        $data['antibiograma'] = $ab;

        $registro = CultivoAntibiograma::updateOrCreate(
            ['solicitude_id' => $id],
            $data
        );

        return response()->json($registro);
    }

    public function destroyBySolicitude($id)
    {
        $registro = CultivoAntibiograma::where('solicitude_id', $id)->firstOrFail();
        $registro->delete();
        return response()->json(['message' => 'Registro eliminado']);
    }

    public function pdfBySolicitude($code)
    {
        $id = CultivoAntibiograma::where('code', $code)->value('solicitude_id');
        $solicitud = Solicitude::with(['paciente', 'doctor'])->findOrFail($id);
        $cultivo = CultivoAntibiograma::where('solicitude_id', $id)->first();

        $pdf = Pdf::loadView('pdf.cultivo_antibiograma', [
            'solicitud' => $solicitud,
            'cultivo'   => $cultivo,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('CULTIVO_ANTIBIOGRAMA_'.$solicitud->nro_registro.'.pdf');
    }
}
