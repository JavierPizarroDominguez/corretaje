<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\ParticipanteContratoController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\Vistas\FichaPropiedadController;
use App\Http\Controllers\Vistas\FichaClienteController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

require __DIR__.'/generated.php';

// [GEN:START:custom_routes]
// CUSTOM ROUTES — safe from InfyOm regeneration
// ============================================

use App\Http\Controllers\Api\CobroRelationshipController;
use App\Http\Controllers\AdministracionController;
use App\Http\Controllers\DashboardController;

Route::post('/api/cobro/resolve-relationships', [CobroRelationshipController::class, 'resolve'])->name('cobro.resolve-relationships');

// [GEN:START:dashboard]
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
// [GEN:END:dashboard]

// [GEN:START:administracion_wizard]
Route::get('/administracion/create', [AdministracionController::class, 'create'])->name('administracion.create');
Route::post('/administracion', [AdministracionController::class, 'store'])->name('administracion.store');
// [GEN:END:administracion_wizard]

// [GEN:START:cliente_custom]
Route::get('/cliente/{id}/reparaciones', [FichaClienteController::class, 'reparaciones'])->name('cliente.reparaciones');
Route::get('/cliente/{id}/contratos', [FichaClienteController::class, 'contratos'])->name('cliente.contratos');
// [GEN:END:cliente_custom]

// Property ficha routes
Route::get('/propiedad/ficha/{id}', [FichaPropiedadController::class, 'show'])->name('propiedad.ficha');
Route::get('/propiedad/{id}/reparaciones', [FichaPropiedadController::class, 'reparaciones'])->name('propiedad.reparaciones');
Route::get('/propiedad/{id}/contratos', [FichaPropiedadController::class, 'contratos'])->name('propiedad.contratos');
Route::get('/cliente/ficha/{id}', fn () => view('coming-soon'))->name('cliente.ficha');
// [GEN:END:custom_routes]