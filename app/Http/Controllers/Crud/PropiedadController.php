<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use Illuminate\Http\Request;
use App\Models\Cliente;

class PropiedadController extends Controller
{
    public function index()
    {
        $propiedads = Propiedad::query()->paginate(10);
        return view('propiedad.index', compact('propiedads'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('propiedad.create', [
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'direccion' => 'sometimes|required|string|max:150',
            'propietario' => 'sometimes|required|integer|exists:cliente,id',
            'nombre-propietario' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $propiedad = new Propiedad();
            // [GEN:START:store_fields]
            $propiedad->direccion = $data['direccion'];
            if (isset($data['propietario']) && $data['propietario'] !== '__nuevo__') {
                $propiedad->propietario = $data['propietario'];
            }

            if (!empty($data['propietario'])) {
                $cliente = Cliente::findOrFail($data['propietario']);
                $propiedad->propietario = $cliente->id;
            }
            // [GEN:END:store_fields]
            $propiedad->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('propiedad.show', $propiedad->id)
                ->with('success', 'Propiedad se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]

            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $propiedad = Propiedad::findOrFail($id);

        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('propiedad.show', [
            'propiedad' => $propiedad,
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $propiedad = Propiedad::findOrFail($id);

        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('propiedad.edit', [
            'propiedad' => $propiedad,
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'direccion' => 'sometimes|required|string|max:150',
            'propietario' => 'sometimes|required|integer|exists:cliente,id',
            'nombre-propietario' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $propiedad = Propiedad::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('direccion', $data)) {
            $propiedad->direccion = $data['direccion'];
        }
        if (array_key_exists('propietario', $data) && $data['propietario'] !== '__nuevo__') {
            $propiedad->propietario = $data['propietario'];
        }

        if (array_key_exists('nombre-propietario', $data)) {
            $cliente = Cliente::firstOrCreate([
                'nombre' => trim($data['nombre-propietario'])
            ]);
            $propiedad->propietario = $cliente->id;
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]

            // [GEN:END:scoped_update_fields]
            $propiedad->save();
            return redirect()->back()
                ->with('success', 'Propiedad se ha actualizado correctamente.');
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
            Propiedad::destroy($id);
            return redirect()->route('propiedad.index')
                ->with('success', 'Propiedad se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]

            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
