<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cobro
 * 
 * @property int $id
 * @property Carbon $fecha_cobro
 * @property string $estado
 * @property string $tipo
 * @property int|null $monto
 * @property string|null $detalle
 * @property int|null $Contrato_id
 * @property int|null $Servicio_id
 * @property int|null $Propiedad_id
 * @property int|null $Unidad_id
 * 
 * @property Contrato|null $contrato
 * @property Servicio|null $servicio
 * @property Propiedad|null $propiedad
 * @property Unidad|null $unidad
 * @property Collection|AplicacionSaldoCobro[] $aplicacion_saldo_cobros
 * @property Collection|ParticipanteCobro[] $participante_cobros
 * @property Collection|Transaccion[] $transaccions
 *
 * @package App\Models
 */
class Cobro extends Model
{
	protected $table = 'Cobro';
	public $timestamps = false;

	protected $casts = [
		'fecha_cobro' => 'datetime',
		'monto' => 'int',
		'Contrato_id' => 'int',
		'Servicio_id' => 'int',
		'Propiedad_id' => 'int',
		'Unidad_id' => 'int'
	];

	protected $fillable = [
		'fecha_cobro',
		'estado',
		'tipo',
		'monto',
		'detalle',
		'Contrato_id',
		'Servicio_id',
		'Propiedad_id',
		'Unidad_id'
	];

	public function deudor()
{
    return $this->hasOne(
        ParticipanteCobro::class,
        'Cobro_id',
        'id'
    )->where('rol', 'Deudor');
}

public function acreedor()
{
    return $this->hasOne(
        ParticipanteCobro::class,
        'Cobro_id',
        'id'
    )->where('rol', 'Acreedor');
}

	public function contrato()
	{
		return $this->belongsTo(Contrato::class, 'Contrato_id');
	}

	public function servicio()
	{
		return $this->belongsTo(Servicio::class, 'Servicio_id');
	}

	public function propiedad()
	{
		return $this->belongsTo(Propiedad::class, 'Propiedad_id');
	}

	public function unidad()
	{
		return $this->belongsTo(Unidad::class, 'Unidad_id');
	}

	public function aplicacion_saldo_cobros()
	{
		return $this->hasMany(AplicacionSaldoCobro::class, 'Cobro_id');
	}

	public function participante_cobros()
	{
		return $this->hasMany(ParticipanteCobro::class, 'Cobro_id');
	}

	public function transaccions()
	{
		return $this->belongsToMany(Transaccion::class, 'Transaccion_Cobro', 'Cobro_id', 'Transaccion_id')
					->withPivot('monto_pagado');
	}
}
