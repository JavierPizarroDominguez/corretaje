<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Clausula
 * 
 * @property int $id
 * @property string $titulo
 * @property string $contenido
 * 
 * @property Collection|CampoClausula[] $campo_clausulas
 * @property Collection|Contrato[] $contratos
 *
 * @package App\Models
 */
class Clausula extends Model
{
	protected $table = 'Clausula';
	public $timestamps = false;

	protected $fillable = [
		'titulo',
		'contenido'
	];

	public function campo_clausulas()
	{
		return $this->hasMany(CampoClausula::class, 'Clausula_id');
	}

	public function contratos()
	{
		return $this->belongsToMany(Contrato::class, 'clausula_contrato', 'Clausula_id', 'Contrato_id');
	}
}
