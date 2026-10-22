<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Actualización del inventario de farmacia con la lista revisada del
 * 06-10-2026 (misma lista del 28-09 con precios, nombres corregidos,
 * conteo nuevo y productos agregados al final).
 *
 * No borra ni duplica nada: cruza cada fila con lo que ya está cargado.
 *   1. Corrige nombres mal escritos en la carga anterior (se renombra el
 *      producto existente en vez de crear otro).
 *   2. Separa productos que la lista trae en dos presentaciones con precio
 *      distinto (p. ej. CEUMID ampolla Bs110 / comprimido Bs17). A cada una
 *      se le agrega la presentación al nombre comercial para distinguirlas
 *      en venta.
 *   3. Lotes que ya existen: el stock disponible queda igual a la cantidad
 *      de la lista. Lo ya vendido o dado de baja se respeta (cantidad del
 *      lote = lista + vendido + bajas), así no se descuenta dos veces.
 *   4. Lotes que no existen: entran en una compra nueva
 *      "ACTUALIZACION INVENTARIO FARMACIA 06-10-2026".
 *   5. Precio de venta: el de la lista (si un producto trae dos precios
 *      para la misma presentación, queda el mayor; cada lote guarda el suyo).
 *   6. Lotes del inventario oficial que ya no figuran en la lista
 *      (LIGALIL, INSUMED) quedan con stock disponible 0.
 *
 * Si algo no cuadra con lo esperado (p. ej. alguien renombró un producto a
 * mano antes de correrla) la migración lanza excepción y no cambia nada.
 *
 * No es reversible: para volver atrás hay que restaurar el respaldo de la BD.
 */
return new class extends Migration
{
    private const COMENTARIO_OFICIAL = 'INVENTARIO OFICIAL DE FARMACIA';

    private const COMENTARIO = 'ACTUALIZACION INVENTARIO FARMACIA 06-10-2026';

    private const FECHA = '2026-10-06 08:00:00';

    // Resultado esperado, verificado sobre una copia de la BD de producción.
    private const PRODUCTOS_NUEVOS = 54;

    private const LOTES_NUEVOS = 48;

    private const LOTES_A_CERO = 2;

    /**
     * Nombres mal escritos en la carga del 28-09 → como vienen en la lista.
     * Clave: nombre|nombre comercial|marca.
     */
    private const RENOMBRES = [
        'KETOROLACO 60MG|SIUPRADOL|INTI' => 'KETOROLACO 60MG|SUPRADOL|INTI',
        'HIERRO/VIT. C|SIDERAL|INTI' => 'HIERRO/VIT. C|SIDERAL FORTE|INTI',
        'HIEROO/VITAMINAS|SIDERAL|INTI' => 'HIEROO/VITAMINAS|SIDERAL ORO|INTI',
        'HIEROO/AC. FOLICO|SIDERAL|INTI' => 'HIEROO/AC. FOLICO|SIDERAL FOLIC|INTI',
        'BRANCOMICINA/LEVOFLOXACINA|BRANCOMICINA/LEVOFLOXACINA|QUIMFA' => 'LEVOFLOXACINA|BRANCOMICINA|QUIMFA',
        'SAL DE REHIDRATACION|DEHIDRILIT|INTI' => 'SAL DE REHIDRATACION|DEHIDROLIT|INTI',
        'AMIKACINA|BETACLOX|TERBOL' => 'AMIKACINA|AMIKACINA|TERBOL',
        'CLOXACILINA|TERBOCAINA|TERBOL' => 'CLOXACILINA|BETACLOX|TERBOL',
        'CEFOTAXIMA 1G|CAFOTAXIM|TERBOL' => 'CEFOTAXIMA 1G|CEFOTAXIM|TERBOL',
        'CLORURO DE SODIO|CLARURO DE SODIO|INTI' => 'CLORURO DE SODIO|CLORURO DE SODIO|INTI',
        'LIDOCAINA 25|LIDOCAINA 25|UNIVERSAL' => 'LIDOCAINA 2% 30ML|LIDOCAINA 2%|UNIVERSAL',
        'ENDOTRAC KID 7.0|ENDOTRAC KID 7.0|TUVREN' => 'TUBO ENDOTRAQUEAL|ENDOTRAC KID 7.0|TUVREN',
        'ENDOTRAC KID 7.5|ENDOTRAC KID 7.5|CRISTALIA' => 'TUBO ENDOTRAQUEAL 7.5|ENDOTRAC KID 7.5|CRISTALIA',
        'VENDA DE HIESO 20 CM|VENDA DE HIESO 20 CM|CREMER' => 'VENDA DE YESO 20 CM|VENDA DE YESO 20 CM|CREMER',
        'VENDA DE HIESO DE 15 CM|VENDA DE HIESO DE 15 CM|CREMER' => 'VENDA DE YESO DE 15 CM|VENDA DE YESO DE 15 CM|CREMER',
        'GENTAMISINA|SINCAGENT|LAQFAGAL' => 'GENTAMICINA|SINCAGENT|LAQFAGAL',
        'PROPINOX 15 ML|DCMULTI|INTI' => 'PROPINOX 15 ML|DEMOTIL|INTI',
        'CLORURO DE SODIO|RINFRIM|INTI' => 'CLORURO DE SODIO|RINOFRIM|INTI',
        'IBUPROFENO|1P50N|SAVAL' => 'IBUPROFENO|IBUPROFENO|SAVAL',
        'AMBROXL|MUXOL|SAVAL' => 'AMBROXOL|MUXOL|SAVAL',
        'CEFOTAXIMA|CEFOTAXIMA|IFA' => 'CEFALEXINA|CEFALEXINA|IFA',
        'METOLAZONA 5 MG|METOLAZONA 5 MG|SAE' => 'METOLAZONA 5 MG|DIURENIL 6|SAE',
        'CLOVETAZOL 0,05 MG|VETAZOL|FARMEDICA' => 'CLOVETAZOL 0,05 MG|BETAZOL|FARMEDICA',
        'SALACILATO|FLOGEATRIN|MEGALABS' => 'SALACILATO|FLOGIATRIN|MEGALABS',
        'ENDOMETACINA|FLOGEATRIN|MEGALABS' => 'ENDOMETACINA|FLOGIATRIN|MEGALABS',
        'GENTAMIZINA|SUPRACEDIN|INTI' => 'GENTAMIZINA|SUPRACOTIN|INTI',
        'TETRAMICINA|TEBRAZOL|LANSIAR' => 'TETRAMICINA|TOBRAZOL|LANSIAR',
        'MELOXCAM -PRIDINOL|FLEXIACAM RELAX|COFAR' => 'MELOXCAM -PRIDINOL|FLEXICAM RELAX|COFAR',
    ];

    /** Lotes mal escritos en la carga anterior: [producto, lote anterior, lote correcto]. */
    private const LOTES_CORREGIDOS = [
        ['GUANTES LATEX M|GUANTES LATEX M|SENSICARE', '25023114', '25023116'],
    ];

    /** Resumen de lo hecho, para revisar en la prueba. */
    public array $resumen = [];

    private int $tipoFarmaciaId;

    private $ahora;

    private ?int $compraId = null;

    private array $fabricantes = [];

    private array $unidades = [];

    public function up(): void
    {
        DB::transaction(function () {
            $this->ahora = now();
            $this->tipoFarmaciaId = (int) DB::table('tipo_productos')->where('nombre', 'FARMACIA')->value('id')
                ?: throw new RuntimeException('No existe el tipo de producto FARMACIA.');
            $this->fabricantes = DB::table('fabricantes')->pluck('id', 'nombre')->all();
            $this->unidades = DB::table('unidades')->pluck('id', 'nombre')->all();
            $this->resumen = [
                'renombrados' => 0, 'fusionados' => 0, 'lotes_corregidos' => 0,
                'productos_nuevos' => 0, 'presentaciones_separadas' => 0,
                'lotes_actualizados' => 0, 'lotes_nuevos' => 0, 'lotes_a_cero' => 0,
                'precios_cambiados' => 0,
            ];

            $this->corregirNombres();
            $this->corregirLotes();
            $this->cargarLista();

            $this->verificar('productos_nuevos', self::PRODUCTOS_NUEVOS);
            $this->verificar('lotes_nuevos', self::LOTES_NUEVOS);
            $this->verificar('lotes_a_cero', self::LOTES_A_CERO);
        });
    }

    public function down(): void
    {
        // Irreversible: los datos anteriores solo se recuperan desde el respaldo.
    }

    private function verificar(string $clave, int $esperado): void
    {
        if ($this->resumen[$clave] !== $esperado) {
            throw new RuntimeException("Inventario farmacia: se esperaban {$esperado} {$clave} y salieron {$this->resumen[$clave]}. No se aplicó ningún cambio.");
        }
    }

    // ── Correcciones de la carga anterior ─────────────────────────

    private function corregirNombres(): void
    {
        foreach (self::RENOMBRES as $anterior => $correcto) {
            $viejo = $this->buscarProducto($anterior);
            $nuevo = $this->buscarProducto($correcto);

            if ($viejo === null) {
                if ($nuevo === null) {
                    throw new RuntimeException("Inventario farmacia: no se encontró el producto {$anterior} ni {$correcto}.");
                }

                continue; // Ya estaba corregido.
            }

            [$nombre, $comercial, $marca] = $this->partes($correcto);

            if ($nuevo === null) {
                DB::table('productos')->where('id', $viejo->id)->update([
                    'nombre' => $nombre,
                    'nombre_comercial' => $comercial,
                    'marca' => $marca,
                    'updated_at' => $this->ahora,
                ]);
                $this->resumen['renombrados']++;
            } else {
                // El nombre correcto ya existe como otro producto: se juntan.
                DB::table('compra_detalles')->where('producto_id', $viejo->id)->update(['producto_id' => $nuevo->id]);
                DB::table('venta_detalles')->where('producto_id', $viejo->id)->update(['producto_id' => $nuevo->id]);
                DB::table('baja_detalles')->where('producto_id', $viejo->id)->update(['producto_id' => $nuevo->id]);
                DB::table('productos')->where('id', $viejo->id)->update(['deleted_at' => $this->ahora, 'updated_at' => $this->ahora]);
                $this->resumen['fusionados']++;
            }

            DB::table('compra_detalles')->where('producto_id', $nuevo->id ?? $viejo->id)->update(['nombre' => $nombre]);
        }
    }

    private function corregirLotes(): void
    {
        foreach (self::LOTES_CORREGIDOS as [$producto, $anterior, $correcto]) {
            $p = $this->buscarProducto($producto);

            if ($p === null) {
                throw new RuntimeException("Inventario farmacia: no se encontró el producto {$producto}.");
            }

            $this->resumen['lotes_corregidos'] += DB::table('compra_detalles')
                ->whereNull('deleted_at')
                ->where('producto_id', $p->id)
                ->where('lote', $anterior)
                ->update(['lote' => $correcto, 'updated_at' => $this->ahora]);
        }
    }

    private function buscarProducto(string $clave): ?object
    {
        [$nombre, $comercial, $marca] = $this->partes($clave);

        $encontrados = DB::table('productos')
            ->whereNull('deleted_at')
            ->where('tipo_producto_id', $this->tipoFarmaciaId)
            ->where('nombre', $nombre)
            ->where(fn ($q) => $comercial === null ? $q->whereNull('nombre_comercial')->orWhere('nombre_comercial', '') : $q->where('nombre_comercial', $comercial))
            ->where(fn ($q) => $marca === null ? $q->whereNull('marca')->orWhere('marca', '') : $q->where('marca', $marca))
            ->get();

        if ($encontrados->count() > 1) {
            throw new RuntimeException("Inventario farmacia: el producto {$clave} está repetido en la BD.");
        }

        return $encontrados->first();
    }

    /** @return array{0: string, 1: ?string, 2: ?string} */
    private function partes(string $clave): array
    {
        [$nombre, $comercial, $marca] = explode('|', $clave);

        return [$nombre, $comercial !== '' ? $comercial : null, $marca !== '' ? $marca : null];
    }

    // ── Carga de la lista ─────────────────────────────────────────

    private function cargarLista(): void
    {
        $filas = $this->filas();

        // Productos de la lista que traen varias presentaciones con precio distinto.
        $grupos = [];
        foreach ($filas as $fila) {
            $grupos[$fila['clave']]['unidades'][$fila['unidad']] = true;
            if ($fila['precio'] !== null) {
                $grupos[$fila['clave']]['precios'][(string) $fila['precio']] = true;
            }
        }
        $separar = array_filter($grupos, fn ($g) => count($g['unidades']) > 1 && count($g['precios'] ?? []) > 1);

        $existentes = DB::table('productos as p')
            ->leftJoin('unidades as u', 'u.id', '=', 'p.unidad_id')
            ->whereNull('p.deleted_at')
            ->where('p.tipo_producto_id', $this->tipoFarmaciaId)
            ->get(['p.*', 'u.nombre as unidad_nombre'])
            ->keyBy(fn ($p) => $this->clave($p->nombre, $p->nombre_comercial, $p->marca));

        $lotes = DB::table('compra_detalles')
            ->whereNull('deleted_at')
            ->whereIn('producto_id', $existentes->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('producto_id');

        $productos = []; // identidad (clave o clave|unidad) => id
        $precios = [];   // id => precios de la lista
        $usados = [];    // ids de compra_detalles ya cruzados

        foreach ($filas as $fila) {
            $existente = $existentes[$fila['clave']] ?? null;
            $dividido = isset($separar[$fila['clave']]);
            $identidad = $dividido ? $fila['clave'].'|'.$fila['unidad'] : $fila['clave'];

            if (! isset($productos[$identidad])) {
                $productos[$identidad] = $this->productoPara($fila, $existente, $dividido, array_keys($separar[$fila['clave']]['unidades'] ?? []));
            }
            $productoId = $productos[$identidad];

            if ($fila['precio'] !== null) {
                $precios[$productoId][] = $fila['precio'];
            }

            $lote = $existente ? $this->cruzarLote($lotes[$existente->id] ?? collect(), $fila, $usados) : null;

            if ($lote) {
                $usados[$lote->id] = true;
                $this->actualizarLote($lote, $fila, $productoId);
            } else {
                $this->insertarLote($fila, $productoId);
            }
        }

        foreach ($precios as $productoId => $lista) {
            $this->resumen['precios_cambiados'] += DB::table('productos')
                ->where('id', $productoId)
                ->where('precio', '<>', max($lista))
                ->update(['precio' => max($lista), 'updated_at' => $this->ahora]);
        }

        $this->dejarEnCeroLoQueFalta($usados);
    }

    /**
     * Producto al que pertenece la fila. En productos con varias presentaciones
     * el existente se queda con la suya y se crea uno nuevo para cada otra.
     */
    private function productoPara(array $fila, ?object $existente, bool $dividido, array $unidadesGrupo): int
    {
        if ($existente && ! $dividido) {
            return (int) $existente->id;
        }

        $comercial = $fila['comercial'];
        if ($dividido) {
            $comercial = ($comercial ?? $fila['nombre']).' ('.$fila['unidad'].')';
        }

        $unidadExistente = $existente ? $this->normalizarUnidad((string) $existente->unidad_nombre) : null;
        if ($existente && ! in_array($unidadExistente, $unidadesGrupo, true)) {
            $unidadExistente = $unidadesGrupo[0];
        }

        if ($existente && $unidadExistente === $fila['unidad']) {
            DB::table('productos')->where('id', $existente->id)->update([
                'nombre_comercial' => $comercial,
                'unidad_id' => $this->unidadId($fila['unidad']),
                'updated_at' => $this->ahora,
            ]);

            return (int) $existente->id;
        }

        $this->resumen['productos_nuevos']++;
        if ($existente) {
            $this->resumen['presentaciones_separadas']++;
        }

        return DB::table('productos')->insertGetId([
            'codigo' => null,
            'nombre' => $fila['nombre'],
            'nombre_comercial' => $comercial,
            'descripcion' => null,
            'marca' => $fila['marca'],
            'fabricante_id' => $this->fabricanteId($fila['marca']),
            'unidad_id' => $this->unidadId($fila['unidad']),
            'tipo_producto_id' => $this->tipoFarmaciaId,
            'precio' => 0,
            'precio_seguro' => $existente->precio_seguro ?? null,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);
    }

    /** Mismo lote y vencimiento; si no, mismo lote. Cada lote se cruza una sola vez. */
    private function cruzarLote($candidatos, array $fila, array $usados): ?object
    {
        if ($fila['lote'] === null) {
            return null;
        }

        $libres = $candidatos->filter(fn ($l) => ! isset($usados[$l->id]) && (string) $l->lote === $fila['lote']);

        return $libres->first(fn ($l) => $l->fecha_vencimiento === $fila['vencimiento']) ?? $libres->first();
    }

    private function actualizarLote(object $lote, array $fila, int $productoId): void
    {
        $cantidad = $fila['cantidad'] + $this->consumido($lote->id);

        $cambios = array_filter([
            'producto_id' => (int) $lote->producto_id !== $productoId ? $productoId : null,
            'nombre' => $lote->nombre !== $fila['nombre'] ? $fila['nombre'] : null,
            'cantidad' => (float) $lote->cantidad !== $cantidad ? $cantidad : null,
            'precio_venta' => $fila['precio'] !== null && (float) $lote->precio_venta !== $fila['precio'] ? $fila['precio'] : null,
        ], fn ($v) => $v !== null);

        if ($cambios === []) {
            return;
        }

        DB::table('compra_detalles')->where('id', $lote->id)->update($cambios + ['updated_at' => $this->ahora]);

        // Lote que pasa a la otra presentación: sus ventas y bajas lo acompañan.
        if (isset($cambios['producto_id'])) {
            DB::table('venta_detalles')->where('compra_detalle_id', $lote->id)->update(['producto_id' => $productoId]);
            DB::table('baja_detalles')->where('compra_detalle_id', $lote->id)->update(['producto_id' => $productoId]);
        }

        $this->resumen['lotes_actualizados']++;
    }

    private function insertarLote(array $fila, int $productoId): void
    {
        $this->compraId ??= DB::table('compras')->insertGetId([
            'user_id' => DB::table('users')->orderBy('id')->value('id'),
            'proveedor_id' => null,
            'fecha_hora' => self::FECHA,
            'nro_factura' => null,
            'tipo_pago' => 'EFECTIVO',
            'comentario' => self::COMENTARIO,
            'estado' => 'ACTIVO',
            'total' => 0,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);

        DB::table('compra_detalles')->insert([
            'compra_id' => $this->compraId,
            'producto_id' => $productoId,
            'nombre' => $fila['nombre'],
            'precio' => 0,
            'cantidad' => $fila['cantidad'],
            'total' => 0,
            'factor' => null,
            'precio_venta' => $fila['precio'],
            'lote' => $fila['lote'],
            'fecha_vencimiento' => $fila['vencimiento'],
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);

        $this->resumen['lotes_nuevos']++;
    }

    /** Lotes del inventario oficial que la lista ya no trae: stock disponible 0. */
    private function dejarEnCeroLoQueFalta(array $usados): void
    {
        $lotes = DB::table('compra_detalles as cd')
            ->join('compras as c', 'c.id', '=', 'cd.compra_id')
            ->join('productos as p', 'p.id', '=', 'cd.producto_id')
            ->whereNull('cd.deleted_at')
            ->where('c.comentario', self::COMENTARIO_OFICIAL)
            ->where('p.tipo_producto_id', $this->tipoFarmaciaId)
            ->whereNotIn('cd.id', array_keys($usados))
            ->get(['cd.id', 'cd.cantidad']);

        foreach ($lotes as $lote) {
            $cantidad = $this->consumido($lote->id);

            if ((float) $lote->cantidad !== $cantidad) {
                DB::table('compra_detalles')->where('id', $lote->id)->update(['cantidad' => $cantidad, 'updated_at' => $this->ahora]);
            }

            $this->resumen['lotes_a_cero']++;
        }
    }

    /** Lo ya vendido y dado de baja del lote. */
    private function consumido(int $loteId): float
    {
        return (float) DB::table('venta_detalles')->where('compra_detalle_id', $loteId)->sum('cantidad')
            + (float) DB::table('baja_detalles')->where('compra_detalle_id', $loteId)->sum('cantidad');
    }

    private function clave(string $nombre, ?string $comercial, ?string $marca): string
    {
        return mb_strtoupper(trim($nombre).'|'.trim($comercial ?? '').'|'.trim($marca ?? ''));
    }

    // ── Catálogos ─────────────────────────────────────────────────

    private function fabricanteId(?string $marca): ?int
    {
        if ($marca === null) {
            return null;
        }

        $this->fabricantes[$marca] ??= DB::table('fabricantes')->insertGetId([
            'nombre' => $marca,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);

        return (int) $this->fabricantes[$marca];
    }

    private function unidadId(?string $nombre): ?int
    {
        if ($nombre === null) {
            return null;
        }

        $this->unidades[$nombre] ??= DB::table('unidades')->insertGetId([
            'nombre' => $nombre,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);

        return (int) $this->unidades[$nombre];
    }

    /** La lista escribe la presentación en plural y con erratas; aquí se unifica. */
    private function normalizarUnidad(string $unidad): ?string
    {
        $unidad = mb_strtoupper(trim($unidad));

        if ($unidad === '') {
            return null;
        }

        $equivalencias = [
            'COMPROMIDO' => 'COMPRIMIDO',
            'COMPRIMIDOS' => 'COMPRIMIDO',
            'AMPOLLAS' => 'AMPOLLA',
            'AN' => 'AMPOLLA',
            'PIEZAS' => 'PIEZA',
            'FRASCOS' => 'FRASCO',
            'SOBRES' => 'SOBRE',
            'CREMAS' => 'CREMA',
            'JABONES' => 'JABON',
            'GOTEROS' => 'GOTERO',
            'ENVASES' => 'ENVASE',
            'CAPSULAS' => 'CAPSULA',
            'OVULOS' => 'OVULO',
            'CREMA VIGINAL' => 'CREMA VAGINAL',
        ];

        return $equivalencias[$unidad] ?? $unidad;
    }

    /**
     * La lista anota el vencimiento como "jun-28" (mes-año) y se guarda el
     * último día de ese mes. Lo que no se entiende ("SV", "7/28/6/27",
     * "24-mar", "jul-02") o viene vacío queda en null para corregirlo a mano.
     */
    private function fecha(string $vencimiento): ?string
    {
        $vencimiento = mb_strtolower(trim($vencimiento));

        if (! preg_match('/^([a-z]+)-(\d{2})$/u', $vencimiento, $partes)) {
            return null;
        }

        $meses = [
            'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'ago' => 8, 'sep' => 9, 'sept' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
        ];

        $mes = $meses[$partes[1]] ?? null;
        $anio = (int) $partes[2];

        if ($mes === null || $anio < 25 || $anio > 40) {
            return null;
        }

        $anio += 2000;

        return sprintf('%04d-%02d-%02d', $anio, $mes, (int) date('t', mktime(0, 0, 0, $mes, 1, $anio)));
    }

    // ── Lista de farmacia ─────────────────────────────────────────

    /**
     * Lista tal como la entregó farmacia, separada por "|":
     * medicamento | nombre comercial | precio (Bs) | marca | lote | vencimiento | cantidad | presentación.
     */
    private function filas(): array
    {
        $csv = <<<'CSV'
        KETOROLACO 60MG|REZITRO 60|30.00|IFA|25663|jun-28|6|AMPOLLAS
        IBUPROFENO/PSEUDOEFETINA|DIPROFEN|14.00|IFA|52573|may-29|20|COMPROMIDO
        DICLOFENACO/GRANOCOBATADINA|FLAMADIN B12|45.00|IFA|26210|feb-28|5|AMPOLLAS
        DICLOFENACO/PARACETAMOL|FLAMADIN PLIS FORTE|4.50|IFA|42584|abr-27|25|COMPROMIDO
        IBUPROFENO 400|DIPROFEN|3.00|IFA|122541|dic-27|10|COMPROMIDO
        IBUPROFENO 600|DOPROFEN|4.50|IFA|122543|dic-27|10|COMPROMIDO
        MELOXICAM/PRIDINOL|FLAMACOX RELAX|9.00|IFA|72519|jul-27|25|COMPROMIDO
        ALBENDAZOL 400|CESTODEN|21.00|IFA|725110|jul-27|5|COMPROMIDO
        CEFIXIMA|CEFABIOTIC|34.00|IFA|12517|ene-29|5|COMPROMIDO
        DICLOFENACO/VITAMINA B12|FLAMADIN B12 FORTE|99.00|IFA|26678|jun-28|3|AMPOLLAS
        DICLOFENACO/VITAMINA B12|FLAMADIN B12 FORTE|99.00|IFA|2511150|nov-27|1|AMPOLLAS
        DICLOFENACO/PARACETAMOL|FLAMADIN PLUS|5.00|IFA|122551|dic-27|190|COMPROMIDO
        IMIPEMEN/CILATADINA|CILASTAX|168.00|IFA|26216|feb-30|6|VIAL
        PARACETAMOL/PSEUDOEFEDRINA|DOLOGRIP|64.00|IFA|52577|may-27|1|JARABE
        DICLOFENACO|FLAMADIN|85.00|IFA|52577|feb-30|1|JARABE
        METAMIZOL|REDUTEN|84.00|IFA|52546|may-27|1|JARABE
        ANTIGIPÀL|DOLOGRIP I|66.00|IFA|725106|may-27|3|JARABE
        CODEINA/CLORFERINAMIDA|TUSSINOL|92.00|IFA|72577|jul-27|2|JARABE
        SALBUTAMOL/AMBROXOL|BRONCOFLU|99.00|IFA|62529|jul-27|1|JARABE
        CITICOLINA|REGELNE 500|18.00|IFA|B-17824|jun-27|8|COMPROMIDO
        ITROCONAZOL|ZITRACON|20.00|IFA|B-48824|sep-28|16|COMPROMIDO
        CARBAMACEPINA|FARMAZEPIM|2.00|IFA|B-24624|7/28/6/27|29|COMPROMIDO
        PIROXICAM/CARISOPROBOL|RELAXICAM|4.00|IFA|B-23524|oct-28|95|COMPROMIDO
        INDOMETACINA100|INDOFAR|4.00|IFA|C-06824|oct-26|30|COMPROMIDO
        NIMODIPINO|USUPEK|2.50|IFA|B-14025|jun-27|97|COMPROMIDO
        NIMODIPINO|USUPEK|2.50|IFA|B-29525|oct-27|74|COMPROMIDO
        CITICOLINA|RECELINE|309.00|IFA|A-05024|jul-28|3|JARABE
        AZITROMICINA|BATAZIM|88.00|AMFAR|AZI250625|jun-28|3|JARABE
        LOZARTAN|LOSARTAN|1.50|IFA|21111224|nov-27|21|COMPROMIDO
        ATORVASTATINA 20|ATORVASTATINA|2.00|DISMEDIN|241075|oct-27|96|COMPROMIDO
        PARACETAMOL|PARACETAMOL|0.35|UNIVERSAL|230628|jun-28|130|COMPROMIDO
        QUETIAPINA 100|QUETIAPIN|16.00|CATEDRAL|20551|jul-27|21|COMPROMIDO
        CLORIXINATO DE LISINA/PROPINOX|VIADIL COMPUESTO|8.00|MEGALABS|10356|may-27|20|COMPROMIDO
        LEVETIRAZETAM|CEUMID|110.00|MEGALABS|11696|jun-28|10|AMPOLLAS
        LEVETIRAZETAM|CEUMID|17.00|MEGALABS|10356|sep-28|55|COMPROMIDO
        BISOPROL FOMARATO|CORNTEL|6.00|MEGALABS|11696|oct-26|1|AMPOLLAS
        BACILLUS CLAUSSIN|DEFLORA|21.00|MEGALABS|12792|may-27|20|FRASCO
        PIROXICAM/VIT. B6B12|FLOGIATRIN|115.00|MEGALABS|8949|jul-27|1|VIAL
        DAPAGLIFLOZINA|DAPAGLICINA|15.00|MEGALABS|H325001|mar-29|30|COMPROMIDO
        RUPATADINA|MEGATADINA|12.00|MEGALABS|2411964601|nov-28|20|COMPROMIDO
        ACIDO TRANEXAMICO 250|RIXAM 250|75.00|MEGALABS|13014|sep-28|6|AMPOLLAS
        ACIDO TRANEXAMICO 500|RIXAM 500|90.00|MEGALABS|12442|jun-28|0|AMPOLLAS
        ACIDO TRANEXAMICO 1000|RIXAM 1000|220.00|MEGALABS|11544|feb-28|9|AMPOLLAS
        HEDERA HELIX|ABRILAR|102.00|MEGALABS|10925|mar-28|8|JARABE
        CLORURO DE SODIO|NASOXY|245.00|MEGALABS|25CO99B|ago-27|2|AEROSOL
        CARBOXIMETILCISTEINA/DEXTROMETOFANO|TUSILEXIL D|125.00|MEGALABS|23292|may-27|1|JARABE
        NEOSTIGMINA|NEOSTEGMINA|15.00|INTI|37810|feb-31|62|AMPOLLAS
        ADRENALINA|ADRENALINA|15.00|INTI|36588|oct-27|24|AMPOLLAS
        PARACETAMOL/CAFEINA/ASA|BIOELECTRO|3.50|INTI|36588|may-28|104|COMPROMIDO
        ACIDO ACETIL SALICILICO|ASA|2.00|INTI|2034335|mar-29|240|COMPROMIDO
        COMPLEJO B|COMPLEJO B VIMIN|1.50|INTI|34359|feb-27|99|COMPROMIDO
        VITAMINA B1|B VIMIN|3.50|INTI|38785|jun-28|20|COMPROMIDO
        ACIDO FOLICO/VIT. C|CARDIOVIMIN|2.00|INTI|20947|oct-28|24|COMPROMIDO
        LEVOTIROXINA 50|EUTIROX|1.50|INTI|3611|nov-26|20|COMPROMIDO
        LEVOTIROXINA 100|EUTIROX|2.50|INTI|M455526|nov-26|50|COMPROMIDO
        PROPINOX|DEMOTIL|4.00|INTI|M44214|oct-28|1|AMPOLLAS
        KETOROLACO 60MG|SUPRADOL|25.00|INTI|25333|oct-27|1|AMPOLLAS
        SACCHAROMYCES BAULARDII|FLORESTOR|14.00|INTI|H10236|abr-28|13|SOBRE
        MAGNESIO/VIT. C|MAGNESIOVIMIN|6.50|INTI|36870|sep-26|25|COMPROMIDO
        LACTOBACILLUS|ZOLIUM RELAX|8.00|INTI|MA421|sep-26|0|COMPROMIDO
        HIERRO/VIT. C|SIDERAL FORTE|13.00|INTI|M40367|sep-26|20|COMPROMIDO
        HIEROO/VITAMINAS|SIDERAL ORO|11.00|INTI|3094|sep-26|5|SOBRE
        HIEROO/AC. FOLICO|SIDERAL FOLIC|15.00|INTI|3034|sep-26|10|SOBRE
        METOCLOPRAMIDA|METOCLOPRAMIDA|2.00|INTI|30597|sep-26|98|COMPROMIDO
        ACIDO DEHIDROCOLICO|BILISAN|3.50|INTI|15415|nov-26|80|COMPROMIDO
        HIDROCORTIZONA|HIDROCLORT|8.00|LAQFAGAL|36713|jul-27|82|COMPROMIDO
        REROMETRINA|ERGO 0.2|6.50|LAQFAGAL|2116|jun-27|94|COMPROMIDO
        ATORVASTATINA 20|ATROVARD|2.00|LAQFAGAL|28027|abr-28|15|COMPROMIDO
        ACICLOVIR|ACYCLO|6.00|LAQFAGAL|14406|feb-28|170|COMPROMIDO
        SINDELAFIL|SUPER ERECTRIX|6.00|LAQFAGAL|24611|oct-27|7|COMPROMIDO
        ESPIRNOLACOTNA 100|VARDARTONE|4.50|LAQFAGAL|24418|jul-27|3|COMPROMIDO
        ESOIRONOLACTONA 25|VARDARTONE|2.00|LAQFAGAL|19317|ago-28|24|COMPROMIDO
        FUROSEMINA 40|LASIVARD|1.00|LAQFAGAL|T28820|dic-27|100|COMPROMIDO
        ENALAPRIL 10|ENAPRIL|0.50|LAQFAGAL|T25052|jul-27|100|COMPROMIDO
        PROPANOLOL|VARVANOL|1.00|LAQFAGAL|CO3414|jul-27|100|COMPROMIDO
        DICLOXACILINA|DOXY|2.00|LAQFAGAL|DLP5008|ago-28|100|COMPROMIDO
        ERGOMETRINA|ERGO|26.00|LAQFAGAL|TP11025|abr-27|18|AMPOLLAS
        METILPREDNISOLONA|METILPREOGAL|212.00|LAQFAGAL|2512222|mar-28|1|VIAL
        FENITOINA|FENITOGAL|25.00|LAQFAGAL|125051|dic-28|24|AMPOLLAS
        OXITOCINA|OXITOXINA|6.00|LAQFAGAL|250580|abr-28|14|AMPOLLAS
        FENOFIBRATO|GALFIBRATO|8.00|LAQFAGAL|UT25004F|may-28|30|COMPROMIDO
        BITAMINA B1|TIAMIGAL|8.00|LAQFAGAL|20T24001|may-28|10|AN
        LEVOFLOXACINA|BRANCOMICINA|18.00|QUIMFA|241071|mar-27|3|COMPROMIDO
        FORTINIL/CITICOLINA|FORTINIL/ CITICOLINA|102.00|QUIMFA|251625|may-27|2|AMPOLLAS
        FORTINIL/CITICOLINA|FORTINIL/ CITICOLINA|102.00|QUIMFA|251625|oct-27|5|AMPOLLAS
        FORTINIL/CITICOLINA|FORTINIL/ CITICOLINA|18.00|QUIMFA|250477|jul-29|30|COMPROMIDO
        METOCLOPRAMIDA|ADECUAN|29.00|SIGMA|254098|ene-27|7|AMPOLLAS
        TAMSULAMINA|FLOW 0.4|12.00|SIGMA|540725|feb-29|30|COMPROMIDO
        NITROFURANTOINA|UVAMIN RETARD|6.00|SIGMA|1030125|feb-27|9|COMPROMIDO
        MELOXICAM|BENFLOGIM|8.00|QUIMFA|80226|sep-27|6|COMPROMIDO
        PIRACETAM|NOPIRAM|65.00|SIGMA|610925|oct-27|9|AMPOLLAS
        SAL DE REHIDRATACION|CURADIL 90|29.00|ALCOS|16520X36|jun-27|3|FRASCO
        SAL DE REHIDRATACION|DEHIDROLIT|29.00|INTI|34136|may-30|3|FRASCO
        AMPICILINA|AMPIORIS|17.00|HANHEMANN|5616|dic-28|25|VIAL
        CEFTAZIDIMA|CEFTADIX11000|37.00|HANHEMANN|123112|jul-27|25|VIAL
        BICARBONATO DE SODIO|BICARBONATO DE SODIO|14.00|INTI|34677|dic-27|1|AMPOLLAS
        BICARBONATO DE SODIO|BICARBONATO DE SODIO|14.00|INTI|36943|dic-27|50|AMPOLLAS
        HILO VICRYL 5-0|HILO VICRYL 5-0|32.00|SUTUMED|20902911|sep-26|35|SACHET
        HILO SEDA NEGRA 3-0|HILO SEDA NEGRA 3-0|22.00|BIOLINO|202436|jul-29|19|SACHET
        HILO SEDA 2-0|HILO SEDA 2-0|22.00|BIOLINO|RL247702|nov-26|35|SACHET
        HILO SEDA NEGRA 1|HILO SEDA NEGRA 1|22.00|BIOLINO|20622055|nov-27|35|SACHET
        HILO CATGUT CROMICO 5-0|HILO CATGUT CROMICO 5-0|30.00|SUTUMED|201121032|jun-30|33|SACHET
        HOLO CATGUT CROMICO 4-0|HOLO CATGUT CROMICO 4-0|30.00|SUTUMED|20108944|nov-27|8|SACHET
        HILO CATGUT CROMICO 3-0|HILO CATGUT CROMICO 3-0|30.00|SUTUMED|21121032|ene-28|28|SACHET
        HILO CATGUT CROMICO 2-0|HILO CATGUT CROMICO 2-0|30.00|SUTUMED|20108944|jun-29|31|SACHET
        HILO CATGUT CROMICO 1|HILO CATGUT CROMICO 1|30.00|SUTUMED|203000754|nov-27|10|SACHET
        APOSITO ADESIVO|TEGRADERM|32.00|LEVKOMEDT|40942822|ene-29|5|SACHET
        MICROPORE|MICROPORE|40.00|3M MICROPORE|345MHM|mar-29|42|
        CATGUT SIMPLE 2-0|CATGUT SIMPLE 2-0|30.00|SUTUMED|203013|ene-29|15|SACHET
        CATGUT SIMPLE 1|CATGUT SIMPLE 1|30.00|SUTUMED|2022934|jun-30|22|SACHET
        HILO CATGUT SIMPLE 0|HILO CATGUT SIMPLE 0|30.00|SUTUMED|20301382|mar-27|26|SACHET
        HILO VICRYL 6-0|HILO VICRYL 6-0|32.00|SUTUMED|253611|jun-29|11|SACHET
        HILO VICRYL 4-0|HILO VICRYL 4-0|32.00|SUTUMED|IA247129|mar-27|12|SACHET
        HILO NYLON 4-0|HILO NYLON 4-0|20.00|SUTUMED|250306|jun-30|21|SACHET
        HILO VICRYL 3-0|HILO VICRYL 3-0|32.00|SUTUMED|RL247702|nov-29|23|SACHET
        HILO NYLON 2-0|HILO NYLON 2-0|20.00|SUTUMED|20606722|jun-29|6|SACHET
        HILO NYLON 1|HILO NYLON 1|20.00|SUTUMED|21203613|dic-28|12|SACHET
        CERA DE HUESO|CERA DE HUESO|60.00|SUTUMED|20626204|jun-30|12|SACHET
        TRANSPORE|TRANSPORE|42.00|3M MICROPORE|33KNJE|dic-28|20|
        MICROPORE|MICROPORE|40.00|3M MICROPORE|33KNJE|jun-29|9|
        MICROPORE|MICROPORE|40.00|3M MICROPORE|33KNJE|ago-28|0|
        MICROPORE|MICROPORE|40.00|3M MICROPORE|231121|jun-27|24|
        CINTA TRANSPARENTE|CINTA ANTIALERGICA|15.00|OPTIMED|W250340|nov-28|13|SACHET
        ABSORBENTE HEMOSTATICO|STYPCEL|550.00|MEDPRIN|508250817|nov-30|5|SACHET
        SET PARA BIOPSIA|BIOPSYSET|655.00|SALUR|51847|oct-28|1|SACHET
        BAJA LENGUAS PEDIATRICO|BAJA LENGUAS PEDIATRICO|21.00||202404|abr-29|8|CAJA
        ESPATULA DE ASA|ESPATULA DE ASA|2.00|OPTIMED|202404|abr-29|4|CAJA
        BOLSA DE ORINA PEDRIATRICO||3.00|OPTIMED|202404|abr-29|43|BOLSA
        FRASCO DE HECES|FRASCO DE HECES|4.00|OPTIMED|202404|ago-29|50|BOLSA
        BOLSA COLECTORA|BOLSA COLECTORA|10.00|OPTIMED|EH-0031|dic-26|9|BOLSA
        BOLSA COLECTORA NIPRO|BOLSA COLECTORA NIPRO|60.00|NIPRO|32519|feb-28|13|BOLSA
        LLAVE DE 3 VIA|LLAVE DE 3 VIA|12.00|HE|240805|ago-29|14|BOLSA
        LLAVE DE 3 VIAS ALARGADOR 30CM|LLAVE DE 3 VIAS ALARGADOR 30CM|15.00|HE|22302|ene-28|22|BOLSA
        LLAVE DE 3 VIAS ALARGADOR 10CM|LLAVE DE 3 VIAS ALARGADOR 10CM|12.00|HE|B24420|sep-29|86|BOLSA
        TELA ADHESIVA|TELA ADHESIVA|32.00|CREMER|24414|oct-30|14|BOLSA
        FRASCO DE ORINA|FRASCO DE ORINA|5.00|FABRIMED|4535160|abr-27|0|BOLSA
        PRESERVATIVO|PRESERVATIVO|3.00|INTENSS|250625|jun-30|160|BOLSA
        SONDA FOLEY 16|SONDA FOLEY 16|18.00|CATHETER|2511039|oct-30|33|BOLSA
        SONDA FOLEY 18|SONDA FOLEY 18|18.00|CATHETER|25A0036|ago-30|13|BOLSA
        SONDA FOLEY 10|SONDA FOLEY 10|18.00|CATHETER|25A0037|ene-29|9|BOLSA
        SONDA FOLEY 12|SONDA FOLEY 12|18.00|CATHETER|25A0038|nov-27|7|BOLSA
        SONDA FOLEY 14|SONDA FOLEY 14|18.00|CATHETER|25A0039|nov-27|3|BOLSA
        TUBO ENDOTRAQUEAL 7.5|TUBO ENDOTRAQUEAL 7.5|20.00|ENDOTRAQUEAL TUB|20240115|ene-29|4|BOLSA
        TUBO ENDOTRAQUEAL 7|TUBO ENDOTRAQUEAL 7|20.00|ENDOTRAQUEAL TUB|20250828|ene-29|13|BOLSA
        TUBO ENDOTRAQUEAL 5|TUBO ENDOTRAQUEAL 5|22.00|ENDOTRAQUEAL TUB|20250828|ago-30|10|BOLSA
        TUBO ENDOTRAQUEAL 5.5|TUBO ENDOTRAQUEAL 5.5|20.00|ENDOTRAQUEAL TUB|20250828|oct-27|10|BOLSA
        TUBO ENDOTRAQUEAL 6|TUBO ENDOTRAQUEAL 6|20.00|ENDOTRAQUEAL TUB|20250828|ene-29|9|BOLSA
        TUBO ENDOTRAQUEAL 6.5|TUBO ENDOTRAQUEAL 6.5|20.00|ENDOTRAQUEAL TUB|20250828|may-27|7|BOLSA
        TUBO ENDOTRAQUEAL 2.5|TUBO ENDOTRAQUEAL 2.5|24.00|ENDOTRAQUEAL TUB|20250828|oct-27|3|BOLSA
        TUBO ENDOTRAQUEAL 3|TUBO ENDOTRAQUEAL 3|24.00|ENDOTRAQUEAL TUB|20250828|jun-27|3|BOLSA
        TUBO ENDOTRAQUEAL 3|TUBO ENDOTRAQUEAL 3|24.00|ENDOTRAQUEAL TUB|20250828|oct-27|2|BOLSA
        VENDA DE YESO 5´´|VENDA DE YESO 5´´|93.00|KENGDA|24042102|abr-27|0|BOLSA
        SONDA NASOGASTRICA 12|SONDA NASOGASTRICA 12|12.00|FREDINATUBE|22102|may-27|34|BOLSA
        SONDA NASOGASTRICA 16|SONDA NASOGASTRICA 16|12.00|FREDINATUBE|23196|dic-30|40|BOLSA
        SONDA NASOGASTRICA 10|SONDA NASOGASTRICA 10|12.00|FREDINATUBE|2022601|may-27|37|BOLSA
        SONDA NASOGASTRICA 6|SONDA NASOGASTRICA 6|10.00|FREDINATUBE|23755133|abr-28|68|BOLSA
        SONDA NASOGASTRICA 4|SONDA NASOGASTRICA 4|10.00|FREDINATUBE|22755131|jun-27|3|SACHET
        HOILO SEDA 1|HOILO SEDA 1|22.00|SUTUMED|21005784|ago-29|1|SACHET
        HILO VICRYL 6-0|HILO VICRYL 6-0|32.00|SUTUMED|2012273|ene-28|30|SACHET
        HILO VICRYL 5-0|HILO VICRYL 5-0|32.00|SUTUMED|20262626|feb-31|36|SACHET
        HILO VICRYL 4-0|HILO VICRYL 4-0|32.00|SUTUMED|21120642|nov-27|18|SACHET
        HILO VICRYL 3-0|HILO VICRYL 3-0|32.00|SUTUMED|20472434|abr-29|29|SACHET
        HILO VICRYL 2-0|HILO VICRYL 2-0|32.00|SUTUMED|20622364|jun-29|14|SACHET
        HILO VICRYL 0|HILO VICRYL 0|32.00|SUTUMED|2093436|sep-30|55|SACHET
        HILO CATGUT SIMPLE 4-0|HILO CATGUT SIMPLE 4-0|30.00|SUTUMED|20605473|jun-28|9|SACHET
        HILO CATGUT SIMPLE 5-0|HILO CATGUT SIMPLE 5-0|30.00|SUTUMED|202300814|mar-29|7|SACHET
        HILO VICRYL 3-0|HILO VICRYL 3-0|32.00|SUTUMED|20605713|dic-28|11|SACHET
        HILO SEDA 0|HILO SEDA 0|22.00|SUTUMED|21205903|mar-27|4|SACHET
        HILO SEDA 5-0|HILO SEDA 5-0|22.00|SUTUMED|20301582|mar-27|9|SACHET
        HILO VICRYL 4-0|HILO VICRYL 4-0|32.00|SUTUMED|20605733|jun-28|5|SACHET
        HILO NYLON|HILO NYLON|20.00|SUTUMED|21255745|dic-30|17|SACHET
        AMIKACINA|AMIKACINA|20.00|TERBOL|1A24024|oct-27|67|AMPOLLAS
        CLOXACILINA|BETACLOX|27.00|TERBOL|2403305|mar-27|11|AMPOLLAS
        LIDOCAINA 2% 20ML|LIDOCAINA 2% 20ML|20.00|TERBOL|1B23014|oct-26|18|AMPOLLAS
        LIDOCAINA 2% 20ML|LIDOCAINA 2% 20ML|20.00|TERBOL|1B250065|dic-28|3|AMPOLLAS
        LIDOCAINA 2% 50ML|LIDOCAINA 2% 50ML|47.00|TERBOL|2507306|jul-27|3|AMPOLLAS
        OMEPRAZOL 40MG|OMEGAZOL|31.00|TERBOL|2504302|abr-28|0|AMPOLLAS
        CEFOTAXIMA 1G|CEFOTAXIM|18.00|TERBOL|2504302|mar-27|3|VIAL
        CEFTRIAXONA|CEXTRIAXON|30.00|TERBOL|2403304|ago-28|23|VIAL
        LIDOCANA 1%|TERBOCAINA|3.00|TERBOL|1A25010|abr-28|18|VIAL
        HIDROCORTIZONA 250MG|HIDROCORTIZONA 250MG|44.00|DISMEDIN|250469|mar-28|27|VIAL
        HIDROCORTIZONA 500MG|HIDROCORTIZONA 500MG|58.00|DISMEDIN|250318|may-28|13|VIAL
        METOCLOPRAMIDA|METOCLOPRAMIDA|9.00|DISMEDIN|250506|may-27|90|AMPOLLAS
        GENTAMICINA|GENTAMICINA|9.00|DISMEDIN|250412|sep-27|70|AMPOLLAS
        FUROSEMIDA|FUROSEMIDA|3.00|DISMEDIN|250311|oct-27|120|AMPOLLAS
        VITAMINA C|VITAMINA C|9.00|DISMEDIN|250820|sep-27|100|AMPOLLAS
        MELOXICAM|FLAMAX|20.00|TERBOL|1A24020|ene-29|25|AMPOLLAS
        BUTIL BROMURO DE HIOSINA|BUTIL BROMURO DE HIOSINA|10.00|DISMEDIN|240918|sep-29|48|AMPOLLAS
        SULFATO DE MAGNESIO|SULFATO DE MAGNESIO|10.00|INTI|34664|sep-27|0|AMPOLLAS
        SULFATO DE MAGNESIO|SULFATO DE MAGNESIO|10.00|INTI|35648|jun-27|40|AMPOLLAS
        VITAMINA C|VITAMINA C|9.00|INTI|36523|feb-28|7|AMPOLLAS
        METAMIZOL 1G|DIPIRONA|8.00|INTI|37419|ene-28|100|AMPOLLAS
        AGUA DE INYECCION|AGUA DE INYECCION|2.00|INTI|35498|ene-31|37|AMPOLLAS
        LIDOCAINA 2% 10ML|LIDOCAINA 2% 10ML|18.00|INTI|5793|oct-30|15|AMPOLLAS
        DEXAMETAZONA 8MG|DEXACOFASONA|9.00|INTI|33963|ago-30|100|AMPOLLAS
        BUPIVACAINA 0.5 10ML|BUPIVACAINA 0.5 10ML|32.00|INTI|37763|dic-28|55|AMPOLLAS
        BUPIVACAINA 0.5 4ML|BUPIVACAINA 0.5 4ML|32.00|INTI|37812|may-30|90|AMPOLLAS
        BUPIVACAINA 0.5 4ML|BUPIVACAINA 0.5 4ML|32.00|DISMEDIN|BEH105|ene-31|0|AMPOLLAS
        CEFOTAXIMA 1G|CEFOTAXIMA 1G|18.00|HANHEMANN|V01601|oct-30|50|VIAL
        CEFOTAXIMA 1G|CEFOTAXIMA 1G|18.00|HANHEMANN|V10535|ago-30|29|VIAL
        CEFAZOLINA 1G|CEFAZOHAN|21.00|HANHEMANN|V08525|ago-30|60|VIAL
        CEFTOZIDIMA|CEFODIX|30.00|HANHEMANN|V123112|dic-28|25|VIAL
        AMPICILINA|AMPICRIS|17.00|HANHEMANN|V05616|may-30|25|VIAL
        CEFTRIAXONA 1G|CEFTRIAX|31.00|IFA|26110|ene-30|25|VIAL
        PREGABALINA|PREGABALINA|10.00|COFAR|7669|sep-28|7|COMPROMIDO
        ROSUBASTATINA|ROSUVASTATINA|11.00|COFAR|B106|feb-29|28|COMPROMIDO
        AZITROMICINA|AZITROMICINA|16.00|COFAR|5484|feb-29|6|COMPROMIDO
        ACETILCISTEINA|ACETILCISTEINA|7.50|COFAR|3056|dic-27|14|SOBRE
        KETOROLACO 60MG|KETOROLAKO|11.00|COFAR|7662|nov-28|15|AMPOLLAS
        AMOXICILINA 1G|AMOXICILINA 1G|2.00|COFAR|3238|mar-27|51|COMPROMIDO
        AMOXICILINA 500MG|AMOXCILINA|1.00|COFAR|6950|may-29|1|JARABE
        IBUPROFENO 100|IBUPROFENO|28.00|COFAR|7234|jun-27|1|JARABE
        IBUPROFENO 200|IBUPROFENO|32.00|COFAR|7302|jun-28|2|JARABE
        HEDERA HELIX|BLOKTUS NATURAL|95.00|COFAR|6939|may-27|2|JARABE
        SUCRALFATO/SIMETTICONA|SUCRABONAGEL|165.00|COFAR|8514|may-28|0|JARABE
        HIDOXIDO DE ALUNIMIO Y MAGNESIO/SIMETICVONA|BONAGEL PLUS|108.00|COFAR|7754|oct-29|3|JARABE
        HIDROXIDO DE MAGNESIO Y ALUMINIO|BONAGEL|58.00|COFAR|7871|dic-30|3|JARABE
        DEXAMETASONA 8MG|CORTIMED 8|20.00|COFAR|7212|oct-28|25|AMPOLLAS
        DICLOFENACO 75/VIT. B12|DOLOCOFAMIN|107.00|COFAR|7257|sep-29|9|AMPOLLAS
        MELOXICAM/VIT. B12|FLEXICAM B12|120.00|COFAR|3987|oct-26|1|AMPOLLAS
        DIPIRONA 1G|TERADOL|22.00|COFAR|6754|mar-28|6|AMPOLLAS
        DIPIRONA 2G|TERADOL FORTE|32.00|COFAR|7275|abr-29|1|AMPOLLAS
        ACIDO TRANEXAMICO 500|NEOTREX|91.00|COFAR|7507|may-30|4|AMPOLLAS
        DICLOFENACO/PARACETAMOL|NOVADOL|5.00|COFAR|8299|ago-27|58|COMPROMIDO
        DICLOFENACO/PARACETAMOL/CAFEINA|NOVADOL MUJER|7.00|COFAR|5828|jun-28|100|COMPROMIDO
        PARACETAMOL 1G|TRASSIL|8.00|COFAR|8699|dic-27|60|SOBRE
        SIMETICONA|DIGESTOGAS|9.00|COFAR|7543|may-28|30|COMPROMIDO
        MELOXICAM/GLUCOSAMINA|DOLOFLEXICAM|20.00|COFAR|8735|ago-27|40|SOBRE
        ORNITINA/ASPARTATO|L DEXAMINO|137.00|COFAR|7269|ago-27|8|AMPOLLAS
        ORNITINA/ASPARTATO|DEXAMINO FUERTE|58.00|COFAR|5873|ago-27|55|SOBRE
        DEXKETOPROFENO/PARACETAMOL|KETOFLEX|8.00|COFAR|7474|mar-27|30|COMPROMIDO
        ETAMCILATO 500|PLATELET|35.00|COFAR|7473|ago-27|20|COMPROMIDO
        ETAMSILATO250|PLATELET|80.00|COFAR|4518|mar-27|8|AMPOLLAS
        ACETILCISTEINA|FLUIDIMED PRO|18.00|COFAR|6971|abr-27|7|SOBRE
        DOMPERIDONA/SIMETICONA|PROCIN DIGEST|12.00|COFAR|7896|dic-30|88|COMPROMIDO
        DL-METRONINA|DEXAMINO FUERTE|99.00|COFAR|7772|abr-28|36|AMPOLLAS
        OLMERSANTAN/TIAZIDA|OXAR D|15.00|COFAR|6569|ene-27|31|COMPROMIDO
        ATROPINA|ATROPINA|12.00|ALFA|1771|dic-28|33|AMPOLLAS
        CLORFERINAMINA|CLORFERINAMINA|8.00|UNIVERSAL|240957|sep-27|77|AMPOLLAS
        AMPICILINA|AMPICILINA|17.00|IFA|25750|jul-28|26|AMPOLLAS
        DEXAMETAZINA 4|DEXAMETAZINA 4|6.00|INTI|38380|mar-28|100|AMPOLLAS
        DEXAMETASONA 8MG|DEXAMETASONA 8MG|9.00|COFAR|7013|mar-28|87|AMPOLLAS
        COMPLEJO B|COMPLEJO B|6.00|MEDICAL|260227|abr-29|0|AMPOLLAS
        COMPLEJO B|COMPLEJO B|6.00|DISMEDIN|251011|feb-29|75|AMPOLLAS
        CLORURO DE SODIO|CLORURO DE SODIO|10.00|INTI|3553|oct-28|14|AMPOLLAS
        CLORURO DE SODIO|CLORURO DE SODIO|10.00|INTI|33231|sep-27|27|AMPOLLAS
        CLORURO DE POTASIO|CLORURO DE POTASIO|10.00|INTI|36868|abr-28|40|AMPOLLAS
        CLORURO DE POTASIO|CLORURO DE POTASIO|10.00|INTI|38868|abr-28|50|AMPOLLAS
        GLUCANATO DE CALCIO|GLUCANATO DE CALCIO|10.00|IFA|259127|abr-29|10|AMPOLLAS
        ATRACURIO|ATRACURIO|82.00|ALFA|ATR245|ago-27|15|AMPOLLAS
        AGUA CON LIDOCAINA 1%|AGUA CON LIDOCAINA 1%|2.50|UNIVERSAL|250249|feb-28|100|AMPOLLAS
        KETOPROFENO 100MG|KETOPROFENO 100MG|24.00|UNIVERSAL|2504307|abr-28|20|AMPOLLAS
        KETOROLACO 60MG|KETOROLACO 60MG|9.00|UNIVERSAL|PY169|ene-28|100|AMPOLLAS
        KETOROLACO 30|KETOROLACO 30|6.00|UNIVERSAL|1012602|dic-28|30|AMPOLLAS
        LIDOCAINA|LIDOCAINA|2.50|IFA|PY171|feb-28|18|AMPOLLAS
        AGUA CON LIDOCAINA 1%|AGUA CON LIDOCAINA|2.50|UNIVERSAL|10666|dic-28|7|AMPOLLAS
        KETOROLACO 60MG|DISICOL|9.00|UNIVERSAL|2510140|oct-27|56|AMPOLLAS
        KETOROLACO 60MG|CIROLAC|9.00|UNIVERSAL|250249|feb-28|0|AMPOLLAS
        KETOPROFENO 100MG|KETOPROFENO|24.00|INTI|PY169|ene-28|88|AMPOLLAS
        FLUCONAZOL 200|FLUXOL|44.00|ALCOS|18250|oct-28|23|AMPOLLAS
        LEVOFLOXACINA|LEVOFLOXACINA|68.00|ALCOS|1100006|mar-27|20|AMPOLLAS
        CIPROFLOXACINA|CIPROXAN|21.00|ALCOS|1605|oct-28|1|AMPOLLAS
        CLINDAMICINA|CLINDALCOS|38.00|ALCOS|27855|nov-28|28|AMPOLLAS
        SOL RINGER LACTATO 500ML|SOL RINGER LACTATO 500ML|18.00|INTI|210972|sep-28|288|FRASCO
        SOL RINGER NORMAL 500ML|SOL RINGER NORMAL 500ML|18.00|INTI|211810|feb-29|228|FRASCO
        SOL RINGER LACTATO 500ML|SOL RINGER LACTATO 500ML|18.00|INTI|26222|jun-29|10|FRASCO
        SOL RINGER NORMAL 500ML|SOL RINGER NORMAL 500ML|18.00|INTI|28228|feb-29|9|FRASCO
        SOL RINGER NORMAL 1000ML|SOL RINGER NORMAL 1000ML|22.00|INTI|39624|jun-31|8|FRASCO
        CEFTRIAXONA 1G|CEFTRIAX|31.00|IFA|26109|ene-30|23|VIAL
        CEFOTAXIMA 1G|IFOTAXIMA|28.00|IFA|25751|jul-29|25|VIAL
        DICLOFENACO 75|EFASIGESIC|19.00|ALFA|32410|mar-27|56|AMPOLLAS
        DICLOFENACO 75|DICLOFENACO|19.00|COFAR|6745|jun-28|2|AMPOLLAS
        FUROSEMIDA|FUROSEMIDA|6.00|FARMASHOPING|154|jun-28|20|AMPOLLAS
        GLUCOSA|GLUCOSA|10.00|ALFA|969|jun-28|17|AMPOLLAS
        HIDROCORTIZONA 100|HIDROCORTIZONA 100|34.00|UNIVERSAL|63148|sep-28|31|AMPOLLAS
        LIDOCAINA 2% 30ML|LIDOCAINA 2%|25.00|UNIVERSAL|46099|abr-28|6|AMPOLLAS
        METOCLOPRAMIDA|METOCLOPRAMIDA|9.00|UNIVERSAL|240584|may-27|10|AMPOLLAS
        VITAMINA K|VITAMINA K|10.00|ALFA|70680|jun-29|73|AMPOLLAS
        CEFTRIAXONA|CEFTRIAXONA|30.00|FARMASHOPING|25073|abr-27|40|VIAL
        METRONIDAZOL|METROGYN|26.00|ALCOS|215-5|dic-28|20|FRASCO
        SOL FISIOLOGIA 1000|SOL FISIOLOGIA 1000|20.00|INTI|37450|ene-31|90|FRASCO
        SOL FISIOLOGICA 500|SOL FISIOLOGICA 500|17.00|INTI|36555|oct-30|27|FRASCO
        SOL FUSIOLOGICA 100|SOL FUSIOLOGICA 100|14.00|INTI|33905|jun-30|48|FRASCO
        GLUCOSA 10% 500ML|GLUCOSA 10% 500ML|19.00|INTI|24387|sep-29|31|FRASCO
        GLUCOSA 5% 1000|GLUCOSA 5% 1000|30.00|INTI|37494|ene-31|54|FRASCO
        GLUCOSA 50% 500ML|GLUCOSA 50% 500ML|34.00|INTI|28195|jun-29|48|FRASCO
        GLUCOSA 50% 500ML|GLUCOSA 50% 500ML|34.00|INTI|28195|jun-29|12|FRASCO
        GLUCOSA 20% 500ML|GLUCOSA 20% 500ML|24.00|INTI|28192|nov-29|44|FRASCO
        GLUCOSA 5% 1000|GLUCOSA 5% 1000|24.00|INTI|31048|dic-28|86|FRASCO
        GLUCOSA 10% 1000ML|GLUCOSA 10% 1000ML|25.00|INTI|25673|nov-26|124|FRASCO
        GLUCOSA 10% 1000ML|GLUCOSA 10% 1000ML|25.00|INTI|32687|nov-27|60|FRASCO
        SOL RINGER NORMAL 1000ML|SOL RINGER NORMAL 1000ML|22.00|INTI|19186|oct-30|36|FRASCO
        SOL RINGER LACTTATO 1000ML|SOL RINGER LACTTATO 1000ML|24.00|INTI|36292|mar-31|7|FRASCO
        SOL RINGER LACTTATO 1000ML|SOL RINGER LACTTATO 1000ML|24.00|INTI|37972|mar-31|36|FRASCO
        OMEPRAZOL 40MG|OMEPRAZOL 40MG|18.00|NOVOPHARMA|37971|jun-28|180|VIAL
        GUANTES LATEX S|GUANTES LATEX S|75.00|SENSICARE|250608|feb-31|0|CAJA
        GUANTES LATEX M|GUANTES LATEX M|75.00|SENSICARE|25023116|feb-31|0|CAJA
        GUANTES NITRILO M|GUANTES NITRILO M|86.00|SENSICARE|226363|ago-27|5|CAJA
        GUANTES LATEX M|GUANTES LATEX M|75.00|INSUMED|20251201|dic-30|15|CAJA
        GUANTES ESTERILES 6.5|GUANTES ESTERILES 6.5|8.00|PREMIER|289|dic-28|3|CAJA
        DIMETILPOLISINOXANO100|DIPOSAN|3.00|INTI|20348|ene-28|60|COMPROMIDO
        KETOROLACO 20MG|QUETOROL|5.00|INTI|38351|mar-28|5|COMPROMIDO
        ANTIGRIPAL|ANTIGRIPAL COMPUESTO|2.00|INTI|31502|dic-27|90|COMPROMIDO
        ACICLOVIR 500|VIRUSAN|120.00|INTI|36645|nov-27|0|AMPOLLAS
        OMEPRAZOL 20MG|OMEGASTRIN|4.00|INTI|36517|oct-27|15|COMPROMIDO
        ACETILCISTEINA|MUAXTIL|14.00|INTI|34572|jul-27|20|SOBRE
        KETOROLACO 30|QUETOROL 30|6.00|INTI|35451|oct-26|5|COMPROMIDO
        DEXKETOPROFENO|DEXALIVIUM|27.00|INTI|37585|may-27|20|AMPOLLAS
        LAXANTE|LAXUAVE|18.00|INTI|30601|ene-30|6|SOBRE
        SAL DE REHIDRATACION|DEHIDROLID|13.00|INTI|33515|ene-28|10|SOBRE
        ACETILCISTEINA|MUXATIL|20.00|INTI|35617|ago-27|30|AMPOLLAS
        COMPLEJO B|NEUROTRAT FORTE|4.00|INTI|29379|abr-27|20|COMPROMIDO
        COMPLEJO B|NEUROTRAT FORTE|24.00|INTI|998|sep-27|3|AMPOLLAS
        CEFALEXIMA|TROXOLINA|195.00|INTI|833|nov-26|1|JARABE
        DIFENHIDRAMINA|LIDRAMINA|10.00|INTI|25306|may-27|16|COMPROMIDO
        AMOXICILINA/ACIDO CLAVULANICO|PENTRAX AC|19.00|INTI|1213|jul-27|16|COMPROMIDO
        LISINOPRIL|HIPOPRES|9.00|INTI|29604|mar-27|2|COMPROMIDO
        IBUPROFENO 400|MEBIDOX 400|3.00|INTI|34001|oct-27|40|COMPROMIDO
        IBUPROFENO 600|MEBIDOX 600|5.00|INTI|37861|jun-28|26|COMPROMIDO
        AMBROXOL|INTIBROXOL|76.00|INTI|34008|oct-28|9|AMPOLLAS
        SUMATRIPTAN|SUMAX|27.00|INTI|30524|jul-28|4|COMPROMIDO
        ATORVASTATINA 20|TRONIX|7.00|INTI|34696|abr-28|20|COMPROMIDO
        KETOROLACO 60MG|QUETOROL 60|20.00|INTI|39381|abr-28|14|AMPOLLAS
        KETOROLACO 30|QUETOROL 30|14.00|INTI|35048|jun-28|25|AMPOLLAS
        METIMAZOL|DYPIRETIC|15.00|INTI|ARMJW6|ago-27|30|AMPOLLAS
        IBUPROFENO 600|ACTRON|10.00|BAGO|ARMJW7|nov-27|5|COMPROMIDO
        IBUPROFENO 400|ACTRON|5.00|BAGO|ARMJW8|jun-27|5|COMPROMIDO
        DICLOFENACO 75|CLOFENAC 75|4.00|BAGO|ARMJW9|feb-30|70|COMPROMIDO
        PROPIXINATO DE LISINA|ESPASMODIOXADOL PLUS|45.00|BAGO|ARMJW10|jul-28|3|AMPOLLAS
        KETOPROFENO 100MG|TALFLEX|6.00|BAGO|ARMJW11|jun-28|15|COMPROMIDO
        KETOPROFENO/VIT. B1 B6 B12|TALFLEX B1B6B12|10.00|BAGO|ARMJW12|jul-29|86|COMPROMIDO
        PREGABALINA|PRESTAT|16.00|BAGO|ARMJW13|jul-27|10|COMPROMIDO
        AGUJA PARA BIOPSIA|AGUJA PARA BIOPSIA|530.00|HISTO|14155240|may-28|7|PIEZA
        MASCARILLA PARA NEBULIZAR ADULTO|MASCARILLA PARA NEBULIZAR ADULTO|29.00|T2V|230512|may-28|5|PIEZA
        MASCARILLA PARA NEBULIZAR PEDIATRICO|MASCARILLA PARA NEBULIZAR PEDIATRICO|21.00|T2V|250603|jul-30|5|PIEZA
        MASCARA NEBULIZADORA|MASCARA NEBULIZADORA|29.00|SUTUMED|2081063|SV|4|PIEZA
        CANULA DE OXIGENO|CANULA DE OXIGENO|15.00|SUTUMED|2304011|may-30|1|PIEZA
        ESCAPULO VAGINAL M|ESCAPULO VAGINAL M|10.00|WELLAD|12516|jul-27|1|PIEZA
        ESCAPULO VAGINAL L|ESCAPULO VAGINAL L|15.00|MADAM|20250510|dic-28|4|PIEZA
        ESCAPULO VAGINAL S|ESCAPULO VAGINAL S|10.00|ESTRERIL|225755131|oct-28|3|PIEZA
        COMPRESA NEUROQUIRURGICA|COMPRESA NEUROQUIRURGICA|130.00|SAMED|202312|SV|4|PIEZA
        ELECTRODO|ELECTRODO|5.00|PHIULIPS|251122|nov-27|1|PIEZA
        HILO VICRYL 0|HILO VICRYL 0|32.00|SUTUMED|260087|dic-30|15|PIEZA
        HILO VICRYL 0|HILO VICRYL 0|32.00|SUTUMED|247101|nov-27|30|PIEZA
        HILO VICRYL 1|HILO VICRYL 1|32.00|SUTUMED|21056314|dic-30|20|PIEZA
        HILO VICRYL 5-0|HILO VICRYL 5-0|32.00|SUTUMED|20262626|nov-29|20|PIEZA
        HILO VICRYL 1|HILO VICRYL 1|32.00|SUTUMED|20262626|oct-29|11|PIEZA
        HILO VICRYL 1|HILO VICRYL 1|32.00|SUTUMED|20262626|feb-31|15|PIEZA
        HILO VICRYL 2-0|HILO VICRYL 2-0|32.00|SUTUMED|20262626|jun-30|24|PIEZA
        HILO VICRYL 5-0|HILO VICRYL 5-0|32.00|SUTUMED|20262626|nov-29|36|PIEZA
        HILO VICRYL|HILO VICRYL|32.00|SUTUMED|20262626|nov-29|33|PIEZA
        HILO NYLON 6-0|HILO NYLON 6-0|20.00|SUTUMED|244581|nov-29|36|PIEZA
        HILO NYLN 5-0|HILO NYLN 5-0|20.00|SUTUMED|5554884|dic-30|30|PIEZA
        HILO NYLON 3-0|HILO NYLON 3-0|20.00|SUTUMED|21120492|may-31|20|PIEZA
        HILO NYLON 2|HILO NYLON 2|20.00|SUTUMED|20220404|nov-27|36|PIEZA
        HILO NYLON 4-0|HILO NYLON 4-0|20.00|SUTUMED|250306|sep-27|12|PIEZA
        HILO NYLON 4-0|HILO NYLON 4-0|20.00|SUTUMED|253613|mar-30|12|PIEZA
        HILO SEDA 1|HILO SEDA 1|22.00|SUTUMED|619264|jun-30|12|PIEZA
        HILO CATGUT CROMICO 1|HILO CATGUT CROMICO 1|30.00|SUTUMED|206192|jun-29|35|PIEZA
        MALLA MARLEX|MALLA MARLEX|440.00|HEALTHIUM|257473|oct-30|1|PIEZA
        ESPONJA HEMOSTATIC|ESPONJA HEMOSTATIC|350.00|SUTUMED|20369286|mar-29|1|PIEZA
        lancetas|lancetas|1.00|SUTUMED|755141|feb-30|4|PIEZA
        hilo seda 3-0|hilo seda 3-0|22.00|SUTUMED|20806113|ago-28|2|PIEZA
        hilo seda negro 2|hilo seda negro 2|22.00|SUTUMED|20806993|ago-28|2|PIEZA
        MANITOL 20%|MANITOL 20%|38.00|INTI|28579|jul-29|127|PIEZA
        JERINGA 10ML|JERINGA 10ML|2.00|OPTIMED|20260108|ene-31|3000|PIEZA
        JERINGA 5 ML|JERINGA 5 ML|1.00|OPTIMED|2060324|mar-31|5400|PIEZA
        JERINGA 20 ML|JERINGA 20 ML|2.00|OPTIMED|20240620|jun-29|200|PIEZA
        JERINGA 3 ML|JERINGA 3 ML|1.00|OPTIMED|221105|nov-27|500|PIEZA
        JERINGA 1 ML|JERINGA 1 ML|1.00|OPTIMED|20241019|oct-28|400|PIEZA
        JERINGA 5ML|JERINGA 5ML|1.00|OPTIMED|20240421|abr-29|50|PIEZA
        JERINGA 50ML|JERINGA 50ML|7.00|OPTIMED|240805|ago-29|25|PIEZA
        GUANTES ESTERILES 6.5|GUANTES ESTERILES 6.5|8.00|PREMIER|302|abr-29|500|PIEZA
        GUANTES ESTERILES 7|GUANTES ESTERILES 7|8.00|PREMIER|250917|sep-30|580|PIEZA
        GUANTES ESTERILES 7|GUANTES ESTERILES 7|8.00|PREMIER|289|oct-28|185|PIEZA
        GUANTES ESTERILES 7.5|GUANTES ESTERILES 7.5|8.00|PREMIER|250917|sep-30|452|PIEZA
        GUANTES ESTERILES 8|GUANTES ESTERILES 8|9.00|PREMIER|2022022|ene-28|51|PIEZA
        GORROS DESECHABLES|GORROS DESECHABLES|66.00|AMSORTRAN|110725|jul-30|100|PIEZA
        GORROS DESECHABLES|GORROS DESECHABLES|66.00|AMSORTRAN|27030008|mar-28|0|PIEZA
        SISTEMA CAAP|SISTEMA CAAP||AMSORTRAN|270532|feb-28|2|PIEZA
        VENTILADOR NEONATAL|VENTILADOR NEONATAL|380.00|AMSORTRAN|2703008|may-27|2|PIEZA
        VENTILADOR|VENTILADOR|350.00|AMSORTRAN|270532|ago-27|3|PIEZA
        DRENAJE PLEURAL 22|DRENAJE PLEURAL 22|1600.00|CRESA|30920|abr-30|2|PIEZA
        DRENAJE PLEURAL 26|DRENAJE PLEURAL 26|1600.00|CRESA|12774|feb-30|1|PIEZA
        DRENAJE PLEURAL 36|DRENAJE PLEURAL 36|1600.00|CRESA|12435|ene-29|2|PIEZA
        DRENAJE PLEURAL 34|DRENAJE PLEURAL 34|1600.00|CRESA|110008|sep-26|1|PIEZA
        DRENAJE PLEURAL 32|DRENAJE PLEURAL 32|1600.00|CRESA|11003|sep-26|1|PIEZA
        CATETER|CATETER|520.00|POLIMED|30014|ago-28|0|PIEZA
        THORAMETRIX|THORAMETRIX|680.00|BIOMETRIX|250733|jun-30|1|PIEZA
        FRASCO DE DRENAJE PLEURAL|FRASCO DE DRENAJE PLEURAL|680.00|BIOMETRIX|4530|sep-28|1|PIEZA
        DISPENSADOR ANESTESICO ADULTO|DISPENSADOR ANESTESICO ADULTO|340.00|BIOMETRIX|220606|jun-27|1|PIEZA
        DISPENSADOR ANESTESICO PEDIATRICO|DISPENSADOR ANESTESICO PEDIATRICO|380.00|TUVREN|220606|jun-27|1|PIEZA
        TUBO ENDOTRAQUEAL|ENDOTRAC KID 7.0|20.00|TUVREN|220608|jun-27|1|PIEZA
        TUBO ENDOTRAQUEAL 7.5|ENDOTRAC KID 7.5|20.00|CRISTALIA|500020009|oct-26|5|PIEZA
        CLORHIDRATO DE ESTAMONA|CLORHIDRATO DE ESTAMONA||FARMEDICAL|245|ago-27|26|PIEZA
        OCTREONTIDA 100MG|BLENTAX|165.00|FARMEDICAL|775101|ago-27|2|AMPOLLA
        CEFEPIME 1G|SUPRAPIME|163.00|FARMEDICAL|250833|ago-27|6|AMPOLLA
        HIDROXILO FERRICO|ENCIFER|80.00|FARMEDICAL|ELF8AM5011|feb-27|3|AMPOLLA
        PANTOPRAZOL 40MG-DOMPERIDONA10MG|GASTROZAC D|11.00|FARMEDICAL|1013260007|ene-28|60|COMPRIMIDOS
        NIFEDIPINO 20MG|DIPIN 20|2.00|FARMEDICAL|KT4007A|jun-27|96|COMPRIMIDOS
        EMPAGLIFLOZINA 25MG|EMPAGLYP 25|12.00|FARMEDICAL|AP250028|dic-27|25|COMPRIMIDOS
        EMPAGLIFLOZINA 10MG|EMPAGLYP 10|9.00|FARMEDICAL|AP250406|jun-27|30|COMPRIMIDOS
        ANTIGRIPAL|ALIVIOL ANTIGRIPAL|3.00|FARMEDICAL|AN5002|feb-28|120|COMPRIMIDOS
        MCT-LCT|CELEPID|455.00|FARMEDICAL|2251256|feb-27|2|FRASCOS
        ALBIMINA HUMANA 20%|ALBUMINA|983.00|FARMEDICAL|AD20F26020|feb-29|4|VIAL
        PIPERACICLINA4-TAZOBACTAM0,5|PIPEBAC T|120.00|FARMEDICAL|2604531|abr-29|3|VIAL
        OLMERSARTAM 40MG|TESIUM 40|10.00|FARMEDICAL|112550498A|jun-28|24|COMPRIMIDOS
        OLMERSARTAN 20|TENSIUM 20|9.00|FARMEDICAL|11250497A|jun-28|30|COMPRIMIDOS
        ESMEPRASOL|ESMUPS 40|13.00|FARMEDICAL|2511889|nov-27|30|COMPRIMIDOS
        DICLOFENACO 75-PARACETAMOL500|ALIVIOL PLUS|3.00|FARMEDICAL|PS4031A|abr-27|175|COMPRIMIDOS
        ACIDO TRNEXAMICO 500MG|PAUSE|50.00|FARMEDICAL|ELF805003|abr-27|1|AMPOLLA
        AMOXICILINA- ACIDO CLAVULANICO|AMOXIDIN PLUS FORTE|18.00|FARMEDICAL|2503006|mar-28|32|COMPRIMIDOS
        PREGABALINA 150MG|GANIUM|11.00|FARMEDICAL|6AB22401|mar-27|24|COMPRIMIDOS
        PREGABALONA 75|GANEUM 75|5.00|FARMEDICAL|GAB032501|abr-28|4|COMPRIMIDOS
        TRAMADOL-PARACETAOL|TAMBOL FORTE|9.00|FARMEDICAL|D02W03|nov-26|25|COMPRIMIDOS
        TRAMADOL-PARACETAOL|TAMBOL|7.00|FARMEDICAL|D0X01|may-27|20|COMPRIMIDOS
        TRAMADOL 100MG|TAMBOL|15.00|FARMEDICAL|A3R24009|abr-27|30|AMPOLLA
        LEVOCETIRIZINA 5 - MUNTELUKAST 10 MG|ARACYL PLUS|18.00|FARMEDICAL|TA2504|dic-27|10|COMPRIMIDOS
        AMOXICILINA 500 MG|AMOXIDIN|32.00|FARMEDICAL|2771123|dic-26|2|FRASCOS
        AMOXICILINA 250|AMOXIDIN|28.00|FARMEDICAL|2701223|dic-26|0|FRASCOS
        CEFIXIMA 100 MG|SITEX|130.00|FARMEDICAL|AP501|ago-27|1|FRASCOS
        CEFIXIMA 200 MG|SITE FORTE|226.00|FARMEDICAL|OV501|feb-27|1|FRASCOS
        AMOXICILINA 1 G|MOXILIN|2.00|TERBOL|2404313|abr-27|65|COMPRIMIDOS
        VITAMINCA C 2 G|VITAMINA C|3.50|TERBOL|252502|jun-27|25|SOBRES
        GENTAMICINA 280 MG|TERBOMICINA|24.00|TERBOL|1A25014|ago-28|25|AMPOLLA
        CEFOTAXIMA 1 G|CEFOLAXIM|23.00|TERBOL|2504302|abr-28|6|VIAL
        LANZOPRAZOL|LANZOPRAL|208.00|MEGALABS|899|nov-27|8|VIAL
        CLORIXNATO DE LISNA - PROPINOX|VIADIL COMPUESTO|87.00|MEGALABS|9627|feb-27|3|AMPOLLA
        SONDA NASOGASTRICA N°8|SONDA NASOGASTRICA N°8|12.00|FENDENIL|22100|mar-27|90|PIEZAS
        MICROGOTERO|MICROGOTERO|35.00|INFUSION|250801|jul-30|24|PIEZAS
        VENDA DE YESO 20 CM|VENDA DE YESO 20 CM|65.00|CREMER|80426247|nov-27|27|PIEZAS
        VENDA DE YESO DE 15 CM|VENDA DE YESO DE 15 CM|46.00|CREMER|798262347|jul-26|49|PIEZAS
        SONDA FOLEY 16|SONDA FOLEY 16|18.00|POLYMET|25143756|jun-30|50|PIEZAS
        STILO INTUBADO|STILO INTUBADO|75.00|WIILSAT|2211022333|oct-27|1|PIEZAS
        SONDA ARCOMET 16|NOLATON|120.00|NELATON|Y-121|sep-26|2|PIEZAS
        CANULA DE ASPIRACION 8|CANULA DE ASPIRACION 8|8.00|TICNOL|20230415|mar-28|16|PIEZAS
        CANULA DE ASPIRACION  6|CANULA DE ASPIRACION  6|8.00|TICNOL|757144|ene-31|11|PIEZAS
        CANULA DE ASPIRACION 12|CANULA DE ASPIRACION 12|8.00|TICNOL|20211015|oct-26|4|PIEZAS
        CANULA DE ASPIRACION 10|CANULA DE ASPIRACION 10|8.00|TICNOL|23114270|ago-26|10|PIEZAS
        CANULA DE ASPIRACION 16|CANULA DE ASPIRACION 16|8.00|TICNOL|25143756|jun-30|25|PIEZAS
        SONDA DE ASPIRACION 14|SONDA DE ASPIRACION 14|8.00|TICNOL|24121900|mar-29|36|PIEZAS
        SONDA DEASPIRACION 18|SONDA DEASPIRACION 18|8.00|TICNOL|20220715|jun-27|10|PIEZAS
        HUMIFICADOR ADULTOR|COMBIME BAT|507.00|-|20230815|ago-28|2|PIEZAS
        SET DE INFUSION|ARCOMET|120.00|SALOR|24PH903|sep-29|21|PIEZAS
        ELECTROBISTURI|ELECTROSUCCIONAL|110.00|SIMGLE|LG2025|nov-27|7|PIEZAS
        UNIDAD DE SUCCION 16|POLIVAC SET|50.00|POLYMET|4176024K|ago-29|4|PIEZAS
        VENDA DE GASA 20 CM|VENDA DE GASA 20 CM|25.00|CREMER|20220302|abr-27|88|PIEZAS
        VENDA DE YESO 10 CM|VENDA DE YESO 10 CM|34.00|CREMER|20220302|abr-27|89|PIEZAS
        VENDA DE YESO 10 CM|VENDA DE YESO 10 CM|34.00|CREMER|774262421|may-27|40|PIEZAS
        VENDA ELASTICA 15 CM|VENDA ELASTICA 15 CM|20.00|PREMIER|2023005|dic-28|22|PIEZAS
        VENDA ELASTICA 10 CM|VENDA ELASTICA 10 CM|16.00|OPTIMET|20240910|sep-29|14|PIEZAS
        VENDA ELASTICA 5 CM|VENDA ELASTICA 5 CM|10.00|OPTIMET|2024093|sep-29|25|PIEZAS
        VENDA ELASTICA CORRUGADA|VENDA ELASTICA CORRUGADA|78.00|ANHIKEN|23755133|abr-28|2|PIEZAS
        VENDA ELASTICA 20 CM|VENDA ELASTICA 20 CM|25.00|PREMIER|2023005|jun-28|11|PIEZAS
        VENDA DE GAS 10 CM|VENDA DE GAS 10 CM|13.00|OPTIMET|20250325|mar-30|38|PIEZAS
        VENDA DE GASA 15 CM|VENDA DE GASA 15 CM|18.00|PREMIER|122023|nov-28|61|PIEZAS
        VENDA DE GAS 7.5 CM|VENDA DE GAS 7.5 CM|15.00|OPTIMET|20250325|24-mar|34|PIEZAS
        VENDA DE GASA 5 CM|VENDA DE GASA 5 CM|8.00|PREMIER|20230204|jun-28|11|PIEZAS
        SONDA URETRAL 14|SONDA URETRAL 14|18.00|WALLEAD|2305011054|abr-28|9|PIEZAS
        SONDA URETRAL 16|SONDA URETRAL 16|18.00|WALLEAD|2204010562|mar-27|5|PIEZAS
        TUBO ENDOTRAQUEAL 4|TUBO ENDOTRAQUEAL 4|20.00|ENDOTRAQUEAL|20240115|ene-29|7|PIEZAS
        CLINAMICINA|CLINAMICINA|17.00|FARMASIMA|CMC2302|oct-26|15|COMPRIMIDOS
        BETAHISTINA|BETISTIN|18.00|EUROFARMA|957474|nov-26|17|COMPRIMIDOS
        CLORFENIRAMINA|SINALERG 4|5.00|SIGMA|1260924|sep-26|13|COMPRIMIDOS
        VITAMINA B 12|COBAVIMIN|28.00|INTI|24907|oct-26|8|AMPOLLA
        PROXIMETOCAINA|ANESTCARS|166.00|LANSIER|211094|nov-26|1|GOTAS
        GENTAMICINA|SINCAGENT|43.00|LAQFAGAL|B4L40|nov-26|2|GOTAS
        VITAMINA A-C-D|ACDVIMIN|84.00|INTI|32468|mar-27|1|FRASCOS
        PROPINOX 15 ML|DEMOTIL|43.00|INTI|24305|ago-27|2|GOTEROS
        PROPINOX 20 ML|DEMOTIL|58.00|INTI|33180|abr-28|2|FRASCOS
        SYLIBUM|FORFIG|14.00|EUROFARMA|939875|sep-26|24|COMPRIMIDOS
        CEFALEXINA|CEFACRIS|21.00|HANEMAN|E04418|jul-02|1|FRASCOS
        CLORURO DE SODIO|RINOFRIM|55.00|INTI|34943|jun-28|2|FRASCOS
        AMBROXOL 15 MG|BROXOL INFANTIL|66.00|INTI|33940|jun-27|2|FRASCOS
        AMBROXOL 30 MG|BROXOL|76.00|INTI|34852|jul-28|2|FRASCOS
        LEVODROPROPIZINA|TUSIBROM|156.00|BAGO|AB5M|jun-27|5|FRASCOS
        HEDERA HELIX|TOCEX|107.00|BAGO|4V2|ene-27|4|FRASCOS
        IBRUPOFENO|PIRONAL|121.00|BAGO|AAXW|abr-27|3|FRASCOS
        DIPIRONA|DIOXOADOL|92.00|BAGO|AVLS|jun-27|3|FRASCOS
        CLORFEMIDAMINA|TUSIGEN|112.00|BAGO|AAND|mar-28|1|FRASCOS
        COTRIMOXAZOL|BACTICEL FORTE|116.00|BAGO|AA3L|abr-28|2|FRASCOS
        BETAMETAZONA|CORTYPIREM|97.00|BAGO|4J5|sep-26|1|FRASCOS
        BETAMETAZONA|CORTYPIREM|97.00|BAGO|ACZY|ago-27|1|FRASCOS
        PROPINOX|ESPASMODIOXADOL|90.00|BAGO|AB9B|jun-27|2|FRASCOS
        IBUPROFENO+PSEUDOFEDRINA|PIRONAL FLUFORTE|137.00|BAGO|AF3C|mar-28|2|FRASCOS
        FLUTICASONA|FLUCOMIX|263.00|BAGO|F16970|abr-27|2|FRASCOS
        SALBUTAMOL|GEL BRONQUIAL|112.00|TERBOL|2A24017|ago-27|1|FRASCOS
        AMAXOCICILINA|AMOBAL|130.00|SAVAL|47193|mar-27|1|FRASCOS
        IBUPROFENO|IBUPROFENO|161.00|SAVAL|35103|mar-28|2|FRASCOS
        IBUPROFENO|IBUPROFENO|161.00|SAVAL|95793|sep-28|3|FRASCOS
        AMBROXOL|MUXOL|66.00|SAVAL|4035963|mar-28|1|FRASCOS
        DEXAMETASONA/CIPROFLOXACINO|CIPRODEX|222.00|SAVAL|22506|feb-29|3|PIEZAS
        RIFAMICINA 10MG|RIFAMICINA|57.00|SAVAL|252265|jun-27|7|PIEZAS
        AGUJA ESPINAL 18|INTROCAM|16.00|QUIMFA|3068912|nov-29|44|PIEZAS
        EXTENSOR 120 CM|ESTENSOFIX|32.00|INTI|23H14|ago-29|7|PIEZAS
        TAPON HEPARINIZADO PARA CATETER|STOPER|8.00|INTI|24G22A|jul-29|2|PIEZAS
        TRAINSOFIX|TRAINSOFIX|15.00|INTI|2.40E+19|may-29|2|PIEZAS
        AGUJA ESPINAL CON BISO|PERICAN|68.00|INTI|23HI36|ago-28|3|PIEZAS
        CANULA IV|INTROCAN|16.00|INTI|23G23|ago-28|39|PIEZAS
        TAPON HEPARINIZADO|STOPER|13.00|INTI|23M17|nov-28|120|PIEZAS
        PROPOFOL|PROPOFOL|120.00|INTI|251730|mar-27|3|AMPOLLA
        OLIGOALIMENTOS|TRACUTIL|100.00|INTI|25465051|oct-30|23|AMPOLLA
        AGUJA ESPINAL 27|SPINOCAM|60.00|INTI|23A18H8|ene-28|9|PIEZAS
        AGUJA ESPINAL 26|SPINOCAM|60.00|INTI|23A18H10|jun-30|5|PIEZAS
        AGUJA ESPINAL 25|SPINOCAM|60.00|INTI|23A18H11|ago-30|15|PIEZAS
        AGUJA ESPINAL 22|SPINOCAM|60.00|INTI|23A18H12|mar-28|26|PIEZAS
        CATETER UMBILICAL|CAT UMBILICAL|180.00|SILMAG|304843|may-27|2|PIEZAS
        IBUPROFENO 600|IBUPROFENO 600|220.00|INTI|24233402|jul-27|11|VIAL
        PARACETAMOL 1G|PARACETAMOL|95.00|INTI|25304453|dic-26|5|VIAL
        PARACETAMOL 1G|PARAVITAMOL|95.00|VITA|154550|jul-27|6|VIAL
        PARACETAMOL 1G|PIREBOL|95.00|TERBOL|253044503|ene-30|3|VIAL
        CATETER CENTRAL VENOSO|CETOFIX 720|780.00|INTI|1545503|may-27|4|PIEZAS
        CATETER CENTRAL VENOSO TIRA|C-CATVEN TRIO|520.00|INTI|82503|feb-30|1|PIEZAS
        CATETER CENTRAL VENOSO|CERTOFIX DUO 413|780.00|INTI|8551|jun-30|1|PIEZAS
        CATETER CENTRAL VENOSO|CERTOFIX TRIO 720|780.00|INTI|220512|abr-27|3|PIEZAS
        SIS. ARMADO DE ESPIRAL G|AVAMON|520.00|INTI|8551|abr-27|1|PIEZAS
        TUBO PENROSE 3/4|DRENES|20.00|INTI|30194017|may-28|2|PIEZAS
        SOL GELATINA SUCCIONADA 4%|GELOFUSIN|495.00|INTI|202306|abr-27|2|FRASCOS
        AMINOACIDOS Y ELECTROLITOS|AMINOPLASMAL|300.00|INTI|252217641|may-28|5|FRASCOS
        EMULSION PERFUSION 500ML|LIPOFUDIN|478.00|INTI|252678061|mar-27|4|FRASCOS
        SOL DE IRRIGACION PA HERIDAS|PENTOSAS|445.00|INTI|2515228082|jun-27|1|FRASCOS
        ALGODÓN 100G|ALGODÓN 100G|18.00|PREMIER|24303140|feb-31|14|BOLSA
        ALGODÓN 400G|ALGODÓN 400G|50.00|PREMIER|4001225|dic-30|12|BOLSA
        EQUIPO DE INYECCIO 150CM|EXADROP|102.00|INTI|25F23|jun-27|6|BOLSA
        MELOXICAM 15ML|MELOXIHAM|2.00|HAHNNEMANN|C105191|oct-30|448|COMPRIMIDO
        ESPIRONOLACTONA 100 ML|UROHAN|6.00|HAHNNEMANN|CO83156|ago-28|16|COMPRIMIDO
        IVERMECTINA|IVERMECTINA|8.00|HAHNNEMANN|CO84129|ago-27|12|COMPRIMIDO
        IBUPROFENO/ERBOTAMINA/CAFEINA|IBUMIGRAM|5.00|HAHNNEMANN|CO2626|feb-28|48|COMPRIMIDO
        FUROSEMIDA|FUROSEMIDA|0.50|HAHNNEMANN|CO1612|ene-31|500|COMPRIMIDO
        OMEPRAZOL|OMEPRAZOL|1.00|HAHNNEMANN|K02639|feb-29|205|COMPRIMIDO
        ECHINACEA|ECHINACEA|130.00|HAHNNEMANN|ZY04401|abr-28|1|SPRAY
        DICLOFENACO 50 MG|DICLOFENACO|0.50|HAHNNEMANN|CO3472|mar-29|257|COMPRIMIDO
        IBUPROFENO/PARACETAMOL|IBUFORT DUO|2.00|HAHNNEMANN|CO54111|may-27|20|COMPRIMIDO
        AZITROMICINA 1G|AZITROMICINA|18.00|HAHNNEMANN|CO84151|ago-28|10|COMPRIMIDO
        DIIMENHIDRINATO CLORHIDRATO|VOMAR|14.00|SAN FERNANDO|DH2501|dic-27|10|AMPOLLA
        TRAMADOL|TRAMADOL|15.00|SAN FERNANDO|CRJ503|abr-27|40|AMPOLLA
        DEXMEDETOMINIDINA|DEXMEDETOMINIDINA|88.00|EURO FARMA|L956061|dic-26|4|AMPOLLA
        DEXMEDETOMINIDINA|DEXMEDETOMINIDINA|88.00|EURO FARMA|L992629|jun-27|5|AMPOLLA
        ESCITALOPRAM 20MG|ETAPRAM|25.00|EURO FARMA|155611|ene-28|40|COMPRIMIDO
        KETOPROFENO 150 MG|BICERTO|8.00|EURO FARMA|977980|mar-27|7|AMPOLLA
        PARACETAMOL + CODEINA|PACO|7.00|EURO FARMA|917901|abr-27|12|COMPRIMIDO
        MELOXICAM 15ML|MELOXICAM|7.00|IFA|72523|jul-27|28|COMPRIMIDO
        IBUPROFENO 400MG|IBUPROFENO|1.00|IFA|52666|may-30|100|COMPRIMIDO
        AMLODIPINA|AMLODIPINA|2.00|IFA|42519|abr-27|16|COMPRIMIDO
        ALBENDAZOL|ALBENDAZOL|4.00|IFA|42521|abr-27|20|COMPRIMIDO
        DICLOXACILINA|DICLOXACILINA|2.50|IFA|12559|ene-27|1|FRASCO
        DEXTROMETORFANO|DEXTROMETORFANO|21.00|IFA|6530|jun-29|1|FRASCO
        DEXTROMETORFANO|DEXTROMETORFANO|21.00|IFA|6531|jun-29|2|FRASCO
        CODEINA|CODEINA|62.00|IFA|72501|jul-27|4|FRASCO
        DICLOXACILINA|DICLOXACILINA|39.00|IFA|42505|abr-29|34|COMPRIMIDO
        CEFIXIMA|CEFIXIMA|7.50|IFA|82506|ago-27|90|COMPRIMIDO
        CIPROFLOXACINO|CIPROFLOXACINO|2.50|IFA|225123|feb-28|94|COMPRIMIDO
        CEFALEXINA|CEFALEXINA|5.00|IFA|112424|nov-28|26|COMPRIMIDO
        PARACETAMOL|PARACCETAMOL|14.00|IFA|32545|mar-27|3|FRASCO
        VANCOMISINA|VANCOMISINA|85.00|IFA|25746|jul-29|2|FRASCO
        KETOROLACO 30 MG|REZITRO|18.00|IFA|26462|abr-30|3|AMPOLLA
        PREDNISONA 5MG|PREDNISONA|3.00|LAB. CHILE|E0125|ene-28|10|COMPRIMIDO
        DIGOXINA 0.25MG|DIGOXINA|2.00|LAB. CHILE|E0224|feb-27|30|COMPRIMIDO
        FLUOXETINA 20 MG|FLUOXETINA|2.00|LAB. CHILE|E0624|jun-27|30|COMPRIMIDO
        CARBAMEZEPINA 200 MG|CARBAPEZINA|3.00|LAB. CHILE|E0525|may-27|77|COMPRIMIDO
        MELOXICAM 15ML|SUPRACAM|30.00|TECNOFARMA|72530|sep-26|1|AMPOLLA
        MELOXICAM 15ML|SUPRACAM|10.00|TECNOFARMA|73137|feb-27|5|COMPRIMIDO
        VITAMINA K + VITAMINA C|FLAVO CKR|8.00|VITA|416528|mar-27|105|COMPRIMIDO
        OXIMETAZOLINA|VITANASAL NIÑO|65.00|VITA|311215|abr-29|2|FRASCO
        ELETRIPTAN 40 MG|KEVAL|41.00|SAVAL|49234|abr-27|7|COMPRIMIDO
        VALSARTAN 160 MG|VALAX|13.00|SAVAL|86924|ago-27|29|COMPRIMIDO
        VALSARTAN + AMLODIPINO|VALAXACAM D|15.00|SAVAL|86034|ago-27|29|COMPRIMIDO
        METILPREDMISOLONA4 MG|FORUCORT|154.00|SAE|MK1C501|feb-28|2|COMPRIMIDO
        METOLAZONA 5 MG|DIURENIL 5|14.00|SAE|PAAH848|jun-27|4|COMPRIMIDO
        METOLAZONA 5 MG|DIURENIL 6|14.00|SAE|7CAB|jun-28|30|COMPRIMIDO
        DEXKETOPROFENO|TARAZOL|24.00|LAFAGE|EP928|feb-28|10|AMPOLLA
        DEXKETOPROFENO|TARAZOL|24.00|LAFAGE|EV940|may-28|10|AMPOLLA
        SIMETICONA|MAGAL D|123.00|HERSIL|2N04054|nov-27|0|FRASCO
        DIOSMEGTITA 3 MG|PARASIN|24.00|SAE|NV001246|jun-27|28|SOBRES
        METRONIDAZOL 550 MG|METROCAPS|5.00|PROCAPS|1580107|sep-28|90|CAPSULAS
        IBUPROFENO 400 MG|NODOL|7.00|SAE|X997|oct-27|20|SOBRES
        AMITRIPTILINA|AMITRIPTILINA|1.00|SAE|AM1C401|ene-27|100|COMPRIMIDO
        SALBUTAMOL|SALBUTAMOL|33.00|SAE|20502073|jul-27|4|AEROSOL
        PROTECTOR SOLAR + VITAMINA E|UMBRELLA|375.00|MEDIHEALTH|518929|feb-28|1|FRASCO
        LOSION LIMPIADORA|LACTIBON|187.00|MEDIHEALTH|516949|abr-27|2|FRASCO
        CLINDAMICINA|ZUDENINA PLUS|362.00|MEDIHEALTH|519405|ene-28|4|FRASCO
        JABON CON AVENA|LACTIBON AVENA|225.00|MEDIHEALTH|520413|jun-28|13|JABONES
        PROTECTOR SOLAR|HELEOCAR|354.00|CANTABRINA LUPPS|256623|jul-28|3|FRASCO
        PROTECTOR SOLAR|HELEOCAR|362.00|CANTABRINA LUPPS|250259|abr-28|6|FRASCO
        PEROXIDO DE BENZOILO|ZUDENINA PV FORTTE|365.00|MEDIHEALTH|360425|abr-28|4|ENVASES
        JABON LIQUIDO|FISIOGEL|320.00|MEGALABS|360425|abr-28|0|FRASCO
        ALOPURINOL 300 MG|GALPURINOL|1.50|LAQFAGAL|HFT4050|nov-27|100|COMPRIMIDO
        LAXANTE|KRITEL|90.00|KRITEL|EA00|sep-28|6|FRASCO
        LAXANTE|ENEMAVIT|90.00|VITA|508545|dic-27|2|FRASCO
        CLOVETAZOL 0,05 MG|BETAZOL|114.00|FARMEDICA|BE506|abr-27|5|POMADA
        UNGÜENTO|MENTISAN|40.00|INTI|39189|may-30|3|POMADA
        SALACILATO|FLOGIATRIN|115.00|MEGALABS|2045525|abr-28|2|POMADA
        ENDOMETACINA|FLOGIATRIN|100.00|MEGALABS|2124865|dic-27|2|SPRAY
        LIDOCAINA 2%|ROXICAINA|60.00|ROBSON|250238|ago-27|10|TUBO
        SULFADRAZINA DE PLATA 1 G|QUEMACURAN|95.00|INTI|37475|ene-29|3|TUBO
        GENTAMIZINA|SUPRACOTIN|65.00|INTI|36121|oct-28|3|CREMAS
        LINOVERA|LINOVERA|210.00|BRAUN|2505213|dic-26|2|TUBO
        DICLOFENACO 5 %|CLOFENAC|106.00|BAGO|E07082025|ago-27|3|TUBO
        DICLOFENACO 2%|NOVADOL|98.00|BRESKOT|8624|jun-28|6|TUBO
        DICLOFENACO 5 %|DOLOCOFAMIN 5 %|108.00|BRESKOT|7875|nov-27|5|TUBO
        METILSALICILATO 2%|GOLPEX|107.00|SAE|820403|jul-27|4|SPRAY
        DICLOFENACO|ALIVIOL|70.00|FARMEDICA|26025240|dic-26|12|TUBO
        SALICILATO DE METILO 18 %|ZASS|82.00|HAHNNEMANN|U04640|abr-30|2|TUBO
        SALICILATO DE METILO 18 %|ZASS|70.00|HAHNNEMANN|U07521|jul-29|1|TUBO
        DICLOFENACO 1%|DICLOFENACO 1%|22.00|DISMEDIN|250529|may-28|4|TUBO
        EXTRACTO ACUOSO|BIOCICATRISANTE|135.00|SAE|BC4V504|nov-28|1|TUBO
        EXTRACTO DE MANZANILLA Y ALOE VERA|EXAMIL DERM|150.00|IFA|82449|ago-27|1|TUBO
        CLOTRIMASOL 1 G|CLOTRIM 1%|40.00|IFA|52543|may-28|1|TUBO
        CLOTRIMASOL 1 G|CLOTRIM 1%|40.00|IFA|102524|oct-29|1|CREMAS
        HIDROCORTISONA 1 %|HIDROCORTISONA 1 %|35.00|IFA|32540|abr-28|4|TUBO
        DEXAMETOZONA 0.1 %|DEXAMETOZONA 0.1 %|28.00|UNIVERSAL FARMA|240902|sep-27|2|GOTAS
        HIALURONATO DE SODIO 0.4%|DACRIHYAL|110.00|INTI|35056|ago-27|1|GOTAS
        TETRAMICINA|TOBRAZOL|145.00|LANSIAR|201064|ene-27|1|GOTAS
        GETAMIZINA 0.3 %|GENTAMIZINA|42.00|VITALIANS|209454|sep-27|1|GOTAS
        CLORANFENICOL|CLORANFENICOL|85.00|NICOLICH|44975|abr-30|5|FRASCO
        PARACETAMO+DICLOFENACO|NOVADOL 75|8.00|COFAR|8357|abr-28|35|COMPRIMIDO
        CEFEXIMA|FIXIM FORTE|283.00|COFAR|7753|abr-27|1|JARABE
        PANTOPRASOL|INHIBIB|18.00|COFAR|2145|jun-27|24|COMPRIMIDO
        PARECTAMOL+DICLOFENAACO|NOVADOL FORTE|8.50|COFAR|8299|mar-28|0|COMPRIMIDO
        IBRUPROFENO 400|ACTICAP|3.00|COFAR|7583|sep-27|10|COMPRIMIDO
        IBUPREFENO 600|ACTCAP|5.00|COFAR|7604|oct-27|36|COMPRIMIDO
        PROPINAXATO 20MG - CLORIXINATO DE LISINA|ESPASMOLOXADIN FORTE|9.00|COFAR|7272|oct-26|43|COMPRIMIDO
        SITOGLICPTINA-METFORMINA|SIGNUM-M|20.00|COFAR|7467|oct-27|17|COMPRIMIDO
        MELOXCAM -PRIDINOL|FLEXICAM RELAX|9.00|COFAR|7484|jul-27|70|COMPRIMIDO
        CELECOXIB 200 MG|MIROUS|20.00|COFAR|6762|feb-27|48|COMPRIMIDO
        BETAMETAZONA 4MG|BECOR|60.00|COFAR|7642|nov-27|3|AMPOLLA
        BETAMETAZONA 10MG|BECOR RAPILENTO|152.00|COFAR|6870|mar-28|3|AMPOLLA
        DIPIRONA 2,5 MG PROPIXINATO 30 MG|ESPASMOLOXADIN FORTE|47.00|COFAR|7658|feb-28|11|AMPOLLA
        DIPIRONA 2 G + PROPIXINATO 30 MG|ESPASMOLOXADIN|43.00|COFAR|7655|mar-28|16|AMPOLLA
        YODO ORGANICAMENTE|IOVERSOL|138.00|GUSIBET|113A|may-27|2|FRASCO
        ELECTRODO ADULTO|ELECTRODO ADULTO|5.00|GIMED|251122084|nov-27|110|FRASCO
        ELECTTRODO PEDIATRICO|ELECTTRODO PEDIATRICO|5.00|GIMED|25058|may-28|45|FRASCO
        CABLE DE CONEXIÓN|CABLE DE CONEXIÓN||GIMED|10731025|sep-29|50|FRASCO
        YODO SOLUCION|YODO SOLUCION|91.00|GIMED|24278|abr-27|1|FRASCO
        YODO POVIDONA|YODO POVIDONA|45.00|TELCHI|107310255|ene-27|3|FRASCO
        AMONIO CUATERNARIO|GERMINIO|50.00|TELCHI|2505629|feb-27|6|FRASCO
        VASELINA|VASELINA|63.00|TELCHI|250134012|oct-28|7|FRASCO
        VASELINA|VASELINA|63.00|TELCHI|1101082|jul-28|1|FRASCO
        PEROXIDO DE HIDROGENO|AGUA OXIGENADA|16.00|TELCHI|102202426|may-29|2|FRASCO
        PEROXIDO DE HIDROGENO|AGUA OXIGENADA|16.00|TELCHI|102150425|feb-31|7|FRASCO
        CLORHEXIDINA GLUTANATO|CLOREX|98.00|SIGMA|1250924|sep-28|7|FRASCO
        SERRA DE GIGLI|SERRA DE GIGLI|123.00|ATYLLE|9223|feb-28|2|FRASCO
        YARDA DE GASA|YARDA DE GASA|975.00|PREMIER|100226|feb-31|7|BOLSA
        BARBIJO|BARBIJO|33.00|ATYLLE|12V25|dic-30|80|CAJA
        THORACIC DRAINAGE|THORACIC DRAINAGE|1560.00|PREMIER|23110836|feb-28|2|FRASCO
        RESUCITADOR MANUAL|RESUCITADOR MANUAL|670.00|PREMIER|220510|feb-28|1|FRASCO
        FLUCONAZOL|FLUXOL|68.00|ALCOS|1825499|feb-29|30|FRASCO
        CIPROFLOXACINO|CIPROXAM|21.00|ALCOS|16050|oct-28|30|FRASCO
        LLAVE DE 3 VIAS 50CM|DISCOFI|14.00|INTI|231129|nov-26|3|SACHET
        EQUIPO DE VENICLISIS|EXADROP|102.00|INTI|25F23F1500|jun-27|3|SACHET
        HOJA DE BISTURI 11|HOJA DE BISTURI 11|3.00|ISUMED|250309||40|PIEZA
        HOJA DE BISTURI 20|HOJA DE BISTURI 20|3.00|ISUMED|22023||512|PIEZA
        TIRAS PARA GLUCOSA|TIRAS PARA GLUCOSA|282.00|ISUMED|25012||8|PIEZA
        CINTA TESTIGO CALOR SECO|CINTA TESTIGO CALOR SECO|88.00|ISUMED|2280||9|PIEZA
        CINTA TESTIGO CALOR HUMEDO|CINTA TESTIGO CALOR HUMEDO|88.00|ISUMED|1327||4|PIEZA
        AGUJA 21G|AGUJA 21G|1.00|OPTIMED|250415||282|PIEZA
        AGUJA 21G|AGUJA 21G|1.00|OPTIMED|202107||202|PIEZA
        AGUJA 23 G|AGUJA 23 G|1.00|OPTIMED|221105||232|PIEZA
        AGUJA 22 G|AGUJA 22 G|1.00|OPTIMED|20250228||380|PIEZA
        AGUJA 19G|AGUJA 19G|1.00|OPTIMED|250415||358|PIEZA
        AGUJA 30 G|AGUJA 30 G|1.00|OPTIMED|202002||99|PIEZA
        BRANULA 14|BRANULA 14|13.00|NIPRO|2502||109|PIEZA
        BRANULA 18|BRANULA 18|13.00|NIPRO|25EIIC||340|PIEZA
        BRANULA 20|BRANULA 20|13.00|NIPRO|2.50E+17||76|PIEZA
        BRANULA 22|BRANULA 22|13.00|NIPRO|202551||143|PIEZA
        BRANULA 24|BRANULA 24|13.00|NIPRO|25L10||69|PIEZA
        JERINGA 50ML|JERINGA 50ML|7.00|OPTIMED|24U80||7|PIEZA
        JERINGA 20ML|JERINGA 20ML|2.00|OPTIMED|20221020||14|PIEZA
        JERINGA 10ML|JERINGA 10ML|2.00|OPTIMED|20260108||66|PIEZA
        JERINGA 5ML|JERINGA 5ML|1.00|OPTIMED|20240421||96|PIEZA
        JERINGA 3ML|JERINGA 3ML|1.00|OPTIMED|22105||134|PIEZA
        JERINGA 1ML|JERINGA 1ML|1.00|OPTIMED|20231030||54|PIEZA
        JERINGA DE INSULINA|JERINGA DE INSULINA|2.00|OPTIMED|20241019||24|PIEZA
        METRONIDAZOL 1,5|METRONIDAZOL 1,5|82.00|INTI|257220||24|FRASCO
        TRANSOFIX|TRANSOFIX|15.00|INTI|22A14LA||106|FRASCO
        BOLSA COLECTORA|BOLSA COLECTORA|10.00|INTI|33963||10|FRASCO
        APOSITO ADHESIVO|APOSITO ADHESIVO|32.00|INTI|412428||40|FRASCO
        FORMOL|FORMOL|40.00|SOLQUIFAR|250316020||3|FRASCO
        ALCOHOL YODADO|ALCOHOL YODADO|95.00|SOLQUIFAR|250316030||5|FRASCO
        DETERGENTE|DETERGENTE|325.00|SOLQUIFAR|250427||1|FRASCO
        GEL ACUOSO|GEL ACUOSO|176.00|SOLQUIFAR|1040926||2|FRASCO
        GEL PARA TRANSEMINACION|GEL PARA TRANSEMINACION|176.00|SOLQUIFAR|251972||1|FRASCO
        METRONIDAZOL/MICONAZOL/NEOMICINA|POLIGYN PLUS|32.00|IFA|62669|jun-28|2|OVULOS
        METRONIDAZOL/MICONAZOL/NEOMICINA|POLIGYN PLUS|215.00|IFA|52666|may-28|2|CREMA VIGINAL
        PROTECTOR SOLAR|HELIO CARE GEL OIL FREE|394.00|MEGALABS|24L939|nov-27|6|FRASCO
        ELECTROLITOS MULTIPLES|ELECTROMAX|36.00|FARMEDICAL|2261304|mar-29|8|FRASCO
        AMOXICILINA/ AC CLAVULANICO|PENTRAX AC|19.00|INTI|1131|abr-27|2|JARABE
        SOL DE REHIDRATACION|VITADRIL|34.00|VITA|5058515|sep-27|10|FRASCO
        BISACODILO 5MG (LAXANTE)|ALCOLAX|2.00|ALCOS|2771|may-31|100|COMPRIMIDOS
        CIPROFLOXACINA 500|CIPROXAN|14.00|ALCOS|1591|ago-28|20|COMPRIMIDOS
        METRONIDAZOL 500MG|METROGYM|8.00|ALCOS|2113|jul-31|10|OVULOS
        SULFATO DE MAGNESIA|SAL INGLESA|4.00|IFARBO|3216|ago-27|6|BOLSA
        LIDOCAINA + EOIN 2%|XYLESTEIN|78.00|IFARBO|50038735|ago-27|9|AMPOLLA
        LEVOFLOXACINA 500|LEVOFLOXACINA|9.00|COFAR|8589|jul-28|0|COMPRIMIDOS
        ROSUVASTATINA 10MG|NEST|17.00|COFAR|7549|sep-27|35|COMPRIMIDOS
        PAROXETINA 20MG|AVATAR|20.00|BAGO|250371|oct-28|30|COMPRIMIDOS
        PENICILINA 1.200000|TRIAPEN FORTE|55.00|BAGO|AHRS-AHNT|mar-29|3|AMPOLLA
        BETAMETASONA|CRONOCORTEROID|170.00|BAGO|PK0130|abr-28|4|AMPOLLA
        DIPIRONA|DIOXADOL|25.00|BAGO|AHBI|abr-28|0|GOTAS
        LACTULOSA|GLADULAX|155.00|LAQFAGAL|25296|oct-28|4|FRASCO
        AZITROMICINA 500|AZITROGAL|7.00|LAQFAGAL|T44307|ene-28|5|COMPRIMIDOS
        AZITROMICINA 200|AZITROALCOS|104.00|ALCOS|2721|nov-30|2|JARABE
        PARA LAS AFTAS|ALCOSEPTOL|44.00|ALCOS|1322|feb-28|3|SPRAY
        LEVOFLOXACINA 500|LEVOALCOS|24.00|ALCOS|2821|ago-29|20|COMPRIMIDOS
        CARBON ACTIVADO|CARBOVIT|5.00|VITA|||100|COMPRIMIDOS
        CLORIXINATO DE LISINA + PROPINOX|SERTAL|98.00|MEGALABS|2134|ago-28|2|GOTAS
        NORADRENALINA|NORADRENALINA|27.00|NOVOPHARMA|25001|abr-27|17|AMPOLLA
        ENOXAPARINA|NOVONOX|125.00|NOVOPHARMA|14341|jul-27|14|JERINGA
        ONDASETRON|ONDASETRON|18.00|NOVOPHARMA|24002|jun-27|30|AMPOLLA
        AZITROMICINA 500MG|AZITROMICINA 500MG|7.00|LABOGEN|2090125|sep-28|100|COMPRIMIDOS
        MULTIVITAMINAS|MULTIVITAMINAS|34.00|INTI|38830|abr-29|2|JARABE
        IMIPEMEN 500|IMIPEMEN 500|145.00|ANGENTINO|2563|jul-28|20|VIAL
        DEXAMETASONA+000TOBRAMICINA|CORTIBIOTICO|59.00|BRIMEG|EZYD01|dic-27|4|COLIRIO
        VENDA PARA YESO 5M|ESTOQUINETE 3|12.00|BRIMEG|1000|SV|1|TELA
        VENDA PARA YESO 5M|ESTOQUINETE 4|18.00|BRIMEG|1000|SV|1|TELA
        CLORURO DE SODIO|ALCODIC NASAL|42.00|ALCOS|1128|sep-27|3|SPRAY
        AZITROMICINA 500|AZITROALCOS|14.00|ALCOS|2731|abr-31|12|COMPRIMIDOS
        ACIDO PIPEMIDICO/FENAZOPIRIDINA|UROTRAC|11.00|ALCOS|2621|abr-31|20|COMPRIMIDOS
        CLOREFERINAMINA|ALERGIN|4.00|ALCOS|1301|mar-31|48|COMPRIMIDOS
        METRONIDAZOL 250MG|METROGYM|68.00|ALCOS|4282|feb-29|3|JARABE
        BICARBONARO DE CALCIO/ACEITES|BEBIDOL|78.00|ALCOS|1492|ene-28|5|JARABE
        CLINDAMICINA/CLOTRIMAZOL|CLINDALCOS PLUS|20.00|ALCOS|2813|abr-31|14|OVULOS
        MICONAZOL/TINIDAZOL|TERGYNAN PLUS|140.00|ALCOS|2853|ene-31|2|CREMA Y OVULOS
        IVABRADINA 5|TENSILAM|13.00|QUINFA|250852|mar-27|30|COMPRIMIDOS
        IVABRADINA 7.5|TENSILAM|17.00|QUINFA|251031|mar-27|30|COMPRIMIDOS
        MIDAZOLAM|MIDAZOLAM|42.00|ABD|4080014|ago-27|8|AMPOLLA
        YESO ORTOPEDICO 4|YESO ORTOPEDICO 4|93.00|SELUR|26012902|ene-29|8|BOLSA
        YESO ORTOPEDICO 5|YESO ORTOPEDICO 5|93.00|SELUR|26042802|abr-29|10|BOLSA
        MEROPEMEN  1GR|MEROPEMEN|155.00|ARGENTINO|12457|abr-28|0|AMPOLLA
        MEROPEMEN  500MG|MEROPEMEN  500MG|90.00|ARGENTINO|12478|may-28|10|AMPOLLA
        CSV;

        $filas = [];

        foreach (explode("\n", trim($csv)) as $numero => $linea) {
            $campos = array_map('trim', explode('|', $linea));

            if (count($campos) !== 8) {
                throw new RuntimeException('Inventario farmacia: fila '.($numero + 1).' mal formada.');
            }

            [$medicamento, $comercial, $precio, $marca, $lote, $vencimiento, $cantidad, $unidad] = $campos;

            // Algunas filas solo traen el nombre comercial.
            $nombre = $medicamento !== '' ? $medicamento : $comercial;
            $comercial = $comercial !== '' ? $comercial : null;
            $marca = $marca !== '' && $marca !== '-' ? $marca : null;

            $filas[] = [
                'clave' => $this->clave($nombre, $comercial, $marca),
                'nombre' => $nombre,
                'comercial' => $comercial,
                'marca' => $marca,
                'precio' => $precio !== '' ? (float) $precio : null,
                'lote' => $lote !== '' ? $lote : null,
                'vencimiento' => $this->fecha($vencimiento),
                'cantidad' => (float) $cantidad,
                'unidad' => $this->normalizarUnidad($unidad),
            ];
        }

        return $filas;
    }
};
