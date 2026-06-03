<?php

namespace Tests\Unit;

use App\Generator\Introspection\RelationResolver;
use Tests\TestCase;

class RelationResolverTest extends TestCase
{
    private RelationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new RelationResolver();
    }

    public function test_resolve_returns_belongs_to_relations_for_participante_cobro(): void
    {
        $relations = $this->resolver->resolve('App\Models\ParticipanteCobro');

        $this->assertCount(2, $relations);

        // cobro() → belongsTo(Cobro::class, 'Cobro_id')
        $cobroRel = $this->findRelation($relations, 'cobro');
        $this->assertNotNull($cobroRel);
        $this->assertSame('belongsTo', $cobroRel->type);
        $this->assertSame('Cobro_id', $cobroRel->foreignKey);
        $this->assertSame('App\Models\Cobro', $cobroRel->relatedModel);

        // cliente() → belongsTo(Cliente::class, 'Cliente_id')
        $clienteRel = $this->findRelation($relations, 'cliente');
        $this->assertNotNull($clienteRel);
        $this->assertSame('belongsTo', $clienteRel->type);
        $this->assertSame('Cliente_id', $clienteRel->foreignKey);
        $this->assertSame('App\Models\Cliente', $clienteRel->relatedModel);
    }

    /**
     * Verifica que getScopedRelations detecta deudor/acreedor en Cobro
     * con isPivotTable=true y retorna los nuevos campos resueltos.
     * Requiere conexión a MySQL para consultar information_schema.
     */
    public function test_get_scoped_relations_returns_cobro_scoped_relations(): void
    {
        $scoped = $this->resolver->getScopedRelations('App\Models\Cobro');

        $this->assertArrayHasKey('deudor', $scoped);
        $this->assertArrayHasKey('acreedor', $scoped);

        $deudor = $scoped['deudor'];
        $this->assertSame('hasOne-scoped', $deudor['type']);
        $this->assertTrue($deudor['isPivotTable']);

        // foreignKey debe usar el FK explícito 'Cobro_id', no getForeignKey()
        // getForeignKey() devolvería 'participante_cobro_id'
        $this->assertNotSame('participante_cobro_id', $deudor['foreignKey']);
        $this->assertSame('Cobro_id', $deudor['foreignKey']);

        // Verificar scope
        $this->assertSame('rol', $deudor['scopeColumn']);
        $this->assertSame('Deudor', $deudor['scopeValue']);

        // Verificar nuevos campos resueltos desde belongsTo del pivote
        $this->assertSame('Cobro_id', $deudor['parentFk']);
        $this->assertSame('Cliente_id', $deudor['targetFk']);
        $this->assertSame('App\Models\Cliente', $deudor['targetModel']);
        $this->assertSame('cliente', $deudor['targetTable']);

        // Verificar acreedor
        $acreedor = $scoped['acreedor'];
        $this->assertSame('Cobro_id', $acreedor['parentFk']);
        $this->assertSame('Cliente_id', $acreedor['targetFk']);
        $this->assertSame('App\Models\Cliente', $acreedor['targetModel']);
        $this->assertSame('cliente', $acreedor['targetTable']);
        $this->assertSame('Acreedor', $acreedor['scopeValue']);
    }

    private function findRelation(array $relations, string $name): ?object
    {
        foreach ($relations as $rel) {
            if ($rel->name === $name) {
                return $rel;
            }
        }
        return null;
    }
}
