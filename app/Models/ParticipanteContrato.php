<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipanteContrato extends Model
{
	protected $table = 'participante_contrato';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Cliente_id' => 'int',
		'Contrato_id' => 'int',
		'monto' => 'int'
	];

	protected $fillable = [
		'Cliente_id',
		'Contrato_id',
		'rol',
		'monto'
	];

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}

	public function contrato()
	{
		return $this->belongsTo(Contrato::class, 'Contrato_id');
	}
}
