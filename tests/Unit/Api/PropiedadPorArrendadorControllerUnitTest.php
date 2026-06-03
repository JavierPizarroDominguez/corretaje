<?php

namespace Tests\Unit\Api;

use App\Http\Controllers\Api\PropiedadPorArrendadorController;
use App\Models\Cliente;
use App\Models\Propiedad;
use App\Models\Unidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PropiedadPorArrendadorControllerUnitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_returns_empty_for_nonexistent_propietario(): void
    {
        $controller = new PropiedadPorArrendadorController;

        $response = $controller->index(99999);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getData(true));
    }

    public function test_index_returns_propiedades_for_given_propietario(): void
    {
        $arrendador = Cliente::create([
            'nombre' => 'Propietario Test',
            'fecha_creacion' => now(),
        ]);

        $propiedad = Propiedad::create([
            'direccion' => 'Av. Principal 123',
            'propietario' => $arrendador->id,
        ]);

        Unidad::create([
            'nombre' => 'Depto 1',
            'Propiedad_id' => $propiedad->id,
        ]);

        $controller = new PropiedadPorArrendadorController;

        $response = $controller->index($arrendador->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Av. Principal 123', $data[0]['direccion']);
    }

    public function test_index_returns_json_content_type(): void
    {
        $controller = new PropiedadPorArrendadorController;

        $response = $controller->index(1);

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}
