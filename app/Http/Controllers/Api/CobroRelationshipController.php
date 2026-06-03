<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CobroRelationshipResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * AJAX endpoint for resolving Cobro relationships.
 *
 * POST /api/cobro/resolve-relationships
 *
 * Resolves Contrato, Unidad, Servicio, Deudor, and Acreedor
 * from cliente_id + tipo + optional propiedad_id.
 *
 * @see \App\Services\CobroRelationshipResolver
 */
class CobroRelationshipController extends Controller
{
    public function __construct(
        protected CobroRelationshipResolver $resolver
    ) {}

    /**
     * Resolve cobro relationships from cliente_id + tipo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|integer|exists:cliente,id',
            'tipo' => 'required|string',
            'propiedad_id' => 'nullable|integer|exists:propiedad,id',
        ]);

        $result = $this->resolver->resolve(
            (int) $validated['cliente_id'],
            $validated['tipo'],
            isset($validated['propiedad_id']) ? (int) $validated['propiedad_id'] : null
        );

        $statusCode = $result['status'] === 'error' ? 400 : 200;

        return response()->json($result, $statusCode);
    }
}