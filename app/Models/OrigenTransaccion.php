<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrigenTransaccion
 * 
 * @property int $id
 * @property string $tipo
 * @property int $Cliente_id
 * @property int|null $Cuenta_Bancaria_id
 * 
 * @property CuentaBancarium|null $cuenta_bancarium
 * @property Cliente $cliente
 * @property Collection|Transaccion[] $transaccions
 *
 * @package App\Models
 */
class OrigenTransaccion extends Model
{
	protected $table = 'Origen_Transaccion';
	public $timestamps = false;

	protected $casts = [
		'Cliente_id' => 'int',
		'Cuenta_Bancaria_id' => 'int'
	];

	protected $fillable = [
		'tipo',
		'Cliente_id',
		'Cuenta_Bancaria_id'
	];

	public function cuenta_bancarium()
	{
		return $this->belongsTo(CuentaBancarium::class, 'Cuenta_Bancaria_id');
	}

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}

	public function transaccions()
	{
		return $this->hasMany(Transaccion::class, 'Origen_Transaccion_id');
	}
}
