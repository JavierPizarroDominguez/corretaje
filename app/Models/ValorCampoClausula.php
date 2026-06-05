<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ValorCampoClausula
 * 
 * @property int $Contrato_id
 * @property int $Campo_Clausula_id
 * @property string $valor
 * 
 * @property Contrato $contrato
 * @property CampoClausula $campo_clausula
 *
 * @package App\Models
 */
class ValorCampoClausula extends Model
{
	protected $table = 'Valor_Campo_Clausula';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Contrato_id' => 'int',
		'Campo_Clausula_id' => 'int'
	];

	protected $fillable = [
		'valor'
	];

	public function contrato()
	{
		return $this->belongsTo(Contrato::class, 'Contrato_id');
	}

	public function campo_clausula()
	{
		return $this->belongsTo(CampoClausula::class, 'Campo_Clausula_id');
	}
}
