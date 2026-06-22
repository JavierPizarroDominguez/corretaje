<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use Illuminate\Http\Request;
use App\Models\Propiedad;
use App\Models\Empresa;


class ServicioFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Servicio::query();

        $filter = $request->input('filter', []);

        // ── Aplicar filtros ────────────────────────────────────

        // Enum: tipo
        if (!empty($filter['tipo'])) {
            $query->whereIn('tipo', (array)$filter['tipo']);
        }

        // Number: dia_pago
        if (isset($filter['dia_pago_min']) && $filter['dia_pago_min'] !== '') {
            $query->where('dia_pago', '>=', $filter['dia_pago_min']);
        }
        if (isset($filter['dia_pago_max']) && $filter['dia_pago_max'] !== '') {
            $query->where('dia_pago', '<=', $filter['dia_pago_max']);
        }

        // FK: Propiedad_id
        if (!empty($filter['Propiedad_id'])) {
            $query->where('Propiedad_id', $filter['Propiedad_id']);
        }

        // Enum: estado
        if (!empty($filter['estado'])) {
            $query->whereIn('estado', (array)$filter['estado']);
        }

        // Text: numero_cliente
        if (!empty($filter['numero_cliente'])) {
            $query->where('numero_cliente', 'LIKE', "%{$filter['numero_cliente']}%");
        }

        // FK: Empresa_id
        if (!empty($filter['Empresa_id'])) {
            $query->where('Empresa_id', $filter['Empresa_id']);
        }

        // Number: monto_fijo
        if (isset($filter['monto_fijo_min']) && $filter['monto_fijo_min'] !== '') {
            $query->where('monto_fijo', '>=', $filter['monto_fijo_min']);
        }
        if (isset($filter['monto_fijo_max']) && $filter['monto_fijo_max'] !== '') {
            $query->where('monto_fijo', '<=', $filter['monto_fijo_max']);
        }

        $servicios = $query->paginate(20);

        if ($request->ajax() || $request->wantsJson()) {
            $rows = view('servicio.table', compact('servicios'))->render();
            $pagination = $servicios->links()->toHtml();
            return response()->json([
                'rows' => $rows,
                'pagination' => $pagination,
                'total' => $servicios->total(),
            ]);
        }

        return view('servicio.index', compact('servicios'));
    }
}