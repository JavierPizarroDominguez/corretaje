<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteSearchController;
use App\Http\Controllers\Api\PropiedadPorArrendadorController;
use App\Http\Controllers\Api\PropiedadContratoVigenteController;
use App\Http\Controllers\Api\PropiedadDireccionCheckController;
use App\Http\Controllers\Api\TerminarContratoController;
use App\Http\Controllers\Api\GarantiaRefundController;

// [GEN:START:administracion_api]
Route::get('/clientes/search', [ClienteSearchController::class, 'search'])->name('api.clientes.search');
Route::get('/propiedades/por-arrendador/{id}', [PropiedadPorArrendadorController::class, 'index'])->name('api.propiedades.por-arrendador');
Route::get('/propiedades/{id}/contrato-vigente', [PropiedadContratoVigenteController::class, 'show'])->name('api.propiedades.contrato-vigente');
Route::get('/propiedades/direccion-check', [PropiedadDireccionCheckController::class, 'index'])->name('api.propiedades.direccion-check');
// [GEN:END:administracion_api]

// [GEN:START:dashboard_api]
use App\Http\Controllers\Api\DashboardPendientesController;
use App\Http\Controllers\Api\DashboardBuscadorController;
use App\Http\Controllers\Api\PagarCobroController;

Route::get('/dashboard/pendientes', [DashboardPendientesController::class, 'index'])->name('api.dashboard.pendientes');
Route::get('/dashboard/buscador', [DashboardBuscadorController::class, 'search'])->name('api.dashboard.buscador');
Route::post('/cobro/pagar', [PagarCobroController::class, 'pagar'])->name('api.cobro.pagar');
Route::post('/cobros/{cobro}/devolver-garantia', GarantiaRefundController::class)->name('api.cobros.devolver-garantia');
Route::post('/contratos/{contrato}/terminar', TerminarContratoController::class)->name('api.contratos.terminar');
Route::get('/cliente/{id}/pendientes', [App\Http\Controllers\Api\ClientePendientesController::class, 'index'])->name('api.cliente.pendientes');
Route::get('/propiedad/{id}/pendientes', [App\Http\Controllers\Api\PropiedadPendientesController::class, 'index'])->name('api.propiedad.pendientes');
// [GEN:END:dashboard_api]
