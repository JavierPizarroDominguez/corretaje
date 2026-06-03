<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\ParticipanteContrato;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Contrato;

class ParticipanteContratoController extends Controller
{
    public function index()
    {
        $participanteContratos = ParticipanteContrato::query()->paginate(10);
        return view('participante_contrato.index', compact('participanteContratos'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('id')->get(['id', 'id']);
        $contratoCount   = \App\Models\Contrato::count();
        $contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('participante_contrato.create', [
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            'contratoCount'   => $contratoCount,
            'contratoOptions' => $contratoOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'Cliente_id' => 'sometimes|required|integer|exists:cliente,id',
            'id-cliente' => 'sometimes|nullable|string',
            'Contrato_id' => 'sometimes|required|integer|exists:contrato,id',
            'id-contrato' => 'sometimes|nullable|string',
            'rol' => 'sometimes|required|in:arrendatario,arrendador,corredor,co-arrendatario,co-arrendador',
            'monto' => 'sometimes|nullable|integer',
            // [GEN:END:validation_rules]
        ]);

        try {
            $participanteContrato = new ParticipanteContrato();
            // [GEN:START:store_fields]
            if (isset($data['Cliente_id']) && $data['Cliente_id'] !== '__nuevo__') {
                $participanteContrato->Cliente_id = $data['Cliente_id'];
            }

            if (isset($data['id-cliente'])) {
                $cliente = Cliente::firstOrCreate([
                    'id' => trim($data['id-cliente'])
                ]);
                $participanteContrato->Cliente_id = $cliente->id;
            }
            if (isset($data['Contrato_id']) && $data['Contrato_id'] !== '__nuevo__') {
                $participanteContrato->Contrato_id = $data['Contrato_id'];
            }

            if (isset($data['id-contrato'])) {
                $contrato = Contrato::firstOrCreate([
                    'id' => trim($data['id-contrato'])
                ]);
                $participanteContrato->Contrato_id = $contrato->id;
            }
            $participanteContrato->rol = $data['rol'];
            $participanteContrato->monto = $data['monto'];
            // [GEN:END:store_fields]
            $participanteContrato->save();

            // [GEN:START:scoped_store_fields]

            // [GEN:END:scoped_store_fields]

            return redirect()->route('participante_contrato.show', $participanteContrato->id)
                ->with('success', 'ParticipanteContrato se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]

            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $participanteContrato = ParticipanteContrato::findOrFail($id);

        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('id')->get(['id', 'id']);
        $contratoCount   = \App\Models\Contrato::count();
        $contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('participante_contrato.show', [
            'participanteContrato' => $participanteContrato,
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            'contratoCount'   => $contratoCount,
            'contratoOptions' => $contratoOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $participanteContrato = ParticipanteContrato::findOrFail($id);

        // [GEN:START:fk_data]
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('id')->get(['id', 'id']);
        $contratoCount   = \App\Models\Contrato::count();
        $contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('participante_contrato.edit', [
            'participanteContrato' => $participanteContrato,
            // [GEN:START:fk_compact_array]
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            'contratoCount'   => $contratoCount,
            'contratoOptions' => $contratoOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'Cliente_id' => 'sometimes|required|integer|exists:cliente,id',
            'id-cliente' => 'sometimes|nullable|string',
            'Contrato_id' => 'sometimes|required|integer|exists:contrato,id',
            'id-contrato' => 'sometimes|nullable|string',
            'rol' => 'sometimes|required|in:arrendatario,arrendador,corredor,co-arrendatario,co-arrendador',
            'monto' => 'sometimes|nullable|integer',
            // [GEN:END:validation_rules]
        ]);

        try {
            $participanteContrato = ParticipanteContrato::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('Cliente_id', $data) && $data['Cliente_id'] !== '__nuevo__') {
            $participanteContrato->Cliente_id = $data['Cliente_id'];
        }

        if (array_key_exists('id-cliente', $data)) {
            $cliente = Cliente::firstOrCreate([
                'id' => trim($data['id-cliente'])
            ]);
            $participanteContrato->Cliente_id = $cliente->id;
        }
        if (array_key_exists('Contrato_id', $data) && $data['Contrato_id'] !== '__nuevo__') {
            $participanteContrato->Contrato_id = $data['Contrato_id'];
        }

        if (array_key_exists('id-contrato', $data)) {
            $contrato = Contrato::firstOrCreate([
                'id' => trim($data['id-contrato'])
            ]);
            $participanteContrato->Contrato_id = $contrato->id;
        }
        if (array_key_exists('rol', $data)) {
            $participanteContrato->rol = $data['rol'];
        }
        if (array_key_exists('monto', $data)) {
            $participanteContrato->monto = $data['monto'];
        }
            // [GEN:END:update_fields]
            $participanteContrato->save();
            return redirect()->back()
                ->with('success', 'ParticipanteContrato se ha actualizado correctamente.');
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
            ParticipanteContrato::destroy($id);
            return redirect()->route('participante_contrato.index')
                ->with('success', 'ParticipanteContrato se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]

            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
