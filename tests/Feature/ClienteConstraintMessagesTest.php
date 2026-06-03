<?php

namespace Tests\Feature;

use Tests\TestCase;

class ClienteConstraintMessagesTest extends TestCase
{
    public function test_store_with_invalid_rut_shows_readable_message(): void
    {
        $response = $this->post(route('cliente.store'), [
            'nombre' => 'Test Cliente',
            'fecha_creacion' => now()->format('Y-m-d'),
            'rut' => 'invalido',
        ]);

        $response->assertSessionHasErrors();
    }
}
