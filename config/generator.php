<?php

return [

    'select_threshold' => 15,

    'search_paths' => [
            'cobro' => [
                0 => '__none__'
            ],
            'contrato' => [
                0 => '__none__'
            ],
            'servicio' => [
                0 => '__none__'
            ],
            'propiedad' => [
                0 => '__none__'
            ],
            'unidad' => [
                0 => 'nombre'
            ],
            'participante_cobro' => [
                0 => '__none__'
            ],
            'cliente' => [
                0 => 'nombre'
            ],
            'Cobro' => [
                0 => '__none__'
            ],
            'nacionalidad' => [
                0 => 'nombre'
            ],
            'ciudad' => [
                0 => 'nombre'
            ],
            'empresa' => [
                0 => 'nombre'
            ]
        ],

    'display_fields' => [
            'contrato' => 'id',
            'servicio' => 'id',
            'propiedad' => 'direccion',
            'unidad' => 'nombre',
            'cliente' => 'nombre',
            'cobro' => 'id',
            'nacionalidad' => 'nombre',
            'ciudad' => 'nombre',
            'empresa' => 'nombre'
        ],
];
