<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SaldoCliente
 * 
 * @property int $Transaccion_id
 * @property int $Cliente_id
 * @property int $monto
 * 
 * @property Cliente $cliente
 * @property Transaccion $transaccion
 * @property Collection|AplicacionSaldoCobro[] $aplicacion_saldo_cobros
 *
 * @package App\Models
 */
class SaldoCliente extends Model
{
	protected $table = 'saldo_cliente';
	protected $primaryKey = 'Transaccion_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Transaccion_id' => 'int',
		'Cliente_id' => 'int',
		'monto' => 'int'
	];

	protected $fillable = [
		'Cliente_id',
		'monto'
	];

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}

	public function transaccion()
	{
		return $this->belongsTo(Transaccion::class, 'Transaccion_id');
	}

	public function aplicacion_saldo_cobros()
	{
		return $this->hasMany(AplicacionSaldoCobro::class, 'Saldo_Cliente_id');
	}
}
