<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropiedadDireccionCheckController extends Controller
{
    /**
     * Check whether a direccion already exists in the propiedad table.
     *
     * Query params:
     *   - q: the direccion string to check (required)
     *
     * Returns JSON:
     *   - exists: bool
     *   - propiedad_id: int|null
     *   - has_contrato_vigente: bool
     *   - unidad_id: int|null
     *   - unidad_nombre: string|null
     *   - contrato_id: int|null
     */
    public function index(Request $request): JsonResponse
    {
        $direccion = $request->query('q');

        if (!$direccion || !is_string($direccion)) {
            return response()->json([
                'error' => 'Parameter "q" (direccion) is required.',
            ], 400);
        }

        // Case-insensitive match
        $propiedad = Propiedad::whereRaw('LOWER(direccion) = ?', [strtolower($direccion)])->first();

        if (!$propiedad) {
            return response()->json([
                'exists' => false,
                'propiedad_id' => null,
                'has_contrato_vigente' => false,
                'unidad_id' => null,
                'unidad_nombre' => null,
                'contrato_id' => null,
            ]);
        }

        // Check if any unidad on this propiedad has a contrato vigente
        $unidades = Unidad::where('Propiedad_id', $propiedad->id)->get();
        $hasContratoVigente = false;
        $unidadId = null;
        $unidadNombre = null;
        $contratoId = null;

        foreach ($unidades as $unidad) {
            $contratoVigente = $unidad->contratoVigente;
            if ($contratoVigente) {
                $hasContratoVigente = true;
                $unidadId = $unidad->id;
                $unidadNombre = $unidad->nombre;
                $contratoId = $contratoVigente->id;
                break;
            }
        }

        return response()->json([
            'exists' => true,
            'propiedad_id' => $propiedad->id,
            'has_contrato_vigente' => $hasContratoVigente,
            'unidad_id' => $unidadId,
            'unidad_nombre' => $unidadNombre,
            'contrato_id' => $contratoId,
        ]);
    }
}
