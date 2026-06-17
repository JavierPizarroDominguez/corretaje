<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use Illuminate\Http\Request;
use App\Models\Unidad;
use App\Models\Ciudad;

class ContratoController extends Controller
{
    public function index()
    {
        $contratos = Contrato::query()->paginate(10);
        return view('contrato.index', compact('contratos'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $unidadCount   = \App\Models\Unidad::count();
        $unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre']);
        $ciudadCount   = \App\Models\Ciudad::count();
        $ciudadOptions = \App\Models\Ciudad::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('contrato.create', [
            // [GEN:START:fk_compact_array]
            'unidadCount'   => $unidadCount,
            'unidadOptions' => $unidadOptions,
            'ciudadCount'   => $ciudadCount,
            'ciudadOptions' => $ciudadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'Unidad_id' => 'sometimes|required|integer|exists:unidad,id',
            'nombre-unidad' => 'sometimes|nullable|string',
            'administracion' => 'sometimes|required|boolean',
            'comision_inicial' => 'sometimes|nullable|integer',
            'garantia' => 'sometimes|nullable|integer',
            'renta' => 'sometimes|nullable|integer',
            'dia_pago' => 'sometimes|nullable|integer|between:1,28',
            'comision_mensual' => 'sometimes|nullable|integer',
            'fecha_firma' => 'sometimes|nullable|date',
            'fecha_inicio' => 'sometimes|nullable|date',
            'fecha_termino' => 'sometimes|nullable|date',
            'url_pdf' => 'sometimes|nullable|string',
            'Ciudad_id' => 'sometimes|nullable|integer|exists:ciudad,id',
            'nombre-ciudad' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $contrato = new Contrato();
            // [GEN:START:store_fields]
            if (isset($data['Unidad_id']) && $data['Unidad_id'] !== '__nuevo__') {
                $contrato->Unidad_id = $data['Unidad_id'];
            }

            if (!empty($data['Unidad_id'])) {
                $unidad = Unidad::findOrFail($data['Unidad_id']);
                $contrato->Unidad_id = $unidad->id;
            }
            $contrato->administracion = $data['administracion'];
            $contrato->comision_inicial = $data['comision_inicial'];
            $contrato->garantia = $data['garantia'];
            $contrato->renta = $data['renta'];
            $contrato->dia_pago = $data['dia_pago'];
            $contrato->comision_mensual = $data['comision_mensual'];
            $contrato->fecha_firma = $data['fecha_firma'];
            $contrato->fecha_inicio = $data['fecha_inicio'];
            $contrato->fecha_termino = $data['fecha_termino'];
            $contrato->url_pdf = $data['url_pdf'];
            if (isset($data['Ciudad_id']) && $data['Ciudad_id'] !== '__nuevo__') {
                $contrato->Ciudad_id = $data['Ciudad_id'];
            }

            if (!empty($data['Ciudad_id'])) {
                $ciudad = Ciudad::findOrFail($data['Ciudad_id']);
                $contrato->Ciudad_id = $ciudad->id;
            }
            // [GEN:END:store_fields]
            $contrato->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('contrato.show', $contrato->id)
                ->with('success', 'Contrato se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_renta_contrato')) {
                return back()->with('error', 'Renta contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_dia_pago_contrato')) {
                return back()->with('error', 'Dia pago contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_fecha_inicio_contrato')) {
                return back()->with('error', 'Fecha inicio contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_fecha_termino_contrato')) {
                return back()->with('error', 'Fecha termino contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_comision_inicial_contrato')) {
                return back()->with('error', 'Comision inicial contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_comision_mensual_contrato')) {
                return back()->with('error', 'Comision mensual contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_datos_administracion')) {
                return back()->with('error', 'Datos administracion.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $contrato = Contrato::with([
            'unidad.propiedad',
            'participante_contratos.cliente',
            'arrendador.cliente',
            'arrendatario.cliente',
            'corredor.cliente',
            'cobros.participante_cobros.cliente',
        ])->findOrFail($id);

        // [GEN:START:fk_data]
        $unidadCount   = \App\Models\Unidad::count();
        $unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre']);
        $ciudadCount   = \App\Models\Ciudad::count();
        $ciudadOptions = \App\Models\Ciudad::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('contrato.show', [
            'contrato' => $contrato,
            // [GEN:START:fk_compact_array]
            'unidadCount'   => $unidadCount,
            'unidadOptions' => $unidadOptions,
            'ciudadCount'   => $ciudadCount,
            'ciudadOptions' => $ciudadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $contrato = Contrato::findOrFail($id);

        // [GEN:START:fk_data]
        $unidadCount   = \App\Models\Unidad::count();
        $unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre']);
        $ciudadCount   = \App\Models\Ciudad::count();
        $ciudadOptions = \App\Models\Ciudad::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('contrato.edit', [
            'contrato' => $contrato,
            // [GEN:START:fk_compact_array]
            'unidadCount'   => $unidadCount,
            'unidadOptions' => $unidadOptions,
            'ciudadCount'   => $ciudadCount,
            'ciudadOptions' => $ciudadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'Unidad_id' => 'sometimes|required|integer|exists:unidad,id',
            'nombre-unidad' => 'sometimes|nullable|string',
            'administracion' => 'sometimes|required|boolean',
            'comision_inicial' => 'sometimes|nullable|integer',
            'garantia' => 'sometimes|nullable|integer',
            'renta' => 'sometimes|nullable|integer',
            'dia_pago' => 'sometimes|nullable|integer|between:1,28',
            'comision_mensual' => 'sometimes|nullable|integer',
            'fecha_firma' => 'sometimes|nullable|date',
            'fecha_inicio' => 'sometimes|nullable|date',
            'fecha_termino' => 'sometimes|nullable|date',
            'url_pdf' => 'sometimes|nullable|string',
            'Ciudad_id' => 'sometimes|nullable|integer|exists:ciudad,id',
            'nombre-ciudad' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $contrato = Contrato::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('Unidad_id', $data) && $data['Unidad_id'] !== '__nuevo__') {
            $contrato->Unidad_id = $data['Unidad_id'];
        }

        if (array_key_exists('nombre-unidad', $data)) {
            $unidad = Unidad::firstOrCreate([
                'nombre' => trim($data['nombre-unidad'])
            ]);
            $contrato->Unidad_id = $unidad->id;
        }
        if (array_key_exists('administracion', $data)) {
            $contrato->administracion = $data['administracion'];
        }
        if (array_key_exists('comision_inicial', $data)) {
            $contrato->comision_inicial = $data['comision_inicial'];
        }
        if (array_key_exists('garantia', $data)) {
            $contrato->garantia = $data['garantia'];
        }
        if (array_key_exists('renta', $data)) {
            $contrato->renta = $data['renta'];
        }
        if (array_key_exists('dia_pago', $data)) {
            $contrato->dia_pago = $data['dia_pago'];
        }
        if (array_key_exists('comision_mensual', $data)) {
            $contrato->comision_mensual = $data['comision_mensual'];
        }
        if (array_key_exists('fecha_firma', $data)) {
            $contrato->fecha_firma = $data['fecha_firma'];
        }
        if (array_key_exists('fecha_inicio', $data)) {
            $contrato->fecha_inicio = $data['fecha_inicio'];
        }
        if (array_key_exists('fecha_termino', $data)) {
            $contrato->fecha_termino = $data['fecha_termino'];
        }
        if (array_key_exists('url_pdf', $data)) {
            $contrato->url_pdf = $data['url_pdf'];
        }
        if (array_key_exists('Ciudad_id', $data) && $data['Ciudad_id'] !== '__nuevo__') {
            $contrato->Ciudad_id = $data['Ciudad_id'];
        }

        if (array_key_exists('nombre-ciudad', $data)) {
            $ciudad = Ciudad::firstOrCreate([
                'nombre' => trim($data['nombre-ciudad'])
            ]);
            $contrato->Ciudad_id = $ciudad->id;
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]

            // [GEN:END:scoped_update_fields]
            $contrato->save();
            return redirect()->back()
                ->with('success', 'Contrato se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_renta_contrato')) {
                return back()->with('error', 'Renta contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_dia_pago_contrato')) {
                return back()->with('error', 'Dia pago contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_fecha_inicio_contrato')) {
                return back()->with('error', 'Fecha inicio contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_fecha_termino_contrato')) {
                return back()->with('error', 'Fecha termino contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_comision_inicial_contrato')) {
                return back()->with('error', 'Comision inicial contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_comision_mensual_contrato')) {
                return back()->with('error', 'Comision mensual contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_datos_administracion')) {
                return back()->with('error', 'Datos administracion.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Contrato::destroy($id);
            return redirect()->route('contrato.index')
                ->with('success', 'Contrato se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]
            if (str_contains($e->getMessage(), 'chk_renta_contrato')) {
                return back()->with('error', 'Renta contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_dia_pago_contrato')) {
                return back()->with('error', 'Dia pago contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_fecha_inicio_contrato')) {
                return back()->with('error', 'Fecha inicio contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_fecha_termino_contrato')) {
                return back()->with('error', 'Fecha termino contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_comision_inicial_contrato')) {
                return back()->with('error', 'Comision inicial contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_comision_mensual_contrato')) {
                return back()->with('error', 'Comision mensual contrato.');
            }
            if (str_contains($e->getMessage(), 'chk_datos_administracion')) {
                return back()->with('error', 'Datos administracion.');
            }
            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
