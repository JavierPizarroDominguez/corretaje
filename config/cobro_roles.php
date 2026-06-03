<?php

/**
 * Cobro Role Mapping Configuration
 *
 * Maps each Cobro tipo to its deudor/acreedor roles and requirements.
 * Used by CobroRelationshipResolver to auto-resolve participants.
 *
 * @see \App\Services\CobroRelationshipResolver
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Tipo Role Map
    |--------------------------------------------------------------------------
    |
    | Each key is a Cobro tipo value. Each entry contains:
    |   - deudor_rol:     Role that should be the Deudor (null = no Deudor participant)
    |   - acreedor_rol:   Role that should be the Acreedor (null = no Acreedor participant)
    |   - requires_contract: Whether this tipo needs an active Contrato to resolve
    |   - requires_servicio: Whether this tipo auto-links a Servicio
    |   - servicio_tipo:  If requires_servicio=true, the service tipo to search for
    |
    */
    'tipo_role_map' => [
        'Ingreso Renta Arrendatario' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => 'Arrendador',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Egreso Renta Arrendador' => [
            'deudor_rol' => 'Corredor',
            'acreedor_rol' => 'Arrendador',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Comision inicial arrendador' => [
            'deudor_rol' => 'Arrendador',
            'acreedor_rol' => 'Corredor',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Comision inicial arrendatario' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => 'Corredor',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Comision Mensual' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => 'Corredor',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Ingreso Garantía Arrendatario' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => 'Arrendador',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Egreso Garantía Arrendador' => [
            'deudor_rol' => 'Corredor',
            'acreedor_rol' => 'Arrendador',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Devolución Garantía Arrendatario' => [
            'deudor_rol' => 'Arrendador',
            'acreedor_rol' => 'Arrendatario',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Aseo Final' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => 'Arrendador',
            'requires_contract' => true,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Luz' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => true,
            'servicio_tipo' => 'Luz',
        ],
        'Agua' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => true,
            'servicio_tipo' => 'Agua',
        ],
        'Gas' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => true,
            'servicio_tipo' => 'Gas',
        ],
        'Gastos comunes' => [
            'deudor_rol' => 'Arrendatario',
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => true,
            'servicio_tipo' => 'Gastos comunes',
        ],
        'Reparación' => [
            'deudor_rol' => null,
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Extra' => [
            'deudor_rol' => null,
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
        'Devolución' => [
            'deudor_rol' => null,
            'acreedor_rol' => null,
            'requires_contract' => false,
            'requires_servicio' => false,
            'servicio_tipo' => null,
        ],
    ],
];