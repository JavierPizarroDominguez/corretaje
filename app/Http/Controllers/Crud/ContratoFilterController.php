<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use Illuminate\Http\Request;
use App\Models\Unidad;
use App\Models\Ciudad;


class ContratoFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::query();

        $filter = $request->input('filter', []);

        // ── Aplicar filtros ────────────────────────────────────

        // FK: Unidad_id
        if (!empty($filter['Unidad_id'])) {
            $query->where('Unidad_id', $filter['Unidad_id']);
        }

        // Boolean: administracion
        if (isset($filter['administracion']) && $filter['administracion'] !== '') {
            $query->where('administracion', $filter['administracion']);
        }

        // Number: comision_inicial
        if (isset($filter['comision_inicial_min']) && $filter['comision_inicial_min'] !== '') {
            $query->where('comision_inicial', '>=', $filter['comision_inicial_min']);
        }
        if (isset($filter['comision_inicial_max']) && $filter['comision_inicial_max'] !== '') {
            $query->where('comision_inicial', '<=', $filter['comision_inicial_max']);
        }

        // Number: garantia
        if (isset($filter['garantia_min']) && $filter['garantia_min'] !== '') {
            $query->where('garantia', '>=', $filter['garantia_min']);
        }
        if (isset($filter['garantia_max']) && $filter['garantia_max'] !== '') {
            $query->where('garantia', '<=', $filter['garantia_max']);
        }

        // Number: renta
        if (isset($filter['renta_min']) && $filter['renta_min'] !== '') {
            $query->where('renta', '>=', $filter['renta_min']);
        }
        if (isset($filter['renta_max']) && $filter['renta_max'] !== '') {
            $query->where('renta', '<=', $filter['renta_max']);
        }

        // Number: dia_pago
        if (isset($filter['dia_pago_min']) && $filter['dia_pago_min'] !== '') {
            $query->where('dia_pago', '>=', $filter['dia_pago_min']);
        }
        if (isset($filter['dia_pago_max']) && $filter['dia_pago_max'] !== '') {
            $query->where('dia_pago', '<=', $filter['dia_pago_max']);
        }

        // Number: comision_mensual
        if (isset($filter['comision_mensual_min']) && $filter['comision_mensual_min'] !== '') {
            $query->where('comision_mensual', '>=', $filter['comision_mensual_min']);
        }
        if (isset($filter['comision_mensual_max']) && $filter['comision_mensual_max'] !== '') {
            $query->where('comision_mensual', '<=', $filter['comision_mensual_max']);
        }

        // Date: fecha_firma
        if (!empty($filter['fecha_firma_year'])) {
            $query->whereYear('fecha_firma', $filter['fecha_firma_year']);
        }
        if (!empty($filter['fecha_firma_month'])) {
            $query->whereMonth('fecha_firma', $filter['fecha_firma_month']);
        }
        if (!empty($filter['fecha_firma_from'])) {
            $query->whereDate('fecha_firma', '>=', $filter['fecha_firma_from']);
        }
        if (!empty($filter['fecha_firma_to'])) {
            $query->whereDate('fecha_firma', '<=', $filter['fecha_firma_to']);
        }

        // Date: fecha_inicio
        if (!empty($filter['fecha_inicio_year'])) {
            $query->whereYear('fecha_inicio', $filter['fecha_inicio_year']);
        }
        if (!empty($filter['fecha_inicio_month'])) {
            $query->whereMonth('fecha_inicio', $filter['fecha_inicio_month']);
        }
        if (!empty($filter['fecha_inicio_from'])) {
            $query->whereDate('fecha_inicio', '>=', $filter['fecha_inicio_from']);
        }
        if (!empty($filter['fecha_inicio_to'])) {
            $query->whereDate('fecha_inicio', '<=', $filter['fecha_inicio_to']);
        }

        // Date: fecha_termino
        if (!empty($filter['fecha_termino_year'])) {
            $query->whereYear('fecha_termino', $filter['fecha_termino_year']);
        }
        if (!empty($filter['fecha_termino_month'])) {
            $query->whereMonth('fecha_termino', $filter['fecha_termino_month']);
        }
        if (!empty($filter['fecha_termino_from'])) {
            $query->whereDate('fecha_termino', '>=', $filter['fecha_termino_from']);
        }
        if (!empty($filter['fecha_termino_to'])) {
            $query->whereDate('fecha_termino', '<=', $filter['fecha_termino_to']);
        }

        // Text: url_pdf
        if (!empty($filter['url_pdf'])) {
            $query->where('url_pdf', 'LIKE', "%{$filter['url_pdf']}%");
        }

        // FK: Ciudad_id
        if (!empty($filter['Ciudad_id'])) {
            $query->where('Ciudad_id', $filter['Ciudad_id']);
        }

        $contratos = $query->paginate(20);

        if ($request->ajax() || $request->wantsJson()) {
            $rows = view('contrato.table', compact('contratos'))->render();
            $pagination = $contratos->links()->toHtml();
            return response()->json([
                'rows' => $rows,
                'pagination' => $pagination,
                'total' => $contratos->total(),
            ]);
        }

        return view('contrato.index', compact('contratos'));
    }
}