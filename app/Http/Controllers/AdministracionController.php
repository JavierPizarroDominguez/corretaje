<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrearAdministracionRequest;
use App\Models\Ciudad;
use App\Services\CrearAdministracionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AdministracionController extends Controller
{
    protected CrearAdministracionService $service;

    public function __construct(CrearAdministracionService $service)
    {
        $this->service = $service;
    }

    /**
     * Show the administration wizard creation form.
     */
    public function create(): View
    {
        $ciudadOptions = Ciudad::orderBy('nombre')
            ->get(['id', 'nombre'])
            ->pluck('nombre', 'id')
            ->toArray();

        return view('administracion.create', [
            'ciudadOptions' => $ciudadOptions,
        ]);
    }

    /**
     * Process the administration wizard submission.
     */
    public function store(CrearAdministracionRequest $request, CrearAdministracionService $service): RedirectResponse
    {
        try {
            $contrato = $service->crearAdministracion($request);

            Session::flash('success', 'Administración creada exitosamente.');

            return Redirect::route('propiedad.ficha', ['id' => $contrato->propiedad_id]);
        } catch (\Throwable $e) {
            Log::error('Error creating administracion', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $this->resolveDbErrorMessage($e->getMessage());

            Session::flash('error', $errorMessage);

            return Redirect::back()
                ->withInput()
                ->withExceptions($request->validated());
        }
    }

    /**
     * Convert database constraint errors into human-readable messages.
     */
    protected function resolveDbErrorMessage(string $message): string
    {
        if (str_contains($message, 'chk_comision_mensual_contrato')) {
            return 'La comisión mensual debe ser mayor que 0 y menor que la renta. Si no aplica, déjela en blanco o marque "Sin comisión mensual".';
        }

        if (str_contains($message, 'chk_comision_inicial_contrato')) {
            return 'La comisión inicial debe ser mayor que 0. Si no aplica, déjela en blanco o marque "Sin comisión inicial".';
        }

        if (str_contains($message, 'chk_renta_contrato')) {
            return 'La renta debe ser mayor que 0.';
        }

        if (str_contains($message, 'chk_dia_pago_contrato')) {
            return 'El día de pago debe estar entre 1 y 31.';
        }

        if (str_contains($message, 'chk_datos_administracion')) {
            return 'Si la propiedad tiene administración, la renta y el día de pago son obligatorios. Si no tiene administración, no deben enviarse.';
        }

        if (str_contains($message, 'chk_fecha_inicio_contrato')) {
            return 'La fecha de inicio debe ser igual o posterior a la fecha de firma.';
        }

        if (str_contains($message, 'chk_fecha_termino_contrato')) {
            return 'La fecha de término debe ser posterior a la fecha de inicio.';
        }

        if (str_contains($message, 'Data truncated')) {
            return 'Uno de los valores ingresados no es válido para la base de datos. Verifique que los montos sean números enteros y que los textos no excedan el límite permitido.';
        }

        if (str_contains($message, 'Duplicate entry')) {
            return 'Ya existe un registro con esos datos. Verifique que no esté duplicando información.';
        }

        if (str_contains($message, 'Foreign key constraint fails')) {
            return 'No se puede crear el registro porque una de las referencias no existe. Verifique que todos los datos relacionados sean correctos.';
        }

        return 'Ocurrió un error al crear la administración. Por favor intente nuevamente. Si el problema persiste, contacte al administrador.';
    }
}
