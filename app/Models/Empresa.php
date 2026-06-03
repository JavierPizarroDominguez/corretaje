<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Empresa
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $url_pago
 * 
 * @property Collection|Servicio[] $servicios
 *
 * @package App\Models
 */
class Empresa extends Model
{
	protected $table = 'empresa';
	public $timestamps = false;

	protected $fillable = [
		'nombre',
		'url_pago'
	];

	public function servicios()
	{
		return $this->hasMany(Servicio::class, 'Empresa_id');
	}
}
