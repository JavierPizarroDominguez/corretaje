<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ClausulaContrato
 * 
 * @property int $Contrato_id
 * @property int $Clausula_id
 * 
 * @property Contrato $contrato
 * @property Clausula $clausula
 *
 * @package App\Models
 */
class ClausulaContrato extends Model
{
	protected $table = 'clausula_contrato';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Contrato_id' => 'int',
		'Clausula_id' => 'int'
	];

	public function contrato()
	{
		return $this->belongsTo(Contrato::class, 'Contrato_id');
	}

	public function clausula()
	{
		return $this->belongsTo(Clausula::class, 'Clausula_id');
	}
}
