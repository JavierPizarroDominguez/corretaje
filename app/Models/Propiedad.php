<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Propiedad
 * 
 * @property int $id
 * @property string $direccion
 * @property int $propietario
 * 
 * @property Cliente $cliente
 * @property Collection|Cobro[] $cobros
 * @property Collection|Servicio[] $servicios
 * @property Collection|Unidad[] $unidad
 *
 * @package App\Models
 */
class Propiedad extends Model
{
	protected $table = 'propiedad';
	public $timestamps = false;

	protected $casts = [
		'propietario' => 'int'
	];

	protected $fillable = [
		'direccion',
		'propietario'
	];

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'propietario');
	}

	public function cobros()
	{
		return $this->hasMany(Cobro::class, 'Propiedad_id');
	}

	public function servicios()
	{
		return $this->hasMany(Servicio::class, 'Propiedad_id');
	}

	public function unidad()
	{
		return $this->hasOne(Unidad::class, 'Propiedad_id');
	}

	public function propietarioCliente()
{
    return $this->belongsTo(
        \App\Models\Cliente::class,
        'propietario'
    );
}
}
