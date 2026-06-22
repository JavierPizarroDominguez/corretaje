<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use Illuminate\Http\Request;
use App\Models\Propiedad;
use App\Models\Empresa;

class ServicioController extends Controller
{
    public function index()
    {
        $servicios = Servicio::query()->paginate(10);
        return view('servicio.index', compact('servicios'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        $empresaCount   = \App\Models\Empresa::count();
        $empresaOptions = \App\Models\Empresa::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('servicio.create', [
            // [GEN:START:fk_compact_array]
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            'empresaCount'   => $empresaCount,
            'empresaOptions' => $empresaOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'tipo' => 'sometimes|required|in:Luz,Agua,Gas,Gastos Comunes',
            'dia_pago' => 'sometimes|required|integer',
            'Propiedad_id' => 'sometimes|required|integer|exists:propiedad,id',
            'direccion-propiedad' => 'sometimes|nullable|string',
            'estado' => 'sometimes|required|in:Activo,Inactivo',
            'numero_cliente' => 'sometimes|nullable|string|max:45',
            'Empresa_id' => 'sometimes|nullable|integer|exists:empresa,id',
            'nombre-empresa' => 'sometimes|nullable|string',
            'monto_fijo' => 'sometimes|nullable|integer',
            // [GEN:END:validation_rules]
        ]);

        try {
            $servicio = new Servicio();
            // [GEN:START:store_fields]
            $servicio->tipo = $data['tipo'];
            $servicio->dia_pago = $data['dia_pago'];
            if (isset($data['Propiedad_id']) && $data['Propiedad_id'] !== '__nuevo__') {
                $servicio->Propiedad_id = $data['Propiedad_id'];
            }

            if (!empty($data['Propiedad_id'])) {
                $propiedad = Propiedad::findOrFail($data['Propiedad_id']);
                $servicio->Propiedad_id = $propiedad->id;
            }
            $servicio->estado = $data['estado'];
            $servicio->numero_cliente = $data['numero_cliente'];
            if (isset($data['Empresa_id']) && $data['Empresa_id'] !== '__nuevo__') {
                $servicio->Empresa_id = $data['Empresa_id'];
            }

            if (!empty($data['Empresa_id'])) {
                $empresa = Empresa::findOrFail($data['Empresa_id']);
                $servicio->Empresa_id = $empresa->id;
            }
            $servicio->monto_fijo = $data['monto_fijo'];
            // [GEN:END:store_fields]
            $servicio->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('servicio.show', $servicio->id)
                ->with('success', 'Servicio se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_dia_pago_servicio')) {
                return back()->with('error', 'Dia pago servicio.');
            }
            if (str_contains($e->getMessage(), 'chk_numero_cliente_servicio')) {
                return back()->with('error', 'Numero cliente servicio.');
            }
            if (str_contains($e->getMessage(), 'chk_monto_servicio')) {
                return back()->with('error', 'Monto servicio.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $servicio = Servicio::findOrFail($id);

        // [GEN:START:fk_data]
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        $empresaCount   = \App\Models\Empresa::count();
        $empresaOptions = \App\Models\Empresa::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('servicio.show', [
            'servicio' => $servicio,
            // [GEN:START:fk_compact_array]
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            'empresaCount'   => $empresaCount,
            'empresaOptions' => $empresaOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $servicio = Servicio::findOrFail($id);

        // [GEN:START:fk_data]
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        $empresaCount   = \App\Models\Empresa::count();
        $empresaOptions = \App\Models\Empresa::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('servicio.edit', [
            'servicio' => $servicio,
            // [GEN:START:fk_compact_array]
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            'empresaCount'   => $empresaCount,
            'empresaOptions' => $empresaOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'tipo' => 'sometimes|required|in:Luz,Agua,Gas,Gastos Comunes',
            'dia_pago' => 'sometimes|required|integer',
            'Propiedad_id' => 'sometimes|required|integer|exists:propiedad,id',
            'direccion-propiedad' => 'sometimes|nullable|string',
            'estado' => 'sometimes|required|in:Activo,Inactivo',
            'numero_cliente' => 'sometimes|nullable|string|max:45',
            'Empresa_id' => 'sometimes|nullable|integer|exists:empresa,id',
            'nombre-empresa' => 'sometimes|nullable|string',
            'monto_fijo' => 'sometimes|nullable|integer',
            // [GEN:END:validation_rules]
        ]);

        try {
            $servicio = Servicio::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('tipo', $data)) {
            $servicio->tipo = $data['tipo'];
        }
        if (array_key_exists('dia_pago', $data)) {
            $servicio->dia_pago = $data['dia_pago'];
        }
        if (array_key_exists('Propiedad_id', $data) && $data['Propiedad_id'] !== '__nuevo__') {
            $servicio->Propiedad_id = $data['Propiedad_id'];
        }

        if (array_key_exists('direccion-propiedad', $data)) {
            $propiedad = Propiedad::firstOrCreate([
                'direccion' => trim($data['direccion-propiedad'])
            ]);
            $servicio->Propiedad_id = $propiedad->id;
        }
        if (array_key_exists('estado', $data)) {
            $servicio->estado = $data['estado'];
        }
        if (array_key_exists('numero_cliente', $data)) {
            $servicio->numero_cliente = $data['numero_cliente'];
        }
        if (array_key_exists('Empresa_id', $data) && $data['Empresa_id'] !== '__nuevo__') {
            $servicio->Empresa_id = $data['Empresa_id'];
        }

        if (array_key_exists('nombre-empresa', $data)) {
            $empresa = Empresa::firstOrCreate([
                'nombre' => trim($data['nombre-empresa'])
            ]);
            $servicio->Empresa_id = $empresa->id;
        }
        if (array_key_exists('monto_fijo', $data)) {
            $servicio->monto_fijo = $data['monto_fijo'];
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]

            // [GEN:END:scoped_update_fields]
            $servicio->save();
            return redirect()->back()
                ->with('success', 'Servicio se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_dia_pago_servicio')) {
                return back()->with('error', 'Dia pago servicio.');
            }
            if (str_contains($e->getMessage(), 'chk_numero_cliente_servicio')) {
                return back()->with('error', 'Numero cliente servicio.');
            }
            if (str_contains($e->getMessage(), 'chk_monto_servicio')) {
                return back()->with('error', 'Monto servicio.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Servicio::destroy($id);
            return redirect()->route('servicio.index')
                ->with('success', 'Servicio se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]
            if (str_contains($e->getMessage(), 'chk_dia_pago_servicio')) {
                return back()->with('error', 'Dia pago servicio.');
            }
            if (str_contains($e->getMessage(), 'chk_numero_cliente_servicio')) {
                return back()->with('error', 'Numero cliente servicio.');
            }
            if (str_contains($e->getMessage(), 'chk_monto_servicio')) {
                return back()->with('error', 'Monto servicio.');
            }
            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
