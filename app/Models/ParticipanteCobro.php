<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ParticipanteCobro
 * 
 * @property int $Cliente_id
 * @property int $Cobro_id
 * @property int|null $monto
 * @property string $rol
 * 
 * @property Cliente $cliente
 * @property Cobro $cobro
 *
 * @package App\Models
 */
class ParticipanteCobro extends Model
{
	protected $table = 'participante_cobro';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Cliente_id' => 'int',
		'Cobro_id' => 'int',
		'monto' => 'int'
	];

	protected $fillable = [
		'Cliente_id',
		'Cobro_id',
		'monto',
		'rol'
	];

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}

	public function cobro()
	{
		return $this->belongsTo(Cobro::class, 'Cobro_id');
	}

	public function getNombreAttribute()
	{
		return $this->cliente?->nombre;
	}
}
