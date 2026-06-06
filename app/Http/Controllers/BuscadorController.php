<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unidad;
use App\Models\Cliente;
use App\Models\Nacionalidad;
use App\Models\Ciudad;


class BuscadorController extends Controller
{
    public function index(Request $request)
    {
        $q         = $request->input('q', '');
        $resultados = [];

        if (empty($q)) {
            return response()->json(['data' => []]);
        }

        if ($request->has('unidad')) {
            $resultados_unidad = \App\Models\Unidad::query()
                ->where('nombre', 'LIKE', "%{$q}%")
                ->limit(10)
                ->get();
            foreach ($resultados_unidad as $item) {
                $resultados[] = [
                    'id'    => $item->id,
                    'tipo'  => 'unidad',
                    'texto' => $this->getSearchText($item, ["nombre"]),
                    'url'   => '/unidad/' . $item->id,
                ];
            }
        }

        if ($request->has('cliente')) {
            $resultados_cliente = \App\Models\Cliente::query()
                ->where('nombre', 'LIKE', "%{$q}%")
                ->limit(10)
                ->get();
            foreach ($resultados_cliente as $item) {
                $resultados[] = [
                    'id'    => $item->id,
                    'tipo'  => 'cliente',
                    'texto' => $this->getSearchText($item, ["nombre"]),
                    'url'   => '/cliente/ficha/' . $item->id,
                ];
            }
        }

        if ($request->has('nacionalidad')) {
            $resultados_nacionalidad = \App\Models\Nacionalidad::query()
                ->where('nombre', 'LIKE', "%{$q}%")
                ->limit(10)
                ->get();
            foreach ($resultados_nacionalidad as $item) {
                $resultados[] = [
                    'id'    => $item->id,
                    'tipo'  => 'nacionalidad',
                    'texto' => $this->getSearchText($item, ["nombre"]),
                    'url'   => '/nacionalidad/' . $item->id,
                ];
            }
        }

        if ($request->has('ciudad')) {
            $resultados_ciudad = \App\Models\Ciudad::query()
                ->where('nombre', 'LIKE', "%{$q}%")
                ->limit(10)
                ->get();
            foreach ($resultados_ciudad as $item) {
                $resultados[] = [
                    'id'    => $item->id,
                    'tipo'  => 'ciudad',
                    'texto' => $this->getSearchText($item, ["nombre"]),
                    'url'   => '/ciudad/' . $item->id,
                ];
            }
        }

        return response()->json(['data' => $resultados]);
    }

    /**
     * Resuelve el texto a mostrar siguiendo una ruta de relaciones Eloquent.
     * Ejemplo: $item, 'participante_contratos.cliente', 'nombre'
     * → $item->participante_contratos->first()->cliente->nombre
     */
    private function resolveDisplay($model, string $path, string $field): ?string
    {
        $parts = explode('.', $path);
        array_pop($parts); // quitar el campo final, ya está en $field

        $current = $model;
        foreach ($parts as $relation) {
            if ($current === null) return null;

            // hasMany/belongsToMany: tomar el primero
            if (is_iterable($current->$relation) && !is_string($current->$relation)) {
                $current = collect($current->$relation)->first();
            } else {
                $current = $current->$relation;
            }
        }

        return $current?->$field;
    }

    /**
     * Construye el closure para whereHas encadenado.
     * Ejemplo: ['participante_contratos', 'cliente'], 'nombre', $q
     * → whereHas('participante_contratos', fn($q) => $q->whereHas('cliente', fn($q2) => $q2->where('nombre', 'LIKE', "%{$q}%")))
     */
    private function buildWhereHas(array $parts, string $field, string $q): array
    {
        // Retorna [$relation, $callback] para usar en whereHas($relation, $callback)
        $innerField = $field;
        $innerQ     = $q;

        $buildNested = function(array $remaining) use (&$buildNested, $innerField, $innerQ) {
            if (count($remaining) === 1) {
                return [$remaining[0], fn($query) => $query->where($innerField, 'LIKE', "%{$innerQ}%")];
            }
            $head = array_shift($remaining);
            [$nextRelation, $nextCallback] = $buildNested($remaining);
            return [$head, fn($query) => $query->whereHas($nextRelation, $nextCallback)];
        };

        return $buildNested($parts);
    }
    /**
     * Obtiene el texto descriptivo de un registro navegando el search_path.
     * Retorna el primer valor no vacío encontrado entre los paths.
     */
    private function getSearchText($item, array $paths): string
    {
        foreach ($paths as $path) {
            $segments = explode('.', $path);
            $value    = $item;
            foreach ($segments as $segment) {
                if (is_null($value)) break;
                $value = $value->$segment ?? null;
            }
            if (!empty($value) && is_scalar($value)) {
                return (string) $value;
            }
        }
        return (string) $item->id;
    }
}
