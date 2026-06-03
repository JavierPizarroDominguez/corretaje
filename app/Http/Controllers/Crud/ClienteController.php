<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Models\Nacionalidad;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::query()->paginate(10);
        return view('cliente.index', compact('clientes'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $nacionalidadCount   = \App\Models\Nacionalidad::count();
        $nacionalidadOptions = \App\Models\Nacionalidad::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('cliente.create', [
            // [GEN:START:fk_compact_array]
            'nacionalidadCount'   => $nacionalidadCount,
            'nacionalidadOptions' => $nacionalidadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'nombre' => 'sometimes|required|string|max:100|unique:cliente,nombre',
            'fecha_creacion' => 'sometimes|required|date',
            'rut' => 'sometimes|nullable|string|max:10',
            'email' => 'sometimes|nullable|string|max:254',
            'ocupacion' => 'sometimes|nullable|string|max:100',
            'Nacionalidad_id' => 'sometimes|nullable|integer|exists:nacionalidad,id',
            'nombre-nacionalidad' => 'sometimes|nullable|string',
            'estado_civil' => 'sometimes|nullable|in:Soltero,Casado,Viudo,Divorciado',
            // [GEN:END:validation_rules]
        ]);

        try {
            $cliente = new Cliente();
            // [GEN:START:store_fields]
            $cliente->nombre = $data['nombre'];
            $cliente->fecha_creacion = $data['fecha_creacion'];
            $cliente->rut = $data['rut'];
            $cliente->email = $data['email'];
            $cliente->ocupacion = $data['ocupacion'];
            if (isset($data['Nacionalidad_id']) && $data['Nacionalidad_id'] !== '__nuevo__') {
                $cliente->Nacionalidad_id = $data['Nacionalidad_id'];
            }

            if (!empty($data['Nacionalidad_id'])) {
                $nacionalidad = Nacionalidad::findOrFail($data['Nacionalidad_id']);
                $cliente->Nacionalidad_id = $nacionalidad->id;
            }
            $cliente->estado_civil = $data['estado_civil'];
            // [GEN:END:store_fields]
            $cliente->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('cliente.show', $cliente->id)
                ->with('success', 'Cliente se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_rut_formato')) {
                return back()->with('error', 'Rut formato.');
            }
            if (str_contains($e->getMessage(), 'chk_email_formato')) {
                return back()->with('error', 'Email formato.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $cliente = Cliente::findOrFail($id);

        // [GEN:START:fk_data]
        $nacionalidadCount   = \App\Models\Nacionalidad::count();
        $nacionalidadOptions = \App\Models\Nacionalidad::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('cliente.show', [
            'cliente' => $cliente,
            // [GEN:START:fk_compact_array]
            'nacionalidadCount'   => $nacionalidadCount,
            'nacionalidadOptions' => $nacionalidadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);

        // [GEN:START:fk_data]
        $nacionalidadCount   = \App\Models\Nacionalidad::count();
        $nacionalidadOptions = \App\Models\Nacionalidad::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('cliente.edit', [
            'cliente' => $cliente,
            // [GEN:START:fk_compact_array]
            'nacionalidadCount'   => $nacionalidadCount,
            'nacionalidadOptions' => $nacionalidadOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'nombre' => 'sometimes|required|string|max:100|unique:cliente,nombre',
            'fecha_creacion' => 'sometimes|required|date',
            'rut' => 'sometimes|nullable|string|max:10',
            'email' => 'sometimes|nullable|string|max:254',
            'ocupacion' => 'sometimes|nullable|string|max:100',
            'Nacionalidad_id' => 'sometimes|nullable|integer|exists:nacionalidad,id',
            'nombre-nacionalidad' => 'sometimes|nullable|string',
            'estado_civil' => 'sometimes|nullable|in:Soltero,Casado,Viudo,Divorciado',
            // [GEN:END:validation_rules]
        ]);

        try {
            $cliente = Cliente::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('nombre', $data)) {
            $cliente->nombre = $data['nombre'];
        }
        if (array_key_exists('fecha_creacion', $data)) {
            $cliente->fecha_creacion = $data['fecha_creacion'];
        }
        if (array_key_exists('rut', $data)) {
            $cliente->rut = $data['rut'];
        }
        if (array_key_exists('email', $data)) {
            $cliente->email = $data['email'];
        }
        if (array_key_exists('ocupacion', $data)) {
            $cliente->ocupacion = $data['ocupacion'];
        }
        if (array_key_exists('Nacionalidad_id', $data) && $data['Nacionalidad_id'] !== '__nuevo__') {
            $cliente->Nacionalidad_id = $data['Nacionalidad_id'];
        }

        if (array_key_exists('nombre-nacionalidad', $data)) {
            $nacionalidad = Nacionalidad::firstOrCreate([
                'nombre' => trim($data['nombre-nacionalidad'])
            ]);
            $cliente->Nacionalidad_id = $nacionalidad->id;
        }
        if (array_key_exists('estado_civil', $data)) {
            $cliente->estado_civil = $data['estado_civil'];
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]

            // [GEN:END:scoped_update_fields]
            $cliente->save();
            return redirect()->back()
                ->with('success', 'Cliente se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_rut_formato')) {
                return back()->with('error', 'Rut formato.');
            }
            if (str_contains($e->getMessage(), 'chk_email_formato')) {
                return back()->with('error', 'Email formato.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Cliente::destroy($id);
            return redirect()->route('cliente.index')
                ->with('success', 'Cliente se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]
            if (str_contains($e->getMessage(), 'chk_rut_formato')) {
                return back()->with('error', 'Rut formato.');
            }
            if (str_contains($e->getMessage(), 'chk_email_formato')) {
                return back()->with('error', 'Email formato.');
            }
            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
