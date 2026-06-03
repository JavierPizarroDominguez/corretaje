<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Servicio;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Resolves Cobro relationships (Contrato, Unidad, Deudor, Acreedor, Servicio)
 * from cliente_id + tipo + optional propiedad_id.
 *
 * Used by:
 *   - CobroRelationshipController (AJAX endpoint)
 *   - CobroController::store() (auto-resolution on create)
 */
class CobroRelationshipResolver
{
    /**
     * Resolve all cobro relationships for the given parameters.
     *
     * @param int $clienteId
     * @param string $tipo
     * @param int|null $propiedadId
     * @return array
     */
    public function resolve(int $clienteId, string $tipo, ?int $propiedadId = null): array
    {
        $roleMap = config('cobro_roles.tipo_role_map.' . $tipo);

        if (!$roleMap) {
            return [
                'status' => 'error',
                'message' => "Tipo '$tipo' not found in role map.",
            ];
        }

        // Utility tipos: auto-link servicio, no contract required
        if (!empty($roleMap['requires_servicio'])) {
            return $this->resolveUtilityTipo($clienteId, $tipo, $propiedadId, $roleMap);
        }

        // Manual tipos (Reparación/Extra/Devolución): resolve from contract or fallback to propietario
        if ($roleMap['deudor_rol'] === null && $roleMap['acreedor_rol'] === null) {
            return $this->resolveManualTipo($clienteId, $propiedadId);
        }

        // Tipos that require a contract
        return $this->resolveContractTipo($clienteId, $tipo, $propiedadId, $roleMap);
    }

    /**
     * Resolve a utility tipo (Luz/Agua/Gas/Gastos comunes).
     * No contract required; links directly to a Servicio on the property.
     */
    protected function resolveUtilityTipo(int $clienteId, string $tipo, ?int $propiedadId, array $roleMap): array
    {
        if (!$propiedadId) {
            return [
                'status' => 'error',
                'message' => 'propiedad_id is required for utility tipo.',
            ];
        }

        $servicio = Servicio::where('Propiedad_id', $propiedadId)
            ->where('tipo', $roleMap['servicio_tipo'])
            ->first();

        $propiedad = Propiedad::find($propiedadId);

        $deudorClienteId = $this->resolveDeudorFromContrato($clienteId, $propiedadId);

        // If no contract found for this cliente on this property, use arrendatario from any active contract
        if (!$deudorClienteId) {
            $fallback = $this->getFallbackDeudorFromProperty($propiedadId);
            if ($fallback) {
                $deudorClienteId = $fallback->Cliente_id;
            }
        }

        $deudorNombre = $deudorClienteId ? (Cliente::find($deudorClienteId)?->nombre ?? '') : '';

        return [
            'status' => 'ok',
            'data' => [
                'contrato_id' => null,
                'unidad_id' => $propiedad?->unidad?->id,
                'propiedad_id' => $propiedadId,
                'propiedad_direccion' => $propiedad?->direccion,
                'servicio_id' => $servicio?->id,
                'deudor_cliente_id' => $deudorClienteId,
                'deudor_nombre' => $deudorNombre,
                'acreedor_cliente_id' => null,
                'acreedor_nombre' => null,
                'has_contract' => false,
                'multiple' => false,
            ],
            'options' => [],
        ];
    }

    /**
     * Resolve a manual tipo (Reparación/Extra/Devolución).
     * Uses contract if available, otherwise falls back to property owner.
     */
    protected function resolveManualTipo(int $clienteId, ?int $propiedadId): array
    {
        $activeContracts = $this->getActiveContractsForCliente($clienteId);

        if ($activeContracts->isEmpty()) {
            // No contracts — fallback to propietario
            if ($propiedadId) {
                $propiedad = Propiedad::with('propietarioCliente')->find($propiedadId);
                return [
                    'status' => 'ok',
                    'data' => [
                        'contrato_id' => null,
                        'unidad_id' => null,
                        'propiedad_id' => $propiedadId,
                        'propiedad_direccion' => $propiedad?->direccion,
                        'servicio_id' => null,
                        'deudor_cliente_id' => $propiedad?->propietario,
                        'deudor_nombre' => $propiedad?->propietarioCliente?->nombre,
                        'acreedor_cliente_id' => null,
                        'acreedor_nombre' => null,
                        'has_contract' => false,
                        'multiple' => false,
                    ],
                    'options' => [],
                ];
            }

            return [
                'status' => 'error',
                'message' => 'No active contracts and no propiedad_id provided.',
            ];
        }

        if ($activeContracts->count() === 1) {
            return $this->buildSingleContractResolution($activeContracts->first(), $propiedadId);
        }

        // Multiple contracts without propiedad filter — return ambiguous with options
        return $this->buildMultipleContractsResolution($activeContracts, $propiedadId);
    }

    /**
     * Resolve a contract-dependent tipo (Renta, Comisión, Garantía, Aseo Final).
     */
    protected function resolveContractTipo(int $clienteId, string $tipo, ?int $propiedadId, array $roleMap): array
    {
        $activeContracts = $this->getActiveContractsForCliente($clienteId);

        if ($activeContracts->isEmpty()) {
            return [
                'status' => 'error',
                'message' => 'No active contracts found for this cliente.',
            ];
        }

        // Filter by property if provided
        if ($propiedadId) {
            $activeContracts = $activeContracts->filter(function ($contrato) use ($propiedadId) {
                return $contrato->unidad?->Propiedad_id === $propiedadId;
            });

            if ($activeContracts->isEmpty()) {
                return [
                    'status' => 'error',
                    'message' => 'No active contracts found for this property.',
                ];
            }
        }

        if ($activeContracts->count() === 1) {
            return $this->buildSingleContractResolution($activeContracts->first(), $propiedadId, $roleMap);
        }

        // Multiple contracts without specific property filter
        return $this->buildMultipleContractsResolution($activeContracts, $propiedadId);
    }

    /**
     * Get active contracts for a cliente (fecha_termino is NULL or > now).
     */
    protected function getActiveContractsForCliente(int $clienteId)
    {
        $now = Carbon::now();

        return Contrato::whereHas('participante_contratos', function ($query) use ($clienteId) {
                $query->where('Cliente_id', $clienteId);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('fecha_termino')
                    ->orWhere('fecha_termino', '>', $now);
            })
            ->with(['unidad.propiedad', 'participante_contratos.cliente'])
            ->get();
    }

    /**
     * Build resolution for a single contract.
     */
    protected function buildSingleContractResolution(Contrato $contrato, ?int $propiedadId, ?array $roleMap = null): array
    {
        $propiedad = $contrato->unidad?->propiedad;
        $deudorClienteId = null;
        $deudorNombre = '';
        $acreedorClienteId = null;
        $acreedorNombre = '';

        if ($roleMap) {
            $deudorClienteId = $this->resolveParticipantByRole($contrato, $roleMap['deudor_rol']);
            $deudorNombre = $deudorClienteId ? (Cliente::find($deudorClienteId)?->nombre ?? '') : '';

            $acreedorClienteId = $this->resolveParticipantByRole($contrato, $roleMap['acreedor_rol']);
            $acreedorNombre = $acreedorClienteId ? (Cliente::find($acreedorClienteId)?->nombre ?? '') : '';
        }

        return [
            'status' => 'ok',
            'data' => [
                'contrato_id' => $contrato->id,
                'unidad_id' => $contrato->Unidad_id,
                'propiedad_id' => $propiedad?->id,
                'propiedad_direccion' => $propiedad?->direccion,
                'servicio_id' => null,
                'renta' => $contrato->renta,
                'deudor_cliente_id' => $deudorClienteId,
                'deudor_nombre' => $deudorNombre,
                'acreedor_cliente_id' => $acreedorClienteId,
                'acreedor_nombre' => $acreedorNombre,
                'has_contract' => true,
                'multiple' => false,
            ],
            'options' => [],
        ];
    }

    /**
     * Build resolution for multiple contracts — return options for disambiguation.
     */
    protected function buildMultipleContractsResolution($contratos, ?int $propiedadId): array
    {
        $options = $contratos->map(function ($contrato) {
            $propiedad = $contrato->unidad?->propiedad;
            return [
                'contrato_id' => $contrato->id,
                'unidad_id' => $contrato->Unidad_id,
                'propiedad_id' => $propiedad?->id,
                'direccion' => $propiedad?->direccion,
                'unidad_nombre' => $contrato->unidad?->nombre,
            ];
        })->values()->all();

        return [
            'status' => 'ok',
            'data' => [
                'contrato_id' => null,
                'unidad_id' => null,
                'propiedad_id' => $propiedadId,
                'propiedad_direccion' => null,
                'servicio_id' => null,
                'deudor_cliente_id' => null,
                'deudor_nombre' => null,
                'acreedor_cliente_id' => null,
                'acreedor_nombre' => null,
                'has_contract' => true,
                'multiple' => true,
            ],
            'options' => $options,
        ];
    }

    /**
     * Resolve a participant by role from a contrato's participante_contratos.
     */
    protected function resolveParticipantByRole(Contrato $contrato, ?string $rol): ?int
    {
        if (!$rol) {
            return null;
        }

        $participante = $contrato->participante_contratos
            ->firstWhere('rol', $rol);

        return $participante?->Cliente_id;
    }

    /**
     * Try to find deudor (Arrendatario) from any active contract on the property.
     */
    protected function resolveDeudorFromContrato(int $clienteId, ?int $propiedadId): ?int
    {
        if (!$propiedadId) {
            return null;
        }

        $now = Carbon::now();

        $contrato = Contrato::whereHas('unidad', function ($q) use ($propiedadId) {
                $q->where('Propiedad_id', $propiedadId);
            })
            ->whereHas('participante_contratos', function ($q) use ($clienteId) {
                $q->where('Cliente_id', $clienteId);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('fecha_termino')
                    ->orWhere('fecha_termino', '>', $now);
            })
            ->with('participante_contratos')
            ->first();

        if (!$contrato) {
            return null;
        }

        $arrendatario = $contrato->participante_contratos->firstWhere('rol', 'Arrendatario');
        return $arrendatario?->Cliente_id;
    }

    /**
     * Get fallback deudor (Arrendatario) from any active contract on the property.
     */
    protected function getFallbackDeudorFromProperty(int $propiedadId): ?ParticipanteContrato
    {
        $now = Carbon::now();

        return ParticipanteContrato::whereHas('contrato.unidad', function ($q) use ($propiedadId) {
                $q->where('Propiedad_id', $propiedadId);
            })
            ->where('rol', 'Arrendatario')
            ->whereHas('contrato', function ($q) use ($now) {
                $q->whereNull('fecha_termino')
                    ->orWhere('fecha_termino', '>', $now);
            })
            ->with('cliente')
            ->first();
    }
}