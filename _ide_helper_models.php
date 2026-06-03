<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Class AplicacionSaldoCobro
 *
 * @property int $id
 * @property int $Saldo_Cliente_id
 * @property int $Cobro_id
 * @property int $monto
 * @property SaldoCliente $saldo_cliente
 * @property Cobro $cobro
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro query()
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro whereCobroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AplicacionSaldoCobro whereSaldoClienteId($value)
 */
	class AplicacionSaldoCobro extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Banco
 *
 * @property int $id
 * @property string $nombre
 * @property Collection|CuentaBancarium[] $cuenta_bancaria
 * @package App\Models
 * @property-read int|null $cuenta_bancaria_count
 * @method static \Illuminate\Database\Eloquent\Builder|Banco newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banco newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banco query()
 * @method static \Illuminate\Database\Eloquent\Builder|Banco whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banco whereNombre($value)
 */
	class Banco extends \Eloquent {}
}

namespace App\Models{
/**
 * Class CampoClausula
 *
 * @property int $id
 * @property string $nombre
 * @property int $Clausula_id
 * @property Clausula $clausula
 * @property Collection|ValorCampoClausula[] $valor_campo_clausulas
 * @package App\Models
 * @property-read int|null $valor_campo_clausulas_count
 * @method static \Illuminate\Database\Eloquent\Builder|CampoClausula newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CampoClausula newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CampoClausula query()
 * @method static \Illuminate\Database\Eloquent\Builder|CampoClausula whereClausulaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CampoClausula whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CampoClausula whereNombre($value)
 */
	class CampoClausula extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Ciudad
 *
 * @property int $id
 * @property string $nombre
 * @property Collection|Contrato[] $contratos
 * @package App\Models
 * @property-read int|null $contratos_count
 * @method static \Illuminate\Database\Eloquent\Builder|Ciudad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ciudad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Ciudad query()
 * @method static \Illuminate\Database\Eloquent\Builder|Ciudad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Ciudad whereNombre($value)
 */
	class Ciudad extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Clausula
 *
 * @property int $id
 * @property string $titulo
 * @property string $contenido
 * @property Collection|CampoClausula[] $campo_clausulas
 * @property Collection|Contrato[] $contratos
 * @package App\Models
 * @property-read int|null $campo_clausulas_count
 * @property-read int|null $contratos_count
 * @method static \Illuminate\Database\Eloquent\Builder|Clausula newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clausula newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clausula query()
 * @method static \Illuminate\Database\Eloquent\Builder|Clausula whereContenido($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clausula whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clausula whereTitulo($value)
 */
	class Clausula extends \Eloquent {}
}

namespace App\Models{
/**
 * Class ClausulaContrato
 *
 * @property int $Contrato_id
 * @property int $Clausula_id
 * @property Contrato $contrato
 * @property Clausula $clausula
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|ClausulaContrato newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClausulaContrato newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClausulaContrato query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClausulaContrato whereClausulaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClausulaContrato whereContratoId($value)
 */
	class ClausulaContrato extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Cliente
 *
 * @property int $id
 * @property string $nombre
 * @property Carbon $fecha_creacion
 * @property string|null $rut
 * @property string|null $email
 * @property string|null $ocupacion
 * @property int|null $Nacionalidad_id
 * @property string|null $estado_civil
 * @property Nacionalidad|null $nacionalidad
 * @property Collection|CuentaBancarium[] $cuenta_bancaria
 * @property Collection|DestinoTransaccion[] $destino_transaccions
 * @property Collection|OrigenTransaccion[] $origen_transaccions
 * @property Collection|ParticipanteCobro[] $participante_cobros
 * @property Collection|ParticipanteContrato[] $participante_contratos
 * @property Collection|Propiedad[] $propiedads
 * @property Collection|SaldoCliente[] $saldo_clientes
 * @property Collection|Telefono[] $telefonos
 * @package App\Models
 * @property-read int|null $cuenta_bancaria_count
 * @property-read int|null $destino_transaccions_count
 * @property-read int|null $origen_transaccions_count
 * @property-read int|null $participante_cobros_count
 * @property-read int|null $participante_contratos_count
 * @property-read int|null $propiedads_count
 * @property-read int|null $saldo_clientes_count
 * @property-read int|null $telefonos_count
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereEstadoCivil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereFechaCreacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereNacionalidadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereOcupacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cliente whereRut($value)
 */
	class Cliente extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Cobro
 *
 * @property int $id
 * @property Carbon $fecha_cobro
 * @property string $estado
 * @property string $tipo
 * @property int|null $monto
 * @property string|null $detalle
 * @property int|null $Contrato_id
 * @property int|null $Servicio_id
 * @property int|null $Propiedad_id
 * @property int|null $Unidad_id
 * @property Contrato|null $contrato
 * @property Servicio|null $servicio
 * @property Propiedad|null $propiedad
 * @property Unidad|null $unidad
 * @property Collection|AplicacionSaldoCobro[] $aplicacion_saldo_cobros
 * @property Collection|ParticipanteCobro[] $participante_cobros
 * @property Collection|Transaccion[] $transaccions
 * @package App\Models
 * @property-read int|null $aplicacion_saldo_cobros_count
 * @property-read int|null $participante_cobros_count
 * @property-read int|null $transaccions_count
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereContratoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereDetalle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereFechaCobro($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro wherePropiedadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cobro whereUnidadId($value)
 */
	class Cobro extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Contrato
 *
 * @property int $id
 * @property int $Unidad_id
 * @property bool $administracion
 * @property int|null $comision_inicial
 * @property int|null $garantia
 * @property int|null $renta
 * @property int|null $dia_pago
 * @property int|null $comision_mensual
 * @property Carbon|null $fecha_firma
 * @property Carbon|null $fecha_inicio
 * @property Carbon|null $fecha_termino
 * @property string|null $url_pdf
 * @property int|null $Ciudad_id
 * @property Unidad $unidad
 * @property Ciudad|null $ciudad
 * @property Collection|Clausula[] $clausulas
 * @property Collection|Cobro[] $cobros
 * @property Collection|ParticipanteContrato[] $participante_contratos
 * @property Collection|ValorCampoClausula[] $valor_campo_clausulas
 * @package App\Models
 * @property-read \App\Models\ParticipanteContrato|null $arrendador
 * @property-read \App\Models\ParticipanteContrato|null $arrendatario
 * @property-read int|null $clausulas_count
 * @property-read int|null $cobros_count
 * @property-read int|null $participante_contratos_count
 * @property-read int|null $valor_campo_clausulas_count
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato query()
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereAdministracion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereCiudadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereComisionInicial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereComisionMensual($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereDiaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereFechaFirma($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereFechaInicio($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereFechaTermino($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereGarantia($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereRenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereUnidadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Contrato whereUrlPdf($value)
 */
	class Contrato extends \Eloquent {}
}

namespace App\Models{
/**
 * Class CuentaBancarium
 *
 * @property int $id
 * @property string $numero_cuenta
 * @property int $Cliente_id
 * @property int $Banco_id
 * @property string $tipo
 * @property Cliente $cliente
 * @property Banco $banco
 * @property Collection|DestinoTransaccion[] $destino_transaccions
 * @property Collection|OrigenTransaccion[] $origen_transaccions
 * @package App\Models
 * @property-read int|null $destino_transaccions_count
 * @property-read int|null $origen_transaccions_count
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium query()
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium whereBancoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium whereNumeroCuenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CuentaBancarium whereTipo($value)
 */
	class CuentaBancarium extends \Eloquent {}
}

namespace App\Models{
/**
 * Class DestinoTransaccion
 *
 * @property int $id
 * @property string $tipo
 * @property int|null $Servicio_id
 * @property int|null $Cliente_id
 * @property int|null $Cuenta_Bancaria_id
 * @property CuentaBancarium|null $cuenta_bancarium
 * @property Servicio|null $servicio
 * @property Cliente|null $cliente
 * @property Collection|Transaccion[] $transaccions
 * @package App\Models
 * @property-read int|null $transaccions_count
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion query()
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion whereServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DestinoTransaccion whereTipo($value)
 */
	class DestinoTransaccion extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Empresa
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $url_pago
 * @property Collection|Servicio[] $servicios
 * @package App\Models
 * @property-read int|null $servicios_count
 * @method static \Illuminate\Database\Eloquent\Builder|Empresa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Empresa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Empresa query()
 * @method static \Illuminate\Database\Eloquent\Builder|Empresa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Empresa whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Empresa whereUrlPago($value)
 */
	class Empresa extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Nacionalidad
 *
 * @property int $id
 * @property string $nombre
 * @property Collection|Cliente[] $clientes
 * @package App\Models
 * @property-read int|null $clientes_count
 * @method static \Illuminate\Database\Eloquent\Builder|Nacionalidad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Nacionalidad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Nacionalidad query()
 * @method static \Illuminate\Database\Eloquent\Builder|Nacionalidad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Nacionalidad whereNombre($value)
 */
	class Nacionalidad extends \Eloquent {}
}

namespace App\Models{
/**
 * Class OrigenTransaccion
 *
 * @property int $id
 * @property string $tipo
 * @property int $Cliente_id
 * @property int|null $Cuenta_Bancaria_id
 * @property CuentaBancarium|null $cuenta_bancarium
 * @property Cliente $cliente
 * @property Collection|Transaccion[] $transaccions
 * @package App\Models
 * @property-read int|null $transaccions_count
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion whereCuentaBancariaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrigenTransaccion whereTipo($value)
 */
	class OrigenTransaccion extends \Eloquent {}
}

namespace App\Models{
/**
 * Class ParticipanteCobro
 *
 * @property int $Cliente_id
 * @property int $Cobro_id
 * @property int|null $monto
 * @property string $rol
 * @property Cliente $cliente
 * @property Cobro $cobro
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro query()
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro whereCobroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteCobro whereRol($value)
 */
	class ParticipanteCobro extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $Cliente_id
 * @property int $Contrato_id
 * @property string $rol
 * @property int|null $monto
 * @property-read \App\Models\Cliente $cliente
 * @property-read \App\Models\Contrato $contrato
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato query()
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato whereContratoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ParticipanteContrato whereRol($value)
 */
	class ParticipanteContrato extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Propiedad
 *
 * @property int $id
 * @property string $direccion
 * @property int $propietario
 * @property Cliente $cliente
 * @property Collection|Cobro[] $cobros
 * @property Collection|Servicio[] $servicios
 * @property Collection|Unidad[] $unidad
 * @package App\Models
 * @property-read int|null $cobros_count
 * @property-read int|null $servicios_count
 * @method static \Illuminate\Database\Eloquent\Builder|Propiedad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Propiedad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Propiedad query()
 * @method static \Illuminate\Database\Eloquent\Builder|Propiedad whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Propiedad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Propiedad wherePropietario($value)
 */
	class Propiedad extends \Eloquent {}
}

namespace App\Models{
/**
 * Class SaldoCliente
 *
 * @property int $Transaccion_id
 * @property int $Cliente_id
 * @property int $monto
 * @property Cliente $cliente
 * @property Transaccion $transaccion
 * @property Collection|AplicacionSaldoCobro[] $aplicacion_saldo_cobros
 * @package App\Models
 * @property-read int|null $aplicacion_saldo_cobros_count
 * @method static \Illuminate\Database\Eloquent\Builder|SaldoCliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SaldoCliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SaldoCliente query()
 * @method static \Illuminate\Database\Eloquent\Builder|SaldoCliente whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaldoCliente whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SaldoCliente whereTransaccionId($value)
 */
	class SaldoCliente extends \Eloquent {}
}

namespace App\Models{
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
 * @property Propiedad $propiedad
 * @property Empresa|null $empresa
 * @property Collection|Cobro[] $cobros
 * @property Collection|DestinoTransaccion[] $destino_transaccions
 * @package App\Models
 * @property-read int|null $cobros_count
 * @property-read int|null $destino_transaccions_count
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio query()
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereDiaPago($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereMontoFijo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereNumeroCliente($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio wherePropiedadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Servicio whereTipo($value)
 */
	class Servicio extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Telefono
 *
 * @property string $numero
 * @property string $codigo
 * @property string $uso
 * @property Collection|Cliente[] $clientes
 * @package App\Models
 * @property-read int|null $clientes_count
 * @method static \Illuminate\Database\Eloquent\Builder|Telefono newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Telefono newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Telefono query()
 * @method static \Illuminate\Database\Eloquent\Builder|Telefono whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Telefono whereNumero($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Telefono whereUso($value)
 */
	class Telefono extends \Eloquent {}
}

namespace App\Models{
/**
 * Class TelefonoCliente
 *
 * @property string $Telefono_id
 * @property int $Cliente_id
 * @property Telefono $telefono
 * @property Cliente $cliente
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|TelefonoCliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelefonoCliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelefonoCliente query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelefonoCliente whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelefonoCliente whereTelefonoId($value)
 */
	class TelefonoCliente extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Transaccion
 *
 * @property int $id
 * @property int $monto
 * @property Carbon $fecha
 * @property int $Destino_Transaccion_id
 * @property int $Origen_Transaccion_id
 * @property string|null $url_comprobante
 * @property DestinoTransaccion $destino_transaccion
 * @property OrigenTransaccion $origen_transaccion
 * @property SaldoCliente|null $saldo_cliente
 * @property Collection|Cobro[] $cobros
 * @package App\Models
 * @property-read int|null $cobros_count
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion whereDestinoTransaccionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion whereOrigenTransaccionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaccion whereUrlComprobante($value)
 */
	class Transaccion extends \Eloquent {}
}

namespace App\Models{
/**
 * Class TransaccionCobro
 *
 * @property int $Transaccion_id
 * @property int $Cobro_id
 * @property int $monto_pagado
 * @property Transaccion $transaccion
 * @property Cobro $cobro
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|TransaccionCobro newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TransaccionCobro newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TransaccionCobro query()
 * @method static \Illuminate\Database\Eloquent\Builder|TransaccionCobro whereCobroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransaccionCobro whereMontoPagado($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TransaccionCobro whereTransaccionId($value)
 */
	class TransaccionCobro extends \Eloquent {}
}

namespace App\Models{
/**
 * Class Unidad
 *
 * @property int $id
 * @property string|null $nombre
 * @property int $Propiedad_id
 * @property Propiedad $propiedad
 * @property Collection|Cobro[] $cobros
 * @property Collection|Contrato[] $contratos
 * @package App\Models
 * @property-read int|null $cobros_count
 * @property-read \App\Models\Contrato|null $contratoVigente
 * @property-read int|null $contratos_count
 * @method static \Illuminate\Database\Eloquent\Builder|Unidad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Unidad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Unidad query()
 * @method static \Illuminate\Database\Eloquent\Builder|Unidad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unidad whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unidad wherePropiedadId($value)
 */
	class Unidad extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * Class ValorCampoClausula
 *
 * @property int $Contrato_id
 * @property int $Campo_Clausula_id
 * @property string $valor
 * @property Contrato $contrato
 * @property CampoClausula $campo_clausula
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|ValorCampoClausula newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ValorCampoClausula newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ValorCampoClausula query()
 * @method static \Illuminate\Database\Eloquent\Builder|ValorCampoClausula whereCampoClausulaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValorCampoClausula whereContratoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValorCampoClausula whereValor($value)
 */
	class ValorCampoClausula extends \Eloquent {}
}

