<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AplicacionSaldoCobro
 * 
 * @property int $id
 * @property int $Saldo_Cliente_id
 * @property int $Cobro_id
 * @property int $monto
 * 
 * @property SaldoCliente $saldo_cliente
 * @property Cobro $cobro
 *
 * @package App\Models
 */
class AplicacionSaldoCobro extends Model
{
	protected $table = 'aplicacion_saldo_cobro';
	public $timestamps = false;

	protected $casts = [
		'Saldo_Cliente_id' => 'int',
		'Cobro_id' => 'int',
		'monto' => 'int'
	];

	protected $fillable = [
		'Saldo_Cliente_id',
		'Cobro_id',
		'monto'
	];

	public function saldo_cliente()
	{
		return $this->belongsTo(SaldoCliente::class, 'Saldo_Cliente_id');
	}

	public function cobro()
	{
		return $this->belongsTo(Cobro::class, 'Cobro_id');
	}
}
