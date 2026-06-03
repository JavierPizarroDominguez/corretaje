<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Transaccion
 * 
 * @property int $id
 * @property int $monto
 * @property Carbon $fecha
 * @property int $Destino_Transaccion_id
 * @property int $Origen_Transaccion_id
 * @property string|null $url_comprobante
 * 
 * @property DestinoTransaccion $destino_transaccion
 * @property OrigenTransaccion $origen_transaccion
 * @property SaldoCliente|null $saldo_cliente
 * @property Collection|Cobro[] $cobros
 *
 * @package App\Models
 */
class Transaccion extends Model
{
	protected $table = 'transaccion';
	public $timestamps = false;

	protected $casts = [
		'monto' => 'int',
		'fecha' => 'datetime',
		'Destino_Transaccion_id' => 'int',
		'Origen_Transaccion_id' => 'int'
	];

	protected $fillable = [
		'monto',
		'fecha',
		'Destino_Transaccion_id',
		'Origen_Transaccion_id',
		'url_comprobante'
	];

	public function destino_transaccion()
	{
		return $this->belongsTo(DestinoTransaccion::class, 'Destino_Transaccion_id');
	}

	public function origen_transaccion()
	{
		return $this->belongsTo(OrigenTransaccion::class, 'Origen_Transaccion_id');
	}

	public function saldo_cliente()
	{
		return $this->hasOne(SaldoCliente::class, 'Transaccion_id');
	}

	public function cobros()
	{
		return $this->belongsToMany(Cobro::class, 'transaccion_cobro', 'Transaccion_id', 'Cobro_id')
					->withPivot('monto_pagado');
	}
}
