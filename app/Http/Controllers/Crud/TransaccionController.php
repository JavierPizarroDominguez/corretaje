<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Transaccion;
use App\Traits\HandlesConstraintErrors;
use Illuminate\Http\Request;
use App\Models\DestinoTransaccion;
use App\Models\OrigenTransaccion;

class TransaccionController extends Controller
{
    use HandlesConstraintErrors;
    public function index()
    {
        $transaccions = Transaccion::query()->paginate(10);
        return view('transaccion.index', compact('transaccions'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $destinotransaccionCount   = \App\Models\DestinoTransaccion::count();
        $destinotransaccionOptions = \App\Models\DestinoTransaccion::orderBy('id')->get(['id', 'id']);
        $origentransaccionCount   = \App\Models\OrigenTransaccion::count();
        $origentransaccionOptions = \App\Models\OrigenTransaccion::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('transaccion.create', [
            // [GEN:START:fk_compact_array]
            'destinotransaccionCount'   => $destinotransaccionCount,
            'destinotransaccionOptions' => $destinotransaccionOptions,
            'origentransaccionCount'   => $origentransaccionCount,
            'origentransaccionOptions' => $origentransaccionOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'monto' => 'sometimes|required|integer',
            'fecha' => 'sometimes|required|date',
            'Destino_Transaccion_id' => 'sometimes|required|integer|exists:destino_transaccion,id',
            'id-destino_transaccion' => 'sometimes|nullable|string',
            'Origen_Transaccion_id' => 'sometimes|required|integer|exists:origen_transaccion,id',
            'id-origen_transaccion' => 'sometimes|nullable|string',
            'url_comprobante' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $transaccion = new Transaccion();
            // [GEN:START:store_fields]
            $transaccion->monto = $data['monto'];
            $transaccion->fecha = $data['fecha'];
            if (isset($data['Destino_Transaccion_id']) && $data['Destino_Transaccion_id'] !== '__nuevo__') {
                $transaccion->Destino_Transaccion_id = $data['Destino_Transaccion_id'];
            }

            if (isset($data['id-destino_transaccion'])) {
                $destinotransaccion = DestinoTransaccion::firstOrCreate([
                    'id' => trim($data['id-destino_transaccion'])
                ]);
                $transaccion->Destino_Transaccion_id = $destinotransaccion->id;
            }
            if (isset($data['Origen_Transaccion_id']) && $data['Origen_Transaccion_id'] !== '__nuevo__') {
                $transaccion->Origen_Transaccion_id = $data['Origen_Transaccion_id'];
            }

            if (isset($data['id-origen_transaccion'])) {
                $origentransaccion = OrigenTransaccion::firstOrCreate([
                    'id' => trim($data['id-origen_transaccion'])
                ]);
                $transaccion->Origen_Transaccion_id = $origentransaccion->id;
            }
            $transaccion->url_comprobante = $data['url_comprobante'];
            // [GEN:END:store_fields]
            $transaccion->save();
            return redirect()->route('transaccion.show', $transaccion->id)
                ->with('success', 'Transaccion se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            // [GEN:END:catch_constraints]
            return $this->handleSaveError($e);
        }
    }

    public function show($id)
    {
        $transaccion = Transaccion::with([
            // [GEN:START:eager_load]
            'destino_transaccion',
            'origen_transaccion',
            'saldo_cliente',
            'cobros',
            // [GEN:END:eager_load]
        ])->findOrFail($id);

        // [GEN:START:fk_data]
        $destinotransaccionCount   = \App\Models\DestinoTransaccion::count();
        $destinotransaccionOptions = \App\Models\DestinoTransaccion::orderBy('id')->get(['id', 'id']);
        $origentransaccionCount   = \App\Models\OrigenTransaccion::count();
        $origentransaccionOptions = \App\Models\OrigenTransaccion::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('transaccion.show', [
            'transaccion' => $transaccion,
            // [GEN:START:fk_compact_array]
            'destinotransaccionCount'   => $destinotransaccionCount,
            'destinotransaccionOptions' => $destinotransaccionOptions,
            'origentransaccionCount'   => $origentransaccionCount,
            'origentransaccionOptions' => $origentransaccionOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $transaccion = Transaccion::findOrFail($id);

        // [GEN:START:fk_data]
        $destinotransaccionCount   = \App\Models\DestinoTransaccion::count();
        $destinotransaccionOptions = \App\Models\DestinoTransaccion::orderBy('id')->get(['id', 'id']);
        $origentransaccionCount   = \App\Models\OrigenTransaccion::count();
        $origentransaccionOptions = \App\Models\OrigenTransaccion::orderBy('id')->get(['id', 'id']);
        // [GEN:END:fk_data]

        return view('transaccion.edit', [
            'transaccion' => $transaccion,
            // [GEN:START:fk_compact_array]
            'destinotransaccionCount'   => $destinotransaccionCount,
            'destinotransaccionOptions' => $destinotransaccionOptions,
            'origentransaccionCount'   => $origentransaccionCount,
            'origentransaccionOptions' => $origentransaccionOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'monto' => 'sometimes|required|integer',
            'fecha' => 'sometimes|required|date',
            'Destino_Transaccion_id' => 'sometimes|required|integer|exists:destino_transaccion,id',
            'id-destino_transaccion' => 'sometimes|nullable|string',
            'Origen_Transaccion_id' => 'sometimes|required|integer|exists:origen_transaccion,id',
            'id-origen_transaccion' => 'sometimes|nullable|string',
            'url_comprobante' => 'sometimes|nullable|string',
            // [GEN:END:validation_rules]
        ]);

        try {
            $transaccion = Transaccion::findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('monto', $data)) {
            $transaccion->monto = $data['monto'];
        }
        if (array_key_exists('fecha', $data)) {
            $transaccion->fecha = $data['fecha'];
        }
        if (array_key_exists('Destino_Transaccion_id', $data) && $data['Destino_Transaccion_id'] !== '__nuevo__') {
            $transaccion->Destino_Transaccion_id = $data['Destino_Transaccion_id'];
        }

        if (array_key_exists('id-destino_transaccion', $data)) {
            $destinotransaccion = DestinoTransaccion::firstOrCreate([
                'id' => trim($data['id-destino_transaccion'])
            ]);
            $transaccion->Destino_Transaccion_id = $destinotransaccion->id;
        }
        if (array_key_exists('Origen_Transaccion_id', $data) && $data['Origen_Transaccion_id'] !== '__nuevo__') {
            $transaccion->Origen_Transaccion_id = $data['Origen_Transaccion_id'];
        }

        if (array_key_exists('id-origen_transaccion', $data)) {
            $origentransaccion = OrigenTransaccion::firstOrCreate([
                'id' => trim($data['id-origen_transaccion'])
            ]);
            $transaccion->Origen_Transaccion_id = $origentransaccion->id;
        }
        if (array_key_exists('url_comprobante', $data)) {
            $transaccion->url_comprobante = $data['url_comprobante'];
        }
            // [GEN:END:update_fields]
            $transaccion->save();
            return redirect()->back()
                ->with('success', 'Transaccion se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            // [GEN:END:catch_constraints]
            return $this->handleSaveError($e);
        }
    }

    public function destroy($id)
    {
        try {
            Transaccion::destroy($id);
            return redirect()->route('transaccion.index')
                ->with('success', 'Transaccion se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]
            // [GEN:END:catch_destroy]
            return $this->handleSaveError($e, 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
