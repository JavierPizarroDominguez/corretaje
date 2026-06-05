<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Ciudad
 * 
 * @property int $id
 * @property string $nombre
 * 
 * @property Collection|Contrato[] $contratos
 *
 * @package App\Models
 */
class Ciudad extends Model
{
	protected $table = 'Ciudad';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];

	public function contratos()
	{
		return $this->hasMany(Contrato::class, 'Ciudad_id');
	}
}
