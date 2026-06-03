<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransaccionCobro
 * 
 * @property int $Transaccion_id
 * @property int $Cobro_id
 * @property int $monto_pagado
 * 
 * @property Transaccion $transaccion
 * @property Cobro $cobro
 *
 * @package App\Models
 */
class TransaccionCobro extends Model
{
	protected $table = 'transaccion_cobro';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Transaccion_id' => 'int',
		'Cobro_id' => 'int',
		'monto_pagado' => 'int'
	];

	protected $fillable = [
		'Transaccion_id',
		'Cobro_id',
		'monto_pagado'
	];

	public function transaccion()
	{
		return $this->belongsTo(Transaccion::class, 'Transaccion_id');
	}

	public function cobro()
	{
		return $this->belongsTo(Cobro::class, 'Cobro_id');
	}
}
