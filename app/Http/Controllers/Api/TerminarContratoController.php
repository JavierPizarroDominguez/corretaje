<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TerminarContratoRequest;
use App\Models\Contrato;
use App\Services\TerminarContratoService;
use Illuminate\Http\JsonResponse;

class TerminarContratoController extends Controller
{
    public function __invoke(
        TerminarContratoRequest $request,
        Contrato $contrato,
        TerminarContratoService $service
    ): JsonResponse {
        return response()->json($service->terminar($contrato, $request->discounts()));
    }
}
