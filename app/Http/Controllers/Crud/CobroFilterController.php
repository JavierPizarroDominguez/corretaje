<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use Illuminate\Http\Request;
use App\Models\Contrato;
use App\Models\Servicio;
use App\Models\Propiedad;
use App\Models\Unidad;
use App\Models\ParticipanteCobro;


class CobroFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Cobro::query();

        $filter = $request->input('filter', []);

        // ── Aplicar filtros ────────────────────────────────────

        // Date: fecha_cobro
        if (!empty($filter['fecha_cobro_year'])) {
            $query->whereYear('fecha_cobro', $filter['fecha_cobro_year']);
        }
        if (!empty($filter['fecha_cobro_month'])) {
            $query->whereMonth('fecha_cobro', $filter['fecha_cobro_month']);
        }
        if (!empty($filter['fecha_cobro_from'])) {
            $query->whereDate('fecha_cobro', '>=', $filter['fecha_cobro_from']);
        }
        if (!empty($filter['fecha_cobro_to'])) {
            $query->whereDate('fecha_cobro', '<=', $filter['fecha_cobro_to']);
        }

        // Enum: estado
        if (!empty($filter['estado'])) {
            $query->whereIn('estado', (array)$filter['estado']);
        }

        // Enum: tipo
        if (!empty($filter['tipo'])) {
            $query->whereIn('tipo', (array)$filter['tipo']);
        }

        // Number: monto
        if (isset($filter['monto_min']) && $filter['monto_min'] !== '') {
            $query->where('monto', '>=', $filter['monto_min']);
        }
        if (isset($filter['monto_max']) && $filter['monto_max'] !== '') {
            $query->where('monto', '<=', $filter['monto_max']);
        }

        // Text: detalle
        if (!empty($filter['detalle'])) {
            $query->where('detalle', 'LIKE', "%{$filter['detalle']}%");
        }

        // FK: Contrato_id
        if (!empty($filter['Contrato_id'])) {
            $query->where('Contrato_id', $filter['Contrato_id']);
        }

        // FK: Servicio_id
        if (!empty($filter['Servicio_id'])) {
            $query->where('Servicio_id', $filter['Servicio_id']);
        }

        // FK: Propiedad_id
        if (!empty($filter['Propiedad_id'])) {
            $query->where('Propiedad_id', $filter['Propiedad_id']);
        }

        // FK: Unidad_id
        if (!empty($filter['Unidad_id'])) {
            $query->where('Unidad_id', $filter['Unidad_id']);
        }

        // Scoped: deudor
        if (!empty($filter['deudor_cliente_id'])) {
            $query->whereHas('deudor', fn($q) => $q->where('Cliente_id', $filter['deudor_cliente_id']));
        }

        // Scoped: acreedor
        if (!empty($filter['acreedor_cliente_id'])) {
            $query->whereHas('acreedor', fn($q) => $q->where('Cliente_id', $filter['acreedor_cliente_id']));
        }

        $cobros = $query->paginate(20);

        if ($request->ajax() || $request->wantsJson()) {
            $rows = view('cobro.table', compact('cobros'))->render();
            $pagination = $cobros->links()->toHtml();
            return response()->json([
                'rows' => $rows,
                'pagination' => $pagination,
                'total' => $cobros->total(),
            ]);
        }

        return view('cobro.index', compact('cobros'));
    }
}