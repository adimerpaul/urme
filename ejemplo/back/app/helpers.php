<?php

if (! function_exists('numero_a_letras')) {
    function numero_a_letras(float $monto, string $moneda = 'BOLIVIANOS'): string
    {
        $entero    = (int) floor(abs($monto));
        $decimales = (int) round((abs($monto) - $entero) * 100);

        $texto = _nletras_entero($entero);

        if ($decimales > 0) {
            return mb_strtoupper($texto, 'UTF-8') . ' ' . $moneda . ' CON ' . sprintf('%02d', $decimales) . '/100';
        }

        return mb_strtoupper($texto, 'UTF-8') . ' ' . $moneda . ' EXACTOS';
    }
}

if (! function_exists('_nletras_entero')) {
    function _nletras_entero(int $n): string
    {
        if ($n === 0) {
            return 'cero';
        }

        $unidades = [
            1 => 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
            'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis',
            'diecisiete', 'dieciocho', 'diecinueve', 'veinte',
        ];

        $veintes = [
            1 => 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco',
            'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve',
        ];

        $decenas = [
            3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa',
        ];

        $centenas = [
            1 => 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
            'seiscientos', 'setecientos', 'ochocientos', 'novecientos',
        ];

        if ($n <= 20) {
            return $unidades[$n];
        }

        if ($n < 30) {
            return $veintes[$n - 20];
        }

        if ($n < 100) {
            $d = intdiv($n, 10);
            $u = $n % 10;

            return $u === 0 ? $decenas[$d] : $decenas[$d] . ' y ' . $unidades[$u];
        }

        if ($n === 100) {
            return 'cien';
        }

        if ($n < 1_000) {
            $c     = intdiv($n, 100);
            $resto = $n % 100;

            return $centenas[$c] . ($resto > 0 ? ' ' . _nletras_entero($resto) : '');
        }

        if ($n < 2_000) {
            $resto = $n % 1_000;

            return 'mil' . ($resto > 0 ? ' ' . _nletras_entero($resto) : '');
        }

        if ($n < 1_000_000) {
            $miles = intdiv($n, 1_000);
            $resto = $n % 1_000;

            return _nletras_entero($miles) . ' mil' . ($resto > 0 ? ' ' . _nletras_entero($resto) : '');
        }

        if ($n < 2_000_000) {
            $resto = $n % 1_000_000;

            return 'un millón' . ($resto > 0 ? ' ' . _nletras_entero($resto) : '');
        }

        if ($n < 1_000_000_000) {
            $millones = intdiv($n, 1_000_000);
            $resto    = $n % 1_000_000;

            return _nletras_entero($millones) . ' millones' . ($resto > 0 ? ' ' . _nletras_entero($resto) : '');
        }

        return (string) $n;
    }
}
