<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;

class PropiedadContratoVigenteController extends Controller
{
    /**
     * Check whether a propiedad (or its unidad) has an active (vigente) contract.
     *
     * Returns JSON:
     *   - has_contrato_vigente: bool
     *   - unidad_id: int|null
     *   - unidad_nombre: string|null
     *   - contrato_id: int|null
     */
    public function show(int $id): JsonResponse
    {
        $propiedad = Propiedad::find($id);

        if (!$propiedad) {
            return response()->json([
                'has_contrato_vigente' => false,
                'unidad_id' => null,
                'unidad_nombre' => null,
                'contrato_id' => null,
            ], 404);
        }

        // A propiedad has a contrato vigente if any of its unidades has one.
        // Query the unidad table directly (one-to-many in practice, despite hasOne on model).
        $unidades = Unidad::where('Propiedad_id', $propiedad->id)->get();

        foreach ($unidades as $unidad) {
            $contratoVigente = $unidad->contratoVigente;
            if ($contratoVigente) {
                return response()->json([
                    'has_contrato_vigente' => true,
                    'unidad_id' => $unidad->id,
                    'unidad_nombre' => $unidad->nombre,
                    'contrato_id' => $contratoVigente->id,
                ]);
            }
        }

        return response()->json([
            'has_contrato_vigente' => false,
            'unidad_id' => null,
            'unidad_nombre' => null,
            'contrato_id' => null,
        ]);
    }
}
