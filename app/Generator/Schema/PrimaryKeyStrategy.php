<?php

namespace App\Generator\Schema;

class PrimaryKeyStrategy
{
    private bool   $isComposite;
    private array  $keys;
    private string $modelVariable;

    public function __construct(array $keys, string $modelVariable)
    {
        $this->keys          = $keys;
        $this->isComposite   = count($keys) > 1;
        $this->modelVariable = $modelVariable;
    }

    /**
     * Segmentos de ruta para la URL.
     *
     * Simple:    {id}
     * Compuesta: {contrato_id}/{cliente_id}
     */
    public function routeSegments(): string
    {
        return implode('/', array_map(
            fn(string $k) => '{' . $this->toParam($k) . '}',
            $this->keys
        ));
    }

    /**
     * Firma del método PHP del controller.
     *
     * Simple:    $id
     * Compuesta: $contrato_id, $cliente_id
     */
    public function methodSignature(): string
    {
        return implode(', ', array_map(
            fn(string $k) => '$' . $this->toParam($k),
            $this->keys
        ));
    }

    /**
     * Genera el findOrFail apropiado para el controller.
     *
     * Simple:
     *   Contrato::findOrFail($id)
     *
     * Compuesta:
     *   ParticipanteContrato::where('Contrato_id', $contrato_id)
     *                        ->where('Cliente_id', $cliente_id)
     *                        ->firstOrFail()
     */
    public function findOrFail(string $modelName): string
    {
        return $this->findOrFailExpression($modelName);
    }

    /**
     * Genera la expresión completa de findOrFail para usar en el controller.
     * Útil para el stub de controller.
     *
     * Simple:   $contrato = Model::findOrFail($id);
     * Compuesta: $participante = Model::query()
     *                 ->where('k1', $v1)
     *                 ->where('k2', $v2)
     *                 ->firstOrFail();
     */
    public function findOrFailExpression(string $modelName): string
    {
        if (!$this->isComposite) {
            $param = '$' . $this->toParam($this->keys[0]);
            return "{$modelName}::findOrFail({$param})";
        }

        $lines = ["{$modelName}::query()"];

        foreach ($this->keys as $key) {
            $param   = '$' . $this->toParam($key);
            $lines[] = "            ->where('{$key}', {$param})";
        }

        $lines[] = '            ->firstOrFail()';

        return implode("\n", $lines);
    }

    /**
     * Genera la expresión de destroy para usar en el controller.
     *
     * Simple:   Model::destroy($id);
     * Compuesta: Model::where('k1', $v1)->where('k2', $v2)->delete();
     */
    public function destroyExpression(string $modelName): string
    {
        if (!$this->isComposite) {
            $param = '$' . $this->toParam($this->keys[0]);
            return "{$modelName}::destroy({$param})";
        }

        $lines = [];

        foreach ($this->keys as $key) {
            $param   = '$' . $this->toParam($key);
            $lines[] = "            ->where('{$key}', {$param})";
        }

        $lines[] = '            ->delete()';

        return "{$modelName}::query()\n" . implode("\n", $lines);
    }

    /**
     * Genera los parámetros para route() en redirect después de store.
     *
     * Simple:   $model->id
     * Compuesta: [$model->k1, $model->k2]
     */
    public function redirectParams(string $modelVar): string
    {
        if (!$this->isComposite) {
            return '$' . $modelVar . '->' . $this->resolvePkAccessor($this->keys[0]);
        }

        $segments = array_map(
            fn(string $k) => '$' . $modelVar . '->' . $this->resolvePkAccessor($k),
            $this->keys
        );

        return '[' . implode(', ', $segments) . ']';
    }

    /**
     * Segmentos Blade para construir URLs en vistas.
     *
     * Simple:    {{ $contrato->id }}
     * Compuesta: {{ $participanteContrato->cliente->id }}/{{ $participanteContrato->cobro->id }}
     */
    public function bladeUrlSegments(): string
    {
        return implode('/', array_map(
            fn(string $k) => '{{ $' . $this->modelVariable . '->' . $this->resolvePkAccessor($k) . ' }}',
            $this->keys
        ));
    }

    /**
     * Segmentos Blade para URLs en índices (sin {{ }}).
     * Útil para href attribute.
     *
     * Simple:    {{ $contrato->id }}
     * Compuesta: {{ $p->contrato->id }}/{{ $p->cliente->id }}
     */
    public function bladeUrlSegmentsRaw(): string
    {
        return implode('/', array_map(
            fn(string $k) => '{{ $' . $this->modelVariable . '->' . $this->resolvePkAccessor($k) . ' }}',
            $this->keys
        ));
    }

    /**
     * Para PK que también es FK (termina en _id), usa la relación:
     *   Cliente_id → $model->cliente->id
     * Para PK simple (id), usa la columna directa:
     *   id → $model->id
     */
    private function resolvePkAccessor(string $column): string
    {
        if (preg_match('/^(.+)_id$/i', $column, $m)) {
            $relation = lcfirst($m[1]);
            return $relation . '->id';
        }
        return strtolower($column);
    }

    public function isComposite(): bool
    {
        return $this->isComposite;
    }

    /**
     * Nombre de la variable PHP para la PK (usada en el stub del controller).
     *
     * Simple:    'id'         → parámetro $id
     * Compuesta: no aplica    → usar methodSignature()
     */
    public function singleParamName(): string
    {
        if ($this->isComposite) {
            throw new \LogicException('No usar singleParamName() con PK compuesta. Usar methodSignature().');
        }

        return $this->toParam($this->keys[0]);
    }

    // Unidad_id → unidad_id
    private function toParam(string $key): string
    {
        return strtolower($key);
    }
}
