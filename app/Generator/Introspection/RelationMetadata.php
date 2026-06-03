<?php

namespace App\Generator\Introspection;

/**
 * DTO que representa una relación Eloquent leída del modelo existente.
 * El RelationResolver construye estas instancias parseando el modelo.
 */
class RelationMetadata
{
    public function __construct(
        // Nombre del método en el modelo: 'ciudad', 'participante_contratos'
        public readonly string  $name,

        // Tipo de relación Eloquent
        // 'belongsTo' | 'hasMany' | 'hasOne' | 'belongsToMany' | 'hasOne-scoped'
        // hasOne-scoped = hasOne con ->where() como arrendador/arrendatario
        public readonly string  $type,

        // Clase del modelo relacionado: 'App\Models\Ciudad'
        public readonly string  $relatedModel,

        // Nombre corto del modelo relacionado: 'Ciudad'
        public readonly string  $relatedModelShort,

        // FK local usada en la relación (para belongsTo): 'Ciudad_id'
        public readonly ?string $foreignKey,

        // Si es hasMany o belongsToMany, se sugiere para eager loading
        public readonly bool    $suggestEagerLoad,

        // Profundidad sugerida de eager load
        // null = solo la relación, 'cliente' = relacion.cliente
        public readonly ?string $eagerLoadNested,
    ) {}
}
