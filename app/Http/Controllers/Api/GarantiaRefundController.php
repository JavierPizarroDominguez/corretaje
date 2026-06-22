<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GarantiaRefundRequest;
use App\Models\Cobro;
use App\Services\GarantiaRefundService;
use Illuminate\Http\JsonResponse;

class GarantiaRefundController extends Controller
{
    public function __invoke(GarantiaRefundRequest $request, Cobro $cobro, GarantiaRefundService $service): JsonResponse
    {
        $result = $service->finalize($cobro, $request->validated('descuentos') ?? []);

        return response()->json($result);
    }
}
