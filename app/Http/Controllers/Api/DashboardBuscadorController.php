<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Propiedad;
use App\Models\Cliente;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardBuscadorController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 1) {
            return response()->json(['data' => []]);
        }

        $resultados = [];

        // Priority 1: Starts with (LIKE 'q%')
        $propiedadesStarts = Propiedad::where('direccion', 'LIKE', "{$q}%")
            ->limit(5)
            ->get();
        foreach ($propiedadesStarts as $item) {
            $resultados[] = [
                'id' => $item->id,
                'texto' => $item->direccion,
                'tipo' => 'propiedad',
                'url' => '/propiedad/ficha/' . $item->id,
                'prioridad' => 1,
            ];
        }

        $clientesStarts = Cliente::where('nombre', 'LIKE', "{$q}%")
            ->limit(5)
            ->get();
        foreach ($clientesStarts as $item) {
            $resultados[] = [
                'id' => $item->id,
                'texto' => $item->nombre,
                'tipo' => 'cliente',
                'url' => '/cliente/ficha/' . $item->id,
                'prioridad' => 1,
            ];
        }

        // Priority 2: Contains (LIKE '%q%')
        $propiedadesContains = Propiedad::where('direccion', 'LIKE', "%{$q}%")
            ->limit(5)
            ->get();
        foreach ($propiedadesContains as $item) {
            // Skip if already added via starts with
            if (!collect($resultados)->contains('id', $item->id)) {
                $resultados[] = [
                    'id' => $item->id,
                    'texto' => $item->direccion,
                    'tipo' => 'propiedad',
                    'url' => '/propiedad/ficha/' . $item->id,
                    'prioridad' => 2,
                ];
            }
        }

        $clientesContains = Cliente::where('nombre', 'LIKE', "%{$q}%")
            ->limit(5)
            ->get();
        foreach ($clientesContains as $item) {
            // Skip if already added via starts with
            if (!collect($resultados)->contains('id', $item->id)) {
                $resultados[] = [
                    'id' => $item->id,
                    'texto' => $item->nombre,
                    'tipo' => 'cliente',
                    'url' => '/cliente/ficha/' . $item->id,
                    'prioridad' => 2,
                ];
            }
        }

        // Sort by prioridad (1 before 2), then by texto
        $resultados = collect($resultados)
            ->sortBy(['prioridad', 'texto'])
            ->take(10)
            ->values()
            ->all();

        // Remove prioridad from final output
        $resultados = array_map(function ($item) {
            unset($item['prioridad']);
            return $item;
        }, $resultados);

        return response()->json(['data' => $resultados]);
    }
}