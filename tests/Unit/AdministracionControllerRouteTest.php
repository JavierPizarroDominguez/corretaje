<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\AdministracionController;
use App\Services\CrearAdministracionService;
use ReflectionClass;

class AdministracionControllerRouteTest extends TestCase
{
    public function test_administracion_controller_has_create_method(): void
    {
        $ref = new ReflectionClass(AdministracionController::class);
        $this->assertTrue($ref->hasMethod('create'));
    }

    public function test_administracion_controller_has_store_method(): void
    {
        $ref = new ReflectionClass(AdministracionController::class);
        $this->assertTrue($ref->hasMethod('store'));
    }

    public function test_administracion_controller_store_accepts_request_and_service(): void
    {
        $ref = new ReflectionClass(AdministracionController::class);
        $store = $ref->getMethod('store');
        $params = $store->getParameters();

        $this->assertCount(2, $params);
        $this->assertEquals('App\Http\Requests\CrearAdministracionRequest', $params[0]->getType()->getName());
        $this->assertEquals('App\Services\CrearAdministracionService', $params[1]->getType()->getName());
    }

    public function test_administracion_controller_create_returns_view(): void
    {
        $ref = new ReflectionClass(AdministracionController::class);
        $create = $ref->getMethod('create');
        $returnType = $create->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\View\View', $returnType->getName());
    }

    public function test_administracion_controller_store_returns_redirect(): void
    {
        $ref = new ReflectionClass(AdministracionController::class);
        $store = $ref->getMethod('store');
        $returnType = $store->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Http\RedirectResponse', $returnType->getName());
    }
}
