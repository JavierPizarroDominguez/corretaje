<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DestinoTransaccion
 * 
 * @property int $id
 * @property string $tipo
 * @property int|null $Servicio_id
 * @property int|null $Cliente_id
 * @property int|null $Cuenta_Bancaria_id
 * 
 * @property CuentaBancarium|null $cuenta_bancarium
 * @property Servicio|null $servicio
 * @property Cliente|null $cliente
 * @property Collection|Transaccion[] $transaccions
 *
 * @package App\Models
 */
class DestinoTransaccion extends Model
{
	protected $table = 'Destino_Transaccion';
	public $timestamps = false;

	protected $casts = [
		'Servicio_id' => 'int',
		'Cliente_id' => 'int',
		'Cuenta_Bancaria_id' => 'int'
	];

	protected $fillable = [
		'tipo',
		'Servicio_id',
		'Cliente_id',
		'Cuenta_Bancaria_id'
	];

	public function cuenta_bancarium()
	{
		return $this->belongsTo(CuentaBancarium::class, 'Cuenta_Bancaria_id');
	}

	public function servicio()
	{
		return $this->belongsTo(Servicio::class, 'Servicio_id');
	}

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}

	public function transaccions()
	{
		return $this->hasMany(Transaccion::class, 'Destino_Transaccion_id');
	}
}
