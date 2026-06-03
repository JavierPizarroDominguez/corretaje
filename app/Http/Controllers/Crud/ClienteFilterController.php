<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Models\Nacionalidad;


class ClienteFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        $filter = $request->input('filter', []);

        // ── Aplicar filtros ────────────────────────────────────

        // Text: nombre
        if (!empty($filter['nombre'])) {
            $query->where('nombre', 'LIKE', "%{$filter['nombre']}%");
        }

        // Date: fecha_creacion
        if (!empty($filter['fecha_creacion_year'])) {
            $query->whereYear('fecha_creacion', $filter['fecha_creacion_year']);
        }
        if (!empty($filter['fecha_creacion_month'])) {
            $query->whereMonth('fecha_creacion', $filter['fecha_creacion_month']);
        }
        if (!empty($filter['fecha_creacion_from'])) {
            $query->whereDate('fecha_creacion', '>=', $filter['fecha_creacion_from']);
        }
        if (!empty($filter['fecha_creacion_to'])) {
            $query->whereDate('fecha_creacion', '<=', $filter['fecha_creacion_to']);
        }

        // Text: rut
        if (!empty($filter['rut'])) {
            $query->where('rut', 'LIKE', "%{$filter['rut']}%");
        }

        // Text: email
        if (!empty($filter['email'])) {
            $query->where('email', 'LIKE', "%{$filter['email']}%");
        }

        // Text: ocupacion
        if (!empty($filter['ocupacion'])) {
            $query->where('ocupacion', 'LIKE', "%{$filter['ocupacion']}%");
        }

        // FK: Nacionalidad_id
        if (!empty($filter['Nacionalidad_id'])) {
            $query->where('Nacionalidad_id', $filter['Nacionalidad_id']);
        }

        // Enum: estado_civil
        if (!empty($filter['estado_civil'])) {
            $query->whereIn('estado_civil', (array)$filter['estado_civil']);
        }

        $clientes = $query->paginate(20);

        if ($request->ajax() || $request->wantsJson()) {
            $rows = view('cliente.table', compact('clientes'))->render();
            $pagination = $clientes->links()->toHtml();
            return response()->json([
                'rows' => $rows,
                'pagination' => $pagination,
                'total' => $clientes->total(),
            ]);
        }

        return view('cliente.index', compact('clientes'));
    }
}