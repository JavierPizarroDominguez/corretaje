<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescuentoGarantia extends Model
{
    protected $table = 'Descuento_Garantia';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'Cobro_Devolucion_id' => 'int',
        'Cobro_Descuento_id' => 'int',
    ];

    protected $fillable = [
        'Cobro_Devolucion_id',
        'Cobro_Descuento_id',
    ];

    public function devolucion()
    {
        return $this->belongsTo(Cobro::class, 'Cobro_Devolucion_id');
    }

    public function descuento()
    {
        return $this->belongsTo(Cobro::class, 'Cobro_Descuento_id');
    }
}
