<?php

/*
 * Rutas generadas automáticamente por el generador.
 * No editar manualmente bloques marcados con [GEN:START/END].
 * Para agregar rutas custom, hacerlo fuera de los bloques.
 */


// [GEN:START:routes_Cobro]
use App\Http\Controllers\Crud\CobroController;

Route::get('/cobro', [CobroController::class, 'index'])->name('cobro.index');
Route::get('/cobro/create', [CobroController::class, 'create'])->name('cobro.create');
Route::post('/cobro', [CobroController::class, 'store'])->name('cobro.store');
Route::get('/cobro/{id}', [CobroController::class, 'show'])->name('cobro.show');
Route::get('/cobro/{id}/edit', [CobroController::class, 'edit'])->name('cobro.edit');
Route::put('/cobro/{id}', [CobroController::class, 'update'])->name('cobro.update');
Route::delete('/cobro/{id}', [CobroController::class, 'destroy'])->name('cobro.destroy');

// [GEN:END:routes_Cobro]


// [GEN:START:routes_Buscador]
use App\Http\Controllers\BuscadorController;

Route::get('/buscador', [BuscadorController::class, 'index'])->name('buscador.index');
// [GEN:END:routes_Buscador]


// [GEN:START:routes_Cliente]
use App\Http\Controllers\Crud\ClienteController;
use App\Http\Controllers\Vistas\FichaClienteController;

Route::get('/cliente', [ClienteController::class, 'index'])->name('cliente.index');
Route::get('/cliente/create', [ClienteController::class, 'create'])->name('cliente.create');
Route::post('/cliente', [ClienteController::class, 'store'])->name('cliente.store');
Route::get('/cliente/{id}', [ClienteController::class, 'show'])->name('cliente.show');
Route::get('/cliente/ficha/{id}', [FichaClienteController::class, 'show'])->name('fichacliente.show');
Route::get('/cliente/{id}/edit', [ClienteController::class, 'edit'])->name('cliente.edit');
Route::put('/cliente/{id}', [ClienteController::class, 'update'])->name('cliente.update');
Route::delete('/cliente/{id}', [ClienteController::class, 'destroy'])->name('cliente.destroy');

// [GEN:END:routes_Cliente]


// [GEN:START:routes_Contrato]
use App\Http\Controllers\Crud\ContratoController;

Route::get('/contrato', [ContratoController::class, 'index'])->name('contrato.index');
Route::get('/contrato/create', [ContratoController::class, 'create'])->name('contrato.create');
Route::post('/contrato', [ContratoController::class, 'store'])->name('contrato.store');
Route::get('/contrato/{id}', [ContratoController::class, 'show'])->name('contrato.show');
Route::get('/contrato/{id}/edit', [ContratoController::class, 'edit'])->name('contrato.edit');
Route::put('/contrato/{id}', [ContratoController::class, 'update'])->name('contrato.update');
Route::delete('/contrato/{id}', [ContratoController::class, 'destroy'])->name('contrato.destroy');

// [GEN:END:routes_Contrato]


// [GEN:START:routes_Transaccion]
use App\Http\Controllers\Crud\TransaccionController;

Route::get('/transaccion', [TransaccionController::class, 'index'])->name('transaccion.index');
Route::get('/transaccion/create', [TransaccionController::class, 'create'])->name('transaccion.create');
Route::post('/transaccion', [TransaccionController::class, 'store'])->name('transaccion.store');
Route::get('/transaccion/{id}', [TransaccionController::class, 'show'])->name('transaccion.show');
Route::get('/transaccion/{id}/edit', [TransaccionController::class, 'edit'])->name('transaccion.edit');
Route::put('/transaccion/{id}', [TransaccionController::class, 'update'])->name('transaccion.update');
Route::delete('/transaccion/{id}', [TransaccionController::class, 'destroy'])->name('transaccion.destroy');

// [GEN:END:routes_Transaccion]


// [GEN:START:routes_ParticipanteContrato]
use App\Http\Controllers\Crud\ParticipanteContratoController;

Route::get('/participante_contrato', [ParticipanteContratoController::class, 'index'])->name('participante_contrato.index');
Route::get('/participante_contrato/create', [ParticipanteContratoController::class, 'create'])->name('participante_contrato.create');
Route::post('/participante_contrato', [ParticipanteContratoController::class, 'store'])->name('participante_contrato.store');
Route::get('/participante_contrato/{id}', [ParticipanteContratoController::class, 'show'])->name('participante_contrato.show');
Route::get('/participante_contrato/{id}/edit', [ParticipanteContratoController::class, 'edit'])->name('participante_contrato.edit');
Route::put('/participante_contrato/{id}', [ParticipanteContratoController::class, 'update'])->name('participante_contrato.update');
Route::delete('/participante_contrato/{id}', [ParticipanteContratoController::class, 'destroy'])->name('participante_contrato.destroy');

// [GEN:END:routes_ParticipanteContrato]


// [GEN:START:routes_ParticipanteCobro]
use App\Http\Controllers\Crud\ParticipanteCobroController;

Route::get('/participante_cobro', [ParticipanteCobroController::class, 'index'])->name('participante_cobro.index');
Route::get('/participante_cobro/create', [ParticipanteCobroController::class, 'create'])->name('participante_cobro.create');
Route::post('/participante_cobro', [ParticipanteCobroController::class, 'store'])->name('participante_cobro.store');
Route::get('/participante_cobro/{cliente_id}/{cobro_id}', [ParticipanteCobroController::class, 'show'])->name('participante_cobro.show');
Route::get('/participante_cobro/{cliente_id}/{cobro_id}/edit', [ParticipanteCobroController::class, 'edit'])->name('participante_cobro.edit');
Route::put('/participante_cobro/{cliente_id}/{cobro_id}', [ParticipanteCobroController::class, 'update'])->name('participante_cobro.update');
Route::delete('/participante_cobro/{cliente_id}/{cobro_id}', [ParticipanteCobroController::class, 'destroy'])->name('participante_cobro.destroy');

// [GEN:END:routes_ParticipanteCobro]


// [GEN:START:routes_CobroFilter]
use App\Http\Controllers\Crud\CobroFilterController;

Route::get('/cobro-filtrar', [CobroFilterController::class, 'index'])->name('cobro.filtrar');
// [GEN:END:routes_CobroFilter]


// [GEN:START:routes_TransaccionFilter]
use App\Http\Controllers\Crud\TransaccionFilterController;

Route::get('/transaccion-filtrar', [TransaccionFilterController::class, 'index'])->name('transaccion.filtrar');
// [GEN:END:routes_TransaccionFilter]


// [GEN:START:routes_ContratoFilter]
use App\Http\Controllers\Crud\ContratoFilterController;

Route::get('/contrato-filtrar', [ContratoFilterController::class, 'index'])->name('contrato.filtrar');
// [GEN:END:routes_ContratoFilter]


// [GEN:START:routes_ClienteFilter]
use App\Http\Controllers\Crud\ClienteFilterController;

Route::get('/cliente-filtrar', [ClienteFilterController::class, 'index'])->name('cliente.filtrar');
// [GEN:END:routes_ClienteFilter]


// [GEN:START:routes_Propiedad]
use App\Http\Controllers\Crud\PropiedadController;

Route::get('/propiedad', [PropiedadController::class, 'index'])->name('propiedad.index');
Route::get('/propiedad/create', [PropiedadController::class, 'create'])->name('propiedad.create');
Route::post('/propiedad', [PropiedadController::class, 'store'])->name('propiedad.store');
Route::get('/propiedad/{id}', [PropiedadController::class, 'show'])->name('propiedad.show');
Route::get('/propiedad/{id}/edit', [PropiedadController::class, 'edit'])->name('propiedad.edit');
Route::put('/propiedad/{id}', [PropiedadController::class, 'update'])->name('propiedad.update');
Route::delete('/propiedad/{id}', [PropiedadController::class, 'destroy'])->name('propiedad.destroy');

// [GEN:END:routes_Propiedad]


// [GEN:START:routes_Unidad]
use App\Http\Controllers\Crud\UnidadController;

Route::get('/unidad', [UnidadController::class, 'index'])->name('unidad.index');
Route::get('/unidad/create', [UnidadController::class, 'create'])->name('unidad.create');
Route::post('/unidad', [UnidadController::class, 'store'])->name('unidad.store');
Route::get('/unidad/{id}', [UnidadController::class, 'show'])->name('unidad.show');
Route::get('/unidad/{id}/edit', [UnidadController::class, 'edit'])->name('unidad.edit');
Route::put('/unidad/{id}', [UnidadController::class, 'update'])->name('unidad.update');
Route::delete('/unidad/{id}', [UnidadController::class, 'destroy'])->name('unidad.destroy');

// [GEN:END:routes_Unidad]


// [GEN:START:routes_Servicio]
use App\Http\Controllers\Crud\ServicioController;

Route::get('/servicio', [ServicioController::class, 'index'])->name('servicio.index');
Route::get('/servicio/create', [ServicioController::class, 'create'])->name('servicio.create');
Route::post('/servicio', [ServicioController::class, 'store'])->name('servicio.store');
Route::get('/servicio/{id}', [ServicioController::class, 'show'])->name('servicio.show');
Route::get('/servicio/{id}/edit', [ServicioController::class, 'edit'])->name('servicio.edit');
Route::put('/servicio/{id}', [ServicioController::class, 'update'])->name('servicio.update');
Route::delete('/servicio/{id}', [ServicioController::class, 'destroy'])->name('servicio.destroy');

// [GEN:END:routes_Servicio]


// [GEN:START:routes_ServicioFilter]
use App\Http\Controllers\Crud\ServicioFilterController;

Route::get('/servicio-filtrar', [ServicioFilterController::class, 'index'])->name('servicio.filtrar');
// [GEN:END:routes_ServicioFilter]
