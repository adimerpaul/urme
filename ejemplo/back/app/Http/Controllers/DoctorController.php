<?php

namespace App\Http\Controllers;

use App\Exports\DoctoresExport;
use App\Models\Doctor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DoctorController extends Controller
{
    public function index()
    {
        return Doctor::orderBy('nombre', 'asc')->with('establecimiento')->get();
    }

    public function show($id)
    {
        return Doctor::findOrFail($id);
    }

    public function store(Request $request)
    {
        $doctor = Doctor::create($request->all());

        return response()->json($doctor, 201);
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->update($request->all());

        return response()->json($doctor);
    }

    public function destroy($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json(['message' => 'Doctor eliminado correctamente']);
    }

    public function exportarExcel(Request $request)
    {
        $filters = $request->only(['estado']);
        $fecha   = now()->format('Y-m-d');

        return Excel::download(new DoctoresExport($filters), "doctores_{$fecha}.xlsx");
    }

    public function exportarPdf(Request $request)
    {
        $filters = $request->only(['estado']);

        $query = Doctor::with('establecimiento')->orderBy('nombre', 'asc');
        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        $doctores = $query->get();

        $pdf = Pdf::loadView('pdf.doctores', [
            'doctores' => $doctores,
            'filtros'  => $filters,
        ])->setPaper('a4', 'landscape');

        $fecha = now()->format('Y-m-d');

        return $pdf->download("doctores_{$fecha}.pdf");
    }
}
