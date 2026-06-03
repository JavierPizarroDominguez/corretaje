<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use Illuminate\Http\JsonResponse;

class PropiedadPorArrendadorController extends Controller
{
    /**
     * List propiedades owned by a given arrendador (propietario).
     */
    public function index(int $id): JsonResponse
    {
        $propiedades = Propiedad::query()
            ->where('propietario', $id)
            ->with('unidad')
            ->get();

        $result = $propiedades->map(function (Propiedad $propiedad) {
            return [
                'id' => $propiedad->id,
                'direccion' => $propiedad->direccion,
                'unidad_id' => $propiedad->unidad?->id,
            ];
        });

        return response()->json($result->all());
    }
}
