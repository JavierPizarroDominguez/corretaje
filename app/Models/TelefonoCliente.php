<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TelefonoCliente
 * 
 * @property string $Telefono_id
 * @property int $Cliente_id
 * 
 * @property Telefono $telefono
 * @property Cliente $cliente
 *
 * @package App\Models
 */
class TelefonoCliente extends Model
{
	protected $table = 'telefono_cliente';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Cliente_id' => 'int'
	];

	public function telefono()
	{
		return $this->belongsTo(Telefono::class, 'Telefono_id');
	}

	public function cliente()
	{
		return $this->belongsTo(Cliente::class, 'Cliente_id');
	}
}
