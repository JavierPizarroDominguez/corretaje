<?php

namespace App\Services;

use App\Http\Requests\CrearAdministracionRequest;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\Contrato;
use App\Models\ParticipanteCobro;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Servicio;
use App\Models\Unidad;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Creates a complete administracion contract: Cliente (arrendador + arrendatario),
 * Propiedad + Unidad, Contrato, ParticipanteContrato records, optional Cobros
 * with ParticipanteCobro pairs, and optional Servicios.
 *
 * All entity-creation steps run inside a single DB::transaction() so that a
 * failure in any step triggers a full rollback.
 */
class CrearAdministracionService
{
    public const ROL_ARRENDADOR = 'Arrendador';
    public const ROL_ARRENDATARIO = 'Arrendatario';
    public const ROL_CORREDOR = 'Corredor';
    public const ROL_DEUDOR = 'Deudor';
    public const ROL_ACREEDOR = 'Acreedor';

    /**
     * Create a complete administracion contract from a validated request.
     */
    public function crearAdministracion(CrearAdministracionRequest $request): Contrato
    {
        return DB::transaction(function () use ($request) {
            // Si propiedad_corredor está marcado, el arrendador es el corredor (id=1)
            if ($request->boolean('propiedad_corredor')) {
                $arrendador = Cliente::findOrFail(1);
            } else {
                $arrendador = $this->resolveOrCreateCliente(
                    $request->input('arrendador_nombre')
                );
            }

            $arrendatario = $this->resolveOrCreateCliente(
                $request->input('arrendatario_nombre')
            );

            if ($request->filled('propiedad_id')) {
                $propiedad = Propiedad::findOrFail($request->input('propiedad_id'));

                // Ownership check: if propiedad_corredor is false, propiedad must belong to arrendador
                if (!$request->boolean('propiedad_corredor')) {
                    if ((int) $propiedad->propietario !== $arrendador->id) {
                        throw ValidationException::withMessages([
                            'propiedad_id' => ['La propiedad seleccionada no pertenece al arrendador seleccionado.'],
                        ]);
                    }
                }

                // Block if propiedad already has a contrato vigente
                $propiedadUnidades = $propiedad->unidad()->get();
                foreach ($propiedadUnidades as $unidad) {
                    $contratoVigente = $unidad->contratoVigente;
                    if ($contratoVigente) {
                        throw ValidationException::withMessages([
                            'propiedad_id' => ['La propiedad ya tiene un contrato vigente.'],
                        ]);
                    }
                }
            } else {
                $propiedad = $this->resolveOrCreatePropiedad(
                    $request->input('propiedad_direccion'),
                    $arrendador->id
                );
            }

            $unidad = $this->resolveOrCreateUnidad(
                $request->input('unidad_nombre'),
                $propiedad->id
            );

            $contrato = $this->createContrato($request, $unidad->id);
            $corredorId = 1;

            $this->createParticipante($contrato, $arrendador->id, self::ROL_ARRENDADOR);
            $this->createParticipante($contrato, $arrendatario->id, self::ROL_ARRENDATARIO);
            $this->createParticipante($contrato, $corredorId, self::ROL_CORREDOR);

            $tieneAdministracion = ! $request->boolean('sin_administracion');

            if ($tieneAdministracion) {
                $this->createCobros($contrato, $propiedad->id, $unidad->id, $arrendador, $arrendatario, $corredorId, $request);
                $this->createServicios($propiedad, $request);
            }

            return $contrato;
        });
    }

    /**
     * Resolve an existing Cliente by nombre or create a new one (idéntico al SP legacy).
     */
    protected function resolveOrCreateCliente(string $nombre): Cliente
    {
        $cliente = Cliente::where('nombre', $nombre)->first();
        if ($cliente) {
            return $cliente;
        }

        return Cliente::create([
            'nombre' => $nombre,
            'fecha_creacion' => now(),
        ]);
    }

    /**
     * Resolve an existing Propiedad by direccion only (case-insensitive), or create a new one.
     *
     * If a propiedad with the same direccion exists but belongs to a different propietario,
     * throws a ValidationException — the frontend should have prevented this via direccion-check.
     */
    protected function resolveOrCreatePropiedad(string $direccion, int $propietarioId): Propiedad
    {
        $existing = Propiedad::whereRaw('LOWER(direccion) = ?', [strtolower($direccion)])->first();

        if ($existing) {
            if ((int) $existing->propietario !== $propietarioId) {
                throw ValidationException::withMessages([
                    'propiedad_direccion' => ['La dirección pertenece a otro propietario.'],
                ]);
            }

            return $existing;
        }

        return Propiedad::create([
            'direccion' => $direccion,
            'propietario' => $propietarioId,
        ]);
    }

    /**
     * Resolve an existing Unidad by nombre + Propiedad_id, or create a new one.
     * If nombre is empty, searches for a Unidad with a null nombre on the property.
     */
    protected function resolveOrCreateUnidad(?string $nombre, int $propiedadId): Unidad
    {
        if ($nombre) {
            return Unidad::firstOrCreate(
                ['nombre' => $nombre, 'Propiedad_id' => $propiedadId]
            );
        }

        return Unidad::firstOrCreate(
            ['nombre' => null, 'Propiedad_id' => $propiedadId]
        );
    }

    /**
     * Create the Contrato record.
     */
    protected function createContrato(CrearAdministracionRequest $request, int $unidadId): Contrato
    {
        $tieneAdministracion = ! $request->boolean('sin_administracion');

        $renta = (int) $request->input('renta', 0);
        $comisionMensual = $request->boolean('no_comision_mensual')
            ? null
            : ($request->filled('comision_mensual') ? (int) $request->input('comision_mensual') : null);

        // Egreso renta: use explicit value if provided, otherwise renta - comision_mensual
        $egresoRenta = $request->has('egreso_renta')
            ? (int) $request->input('egreso_renta')
            : max(0, $renta - $comisionMensual);

        $data = [
            'Unidad_id' => $unidadId,
            'administracion' => $tieneAdministracion,
            'renta' => $renta,
            'comision_mensual' => $comisionMensual,
            'dia_pago' => $request->input('dia_pago'),
            'comision_inicial' => $request->input('comision_inicial'),
            'garantia' => $request->boolean('no_garantia') ? null : $request->input('garantia'),
            'fecha_firma' => $request->input('fecha_firma'),
            'fecha_inicio' => $request->input('fecha_inicio'),
            'fecha_termino' => $request->input('fecha_termino'),
        ];

        // Handle file upload
        if ($request->hasFile('contrato_file')) {
            $path = $request->file('contrato_file')->store('contratos', 'public');
            $data['url_pdf'] = Storage::disk('public')->url($path);
        }

        return Contrato::create(array_filter($data, fn ($v) => $v !== null));
    }

    /**
     * Create a single ParticipanteContrato record.
     */
    protected function createParticipante(Contrato $contrato, int $clienteId, string $rol): void
    {
        ParticipanteContrato::create([
            'Contrato_id' => $contrato->id,
            'Cliente_id' => $clienteId,
            'rol' => $rol,
        ]);
    }

    /**
     * Create Cobro records and their ParticipanteCobro pairs when administracion is enabled.
     *
     * Always creates:
     *   - Ingreso Renta Arrendatario (deudor=Arrendatario, acreedor=Corredor)
     *
     * Skips Egreso when arrendador is the corredor (id=1):
     *   - Egreso Renta Arrendador (deudor=Corredor, acreedor=Arrendador)
     *
     * Creates when comision_inicial is provided AND arrendador <> corredor AND no_comision_inicial is false:
     *   - Comision inicial arrendador (deudor=Arrendador, acreedor=Corredor) — if cobrar_arrendador is true
     *   - Comision inicial arrendatario (deudor=Arrendatario, acreedor=Corredor) — if cobrar_arrendatario is true
     *
     * Skips garantia cobros when no_garantia is true.
     * Creates when garantia is provided and no_garantia is false:
     *   - Ingreso Garantía Arrendatario (deudor=Arrendatario, acreedor=Corredor)
     *   - Egreso Garantía Arrendador (deudor=Corredor, acreedor=Arrendador) — skipped when arrendador=corredor
     */
    protected function createCobros(
        Contrato $contrato,
        int $propiedadId,
        int $unidadId,
        Cliente $arrendador,
        Cliente $arrendatario,
        int $corredorId,
        CrearAdministracionRequest $request
    ): void {
        $arrendadorId = $arrendador->id;
        $arrendatarioId = $arrendatario->id;
        $renta = (int) $request->input('renta', 0);

        // Always: Ingreso Renta Arrendatario (acreedor = corredor)
        $this->createCobroPair(
            $contrato,
            $propiedadId,
            $unidadId,
            $arrendatarioId,
            $corredorId,
            'Ingreso Renta Arrendatario',
            self::ROL_DEUDOR,
            self::ROL_ACREEDOR,
            $renta
        );

        // Egreso only when arrendador is NOT the corredor
        if ($arrendadorId !== $corredorId) {
            $egresoRenta = $request->has('egreso_renta')
                ? (int) $request->input('egreso_renta')
                : max(0, $renta - (int) $request->input('comision_mensual', 0));

            $this->createCobroPair(
                $contrato,
                $propiedadId,
                $unidadId,
                $corredorId,
                $arrendadorId,
                'Egreso Renta Arrendador',
                self::ROL_DEUDOR,
                self::ROL_ACREEDOR,
                $egresoRenta
            );
        }

        // Comision inicial pairs (SP: only when arrendador_id <> 1 AND no_comision_inicial is false)
        if (
            $request->filled('comision_inicial')
            && $arrendadorId !== $corredorId
            && ! $request->boolean('no_comision_inicial')
        ) {
            if ($request->boolean('cobrar_arrendador', true)) {
                $this->createCobroPair(
                    $contrato,
                    $propiedadId,
                    $unidadId,
                    $arrendadorId,
                    $corredorId,
                    'Comision inicial arrendador',
                    self::ROL_DEUDOR,
                    self::ROL_ACREEDOR,
                    $request->input('comision_inicial')
                );
            }

            if ($request->boolean('cobrar_arrendatario', true)) {
                $this->createCobroPair(
                    $contrato,
                    $propiedadId,
                    $unidadId,
                    $arrendatarioId,
                    $corredorId,
                    'Comision inicial arrendatario',
                    self::ROL_DEUDOR,
                    self::ROL_ACREEDOR,
                    $request->input('comision_inicial')
                );
            }
        }

        // Garantia pairs (skipped when no_garantia is true)
        if ($request->filled('garantia') && ! $request->boolean('no_garantia')) {
            // Ingreso Garantía (acreedor = corredor)
            $this->createCobroPair(
                $contrato,
                $propiedadId,
                $unidadId,
                $arrendatarioId,
                $corredorId,
                'Ingreso Garantía Arrendatario',
                self::ROL_DEUDOR,
                self::ROL_ACREEDOR,
                $request->input('garantia')
            );

            // Egreso Garantia skipped when arrendador = corredor
            if ($arrendadorId !== $corredorId) {
                $this->createCobroPair(
                    $contrato,
                    $propiedadId,
                    $unidadId,
                    $corredorId,
                    $arrendadorId,
                    'Egreso Garantía Arrendador',
                    self::ROL_DEUDOR,
                    self::ROL_ACREEDOR,
                    $request->input('garantia')
                );
            }
        }
    }

    /**
     * Create one Cobro with two ParticipanteCobro records.
     */
    protected function createCobroPair(
        Contrato $contrato,
        int $propiedadId,
        int $unidadId,
        int $deudorId,
        int $acreedorId,
        string $tipo,
        string $deudorRol,
        string $acreedorRol,
        ?int $monto
    ): void {
        $cobro = Cobro::create([
            'Contrato_id' => $contrato->id,
            'tipo' => $tipo,
            'monto' => $monto,
            'estado' => 'Pendiente',
            'fecha_cobro' => now(),
            'Propiedad_id' => $propiedadId,
            'Unidad_id' => $unidadId,
        ]);

        ParticipanteCobro::create([
            'Cobro_id' => $cobro->id,
            'Cliente_id' => $deudorId,
            'rol' => $deudorRol,
            'monto' => $monto,
        ]);

        ParticipanteCobro::create([
            'Cobro_id' => $cobro->id,
            'Cliente_id' => $acreedorId,
            'rol' => $acreedorRol,
            'monto' => $monto,
        ]);
    }

    /**
     * Create Servicio records from the servicios array.
     * Each item has: tipo, dia, monto (optional).
     */
    protected function createServicios(Propiedad $propiedad, CrearAdministracionRequest $request): void
    {
        $servicios = $request->input('servicios');

        if (empty($servicios) || ! is_array($servicios)) {
            return;
        }

        foreach ($servicios as $servicioData) {
            $tipo = $servicioData['tipo'] ?? null;
            if (! $tipo || $tipo === 'Sin servicios') {
                continue;
            }

            Servicio::firstOrCreate(
                ['tipo' => $tipo, 'Propiedad_id' => $propiedad->id],
                [
                    'dia_pago' => $servicioData['dia'] ?? null,
                    'monto_fijo' => $servicioData['monto'] ?? null,
                    'estado' => 'Activo',
                ]
            );
        }
    }
}
