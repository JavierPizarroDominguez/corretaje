<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Nacionalidad
 * 
 * @property int $id
 * @property string $nombre
 * 
 * @property Collection|Cliente[] $clientes
 *
 * @package App\Models
 */
class Nacionalidad extends Model
{
	protected $table = 'nacionalidad';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];

	public function clientes()
	{
		return $this->hasMany(Cliente::class, 'Nacionalidad_id');
	}
}
