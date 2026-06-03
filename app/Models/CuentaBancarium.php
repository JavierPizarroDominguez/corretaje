<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CuentaBancarium
 * 
 * @property int $id
 * @property string $numero_cuenta
 * @property int $Cliente_id
 * @property int $Banco_id
 * @property string $tipo
 * 
 * @property Cliente $cliente
 * @property Banco $banco
 * @property Collection|DestinoTransaccion[] $destino_transaccions
 * @property Collection|OrigenTransaccion[] $origen_transaccions
 *
 * @package App\Models
 */
class CuentaBancarium extends Model
{
	protected $table = 'cuenta_bancaria';
	public $timestamps = false;

	protected $casts = [
		'Cliente_id' => 'int',
		'Banco_id' => 'int'
	];

	protected $fillable = [
		'numero_cuenta',
		'Cliente_id',
		'Banco_id',
		'tipo'
	];

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}

	public function banco()
	{
		return $this->belongsTo(Banco::class, 'Banco_id');
	}

	public function destino_transaccions()
	{
		return $this->hasMany(DestinoTransaccion::class, 'Cuenta_Bancaria_id');
	}

	public function origen_transaccions()
	{
		return $this->hasMany(OrigenTransaccion::class, 'Cuenta_Bancaria_id');
	}
}
