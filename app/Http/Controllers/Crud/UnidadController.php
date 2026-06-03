<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Unidad;
use Illuminate\Http\Request;
use App\Models\Propiedad;

class UnidadController extends Controller
{
    public function index()
    {
        $unidads = Unidad::query()->paginate(10);
        return view('unidad.index', compact('unidads'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        // [GEN:END:fk_data]

        return view('unidad.create', [
            // [GEN:START:fk_compact_array]
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'nombre' => 'sometimes|nullable|string|max:100',
            'Propiedad_id' => 'sometimes|required|integer|exists:propiedad,id',
            'direccion-propiedad' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $unidad = new Unidad();
            // [GEN:START:store_fields]
            $unidad->nombre = $data['nombre'];
            if (isset($data['Propiedad_id']) && $data['Propiedad_id'] !== '__nuevo__') {
                $unidad->Propiedad_id = $data['Propiedad_id'];
            }

            if (!empty($data['Propiedad_id'])) {
                $propiedad = Propiedad::findOrFail($data['Propiedad_id']);
                $unidad->Propiedad_id = $propiedad->id;
            }
            // [GEN:END:store_fields]
            $unidad->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('unidad.show', $unidad->id)
                ->with('success', 'Unidad se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]

            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $unidad = Unidad::findOrFail($id);

        // [GEN:START:fk_data]
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        // [GEN:END:fk_data]

        return view('unidad.show', [
            'unidad' => $unidad,
            // [GEN:START:fk_compact_array]
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $unidad = Unidad::findOrFail($id);

        // [GEN:START:fk_data]
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        // [GEN:END:fk_data]

        return view('unidad.edit', [
            'unidad' => $unidad,
            // [GEN:START:fk_compact_array]
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'nombre' => 'sometimes|nullable|string|max:100',
            'Propiedad_id' => 'sometimes|required|integer|exists:propiedad,id',
            'direccion-propiedad' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $unidad = Unidad::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('nombre', $data)) {
            $unidad->nombre = $data['nombre'];
        }
        if (array_key_exists('Propiedad_id', $data) && $data['Propiedad_id'] !== '__nuevo__') {
            $unidad->Propiedad_id = $data['Propiedad_id'];
        }

        if (array_key_exists('direccion-propiedad', $data)) {
            $propiedad = Propiedad::firstOrCreate([
                'direccion' => trim($data['direccion-propiedad'])
            ]);
            $unidad->Propiedad_id = $propiedad->id;
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]

            // [GEN:END:scoped_update_fields]
            $unidad->save();
            return redirect()->back()
                ->with('success', 'Unidad se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]

            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Unidad::destroy($id);
            return redirect()->route('unidad.index')
                ->with('success', 'Unidad se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]

            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
