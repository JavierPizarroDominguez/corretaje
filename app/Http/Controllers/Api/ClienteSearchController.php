<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteSearchController extends Controller
{
    /**
     * Search clientes by nombre or rut.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        if (strlen($q) < 2) {
            return response()->json([], 422);
        }

        $clientes = Cliente::query()
            ->where(function ($query) use ($q) {
                $query->where('nombre', 'LIKE', "%{$q}%")
                    ->orWhere('rut', 'LIKE', "%{$q}%");
            })
            ->limit(20)
            ->get();

        $result = $clientes->map(function (Cliente $cliente) {
            return [
                'id' => $cliente->id,
                'texto' => $cliente->nombre,
                'tipo' => 'cliente',
            ];
        });

        return response()->json($result->all());
    }
}
