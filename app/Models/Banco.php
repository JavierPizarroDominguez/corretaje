<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Banco
 * 
 * @property int $id
 * @property string $nombre
 * 
 * @property Collection|CuentaBancarium[] $cuenta_bancaria
 *
 * @package App\Models
 */
class Banco extends Model
{
	protected $table = 'Banco';
	public $timestamps = false;

	protected $fillable = [
		'nombre'
	];

	public function cuenta_bancaria()
	{
		return $this->hasMany(CuentaBancarium::class, 'Banco_id');
	}
}
