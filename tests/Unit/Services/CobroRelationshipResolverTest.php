<?php

namespace Tests\Unit\Services;

use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ParticipanteContrato;
use App\Models\Propiedad;
use App\Models\Unidad;
use App\Services\CobroRelationshipResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CobroRelationshipResolverTest extends TestCase
{
    use DatabaseTransactions;

    private CobroRelationshipResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(CobroRelationshipResolver::class);

        if (! Cliente::find(1)) {
            Cliente::create(['id' => 1, 'nombre' => 'Corredor Principal', 'fecha_creacion' => now()]);
        }
    }

    public function test_resolve_manual_tipo_returns_participants_array_with_contract_participants(): void
    {
        // Arrange
        $arrendador = Cliente::create(['nombre' => 'Arrendador Test', 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => 'Arrendatario Test', 'fecha_creacion' => now()]);
        $cliente = Cliente::create(['nombre' => 'Cliente Test', 'fecha_creacion' => now()]);

        $propiedad = Propiedad::create(['direccion' => 'Test Address 123', 'propietario' => $arrendador->id]);
        $unidad = Unidad::create(['nombre' => 'Test Unidad', 'Propiedad_id' => $propiedad->id]);
        $contrato = Contrato::create([
            'Unidad_id' => $unidad->id,
            'administracion' => true,
            'renta' => 500000,
        ]);

        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato->id, 'Cliente_id' => $cliente->id, 'rol' => 'Corredor']);

        // Act
        $result = $this->resolver->resolve($cliente->id, 'Reparación', $propiedad->id);

        // Assert
        $this->assertSame('ok', $result['status']);
        $this->assertArrayHasKey('participants', $result['data']);

        $participants = $result['data']['participants'];
        $this->assertCount(3, $participants);

        // Each participant should have id, nombre, rol
        $participantIds = array_column($participants, 'id');
        $this->assertContains($arrendador->id, $participantIds);
        $this->assertContains($arrendatario->id, $participantIds);
        $this->assertContains($cliente->id, $participantIds);

        foreach ($participants as $p) {
            $this->assertArrayHasKey('id', $p);
            $this->assertArrayHasKey('nombre', $p);
            $this->assertArrayHasKey('rol', $p);
            $this->assertNotEmpty($p['nombre']);
            $this->assertNotEmpty($p['rol']);
        }

        // Verify arrendador's data
        $arrendadorData = array_values(array_filter($participants, fn($p) => $p['id'] === $arrendador->id))[0] ?? null;
        $this->assertNotNull($arrendadorData);
        $this->assertSame('Arrendador Test', $arrendadorData['nombre']);
        $this->assertSame('Arrendador', $arrendadorData['rol']);
    }

    public function test_resolve_manual_tipo_returns_empty_participants_when_no_active_contracts(): void
    {
        // Arrange: cliente with no contracts
        $cliente = Cliente::create(['nombre' => 'Cliente Sin Contrato', 'fecha_creacion' => now()]);

        // Act
        $result = $this->resolver->resolve($cliente->id, 'Reparación');

        // Assert — should fallback to error since no contracts and no propiedad_id
        $this->assertSame('error', $result['status']);
    }

    public function test_resolve_manual_tipo_returns_deduplicated_participants_across_multiple_contracts(): void
    {
        // Arrange
        $arrendador = Cliente::create(['nombre' => 'Arrendador Multi', 'fecha_creacion' => now()]);
        $arrendatario = Cliente::create(['nombre' => 'Arrendatario Multi', 'fecha_creacion' => now()]);
        $cliente = Cliente::create(['nombre' => 'Cliente Multi', 'fecha_creacion' => now()]);

        $prop1 = Propiedad::create(['direccion' => 'Prop 1', 'propietario' => $arrendador->id]);
        $unidad1 = Unidad::create(['nombre' => 'Unidad 1', 'Propiedad_id' => $prop1->id]);
        $contrato1 = Contrato::create(['Unidad_id' => $unidad1->id, 'administracion' => true]);
        ParticipanteContrato::create(['Contrato_id' => $contrato1->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato1->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato1->id, 'Cliente_id' => $cliente->id, 'rol' => 'Corredor']);

        $prop2 = Propiedad::create(['direccion' => 'Prop 2', 'propietario' => $arrendador->id]);
        $unidad2 = Unidad::create(['nombre' => 'Unidad 2', 'Propiedad_id' => $prop2->id]);
        $contrato2 = Contrato::create(['Unidad_id' => $unidad2->id, 'administracion' => true]);
        // Same arrendador and arrendatario, cliente participates in both
        $cliente2 = Cliente::create(['nombre' => 'Corredor Multi 2', 'fecha_creacion' => now()]);
        ParticipanteContrato::create(['Contrato_id' => $contrato2->id, 'Cliente_id' => $arrendador->id, 'rol' => 'Arrendador']);
        ParticipanteContrato::create(['Contrato_id' => $contrato2->id, 'Cliente_id' => $arrendatario->id, 'rol' => 'Arrendatario']);
        ParticipanteContrato::create(['Contrato_id' => $contrato2->id, 'Cliente_id' => $cliente->id, 'rol' => 'Corredor']);
        ParticipanteContrato::create(['Contrato_id' => $contrato2->id, 'Cliente_id' => $cliente2->id, 'rol' => 'Aval']);

        // Act — this will go to buildMultipleContractsResolution because
        // $cliente participates in both contracts (no propiedad filter)
        $result = $this->resolver->resolve($cliente->id, 'Reparación');

        // Assert
        $this->assertSame('ok', $result['status']);
        $this->assertArrayHasKey('participants', $result['data']);

        $participants = $result['data']['participants'];
        // Should have 4 unique participants (arrendador, arrendatario, cliente, cliente2)
        // arrendador and arrendatario appear in both contracts but should be deduplicated
        $this->assertCount(4, $participants);
    }
}
