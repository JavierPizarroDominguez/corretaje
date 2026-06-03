<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Transaccion;
use Illuminate\Http\Request;
use App\Models\DestinoTransaccion;
use App\Models\OrigenTransaccion;


class TransaccionFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaccion::query();

        $filter = $request->input('filter', []);

        // ── Aplicar filtros ────────────────────────────────────

        // Number: monto
        if (isset($filter['monto_min']) && $filter['monto_min'] !== '') {
            $query->where('monto', '>=', $filter['monto_min']);
        }
        if (isset($filter['monto_max']) && $filter['monto_max'] !== '') {
            $query->where('monto', '<=', $filter['monto_max']);
        }

        // Date: fecha
        if (!empty($filter['fecha_year'])) {
            $query->whereYear('fecha', $filter['fecha_year']);
        }
        if (!empty($filter['fecha_month'])) {
            $query->whereMonth('fecha', $filter['fecha_month']);
        }
        if (!empty($filter['fecha_from'])) {
            $query->whereDate('fecha', '>=', $filter['fecha_from']);
        }
        if (!empty($filter['fecha_to'])) {
            $query->whereDate('fecha', '<=', $filter['fecha_to']);
        }

        // FK: Destino_Transaccion_id
        if (!empty($filter['Destino_Transaccion_id'])) {
            $query->where('Destino_Transaccion_id', $filter['Destino_Transaccion_id']);
        }

        // FK: Origen_Transaccion_id
        if (!empty($filter['Origen_Transaccion_id'])) {
            $query->where('Origen_Transaccion_id', $filter['Origen_Transaccion_id']);
        }

        // Text: url_comprobante
        if (!empty($filter['url_comprobante'])) {
            $query->where('url_comprobante', 'LIKE', "%{$filter['url_comprobante']}%");
        }

        $transaccions = $query->paginate(20);

        if ($request->ajax() || $request->wantsJson()) {
            $rows = view('transaccion.table', compact('transaccions'))->render();
            $pagination = $transaccions->links()->toHtml();
            return response()->json([
                'rows' => $rows,
                'pagination' => $pagination,
                'total' => $transaccions->total(),
            ]);
        }

        return view('transaccion.index', compact('transaccions'));
    }
}