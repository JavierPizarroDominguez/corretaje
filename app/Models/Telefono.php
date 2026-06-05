<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Telefono
 * 
 * @property string $numero
 * @property string $codigo
 * @property string $uso
 * 
 * @property Collection|Cliente[] $clientes
 *
 * @package App\Models
 */
class Telefono extends Model
{
	protected $table = 'Telefono';
	protected $primaryKey = 'numero';
	public $incrementing = false;
	public $timestamps = false;

	protected $fillable = [
		'codigo',
		'uso'
	];

	public function clientes()
	{
		return $this->belongsToMany(Cliente::class, 'telefono_cliente', 'Telefono_id', 'Cliente_id');
	}
}
