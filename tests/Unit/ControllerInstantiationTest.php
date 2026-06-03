<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\ClienteSearchController;
use App\Http\Controllers\Api\PropiedadPorArrendadorController;
use App\Http\Controllers\AdministracionController;
use App\Services\CrearAdministracionService;
use ReflectionClass;

class ControllerInstantiationTest extends TestCase
{
    public function test_cliente_search_controller_can_be_instantiated(): void
    {
        $controller = new ClienteSearchController();
        $this->assertInstanceOf(ClienteSearchController::class, $controller);
    }

    public function test_propiedad_por_arrendador_controller_can_be_instantiated(): void
    {
        $controller = new PropiedadPorArrendadorController();
        $this->assertInstanceOf(PropiedadPorArrendadorController::class, $controller);
    }

    public function test_administracion_controller_can_be_instantiated(): void
    {
        $service = $this->createMock(CrearAdministracionService::class);
        $controller = new AdministracionController($service);
        $this->assertInstanceOf(AdministracionController::class, $controller);
    }

    public function test_cliente_search_controller_has_search_method(): void
    {
        $ref = new ReflectionClass(ClienteSearchController::class);
        $this->assertTrue($ref->hasMethod('search'));
    }

    public function test_propiedad_por_arrendador_controller_has_index_method(): void
    {
        $ref = new ReflectionClass(PropiedadPorArrendadorController::class);
        $this->assertTrue($ref->hasMethod('index'));
    }

    public function test_administracion_controller_has_create_and_store_methods(): void
    {
        $ref = new ReflectionClass(AdministracionController::class);
        $this->assertTrue($ref->hasMethod('create'));
        $this->assertTrue($ref->hasMethod('store'));
    }
}
