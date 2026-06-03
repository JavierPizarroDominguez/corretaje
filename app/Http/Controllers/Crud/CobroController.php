<?php

namespace App\Http\Controllers\Crud;

use App\Http\Controllers\Controller;
use App\Models\Cobro;
use App\Services\CobroRelationshipResolver;
use Illuminate\Http\Request;
use App\Models\Contrato;
use App\Models\Servicio;
use App\Models\Propiedad;
use App\Models\Unidad;
use App\Models\Cliente;

class CobroController extends Controller
{
    public function index()
    {
        $cobros = Cobro::query()->with(['deudor.cliente', 'acreedor.cliente'])->paginate(10);
        return view('cobro.index', compact('cobros'));
    }

    public function create()
    {
        // [GEN:START:fk_data]
        $contratoCount   = \App\Models\Contrato::count();
        $contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id']);
        $servicioCount   = \App\Models\Servicio::count();
        $servicioOptions = \App\Models\Servicio::orderBy('id')->get(['id', 'id']);
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        $unidadCount   = \App\Models\Unidad::count();
        $unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre']);
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('cobro.create', [
            // [GEN:START:fk_compact_array]
            'contratoCount'   => $contratoCount,
            'contratoOptions' => $contratoOptions,
            'servicioCount'   => $servicioCount,
            'servicioOptions' => $servicioOptions,
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            'unidadCount'   => $unidadCount,
            'unidadOptions' => $unidadOptions,
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // CUSTOM validation rule for auto_resolve_participantes (utility tipos: Luz, Agua, Gas, Gastos comunes)
            'cliente_id' => 'nullable|integer|exists:cliente,id',
            // [GEN:START:validation_rules]
            'fecha_cobro' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:Pagado,Incompleto,Pendiente,Vencido,Anulado',
            'tipo' => 'sometimes|required|in:Ingreso Renta Arrendatario,Egreso Renta Arrendador,Comision inicial arrendador,Comision inicial arrendatario,Comision Mensual,Ingreso Garantía Arrendatario,Egreso Garantía Arrendador,Devolución Garantía Arrendatario,Aseo Final,Luz,Agua,Gas,Gastos comunes,Reparación,Extra,Devolución',
            'monto' => 'sometimes|nullable|integer',
            'detalle' => 'sometimes|nullable|string',
            'Contrato_id' => 'sometimes|nullable|integer|exists:contrato,id',
            'id-contrato' => 'sometimes|nullable|string',
            'Servicio_id' => 'sometimes|nullable|integer|exists:servicio,id',
            'id-servicio' => 'sometimes|nullable|string',
            'Propiedad_id' => 'sometimes|nullable|integer|exists:propiedad,id',
            'direccion-propiedad' => 'sometimes|nullable|string',
            'Unidad_id' => 'sometimes|nullable|integer|exists:unidad,id',
            'nombre-unidad' => 'sometimes|nullable|string',
            'nombre-deudor' => 'sometimes|nullable|string',
            'deudor_Cliente_id' => 'required_with:nombre-deudor|integer|exists:cliente,id',
            'nombre-acreedor' => 'sometimes|nullable|string',
            'acreedor_Cliente_id' => 'required_with:nombre-acreedor|integer|exists:cliente,id',
            // [GEN:END:validation_rules]
        ]);

        try {
            $cobro = new Cobro();
            // [GEN:START:store_fields]
            $cobro->fecha_cobro = $data['fecha_cobro'];
            $cobro->estado = $data['estado'];
            $cobro->tipo = $data['tipo'];
            $cobro->monto = $data['monto'];
            $cobro->detalle = $data['detalle'];
            if (isset($data['Contrato_id']) && $data['Contrato_id'] !== '__nuevo__') {
                $cobro->Contrato_id = $data['Contrato_id'];
            }

            if (!empty($data['Contrato_id'])) {
                $contrato = Contrato::findOrFail($data['Contrato_id']);
                $cobro->Contrato_id = $contrato->id;
            }
            if (isset($data['Servicio_id']) && $data['Servicio_id'] !== '__nuevo__') {
                $cobro->Servicio_id = $data['Servicio_id'];
            }

            if (!empty($data['Servicio_id'])) {
                $servicio = Servicio::findOrFail($data['Servicio_id']);
                $cobro->Servicio_id = $servicio->id;
            }
            if (isset($data['Propiedad_id']) && $data['Propiedad_id'] !== '__nuevo__') {
                $cobro->Propiedad_id = $data['Propiedad_id'];
            }

            if (!empty($data['Propiedad_id'])) {
                $propiedad = Propiedad::findOrFail($data['Propiedad_id']);
                $cobro->Propiedad_id = $propiedad->id;
            }
            if (isset($data['Unidad_id']) && $data['Unidad_id'] !== '__nuevo__') {
                $cobro->Unidad_id = $data['Unidad_id'];
            }

            if (!empty($data['Unidad_id'])) {
                $unidad = Unidad::findOrFail($data['Unidad_id']);
                $cobro->Unidad_id = $unidad->id;
            }
            // [GEN:END:store_fields]
            $cobro->save();

            // [GEN:START:scoped_store_fields]
            // Crear deudor
            $relatedCliente = null;
            if (!empty($data['deudor_Cliente_id'])) {
                $relatedCliente = \App\Models\Cliente::findOrFail($data['deudor_Cliente_id']);
            }
            if (!empty($relatedCliente)) {
                $pivotParticipanteCobro = new \App\Models\ParticipanteCobro();
                $pivotParticipanteCobro->Cobro_id = $cobro->id;
                $pivotParticipanteCobro->Cliente_id = $relatedCliente->id;
                $pivotParticipanteCobro->rol = 'Deudor';
                $pivotParticipanteCobro->save();
            }


            // Crear acreedor
            $relatedCliente = null;
            if (!empty($data['acreedor_Cliente_id'])) {
                $relatedCliente = \App\Models\Cliente::findOrFail($data['acreedor_Cliente_id']);
            }
            if (!empty($relatedCliente)) {
                $pivotParticipanteCobro = new \App\Models\ParticipanteCobro();
                $pivotParticipanteCobro->Cobro_id = $cobro->id;
                $pivotParticipanteCobro->Cliente_id = $relatedCliente->id;
                $pivotParticipanteCobro->rol = 'Acreedor';
                $pivotParticipanteCobro->save();
            }
            // [GEN:END:scoped_store_fields]

            // CUSTOM: delete any ParticipanteCobro records the GEN block may have created
            // when cliente_id is present, since auto_resolve will create the correct ones
            // [CUSTOM:START:auto_resolve_participantes]
            if (!empty($data['cliente_id']) && empty($data['Contrato_id'])) {
                \App\Models\ParticipanteCobro::where('Cobro_id', $cobro->id)->delete();
            }
            // [CUSTOM:END:auto_resolve_participantes]

            // [CUSTOM:START:auto_resolve_participantes]
            // AUTO-RESOLVE: when cliente_id is provided but no Contrato_id explicitly set,
            // resolve relationships using CobroRelationshipResolver and create ParticipanteCobro records.
            if (!empty($data['cliente_id']) && empty($data['Contrato_id'])) {
                $resolved = app(CobroRelationshipResolver::class)->resolve(
                    (int) $data['cliente_id'],
                    $data['tipo'],
                    isset($data['Propiedad_id']) ? (int) $data['Propiedad_id'] : null
                );

                if ($resolved['status'] === 'ok' && !empty($resolved['data'])) {
                    $cobro->Contrato_id = $resolved['data']['contrato_id'] ?? null;
                    $cobro->Unidad_id = $resolved['data']['unidad_id'] ?? null;
                    $cobro->Servicio_id = $resolved['data']['servicio_id'] ?? null;
                    $cobro->save();

                    // Deudor
                    if (!empty($resolved['data']['deudor_cliente_id'])) {
                        \App\Models\ParticipanteCobro::create([
                            'Cobro_id' => $cobro->id,
                            'Cliente_id' => $resolved['data']['deudor_cliente_id'],
                            'rol' => 'Deudor',
                        ]);
                    }

                    // Acreedor (null for utility tipos)
                    if (!empty($resolved['data']['acreedor_cliente_id'])) {
                        \App\Models\ParticipanteCobro::create([
                            'Cobro_id' => $cobro->id,
                            'Cliente_id' => $resolved['data']['acreedor_cliente_id'],
                            'rol' => 'Acreedor',
                        ]);
                    }
                }
            }
            // [CUSTOM:END:auto_resolve_participantes]

            return redirect()->route('cobro.show', $cobro->id)
                ->with('success', 'Cobro se ha creado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_cobro_monto')) {
                return back()->with('error', 'Cobro monto.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $cobro = Cobro::with(['deudor.cliente', 'acreedor.cliente'])->findOrFail($id);

        // [GEN:START:fk_data]
        $contratoCount   = \App\Models\Contrato::count();
        $contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id']);
        $servicioCount   = \App\Models\Servicio::count();
        $servicioOptions = \App\Models\Servicio::orderBy('id')->get(['id', 'id']);
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        $unidadCount   = \App\Models\Unidad::count();
        $unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre']);
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('cobro.show', [
            'cobro' => $cobro,
            // [GEN:START:fk_compact_array]
            'contratoCount'   => $contratoCount,
            'contratoOptions' => $contratoOptions,
            'servicioCount'   => $servicioCount,
            'servicioOptions' => $servicioOptions,
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            'unidadCount'   => $unidadCount,
            'unidadOptions' => $unidadOptions,
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function edit($id)
    {
        $cobro = Cobro::findOrFail($id);

        // [GEN:START:fk_data]
        $contratoCount   = \App\Models\Contrato::count();
        $contratoOptions = \App\Models\Contrato::orderBy('id')->get(['id', 'id']);
        $servicioCount   = \App\Models\Servicio::count();
        $servicioOptions = \App\Models\Servicio::orderBy('id')->get(['id', 'id']);
        $propiedadCount   = \App\Models\Propiedad::count();
        $propiedadOptions = \App\Models\Propiedad::orderBy('direccion')->get(['id', 'direccion']);
        $unidadCount   = \App\Models\Unidad::count();
        $unidadOptions = \App\Models\Unidad::orderBy('nombre')->get(['id', 'nombre']);
        $clienteCount   = \App\Models\Cliente::count();
        $clienteOptions = \App\Models\Cliente::orderBy('nombre')->get(['id', 'nombre']);
        // [GEN:END:fk_data]

        return view('cobro.edit', [
            'cobro' => $cobro,
            // [GEN:START:fk_compact_array]
            'contratoCount'   => $contratoCount,
            'contratoOptions' => $contratoOptions,
            'servicioCount'   => $servicioCount,
            'servicioOptions' => $servicioOptions,
            'propiedadCount'   => $propiedadCount,
            'propiedadOptions' => $propiedadOptions,
            'unidadCount'   => $unidadCount,
            'unidadOptions' => $unidadOptions,
            'clienteCount'   => $clienteCount,
            'clienteOptions' => $clienteOptions,
            // [GEN:END:fk_compact_array]
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            // [GEN:START:validation_rules]
            'fecha_cobro' => 'sometimes|required|date',
            'estado' => 'sometimes|required|in:Pagado,Incompleto,Pendiente,Vencido,Anulado',
            'tipo' => 'sometimes|required|in:Ingreso Renta Arrendatario,Egreso Renta Arrendador,Comision inicial arrendador,Comision inicial arrendatario,Comision Mensual,Ingreso Garantía Arrendatario,Egreso Garantía Arrendador,Devolución Garantía Arrendatario,Aseo Final,Luz,Agua,Gas,Gastos comunes,Reparación,Extra,Devolución',
            'monto' => 'sometimes|nullable|integer',
            'detalle' => 'sometimes|nullable|string',
            'Contrato_id' => 'sometimes|nullable|integer|exists:contrato,id',
            'id-contrato' => 'sometimes|nullable|string',
            'Servicio_id' => 'sometimes|nullable|integer|exists:servicio,id',
            'id-servicio' => 'sometimes|nullable|string',
            'Propiedad_id' => 'sometimes|nullable|integer|exists:propiedad,id',
            'direccion-propiedad' => 'sometimes|nullable|string',
            'Unidad_id' => 'sometimes|nullable|integer|exists:unidad,id',
            'nombre-unidad' => 'sometimes|nullable|string',
            'nombre-deudor' => 'sometimes|nullable|string',
            'deudor_Cliente_id' => 'required_with:nombre-deudor|integer|exists:cliente,id',
            'nombre-acreedor' => 'sometimes|nullable|string',
            'acreedor_Cliente_id' => 'required_with:nombre-acreedor|integer|exists:cliente,id',
            // [GEN:END:validation_rules]
        ]);

        try {
        $cobro = Cobro::with(['deudor.cliente', 'acreedor.cliente'])->findOrFail($id);
            // [GEN:START:update_fields]
        if (array_key_exists('fecha_cobro', $data)) {
            $cobro->fecha_cobro = $data['fecha_cobro'];
        }
        if (array_key_exists('estado', $data)) {
            $cobro->estado = $data['estado'];
        }
        if (array_key_exists('tipo', $data)) {
            $cobro->tipo = $data['tipo'];
        }
        if (array_key_exists('monto', $data)) {
            $cobro->monto = $data['monto'];
        }
        if (array_key_exists('detalle', $data)) {
            $cobro->detalle = $data['detalle'];
        }
        if (array_key_exists('Contrato_id', $data) && $data['Contrato_id'] !== '__nuevo__') {
            $cobro->Contrato_id = $data['Contrato_id'];
        }

        if (array_key_exists('id-contrato', $data)) {
            $contrato = Contrato::firstOrCreate([
                'id' => trim($data['id-contrato'])
            ]);
            $cobro->Contrato_id = $contrato->id;
        }
        if (array_key_exists('Servicio_id', $data) && $data['Servicio_id'] !== '__nuevo__') {
            $cobro->Servicio_id = $data['Servicio_id'];
        }

        if (array_key_exists('id-servicio', $data)) {
            $servicio = Servicio::firstOrCreate([
                'id' => trim($data['id-servicio'])
            ]);
            $cobro->Servicio_id = $servicio->id;
        }
        if (array_key_exists('Propiedad_id', $data) && $data['Propiedad_id'] !== '__nuevo__') {
            $cobro->Propiedad_id = $data['Propiedad_id'];
        }

        if (array_key_exists('direccion-propiedad', $data)) {
            $propiedad = Propiedad::firstOrCreate([
                'direccion' => trim($data['direccion-propiedad'])
            ]);
            $cobro->Propiedad_id = $propiedad->id;
        }
        if (array_key_exists('Unidad_id', $data) && $data['Unidad_id'] !== '__nuevo__') {
            $cobro->Unidad_id = $data['Unidad_id'];
        }

        if (array_key_exists('nombre-unidad', $data)) {
            $unidad = Unidad::firstOrCreate([
                'nombre' => trim($data['nombre-unidad'])
            ]);
            $cobro->Unidad_id = $unidad->id;
        }
            // [GEN:END:update_fields]

            // [GEN:START:scoped_update_fields]
            // Actualizar deudor
            $relatedCliente = null;
            if (!empty($data['deudor_Cliente_id'])) {
                $relatedCliente = \App\Models\Cliente::findOrFail($data['deudor_Cliente_id']);
            }
            if (!empty($relatedCliente)) {
                $pivotParticipanteCobro = \App\Models\ParticipanteCobro::firstOrNew([
                    'Cobro_id' => $cobro->id,
                    'rol' => 'Deudor',
                ]);
                $pivotParticipanteCobro->Cliente_id = $relatedCliente->id;
                $pivotParticipanteCobro->save();
            }


            // Actualizar acreedor
            $relatedCliente = null;
            if (!empty($data['acreedor_Cliente_id'])) {
                $relatedCliente = \App\Models\Cliente::findOrFail($data['acreedor_Cliente_id']);
            }
            if (!empty($relatedCliente)) {
                $pivotParticipanteCobro = \App\Models\ParticipanteCobro::firstOrNew([
                    'Cobro_id' => $cobro->id,
                    'rol' => 'Acreedor',
                ]);
                $pivotParticipanteCobro->Cliente_id = $relatedCliente->id;
                $pivotParticipanteCobro->save();
            }

            // [GEN:END:scoped_update_fields]
            $cobro->save();
            return redirect()->back()
                ->with('success', 'Cobro se ha actualizado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_constraints]
            if (str_contains($e->getMessage(), 'chk_cobro_monto')) {
                return back()->with('error', 'Cobro monto.');
            }
            // [GEN:END:catch_constraints]
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Cobro::destroy($id);
            return redirect()->route('cobro.index')
                ->with('success', 'Cobro se ha eliminado correctamente.');
        } catch (\Exception $e) {
            // [GEN:START:catch_destroy]
            if (str_contains($e->getMessage(), 'chk_cobro_monto')) {
                return back()->with('error', 'Cobro monto.');
            }
            // [GEN:END:catch_destroy]
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el registro está siendo usado por otros datos.');
        }
    }
}
