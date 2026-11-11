<?php

use App\Http\Controllers\InternacionItemController;
use App\Http\Controllers\LaboratorioReporteController;
use App\Http\Controllers\SolicitudeController;
use App\Http\Controllers\VentaController;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\ProductoLaboratorioDato;
use App\Models\ProductoPerfilItem;
use App\Models\Reactivo;
use App\Models\ServicioLaboratorioReactivo;
use App\Models\Solicitude;
use App\Models\SolicitudLaboratorioItem;
use App\Models\SolicitudLaboratorioPerfil;
use App\Models\SolicitudLaboratorioResultado;
use App\Models\TipoProducto;
use App\Models\TipoProductoPadre;
use App\Models\User;
use App\Models\Venta;
use App\Services\ReactivoKardex;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    // Esquema aislado en SQLite con lo que usan venta, solicitud, kardex y reporte: no ejecuta las migraciones.
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'laboratorio.perfiles_habilitado' => true,
    ]);
    app('db')->purge('sqlite');

    $tabla = function (string $nombre, Closure $columnas, bool $soft = true) {
        Schema::create($nombre, function (Blueprint $table) use ($columnas, $soft) {
            $table->id();
            $columnas($table);
            $table->timestamps();
            $soft && $table->softDeletes();
        });
    };
    $textos = fn (array $campos) => fn (Blueprint $t) => array_map(fn ($campo) => $t->string($campo)->nullable(), $campos);

    $tabla('users', $textos(['name', 'username', 'password']));
    $tabla('tipo_producto_padres', fn ($t) => [$t->string('nombre'), $t->string('color')->nullable(), $t->string('icono')->nullable(),
        $t->boolean('es_laboratorio')->default(false), $t->integer('orden')->default(0)]);
    $tabla('tipo_productos', fn ($t) => [$t->unsignedBigInteger('tipo_producto_padre_id')->nullable(), $t->string('nombre'),
        $t->string('color')->nullable(), $t->boolean('es_laboratorio')->default(false), $t->integer('orden')->default(0)]);
    $tabla('productos', fn ($t) => [$t->string('codigo')->nullable(), $t->string('nombre'), $t->string('nombre_comercial')->nullable(),
        $t->text('descripcion')->nullable(), $t->string('marca')->nullable(), $t->unsignedBigInteger('fabricante_id')->nullable(),
        $t->unsignedBigInteger('unidad_id')->nullable(), $t->unsignedBigInteger('tipo_producto_id')->nullable(),
        $t->string('grupo_reporte')->nullable(), $t->integer('orden_reporte')->nullable(), $t->decimal('precio', 10, 2)->default(0),
        $t->decimal('precio_seguro', 12, 2)->nullable(), $t->boolean('es_perfil')->default(false)]);
    $tabla('producto_perfil_items', fn ($t) => [$t->unsignedBigInteger('perfil_id'), $t->unsignedBigInteger('producto_id'),
        $t->integer('orden')->default(0)], false);
    $tabla('producto_laboratorio_datos', fn ($t) => [$t->unsignedBigInteger('producto_id'), $t->string('nombre'),
        $t->string('nombre_variable'), $t->string('unidad')->nullable(), $t->string('metodo')->nullable(), $t->string('muestra')->nullable(),
        $t->text('rango_referencia')->nullable(), $t->string('valor_defecto')->nullable(), $t->integer('orden')->default(0),
        $t->boolean('visible')->default(true)]);
    $tabla('producto_laboratorio_formulas', $textos(['producto_id', 'producto_laboratorio_dato_id', 'nombre', 'nombre_variable', 'formula', 'unidad', 'orden', 'visible']));
    $tabla('producto_laboratorio_dato_opciones', $textos(['producto_laboratorio_dato_id', 'valor', 'orden']));
    $tabla('producto_laboratorio_validaciones', $textos(['producto_id', 'expresion', 'operador', 'valor', 'valor_hasta', 'mensaje', 'activo', 'orden']));
    $tabla('reactivos', $textos(['codigo', 'nombre', 'unidad', 'stock_actual', 'stock_minimo', 'estado', 'descripcion']));
    $tabla('servicio_laboratorio_reactivos', fn ($t) => [$t->unsignedBigInteger('producto_id'), $t->unsignedBigInteger('reactivo_id'),
        $t->decimal('cantidad', 12, 4)]);
    $tabla('pacientes', $textos(['seguro_id', 'nombre_completo', 'sexo', 'fecha_nacimiento', 'ci', 'estado', 'direccion', 'telefono']));
    $tabla('internaciones', $textos(['paciente_id', 'fecha_ingreso', 'fecha_alta']));
    $tabla('solicitudes', fn ($t) => [$t->unsignedBigInteger('paciente_id'), $t->unsignedBigInteger('doctor_id')->nullable(),
        $t->unsignedBigInteger('user_id')->nullable(), $t->string('codigo_solicitud')->nullable(), $t->string('codigo_verificacion')->nullable(),
        $t->string('codigo_laboratorio')->nullable(), $t->date('fecha_solicitud'), $t->string('hora_solicitud'),
        $t->string('diagnostico_clinico')->nullable(), $t->text('observaciones')->nullable(), $t->string('estado')->default('CREADO'),
        $t->decimal('total', 12, 2)->default(0)]);
    $tabla('solicitud_laboratorio_perfiles', fn ($t) => [$t->unsignedBigInteger('solicitude_id'),
        $t->unsignedBigInteger('perfil_producto_id')->nullable(), $t->string('nombre'), $t->decimal('precio', 10, 2)->default(0),
        $t->integer('orden')->default(0)]);
    $tabla('solicitud_laboratorio_items', fn ($t) => [$t->unsignedBigInteger('solicitude_id'), $t->unsignedBigInteger('producto_id')->nullable(),
        $t->unsignedBigInteger('solicitud_laboratorio_perfil_id')->nullable(), $t->string('producto_nombre'),
        $t->decimal('precio', 10, 2)->default(0), $t->integer('orden')->default(0)]);
    $tabla('solicitud_laboratorio_resultados', fn ($t) => [$t->unsignedBigInteger('solicitud_laboratorio_item_id'),
        $t->unsignedBigInteger('producto_laboratorio_dato_id')->nullable(), $t->string('nombre'), $t->string('nombre_variable'),
        $t->string('unidad')->nullable(), $t->string('metodo')->nullable(), $t->string('muestra')->nullable(),
        $t->text('rango_referencia')->nullable(), $t->text('formula')->nullable(), $t->text('valor')->nullable(),
        $t->integer('orden')->default(0), $t->boolean('visible')->default(true)]);
    $tabla('cierres_caja', $textos(['user_id', 'fecha', 'autorizado_hasta']));
    $tabla('ventas', fn ($t) => [$t->unsignedBigInteger('user_id')->nullable(), $t->unsignedBigInteger('cobrado_por_id')->nullable(),
        $t->string('tipo_movimiento')->nullable(), $t->unsignedBigInteger('paciente_id')->nullable(), $t->unsignedBigInteger('doctor_id')->nullable(),
        $t->unsignedBigInteger('seguro_id')->nullable(), $t->string('cliente')->nullable(), $t->dateTime('fecha_hora')->nullable(),
        $t->dateTime('fecha_hora_cobro')->nullable(), $t->string('tipo_pago')->nullable(), $t->string('comentario')->nullable(),
        $t->string('estado')->nullable(), $t->decimal('total', 14, 2)->default(0), $t->decimal('total_original', 14, 2)->nullable(),
        $t->decimal('pago', 14, 2)->default(0), $t->decimal('cambio', 14, 2)->default(0), $t->decimal('monto_efectivo', 14, 2)->nullable(),
        $t->decimal('monto_qr', 14, 2)->nullable()]);
    $tabla('venta_detalles', fn ($t) => [$t->unsignedBigInteger('venta_id'), $t->unsignedBigInteger('producto_id')->nullable(),
        $t->unsignedBigInteger('compra_detalle_id')->nullable(), $t->string('nombre'), $t->string('lote')->nullable(),
        $t->date('fecha_vencimiento')->nullable(), $t->decimal('precio', 14, 2), $t->decimal('precio_original', 14, 2)->nullable(),
        $t->decimal('cantidad', 10, 4), $t->decimal('total', 14, 2), $t->decimal('total_original', 14, 2)->nullable()]);

    $padreLab = TipoProductoPadre::create(['nombre' => 'LABORATORIO', 'es_laboratorio' => true]);
    $padreFarmacia = TipoProductoPadre::create(['nombre' => 'FARMACIA', 'es_laboratorio' => false]);
    $this->area = TipoProducto::create(['nombre' => 'IONOGRAMA / ELECTROLITOS', 'tipo_producto_padre_id' => $padreLab->id]);
    $this->farmacia = TipoProducto::create(['nombre' => 'FARMACIA', 'tipo_producto_padre_id' => $padreFarmacia->id]);

    // Cada examen con un dato, para poder cargarle resultado.
    $examen = function (string $nombre, float $precio = 43) {
        $producto = Producto::create(['nombre' => $nombre, 'precio' => $precio, 'tipo_producto_id' => $this->area->id]);
        ProductoLaboratorioDato::create(['producto_id' => $producto->id, 'nombre' => $nombre, 'nombre_variable' => mb_strtolower($nombre), 'orden' => 10]);

        return $producto;
    };
    $this->sodio = $examen('SODIO');
    $this->potasio = $examen('POTASIO');
    $this->cloro = $examen('CLORO');
    $this->calcio = $examen('CALCIO', 40);
    $this->glucosa = $examen('GLUCOSA', 20);
    $perfil = function (string $nombre, float $precio, array $examenes) {
        $perfil = Producto::create(['nombre' => $nombre, 'precio' => $precio, 'tipo_producto_id' => $this->area->id, 'es_perfil' => true]);
        foreach ($examenes as $indice => $examen) {
            ProductoPerfilItem::create(['perfil_id' => $perfil->id, 'producto_id' => $examen->id, 'orden' => ($indice + 1) * 10]);
        }

        return $perfil;
    };
    $this->ionograma = $perfil('IONOGRAMA', 100, [$this->sodio, $this->potasio, $this->cloro]);
    $this->renal = $perfil('PERFIL MINERAL', 90, [$this->cloro, $this->calcio]);
    $this->paciente = Paciente::create(['nombre_completo' => 'PACIENTE PRUEBA']);

    $usuario = Mockery::mock(User::class)->makePartial();
    $usuario->shouldReceive('hasPermissionTo')->andReturnTrue();
    $usuario->shouldReceive('can')->andReturnTrue();
    $usuario->id = 1;
    $this->peticion = function (array $datos = [], string $metodo = 'POST') use ($usuario) {
        $request = Request::create('/', $metodo, $datos);
        $request->setUserResolver(fn () => $usuario);

        return $request;
    };
    $this->datosSolicitud = fn (array $productos, array $resultados = []) => [
        'codigo_laboratorio' => 'A1', 'paciente_id' => $this->paciente->id, 'fecha_solicitud' => '2026-11-12',
        'hora_solicitud' => '10:00', 'producto_ids' => array_map(fn ($p) => $p->id, $productos), 'resultados' => $resultados,
    ];
    $this->crear = fn (array $productos, array $resultados = []) => (new SolicitudeController)
        ->store(($this->peticion)(($this->datosSolicitud)($productos, $resultados)))->getData(true);
    $this->editar = fn (int $id, array $productos, array $resultados = []) => (new SolicitudeController)
        ->update(($this->peticion)(($this->datosSolicitud)($productos, $resultados), 'PUT'), Solicitude::findOrFail($id))->getData(true);
    $this->resultado = fn (Producto $examen, string $valor) => [
        'producto_laboratorio_dato_id' => $examen->laboratorioDatos()->value('id'), 'valor' => $valor,
    ];
});

/** Mensaje de la HttpException (abort) que lanza $accion. */
function mensajeDe(Closure $accion): string
{
    try {
        $accion();
    } catch (HttpException $e) {
        return $e->getMessage();
    }
    throw new RuntimeException('Se esperaba una HttpException');
}

it('expande el perfil: precio del perfil, exámenes en 0 y total sin duplicar', function () {
    $datos = ($this->crear)([$this->glucosa, $this->ionograma]);
    $solicitud = Solicitude::with(['laboratorioItems', 'laboratorioPerfiles'])->find($datos['id']);

    expect((float) $solicitud->total)->toBe(120.0)
        ->and($solicitud->laboratorioPerfiles->map->only(['nombre', 'precio', 'orden'])->all())
        ->toBe([['nombre' => 'IONOGRAMA', 'precio' => '100.00', 'orden' => 20]])
        ->and($solicitud->laboratorioItems->map(fn ($i) => [$i->producto_nombre, (float) $i->precio, $i->orden, $i->solicitud_laboratorio_perfil_id !== null])->all())
        ->toBe([['GLUCOSA', 20.0, 10, false], ['SODIO', 0.0, 20, true], ['POTASIO', 0.0, 30, true], ['CLORO', 0.0, 40, true]]);
});

it('rechaza un examen suelto incluido en un perfil, en cualquier orden', function (bool $perfilPrimero) {
    $productos = $perfilPrimero ? [$this->ionograma, $this->sodio] : [$this->sodio, $this->ionograma];

    expect(mensajeDe(fn () => ($this->crear)($productos)))
        ->toBe('SODIO está incluido en el perfil IONOGRAMA: quítelo de los exámenes sueltos.')
        ->and(Solicitude::count())->toBe(0);
})->with([true, false]);

it('dos perfiles que comparten un examen: aparece una vez, en el primero, con aviso', function () {
    $datos = ($this->crear)([$this->ionograma, $this->renal]);
    $items = SolicitudLaboratorioItem::with('perfil')->orderBy('orden')->get();

    expect($items->pluck('producto_nombre')->all())->toBe(['SODIO', 'POTASIO', 'CLORO', 'CALCIO'])
        ->and($items->firstWhere('producto_nombre', 'CLORO')->perfil->nombre)->toBe('IONOGRAMA')
        ->and($items->firstWhere('producto_nombre', 'CALCIO')->perfil->nombre)->toBe('PERFIL MINERAL')
        ->and((float) Solicitude::first()->total)->toBe(190.0)
        ->and($datos['avisos'])->toBe(['CLORO está en PERFIL MINERAL y en IONOGRAMA: aparece una sola vez, en IONOGRAMA.']);
});

it('editar sin cambios conserva ítems y resultados; un examen que pasa de suelto a perfil también', function () {
    $datos = ($this->crear)([$this->sodio, $this->glucosa], [($this->resultado)($this->sodio, '140')]);
    $itemSodio = SolicitudLaboratorioItem::where('producto_nombre', 'SODIO')->first();

    // SODIO suelto pasa a estar dentro del IONOGRAMA: mismo ítem, mismo resultado, precio 0.
    ($this->editar)($datos['id'], [$this->ionograma, $this->glucosa], [($this->resultado)($this->sodio, '140')]);
    $sodio = SolicitudLaboratorioItem::with('resultados')->where('producto_nombre', 'SODIO')->first();
    expect($sodio->id)->toBe($itemSodio->id)
        ->and((float) $sodio->precio)->toBe(0.0)
        ->and($sodio->resultados->first()->valor)->toBe('140')
        ->and((float) Solicitude::find($datos['id'])->total)->toBe(120.0);

    // Guardar otra vez igual: ni ítems ni filas de perfil nuevas.
    $antes = [SolicitudLaboratorioItem::withTrashed()->count(), SolicitudLaboratorioPerfil::withTrashed()->count()];
    ($this->editar)($datos['id'], [$this->ionograma, $this->glucosa], [($this->resultado)($this->sodio, '140')]);
    expect([SolicitudLaboratorioItem::withTrashed()->count(), SolicitudLaboratorioPerfil::withTrashed()->count()])->toBe($antes)
        ->and(SolicitudLaboratorioResultado::where('valor', '140')->count())->toBe(1);
});

it('quitar el perfil quita sus exámenes y resultados (soft delete); volver a ponerlo crea filas nuevas', function () {
    $datos = ($this->crear)([$this->ionograma, $this->glucosa], [($this->resultado)($this->potasio, '4.1')]);

    ($this->editar)($datos['id'], [$this->glucosa]);
    expect(SolicitudLaboratorioPerfil::count())->toBe(0)
        ->and(SolicitudLaboratorioPerfil::onlyTrashed()->count())->toBe(1)
        ->and(SolicitudLaboratorioItem::pluck('producto_nombre')->all())->toBe(['GLUCOSA'])
        ->and(SolicitudLaboratorioResultado::onlyTrashed()->where('valor', '4.1')->count())->toBe(1)
        ->and((float) Solicitude::find($datos['id'])->total)->toBe(20.0);

    ($this->editar)($datos['id'], [$this->glucosa, $this->ionograma]);
    expect(SolicitudLaboratorioPerfil::count())->toBe(1)
        ->and(SolicitudLaboratorioItem::orderBy('orden')->pluck('producto_nombre')->all())->toBe(['GLUCOSA', 'SODIO', 'POTASIO', 'CLORO'])
        ->and((float) Solicitude::find($datos['id'])->total)->toBe(120.0);
});

it('al editar vuelve a expandir con la composición actual del catálogo', function () {
    $datos = ($this->crear)([$this->ionograma], [($this->resultado)($this->cloro, '101')]);
    ProductoPerfilItem::where('perfil_id', $this->ionograma->id)->where('producto_id', $this->cloro->id)->delete();
    ProductoPerfilItem::create(['perfil_id' => $this->ionograma->id, 'producto_id' => $this->calcio->id, 'orden' => 40]);

    ($this->editar)($datos['id'], [$this->ionograma]);
    expect(SolicitudLaboratorioItem::orderBy('orden')->pluck('producto_nombre')->all())->toBe(['SODIO', 'POTASIO', 'CALCIO'])
        ->and(SolicitudLaboratorioResultado::onlyTrashed()->where('valor', '101')->count())->toBe(1);
});

it('bloquea un perfil no vendible y nombra el perfil y el examen', function () {
    $datos = ($this->crear)([$this->ionograma]);
    $this->potasio->update(['tipo_producto_id' => $this->farmacia->id]);
    $mensaje = 'El perfil IONOGRAMA no se puede usar: su examen POTASIO no pertenece a un área de laboratorio.';

    expect(mensajeDe(fn () => ($this->crear)([$this->ionograma])))->toBe($mensaje)
        ->and(mensajeDe(fn () => ($this->editar)($datos['id'], [$this->ionograma])))->toBe($mensaje)
        ->and(Solicitude::count())->toBe(1);
});

it('con los perfiles apagados: rechaza el perfil, no edita solicitudes con perfiles y lo demás sigue igual', function () {
    $conPerfil = ($this->crear)([$this->ionograma]);
    config(['laboratorio.perfiles_habilitado' => false]);

    expect(mensajeDe(fn () => ($this->crear)([$this->ionograma])))->toBe('Seleccione únicamente productos de laboratorio.')
        ->and(mensajeDe(fn () => ($this->editar)($conPerfil['id'], [$this->glucosa])))
        ->toBe('Esta solicitud incluye perfiles de exámenes y los perfiles están deshabilitados: no se puede modificar.');

    $sueltos = ($this->crear)([$this->glucosa, $this->sodio]);
    expect((float) $sueltos['total'])->toBe(63.0)
        ->and(collect($sueltos['laboratorio_items'])->map(fn ($i) => [$i['producto_nombre'], (float) $i['precio'], $i['orden']])->all())
        ->toBe([['GLUCOSA', 20.0, 10], ['SODIO', 43.0, 20]])
        ->and($sueltos)->not->toHaveKey('avisos');
});

it('vende el perfil como una línea si es vendible; si no, o con los perfiles apagados, lo rechaza', function () {
    $vender = fn () => (new VentaController)->store(($this->peticion)([
        'paciente_id' => $this->paciente->id, 'tipo_pago' => 'EFECTIVO', 'pago' => 100,
        'detalles' => [['producto_id' => $this->ionograma->id, 'precio' => 100, 'cantidad' => 1]],
    ]));

    $vender();
    $venta = Venta::with('detalles')->first();
    expect($venta->detalles->map->only(['nombre', 'producto_id', 'lote'])->all())
        ->toBe([['nombre' => 'IONOGRAMA', 'producto_id' => $this->ionograma->id, 'lote' => null]])
        ->and((float) $venta->total)->toBe(100.0);

    $this->cloro->update(['tipo_producto_id' => null]);
    expect(mensajeDe($vender))->toBe('El perfil IONOGRAMA no se puede usar: su examen CLORO no pertenece a un área de laboratorio.');

    $this->cloro->update(['tipo_producto_id' => $this->area->id]);
    config(['laboratorio.perfiles_habilitado' => false]);
    expect(mensajeDe($vender))->toBe('IONOGRAMA es un perfil de exámenes: todavía no se puede vender')
        ->and(Venta::count())->toBe(1);
});

it('no carga un perfil a una internación', function () {
    $peticion = ($this->peticion)(['producto_id' => $this->ionograma->id, 'nombre' => 'IONOGRAMA', 'cantidad' => 1, 'precio' => 100]);

    expect(mensajeDe(fn () => (new InternacionItemController)->store($peticion, 1)))
        ->toBe('Los perfiles de exámenes no se pueden cargar a una internación: agregue sus exámenes por separado.');
});

it('el kardex cuenta los exámenes expandidos una vez, aunque dos perfiles compartan uno', function () {
    $reactivo = Reactivo::create(['codigo' => 'R-CL', 'nombre' => 'REACTIVO CLORO', 'unidad' => 'ML', 'stock_actual' => 0, 'stock_minimo' => 0, 'estado' => 'ACTIVO']);
    ServicioLaboratorioReactivo::create(['producto_id' => $this->cloro->id, 'reactivo_id' => $reactivo->id, 'cantidad' => 2]);
    ($this->crear)([$this->ionograma, $this->renal], [($this->resultado)($this->cloro, '100')]);

    $kardex = ReactivoKardex::datos($reactivo, '2026-11-01', '2026-11-30');
    expect($kardex['cantidad_pruebas'])->toBe(1)
        ->and($kardex['total_salidas'])->toBe(2.0);
});

it('el reporte general suma el perfil una vez y muestra sus exámenes como incluidos', function () {
    ($this->crear)([$this->ionograma, $this->glucosa]);
    $peticion = ($this->peticion)(['desde' => '2026-11-01', 'hasta' => '2026-11-30'], 'GET');
    $datos = (new LaboratorioReporteController)->index($peticion)->getData(true);

    // El JSON decodifica 120.0 como entero: se compara con toEqual.
    expect($datos['resumen'])->toEqual(['pruebas' => 4, 'perfiles' => 1, 'solicitudes' => 1, 'importe' => 120])
        ->and(collect($datos['data'])->map(fn ($f) => [$f['clase'], $f['producto_nombre'], $f['precio'], $f['perfil_nombre']])->all())
        ->toBe([
            ['perfil', 'IONOGRAMA', '100.00', null],
            ['examen', 'SODIO', '0.00', 'IONOGRAMA'],
            ['examen', 'POTASIO', '0.00', 'IONOGRAMA'],
            ['examen', 'CLORO', '0.00', 'IONOGRAMA'],
            ['examen', 'GLUCOSA', '20.00', null],
        ]);
});

/** Resumen de los bloques del informe: [tipo, título, filas] para comparar fácil. */
function bloquesDe(int $solicitudId): array
{
    $solicitud = Solicitude::with(['laboratorioItems.producto.tipoProducto', 'laboratorioItems.resultados', 'laboratorioItems.perfil'])->find($solicitudId);
    $bloques = (new ReflectionMethod(SolicitudeController::class, 'bloquesInforme'))->invoke(new SolicitudeController, $solicitud);

    return array_map(fn ($b) => match ($b['tipo']) {
        'perfil' => ['perfil', $b['titulo'], array_map(fn ($f) => match ($f['tipo']) {
            'area' => 'ÁREA '.$f['nombre'], 'examen' => '['.$f['nombre'].']', default => $f['nombre'],
        }, $b['filas'])],
        'grupo' => ['grupo', $b['titulo'], array_column($b['filas'], 'nombre')],
        default => ['producto', $b['item']->producto_nombre],
    }, $bloques);
}

it('informe: el perfil va en su posición, con fila de área al cambiar y sin grupo_reporte adentro', function () {
    $hemato = TipoProducto::create(['nombre' => 'HEMATOLOGIA', 'tipo_producto_padre_id' => $this->area->tipo_producto_padre_id]);
    $hemograma = Producto::create(['nombre' => 'HEMOGRAMA', 'precio' => 30, 'tipo_producto_id' => $hemato->id]);
    foreach (['HEMOGLOBINA', 'HEMATOCRITO'] as $i => $dato) {
        ProductoLaboratorioDato::create(['producto_id' => $hemograma->id, 'nombre' => $dato, 'nombre_variable' => mb_strtolower($dato), 'orden' => $i]);
    }
    $mixto = Producto::create(['nombre' => 'PERFIL MIXTO', 'precio' => 120, 'tipo_producto_id' => $this->area->id, 'es_perfil' => true]);
    foreach ([$this->sodio, $hemograma, $this->cloro] as $i => $examen) {
        ProductoPerfilItem::create(['perfil_id' => $mixto->id, 'producto_id' => $examen->id, 'orden' => $i]);
    }
    // Con grupo_reporte: suelto se agrupa; dentro del perfil se ignora.
    $this->sodio->update(['grupo_reporte' => 'Electrolitos']);
    $this->potasio->update(['grupo_reporte' => 'Electrolitos']);

    $datos = ($this->crear)([$this->glucosa, $mixto, $this->potasio]);

    expect(bloquesDe($datos['id']))->toBe([
        ['producto', 'GLUCOSA'],
        ['perfil', 'PERFIL MIXTO', [
            'ÁREA IONOGRAMA / ELECTROLITOS', 'SODIO',
            'ÁREA HEMATOLOGIA', '[HEMOGRAMA]', 'HEMOGLOBINA', 'HEMATOCRITO',
            'ÁREA IONOGRAMA / ELECTROLITOS', 'CLORO',
        ]],
        ['grupo', 'Electrolitos', ['POTASIO']],
    ]);

    // Verificación pública: cada examen dice de qué perfil viene.
    $codigo = Solicitude::find($datos['id'])->codigo_verificacion;
    $publico = (new SolicitudeController)->verificacion($codigo)->getData(true);
    expect(array_map(fn ($l) => [$l['nombre'], $l['perfil']], $publico['laboratorios']))
        ->toBe([['GLUCOSA', null], ['SODIO', 'PERFIL MIXTO'], ['HEMOGRAMA', 'PERFIL MIXTO'], ['CLORO', 'PERFIL MIXTO'], ['POTASIO', null]]);
});

it('informe: dos perfiles que comparten un examen lo muestran una vez, en el primero', function () {
    $datos = ($this->crear)([$this->ionograma, $this->renal]);

    expect(bloquesDe($datos['id']))->toBe([
        ['perfil', 'IONOGRAMA', ['ÁREA IONOGRAMA / ELECTROLITOS', 'SODIO', 'POTASIO', 'CLORO']],
        ['perfil', 'PERFIL MINERAL', ['ÁREA IONOGRAMA / ELECTROLITOS', 'CALCIO']],
    ]);
});

it('informe: un perfil sin resultados visibles no se imprime, ni su título solo', function () {
    $ocultos = array_map(fn ($examen) => [...($this->resultado)($examen, '1'), 'visible' => false], [$this->sodio, $this->potasio, $this->cloro]);
    $datos = ($this->crear)([$this->ionograma, $this->glucosa], $ocultos);

    expect(bloquesDe($datos['id']))->toBe([['producto', 'GLUCOSA']]);
});

it('informe: usa el nombre del perfil guardado en la solicitud aunque el catálogo cambie', function () {
    $datos = ($this->crear)([$this->ionograma]);
    $this->ionograma->update(['nombre' => 'ELECTROLITOS NUEVO']);
    $this->ionograma->delete();

    expect(bloquesDe($datos['id'])[0][1])->toBe('IONOGRAMA');
});
