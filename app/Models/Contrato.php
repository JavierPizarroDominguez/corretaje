<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Contrato
 * 
 * @property int $id
 * @property int $Unidad_id
 * @property bool $administracion
 * @property int|null $comision_inicial
 * @property int|null $garantia
 * @property int|null $renta
 * @property int|null $dia_pago
 * @property int|null $comision_mensual
 * @property Carbon|null $fecha_firma
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_termino
 * @property string|null $url_pdf
 * @property int|null $Ciudad_id
 * 
 * @property Unidad $unidad
 * @property Ciudad|null $ciudad
 * @property Collection|Clausula[] $clausulas
 * @property Collection|Cobro[] $cobros
 * @property Collection|ParticipanteContrato[] $participante_contratos
 * @property Collection|ValorCampoClausula[] $valor_campo_clausulas
 *
 * @package App\Models
 */
class Contrato extends Model
{
	protected $table = 'Contrato';
	public $timestamps = false;

	protected $casts = [
		'Unidad_id' => 'int',
		'administracion' => 'bool',
		'comision_inicial' => 'int',
		'garantia' => 'int',
		'renta' => 'int',
		'dia_pago' => 'int',
		'comision_mensual' => 'int',
		'fecha_firma' => 'datetime',
		'fecha_inicio' => 'datetime',
		'fecha_termino' => 'datetime',
		'Ciudad_id' => 'int'
	];

	protected $fillable = [
		'Unidad_id',
		'administracion',
		'comision_inicial',
		'garantia',
		'renta',
		'dia_pago',
		'comision_mensual',
		'fecha_firma',
		'fecha_inicio',
		'fecha_termino',
		'url_pdf',
		'Ciudad_id'
	];

	public function arrendatario()
	{
		return $this->hasOne(ParticipanteContrato::class)->where('rol', 'Arrendatario');
	}

	public function arrendador()
	{
		return $this->hasOne(ParticipanteContrato::class)->where('rol', 'Arrendador');
	}

	public function corredor()
	{
		return $this->hasOne(ParticipanteContrato::class)->where('rol', 'Corredor');
	}

	public function getEgresoArrendadorAttribute()
	{
		return $this->renta - $this->comision_mensual;
	}

	public function unidad()
	{
		return $this->belongsTo(Unidad::class, 'Unidad_id');
	}

	public function ciudad()
	{
		return $this->belongsTo(Ciudad::class, 'Ciudad_id');
	}

	public function clausulas()
	{
		return $this->belongsToMany(Clausula::class, 'Clausula_Contrato', 'Contrato_id', 'Clausula_id');
	}

	public function cobros()
	{
		return $this->hasMany(Cobro::class, 'Contrato_id');
	}

	public function participante_contratos()
	{
		return $this->hasMany(ParticipanteContrato::class, 'Contrato_id');
	}

	public function valor_campo_clausulas()
	{
		return $this->hasMany(ValorCampoClausula::class, 'Contrato_id');
	}
}
