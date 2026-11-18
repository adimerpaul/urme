<?php

use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\VentaController;
use App\Models\CierreCaja;
use App\Models\Internacion;
use App\Models\InternacionItem;
use App\Models\User;
use App\Models\Venta;
use App\Services\ArqueoCaja;
use App\Services\CobroInternacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

/*
 * Cierre de caja con diferencia solo de efectivo (docs/ANALISIS_CIERRES_CAJA.md §8).
 * Esquema mínimo en SQLite: no ejecuta las migraciones ni toca datos reales.
 * Datos: el ejemplo §8.12 (cajero 1, jornada 16/11/2026).
 */
beforeEach(function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'audit.enabled' => false]);
    app('db')->purge('sqlite');

    Schema::create('users', function ($t) {
        $t->id();
        $t->string('name');
        $t->string('username')->nullable();
        $t->string('password')->nullable();
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('pacientes', function ($t) {
        $t->id();
        $t->string('nombre_completo')->nullable();
        $t->string('ci')->nullable();
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('tipo_producto_padres', function ($t) {
        $t->id();
        $t->string('nombre');
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('tipo_productos', function ($t) {
        $t->id();
        $t->string('nombre');
        $t->unsignedBigInteger('tipo_producto_padre_id')->nullable();
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('productos', function ($t) {
        $t->id();
        $t->string('nombre');
        $t->unsignedBigInteger('tipo_producto_id')->nullable();
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('internaciones', function ($t) {
        $t->id();
        foreach (['paciente_id', 'seguro_id', 'fecha_ingreso', 'pagado_en', 'pagado_por_id', 'venta_id', 'monto_pagado', 'pago_tipo', 'pago_observacion'] as $c) {
            $t->string($c)->nullable();
        } $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('internacion_items', function ($t) {
        $t->id();
        foreach (['internacion_id', 'nombre', 'precio', 'cantidad', 'total'] as $c) {
            $t->string($c)->nullable();
        } $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('ventas', function ($t) {
        $t->id();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->unsignedBigInteger('cobrado_por_id')->nullable();
        $t->string('tipo_movimiento')->default('INGRESO');
        $t->string('tipo_paciente')->nullable();
        $t->string('servicio')->nullable();
        $t->unsignedBigInteger('tipo_producto_padre_id')->nullable();
        $t->unsignedBigInteger('tipo_producto_id')->nullable();
        $t->unsignedBigInteger('paciente_id')->nullable();
        $t->unsignedBigInteger('doctor_id')->nullable();
        $t->unsignedBigInteger('seguro_id')->nullable();
        $t->string('cliente')->nullable();
        $t->dateTime('fecha_hora')->nullable();
        $t->dateTime('fecha_hora_cobro')->nullable();
        $t->string('tipo_pago')->nullable();
        $t->string('comentario')->nullable();
        $t->boolean('con_factura')->default(false);
        $t->string('numero_factura')->nullable();
        $t->string('estado')->nullable();
        $t->dateTime('anulado_en')->nullable();
        $t->unsignedBigInteger('anulado_por_id')->nullable();
        $t->decimal('total', 14, 2)->default(0);
        $t->decimal('total_original', 14, 2)->nullable();
        $t->decimal('pago', 14, 2)->default(0);
        $t->decimal('cambio', 14, 2)->default(0);
        $t->decimal('monto_efectivo', 10, 2)->default(0);
        $t->decimal('monto_qr', 10, 2)->default(0);
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('venta_detalles', function ($t) {
        $t->id();
        $t->unsignedBigInteger('venta_id');
        $t->unsignedBigInteger('producto_id')->nullable();
        $t->string('nombre');
        $t->decimal('precio', 12, 2);
        $t->decimal('precio_original', 12, 2)->nullable();
        $t->decimal('cantidad', 12, 4);
        $t->decimal('total', 12, 2);
        $t->decimal('total_original', 12, 2)->nullable();
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('cierres_caja', function ($t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->date('fecha');
        $t->decimal('monto_sistema', 14, 2);
        $t->decimal('monto', 14, 2);
        $t->text('detalle_efectivo')->nullable();
        $t->decimal('diferencia', 14, 2);
        $t->string('formula', 10)->nullable();
        foreach (['ingresos_total', 'efectivo_ingresos', 'qr_total', 'otros_total', 'gastos_efectivo', 'efectivo_esperado', 'diferencia_efectivo'] as $c) {
            $t->decimal($c, 14, 2)->nullable();
        }
        foreach (['qr_cantidad', 'otros_cantidad', 'gastos_cantidad'] as $c) {
            $t->unsignedInteger($c)->nullable();
        }
        $t->integer('cantidad_ventas');
        $t->dateTime('fecha_hora');
        $t->string('comentario')->nullable();
        $t->dateTime('modificado_en')->nullable();
        $t->unsignedBigInteger('validado_por_id')->nullable();
        $t->dateTime('validado_en')->nullable();
        $t->dateTime('autorizado_hasta')->nullable();
        $t->unsignedBigInteger('autorizado_por_id')->nullable();
        $t->timestamps();
        $t->softDeletes();
    });
    Schema::create('audits', function ($t) {
        $t->id();
        $t->string('user_type')->nullable();
        $t->unsignedBigInteger('user_id')->nullable();
        $t->string('event');
        $t->morphs('auditable');
        $t->text('old_values')->nullable();
        $t->text('new_values')->nullable();
        $t->timestamps();
    });

    foreach ([1 => 'CAJERO', 2 => 'OTRO CAJERO', 3 => 'SUPERVISOR'] as $id => $nombre) {
        DB::table('users')->insert(['id' => $id, 'name' => $nombre]);
    }
    foreach ([1 => 'FARMACIA', 2 => 'LABORATORIO', 3 => 'SERVICIOS MÉDICOS'] as $id => $nombre) {
        DB::table('tipo_producto_padres')->insert(['id' => $id, 'nombre' => $nombre]);
        DB::table('tipo_productos')->insert(['id' => $id, 'nombre' => "TIPO $nombre", 'tipo_producto_padre_id' => $id]);
        DB::table('productos')->insert(['id' => $id, 'nombre' => "PRODUCTO $nombre", 'tipo_producto_id' => $id]);
    }

    // Ejemplo §8.12: un movimiento con un ítem.
    $this->venta = function (array $datos, ?int $producto = null, ?float $lista = null) {
        $id = DB::table('ventas')->insertGetId($datos + ['user_id' => 1, 'tipo_movimiento' => 'INGRESO', 'estado' => 'ACTIVO', 'cliente' => 'CLIENTE PRUEBA']);
        DB::table('venta_detalles')->insert(['venta_id' => $id, 'producto_id' => $producto, 'nombre' => 'ITEM', 'precio' => $datos['total'],
            'precio_original' => $lista ?? $datos['total'], 'cantidad' => 1, 'total' => $datos['total']]);

        return $id;
    };
    $d = '2026-11-16';
    $v = $this->venta;
    $this->v1 = $v(['fecha_hora' => "$d 09:00:00", 'tipo_paciente' => 'EXTERNO', 'tipo_pago' => 'EFECTIVO', 'total' => 100, 'monto_efectivo' => 100], 1);
    $v(['fecha_hora' => "$d 10:00:00", 'tipo_paciente' => 'EXTERNO', 'tipo_pago' => 'QR', 'total' => 50, 'monto_qr' => 50], 2);
    $v(['fecha_hora' => "$d 10:30:00", 'tipo_paciente' => 'AMBULATORIO', 'tipo_pago' => 'MIXTO', 'total' => 80, 'monto_efectivo' => 50, 'monto_qr' => 30], 3);
    $v(['estado' => 'PENDIENTE', 'fecha_hora' => '2026-11-15 18:00:00', 'cobrado_por_id' => 1, 'fecha_hora_cobro' => "$d 11:00:00",
        'tipo_paciente' => 'EXTERNO', 'tipo_pago' => 'EFECTIVO', 'total' => 40, 'monto_efectivo' => 40], 2);
    $v5 = $v(['fecha_hora' => "$d 12:00:00", 'tipo_paciente' => 'INTERNO', 'tipo_pago' => 'EFECTIVO', 'total' => 200, 'monto_efectivo' => 200]);
    DB::table('internaciones')->insert(['venta_id' => $v5]);
    $v(['tipo_movimiento' => 'EGRESO', 'fecha_hora' => "$d 13:00:00", 'tipo_pago' => 'EFECTIVO', 'total' => 20, 'monto_efectivo' => 20, 'cliente' => null]);
    $v(['estado' => 'ANULADO', 'anulado_en' => "$d 15:00:00", 'fecha_hora' => "$d 14:00:00", 'tipo_pago' => 'EFECTIVO', 'total' => 30, 'monto_efectivo' => 30], 1, 35);
    $v(['estado' => 'PENDIENTE', 'fecha_hora' => "$d 15:30:00", 'tipo_pago' => 'EFECTIVO', 'total' => 60], 2);

    // Usuario con permisos simulados (sin las tablas de spatie).
    $this->usuario = function (int $id, array $permisos) {
        $usuario = Mockery::mock(User::class)->makePartial();
        $usuario->id = $id;
        $usuario->name = "USUARIO $id";
        $usuario->shouldReceive('hasPermissionTo')->andReturnUsing(fn ($p) => in_array($p, $permisos, true));
        $usuario->shouldReceive('can')->andReturnUsing(fn ($p) => in_array($p, $permisos, true));

        return $usuario;
    };
    $this->peticion = function ($usuario, array $datos = [], string $metodo = 'POST') {
        $request = Request::create('/', $metodo, $datos);
        $request->setUserResolver(fn () => $usuario);

        return $request;
    };
    $this->estado = function (Closure $accion): array {
        try {
            $accion();

            return [200, ''];
        } catch (HttpException $e) {
            return [$e->getStatusCode(), $e->getMessage()];
        }
    };
    $this->supervisor = ($this->usuario)(3, ['Ver Cierres Caja', 'Ver Montos Caja', 'Validar Cierres Caja', 'Autorizar Ventas Caja Cerrada',
        'Anular Ventas Caja Cerrada', 'Eliminar Ventas', 'Cerrar Caja']);
    $this->cajero = ($this->usuario)(1, ['Cerrar Caja', 'Crear Ventas']);
});

afterEach(fn () => Carbon::setTestNow());

it('calcula el arqueo del ejemplo: esperado 370, diferencia −5, QR 80, y la fórmula anterior aparte', function () {
    Carbon::setTestNow('2026-11-16 20:00:00');
    $respuesta = (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 365]))->getData(true)['cierre'];
    // El cajero no tiene 'Ver Montos Caja': su respuesta no trae importes (cierre a ciegas).
    expect($respuesta)->not->toHaveKeys(['efectivo_esperado', 'diferencia_efectivo', 'monto_sistema']);
    $datos = CierreCaja::find($respuesta['id'])->toArray();

    expect($datos['formula'])->toBe('EFECTIVO')
        ->and((float) $datos['efectivo_ingresos'])->toBe(390.0)
        ->and((float) $datos['gastos_efectivo'])->toBe(20.0)
        ->and((float) $datos['efectivo_esperado'])->toBe(370.0)
        ->and((float) $datos['diferencia_efectivo'])->toBe(-5.0)
        ->and((float) $datos['qr_total'])->toBe(80.0)
        ->and($datos['qr_cantidad'])->toBe(2)
        ->and((float) $datos['ingresos_total'])->toBe(470.0)
        ->and((float) $datos['monto_sistema'])->toBe(450.0)
        ->and((float) $datos['diferencia'])->toBe(-85.0)
        ->and($datos['cantidad_ventas'])->toBe(5);
});

it('reparte los cobros de internación nuevos y clasifica los antiguos por tipo_pago', function () {
    expect(ArqueoCaja::reparto('EFECTIVO', 100))->toBe(['monto_efectivo' => 100.0, 'monto_qr' => 0.0])
        ->and(ArqueoCaja::reparto('qr', 100))->toBe(['monto_efectivo' => 0.0, 'monto_qr' => 100.0])
        ->and(ArqueoCaja::reparto('TARJETA', 100))->toBe(['monto_efectivo' => 0.0, 'monto_qr' => 0.0]);

    Carbon::setTestNow('2026-11-17 10:00:00');
    $usuario = ($this->usuario)(2, ['Crear Ventas']);
    foreach (['QR' => 120, 'TRANSFERENCIA' => 80] as $medio => $total) {
        $internacion = Internacion::create(['fecha_ingreso' => '2026-11-10']);
        InternacionItem::create(['internacion_id' => $internacion->id, 'nombre' => 'CARGO', 'precio' => $total, 'cantidad' => 1, 'total' => $total]);
        $venta = CobroInternacion::registrar($internacion->load('items'), $usuario, $medio);
        expect($venta->tipo_paciente)->toBe('INTERNO')->and((float) $venta->total_original)->toBe((float) $total);
    }
    // Cobro antiguo sin reparto (monto_efectivo = monto_qr = 0): se clasifica al leer.
    ($this->venta)(['user_id' => 2, 'fecha_hora' => '2026-11-17 09:00:00', 'tipo_pago' => 'QR', 'total' => 15]);

    $arqueo = ArqueoCaja::calcular(CierreCajaController::ventasDelDia(2, '2026-11-17'));
    expect($arqueo['qr_total'])->toBe(135.0)
        ->and($arqueo['otros_total'])->toBe(80.0)
        ->and($arqueo['efectivo_ingresos'])->toBe(0.0)
        ->and($arqueo['ingresos_total'])->toBe(215.0);
});

it('bloquea anular con la caja cerrada salvo con el permiso, recalcula, y nunca con el cierre validado', function () {
    Carbon::setTestNow('2026-11-16 20:00:00');
    $cierre = (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 365]))->getData(true)['cierre'];
    $anular = fn ($usuario) => (new VentaController)->destroy(($this->peticion)($usuario, [], 'DELETE'), $this->v1);

    $sinPermiso = ($this->usuario)(5, ['Eliminar Ventas']);
    expect(($this->estado)(fn () => $anular($sinPermiso))[0])->toBe(422)
        ->and(Venta::find($this->v1)->estado)->toBe('ACTIVO');

    Carbon::setTestNow('2026-11-16 20:15:00');
    $anular($this->supervisor);
    $venta = Venta::find($this->v1);
    $recalculado = CierreCaja::find($cierre['id']);
    expect($venta->estado)->toBe('ANULADO')
        ->and($venta->anulado_en->format('H:i'))->toBe('20:15')
        ->and((int) $venta->anulado_por_id)->toBe(3)
        ->and((float) $recalculado->efectivo_esperado)->toBe(270.0)
        ->and((float) $recalculado->diferencia_efectivo)->toBe(95.0);

    // Validado: ninguna anulación, aun con el permiso.
    (new CierreCajaController)->validar(($this->peticion)($this->supervisor, [], 'PUT'), $cierre['id']);
    $otra = Venta::where('estado', 'ACTIVO')->where('tipo_movimiento', 'INGRESO')->whereDate('fecha_hora', '2026-11-16')->first();
    [$codigo, $mensaje] = ($this->estado)(fn () => (new VentaController)->destroy(($this->peticion)($this->supervisor, [], 'DELETE'), $otra->id));
    expect($codigo)->toBe(422)->and($mensaje)->toContain('validado');

    // Una venta sin cierre (pendiente sin cobrar) se anula como siempre.
    $pendiente = Venta::where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->first();
    (new VentaController)->destroy(($this->peticion)($sinPermiso, [], 'DELETE'), $pendiente->id);
    expect($pendiente->fresh()->estado)->toBe('ANULADO');
});

it('no recalcula ni guarda nada al consultar (listado, detalle) y no modifica los cierres antiguos', function () {
    Carbon::setTestNow('2026-11-16 20:00:00');
    $cierre = (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 365]))->getData(true)['cierre'];
    // Cierre antiguo (formula null) con una autorización: antes se recalculaba en cada GET.
    $antiguoId = DB::table('cierres_caja')->insertGetId(['user_id' => 2, 'fecha' => '2026-11-16', 'monto_sistema' => 999, 'monto' => 10,
        'diferencia' => -989, 'cantidad_ventas' => 9, 'fecha_hora' => '2026-11-16 18:00:00', 'autorizado_hasta' => '2026-11-16 19:00:00',
        'created_at' => '2026-11-16 18:00:00', 'updated_at' => '2026-11-16 18:00:00']);
    $antes = DB::table('cierres_caja')->orderBy('id')->get()->map(fn ($c) => (array) $c)->all();
    DB::table('ventas')->where('id', $this->v1)->update(['estado' => 'ANULADO']); // cambio sin pasar por destroy

    Carbon::setTestNow('2026-11-17 09:00:00');
    $controlador = new CierreCajaController;
    $lista = $controlador->index(($this->peticion)($this->supervisor, ['fecha_inicio' => '2026-11-01'], 'GET'))->getData(true);
    $controlador->ventas(($this->peticion)($this->supervisor, [], 'GET'), $cierre['id']);
    $controlador->ventas(($this->peticion)($this->supervisor, [], 'GET'), $antiguoId);

    expect(DB::table('cierres_caja')->orderBy('id')->get()->map(fn ($c) => (array) $c)->all())->toBe($antes);
    $antiguo = collect($lista['data'])->firstWhere('id', $antiguoId);
    expect($antiguo['formula_anterior'])->toBeTrue()
        ->and((float) $antiguo['diferencia'])->toBe(-989.0)
        ->and($lista['totales']['cantidad'])->toBe(2);
});

it('cierra una jornada anterior: el cajero hasta las 12:00 del día siguiente; después, el supervisor en su nombre', function () {
    $cierreDe = fn () => CierreCajaController::cierreDelDia(1, '2026-11-16');

    Carbon::setTestNow('2026-11-17 12:30:00');
    $estado = (new CierreCajaController)->estado(($this->peticion)($this->cajero, [], 'GET'))->getData(true);
    expect($estado['jornada_pendiente'])->toMatchArray(['fecha' => '2026-11-16', 'puede_cerrar' => false]);
    [$codigo, $mensaje] = ($this->estado)(fn () => (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 365, 'fecha' => '2026-11-16'])));
    expect($codigo)->toBe(422)->and($mensaje)->toContain('supervisor')->and($cierreDe())->toBeNull();

    // El supervisor, con comentario obligatorio.
    expect(($this->estado)(fn () => (new CierreCajaController)->store(($this->peticion)($this->supervisor, ['monto' => 365, 'fecha' => '2026-11-16', 'user_id' => 1])))[0])->toBe(422);
    (new CierreCajaController)->store(($this->peticion)($this->supervisor, ['monto' => 365, 'fecha' => '2026-11-16', 'user_id' => 1, 'comentario' => 'turno de noche']));
    expect((float) $cierreDe()->efectivo_esperado)->toBe(370.0)->and($cierreDe()->fecha_hora->format('Y-m-d H:i'))->toBe('2026-11-17 12:30');

    // Fuera de los 2 días: rechazado.
    Carbon::setTestNow('2026-11-19 09:00:00');
    expect(($this->estado)(fn () => (new CierreCajaController)->store(($this->peticion)($this->supervisor, ['monto' => 1, 'fecha' => '2026-11-15', 'user_id' => 1, 'comentario' => 'x'])))[0])->toBe(422);
});

it('el cajero cierra su jornada de ayer antes de las 12:00', function () {
    Carbon::setTestNow('2026-11-17 11:59:00');
    (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 370, 'fecha' => '2026-11-16']));
    $cierre = CierreCajaController::cierreDelDia(1, '2026-11-16');
    expect((float) $cierre->diferencia_efectivo)->toBe(0.0)->and(CierreCajaController::jornadaPendiente(1))->toBeNull();
});

it('el reporte exige ver montos y un cierre validado, y todo cuadra; el cierre antiguo muestra las dos diferencias', function () {
    Carbon::setTestNow('2026-11-16 20:00:00');
    $cierre = (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 365]))->getData(true)['cierre'];
    $reporte = fn ($usuario, $id) => (new CierreCajaController)->reporte(($this->peticion)($usuario, [], 'GET'), $id);

    expect(($this->estado)(fn () => $reporte(($this->usuario)(4, ['Ver Cierres Caja']), $cierre['id']))[0])->toBe(403)
        ->and(($this->estado)(fn () => $reporte($this->supervisor, $cierre['id']))[0])->toBe(422);

    (new CierreCajaController)->validar(($this->peticion)($this->supervisor, [], 'PUT'), $cierre['id']);
    $r = $reporte($this->supervisor, $cierre['id'])->getData(true);
    expect($r['cuadra'])->toBeTrue()
        ->and($r['arqueo']['efectivo_esperado'])->toEqual(370)
        ->and($r['ventas']['total'])->toEqual(470)
        ->and(collect($r['ventas']['por_categoria'])->sum('total'))->toEqual(470)
        ->and($r['ventas']['internaciones'])->toEqual(['cantidad' => 1, 'total' => 200])
        ->and($r['ventas']['pendientes_otros_dias'])->toEqual(['cantidad' => 1, 'total' => 40])
        ->and(count($r['controles']['anuladas']))->toBe(1)
        ->and($r['controles']['anuladas'][0]['despues_del_cierre'])->toBeFalse()
        ->and(count($r['controles']['pendientes_sin_cobrar']))->toBe(1);

    // Cierre antiguo validado: diferencia recalculada y la registrada aparte, sin modificarlo.
    ($this->venta)(['user_id' => 2, 'fecha_hora' => '2026-11-16 09:00:00', 'tipo_pago' => 'EFECTIVO', 'total' => 70, 'monto_efectivo' => 70]);
    ($this->venta)(['user_id' => 2, 'fecha_hora' => '2026-11-16 09:30:00', 'tipo_pago' => 'QR', 'total' => 30, 'monto_qr' => 30]);
    $antiguo = DB::table('cierres_caja')->insertGetId(['user_id' => 2, 'fecha' => '2026-11-16', 'monto_sistema' => 100, 'monto' => 70,
        'diferencia' => -30, 'cantidad_ventas' => 2, 'fecha_hora' => '2026-11-16 18:00:00', 'validado_en' => '2026-11-16 18:30:00', 'validado_por_id' => 3]);
    $a = $reporte($this->supervisor, $antiguo)->getData(true)['arqueo'];
    expect($a['formula_anterior'])->toBeTrue()
        ->and($a['diferencia_efectivo'])->toEqual(0)
        ->and($a['diferencia_registrada'])->toEqual(-30)
        ->and(DB::table('cierres_caja')->where('id', $antiguo)->value('formula'))->toBeNull();
});

it('sin Ver Montos Caja el detalle del cierre viaja sin importes', function () {
    Carbon::setTestNow('2026-11-16 20:00:00');
    $cierre = (new CierreCajaController)->store(($this->peticion)($this->cajero, ['monto' => 365]))->getData(true)['cierre'];
    $d = (new CierreCajaController)->ventas(($this->peticion)(($this->usuario)(4, ['Ver Cierres Caja']), [], 'GET'), $cierre['id'])->getData(true);

    expect($d['cierre'])->not->toHaveKeys(['efectivo_esperado', 'diferencia_efectivo', 'monto_sistema', 'qr_total'])
        ->and(collect($d['ventas']['data'])->every(fn ($v) => ! array_key_exists('total', $v)))->toBeTrue()
        ->and($d['resumen']['neto'])->toBeNull()
        ->and($d['resumen_caja']['total_efectivo'])->toBeNull();

    $conMontos = (new CierreCajaController)->ventas(($this->peticion)($this->supervisor, [], 'GET'), $cierre['id'])->getData(true);
    expect($conMontos['resumen_caja']['total_efectivo'])->toEqual(370);
});
