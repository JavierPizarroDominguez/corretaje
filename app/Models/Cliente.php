<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cliente
 * 
 * @property int $id
 * @property string $nombre
 * @property Carbon $fecha_creacion
 * @property string|null $rut
 * @property string|null $email
 * @property string|null $ocupacion
 * @property int|null $Nacionalidad_id
 * @property string|null $estado_civil
 * 
 * @property Nacionalidad|null $nacionalidad
 * @property Collection|CuentaBancarium[] $cuenta_bancaria
 * @property Collection|DestinoTransaccion[] $destino_transaccions
 * @property Collection|OrigenTransaccion[] $origen_transaccions
 * @property Collection|ParticipanteCobro[] $participante_cobros
 * @property Collection|ParticipanteContrato[] $participante_contratos
 * @property Collection|Propiedad[] $propiedads
 * @property Collection|SaldoCliente[] $saldo_clientes
 * @property Collection|Telefono[] $telefonos
 *
 * @package App\Models
 */
class Cliente extends Model
{
	protected $table = 'Cliente';
	public $timestamps = false;

	protected $casts = [
		'fecha_creacion' => 'datetime',
		'Nacionalidad_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'fecha_creacion',
		'rut',
		'email',
		'ocupacion',
		'Nacionalidad_id',
		'estado_civil'
	];

	public function nacionalidad()
	{
		return $this->belongsTo(Nacionalidad::class, 'Nacionalidad_id');
	}

	public function cuenta_bancaria()
	{
		return $this->hasMany(CuentaBancarium::class, 'Cliente_id');
	}

	public function destino_transaccions()
	{
		return $this->hasMany(DestinoTransaccion::class, 'Cliente_id');
	}

	public function origen_transaccions()
	{
		return $this->hasMany(OrigenTransaccion::class, 'Cliente_id');
	}

	public function participante_cobros()
	{
		return $this->hasMany(ParticipanteCobro::class, 'Cliente_id');
	}

	public function participante_contratos()
	{
		return $this->hasMany(ParticipanteContrato::class, 'Cliente_id');
	}

	public function propiedades()
	{
		return $this->hasMany(Propiedad::class, 'propietario');
	}

	public function saldo_clientes()
	{
		return $this->hasMany(SaldoCliente::class, 'Cliente_id');
	}

	public function telefonos()
	{
		return $this->belongsToMany(Telefono::class, 'telefono_cliente', 'Cliente_id', 'Telefono_id');
	}
}
