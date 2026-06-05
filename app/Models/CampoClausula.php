<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CampoClausula
 * 
 * @property int $id
 * @property string $nombre
 * @property int $Clausula_id
 * 
 * @property Clausula $clausula
 * @property Collection|ValorCampoClausula[] $valor_campo_clausulas
 *
 * @package App\Models
 */
class CampoClausula extends Model
{
	protected $table = 'Campo_Clausula';
	public $timestamps = false;

	protected $casts = [
		'Clausula_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'Clausula_id'
	];

	public function clausula()
	{
		return $this->belongsTo(Clausula::class, 'Clausula_id');
	}

	public function valor_campo_clausulas()
	{
		return $this->hasMany(ValorCampoClausula::class, 'Campo_Clausula_id');
	}
}
