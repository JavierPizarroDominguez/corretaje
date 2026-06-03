<?php

namespace Tests\Unit\Api;

use App\Http\Controllers\Api\ClienteSearchController;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClienteSearchControllerUnitTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_search_returns_empty_for_short_query(): void
    {
        $controller = new ClienteSearchController;
        $request = new Request(['q' => 'a']);

        $response = $controller->search($request);

        // Controller returns 422 when query is too short
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals([], $response->getData(true));
    }

    public function test_search_returns_empty_when_no_matches(): void
    {
        $controller = new ClienteSearchController;
        $request = new Request(['q' => 'zzznonexistent']);

        $response = $controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getData(true));
    }

    public function test_search_returns_matching_clientes(): void
    {
        Cliente::create([
            'nombre' => 'Juan Perez',
            'rut' => '12345678-9',
            'fecha_creacion' => now(),
        ]);

        $controller = new ClienteSearchController;
        $request = new Request(['q' => 'Juan']);

        $response = $controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Juan Perez', $data[0]['texto']);
    }

    public function test_search_returns_json_content_type(): void
    {
        $controller = new ClienteSearchController;
        $request = new Request(['q' => 'Juan']);

        $response = $controller->search($request);

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}
