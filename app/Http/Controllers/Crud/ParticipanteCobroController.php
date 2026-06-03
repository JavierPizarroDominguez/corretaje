<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\ParticipanteCobro;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Cobro;

class ParticipanteCobroController extends Controller
{
    public function index()
    {
        $participanteCobros = ParticipanteCobro::query()->paginate(10);
        return view('participante_cobro.index', compact('participanteCobros'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        $cobroCount   = \App\Models\Cobro::count();
        $cobroOptions = \App\Models\Cobro::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('participante_cobro.create', [
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            'cobroCount'   => $cobroCount,
            'cobroOptions' => $cobroOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'nombre-cliente' => 'sometimes|nullable|string',
            'Cliente_id' => 'required_with:nombre-cliente|integer|exists:cliente,id',
            'id-cobro' => 'sometimes|nullable|string',
            'Cobro_id' => 'required_with:id-cobro|integer|exists:cobro,id',
            'monto' => 'sometimes|nullable|integer',
            'rol' => 'sometimes|required|in:Deudor,Acreedor',
            // [GEN:END:validation_rules]
        ]);

        try {
            $participanteCobro = new ParticipanteCobro();
            // [GEN:START:store_fields]
            if (!empty($data['Cliente_id'])) {
                $cliente = Cliente::findOrFail($data['Cliente_id']);
                $participanteCobro->Cliente_id = $cliente->id;
            }
            if (!empty($data['Cobro_id'])) {
                $cobro = Cobro::findOrFail($data['Cobro_id']);
                $participanteCobro->Cobro_id = $cobro->id;
            }
            $participanteCobro->monto = $data['monto'];
            $participanteCobro->rol = $data['rol'];
            // [GEN:END:store_fields]
            $participanteCobro->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('participante_cobro.show', [$participanteCobro->cliente->id, $participanteCobro->cobro->id])
                ->with('success', 'ParticipanteCobro se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_participante_cobro_monto')) {
                return back()->with('error', 'Participante cobro monto.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($cliente_id, $cobro_id)
    {
        $participanteCobro = ParticipanteCobro::query()
            ->where('Cliente_id', $cliente_id)
            ->where('Cobro_id', $cobro_id)
            ->firstOrFail();

        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        $cobroCount   = \App\Models\Cobro::count();
        $cobroOptions = \App\Models\Cobro::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('participante_cobro.show', [
            'participanteCobro' => $participanteCobro,
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            'cobroCount'   => $cobroCount,
            'cobroOptions' => $cobroOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($cliente_id, $cobro_id)
    {
        $participanteCobro = ParticipanteCobro::query()
            ->where('Cliente_id', $cliente_id)
            ->where('Cobro_id', $cobro_id)
            ->firstOrFail();

        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        $cobroCount   = \App\Models\Cobro::count();
        $cobroOptions = \App\Models\Cobro::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('participante_cobro.edit', [
            'participanteCobro' => $participanteCobro,
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            'cobroCount'   => $cobroCount,
            'cobroOptions' => $cobroOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $cliente_id, $cobro_id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'nombre-cliente' => 'sometimes|nullable|string',
            'Cliente_id' => 'required_with:nombre-cliente|integer|exists:cliente,id',
            'id-cobro' => 'sometimes|nullable|string',
            'Cobro_id' => 'required_with:id-cobro|integer|exists:cobro,id',
            'monto' => 'sometimes|nullable|integer',
            'rol' => 'sometimes|required|in:Deudor,Acreedor',
            // [GEN:END:validation_rules]
        ]);

        try {
            $participanteCobro = ParticipanteCobro::query()
            ->where('Cliente_id', $cliente_id)
            ->where('Cobro_id', $cobro_id)
            ->firstOrFail();
            // [GEN:START:update_fields]
        if (array_key_exists('nombre-cliente', $data)) {
            $cliente = Cliente::firstOrCreate([
                'nombre' => trim($data['nombre-cliente'])
            ]);
            $participanteCobro->Cliente_id = $cliente->id;
        }
        if (array_key_exists('id-cobro', $data)) {
            $cobro = Cobro::firstOrCreate([
                'id' => trim($data['id-cobro'])
            ]);
            $participanteCobro->Cobro_id = $cobro->id;
        }
        if (array_key_exists('monto', $data)) {
            $participanteCobro->monto = $data['monto'];
        }
        if (array_key_exists('rol', $data)) {
            $participanteCobro->rol = $data['rol'];
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]

            // [GEN:END:scoped_update_fields]
            ParticipanteCobro::query()->where(['Cliente_id' => $cliente_id, 'Cobro_id' => $cobro_id])->update($participanteCobro->getDirty());
            return redirect()->back()
                ->with('success', 'ParticipanteCobro se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_participante_cobro_monto')) {
                return back()->with('error', 'Participante cobro monto.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($cliente_id, $cobro_id)
    {
        try {
            ParticipanteCobro::query()
            ->where('Cliente_id', $cliente_id)
            ->where('Cobro_id', $cobro_id)
            ->delete();
            return redirect()->route('participante_cobro.index')
                ->with('success', 'ParticipanteCobro se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]
            if (str_contains($e->getMessage(), 'chk_participante_cobro_monto')) {
                return back()->with('error', 'Participante cobro monto.');
            }
            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
