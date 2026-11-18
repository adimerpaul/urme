<?php

namespace App\Services;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fuente única del arqueo de caja (docs/ANALISIS_CIERRES_CAJA.md §8.1).
 *
 * Cada movimiento se reparte en efectivo, QR y otros medios (transferencia,
 * tarjeta). Las ventas guardan el reparto (monto_efectivo + monto_qr = total).
 * Los movimientos antiguos sin reparto (cobros de internación y cobros de
 * pendientes desde la cuenta del paciente: monto_efectivo = monto_qr = 0) se
 * clasifican al leer por su tipo_pago, sin modificar lo guardado:
 *   QR → QR; TRANSFERENCIA o TARJETA → otros medios; lo demás → efectivo.
 *
 * Efectivo esperado = efectivo de los ingresos − gastos en efectivo. El QR y los
 * otros medios se informan aparte y no entran en la diferencia del cierre.
 */
class ArqueoCaja
{
    /** Medios que no son efectivo ni QR (solo en cobros de internación). */
    public const OTROS_MEDIOS = ['TRANSFERENCIA', 'TARJETA'];

    private const SIN_REPARTO = '(monto_efectivo = 0 AND monto_qr = 0 AND total > 0)';

    private const MEDIO = "UPPER(COALESCE(tipo_pago, ''))";

    /** Parte en efectivo de un movimiento (SQL). */
    public const SQL_EFECTIVO = 'CASE WHEN '.self::SIN_REPARTO.' THEN (CASE WHEN '.self::MEDIO." IN ('QR', 'TRANSFERENCIA', 'TARJETA') THEN 0 ELSE total END) ELSE monto_efectivo END";

    /** Parte QR de un movimiento (SQL). */
    public const SQL_QR = 'CASE WHEN '.self::SIN_REPARTO.' THEN (CASE WHEN '.self::MEDIO." = 'QR' THEN total ELSE 0 END) ELSE monto_qr END";

    /** Parte en otros medios de un movimiento (SQL). */
    public const SQL_OTROS = 'CASE WHEN '.self::SIN_REPARTO.' AND '.self::MEDIO." IN ('TRANSFERENCIA', 'TARJETA') THEN total ELSE 0 END";

    /**
     * Reparto que se guarda en un cobro nuevo según su medio de pago
     * (internaciones y cobros desde la cuenta del paciente).
     *
     * @return array{monto_efectivo: float, monto_qr: float}
     */
    public static function reparto(?string $tipoPago, float $total): array
    {
        $medio = mb_strtoupper((string) $tipoPago);

        return match (true) {
            $medio === 'QR' => ['monto_efectivo' => 0.0, 'monto_qr' => $total],
            in_array($medio, self::OTROS_MEDIOS, true) => ['monto_efectivo' => 0.0, 'monto_qr' => 0.0],
            default => ['monto_efectivo' => $total, 'monto_qr' => 0.0],
        };
    }

    /**
     * Reparto de un movimiento ya guardado (la misma regla que los fragmentos SQL).
     *
     * @return array{efectivo: float, qr: float, otros: float}
     */
    public static function repartoDe(Venta $venta): array
    {
        $total = round((float) $venta->total, 2);
        $efectivo = round((float) $venta->monto_efectivo, 2);
        $qr = round((float) $venta->monto_qr, 2);
        if ($efectivo != 0 || $qr != 0 || $total <= 0) {
            return ['efectivo' => $efectivo, 'qr' => $qr, 'otros' => 0.0];
        }
        $medio = mb_strtoupper((string) $venta->tipo_pago);

        return match (true) {
            $medio === 'QR' => ['efectivo' => 0.0, 'qr' => $total, 'otros' => 0.0],
            in_array($medio, self::OTROS_MEDIOS, true) => ['efectivo' => 0.0, 'qr' => 0.0, 'otros' => $total],
            default => ['efectivo' => $total, 'qr' => 0.0, 'otros' => 0.0],
        };
    }

    /**
     * Cifras del arqueo de un conjunto de movimientos de caja (una sola consulta).
     *
     * @param  Builder  $movimientos  Movimientos de la caja (CierreCajaController::ventasDelDia o ventasEnRango).
     */
    public static function calcular(Builder $movimientos): array
    {
        $ingreso = "tipo_movimiento = 'INGRESO'";
        $egreso = "tipo_movimiento = 'EGRESO'";
        $fila = (clone $movimientos)->toBase()->selectRaw(implode(', ', [
            "SUM(CASE WHEN $ingreso THEN total ELSE 0 END) AS ingresos_total",
            "SUM(CASE WHEN $ingreso THEN 1 ELSE 0 END) AS cantidad_ventas",
            "SUM(CASE WHEN $ingreso THEN ".self::SQL_EFECTIVO.' ELSE 0 END) AS efectivo_ingresos',
            "SUM(CASE WHEN $ingreso THEN ".self::SQL_QR.' ELSE 0 END) AS qr_total',
            "SUM(CASE WHEN $ingreso AND (".self::SQL_QR.') > 0 THEN 1 ELSE 0 END) AS qr_cantidad',
            "SUM(CASE WHEN $ingreso THEN ".self::SQL_OTROS.' ELSE 0 END) AS otros_total',
            "SUM(CASE WHEN $ingreso AND (".self::SQL_OTROS.') > 0 THEN 1 ELSE 0 END) AS otros_cantidad',
            "SUM(CASE WHEN $egreso THEN ".self::SQL_EFECTIVO.' ELSE 0 END) AS gastos_efectivo',
            "SUM(CASE WHEN $egreso THEN 1 ELSE 0 END) AS gastos_cantidad",
            "SUM(CASE WHEN $egreso THEN total ELSE 0 END) AS egresos_total",
        ]))->first();

        $monto = fn (string $campo) => round((float) ($fila->{$campo} ?? 0), 2);
        $efectivoIngresos = $monto('efectivo_ingresos');
        $gastosEfectivo = $monto('gastos_efectivo');

        return [
            'ingresos_total' => $monto('ingresos_total'),
            'cantidad_ventas' => (int) ($fila->cantidad_ventas ?? 0),
            'efectivo_ingresos' => $efectivoIngresos,
            'qr_total' => $monto('qr_total'),
            'qr_cantidad' => (int) ($fila->qr_cantidad ?? 0),
            'otros_total' => $monto('otros_total'),
            'otros_cantidad' => (int) ($fila->otros_cantidad ?? 0),
            'gastos_efectivo' => $gastosEfectivo,
            'gastos_cantidad' => (int) ($fila->gastos_cantidad ?? 0),
            'efectivo_esperado' => round($efectivoIngresos - $gastosEfectivo, 2),
            // Fórmula anterior (total − gastos): se sigue guardando como monto_sistema.
            'monto_sistema' => round($monto('ingresos_total') - $monto('egresos_total'), 2),
        ];
    }
}
