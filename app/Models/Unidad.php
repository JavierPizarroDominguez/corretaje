<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Unidad
 * 
 * @property int $id
 * @property string|null $nombre
 * @property int $Propiedad_id
 * 
 * @property Propiedad $propiedad
 * @property Collection|Cobro[] $cobros
 * @property Collection|Contrato[] $contratos
 *
 * @package App\Models
 */
class Unidad extends Model
{
	protected $table = 'Unidad';
	public $timestamps = false;

	protected $casts = [
		'Propiedad_id' => 'int'
	];

	protected $fillable = [
		'nombre',
		'Propiedad_id'
	];

	public function contratoVigente()
	{
		return $this->hasOne(Contrato::class)
			->where(function ($query) {
				$query->where('fecha_termino', '>', now())
					->orWhereNull('fecha_termino');
			});
	}

	public function propiedad()
	{
		return $this->belongsTo(Propiedad::class, 'Propiedad_id');
	}

	public function cobros()
	{
		return $this->hasMany(Cobro::class, 'Unidad_id');
	}

	public function contratos()
	{
		return $this->hasMany(Contrato::class, 'Unidad_id');
	}
}
