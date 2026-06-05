<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Servicio
 * 
 * @property int $id
 * @property string $tipo
 * @property int $dia_pago
 * @property int $Propiedad_id
 * @property string $estado
 * @property string|null $numero_cliente
 * @property int|null $Empresa_id
 * @property int|null $monto_fijo
 * 
 * @property Propiedad $propiedad
 * @property Empresa|null $empresa
 * @property Collection|Cobro[] $cobros
 * @property Collection|DestinoTransaccion[] $destino_transaccions
 *
 * @package App\Models
 */
class Servicio extends Model
{
	protected $table = 'Servicio';
	public $timestamps = false;

	protected $casts = [
		'dia_pago' => 'int',
		'Propiedad_id' => 'int',
		'Empresa_id' => 'int',
		'monto_fijo' => 'int'
	];

	protected $fillable = [
		'tipo',
		'dia_pago',
		'Propiedad_id',
		'estado',
		'numero_cliente',
		'Empresa_id',
		'monto_fijo'
	];

	public function propiedad()
	{
		return $this->belongsTo(Propiedad::class, 'Propiedad_id');
	}

	public function empresa()
	{
		return $this->belongsTo(Empresa::class, 'Empresa_id');
	}

	public function cobros()
	{
		return $this->hasMany(Cobro::class, 'Servicio_id');
	}

	public function destino_transaccions()
	{
		return $this->hasMany(DestinoTransaccion::class, 'Servicio_id');
	}
}
