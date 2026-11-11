<?php

use App\Http\Controllers\ProductoLaboratorioController;
use App\Http\Controllers\ReactivoController;
use App\Models\Producto;
use App\Models\ProductoLaboratorioDato;
use App\Models\ProductoPerfilItem;
use App\Models\TipoProducto;
use App\Models\TipoProductoPadre;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Models\Audit;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    // Esquema aislado en SQLite con solo las columnas que usan los perfiles: no ejecuta las migraciones.
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'laboratorio.perfiles_habilitado' => true,
        'audit.console' => true,
    ]);
    app('db')->purge('sqlite');

    $tabla = function (string $nombre, array $textos, ?Closure $extra = null, bool $soft = true) {
        Schema::create($nombre, function (Blueprint $table) use ($textos, $extra, $soft) {
            $table->id();
            foreach ($textos as $campo) {
                $table->string($campo)->nullable();
            }
            $extra && $extra($table);
            $table->timestamps();
            $soft && $table->softDeletes();
        });
    };
    $tabla('tipo_producto_padres', ['nombre', 'color', 'icono', 'orden'], fn ($t) => $t->boolean('es_laboratorio')->default(false));
    $tabla('tipo_productos', ['tipo_producto_padre_id', 'nombre', 'color', 'orden'], fn ($t) => $t->boolean('es_laboratorio')->default(false));
    $tabla('productos', ['codigo', 'nombre', 'nombre_comercial', 'descripcion', 'marca', 'fabricante_id', 'unidad_id',
        'tipo_producto_id', 'grupo_reporte', 'orden_reporte', 'precio', 'precio_seguro'], fn ($t) => $t->boolean('es_perfil')->default(false));
    $tabla('producto_perfil_items', [], function ($t) {
        $t->unsignedBigInteger('perfil_id');
        $t->unsignedBigInteger('producto_id');
        $t->unsignedInteger('orden')->default(0);
        $t->unique(['perfil_id', 'producto_id']);
    }, false);
    $tabla('producto_laboratorio_datos', ['producto_id', 'nombre', 'nombre_variable', 'unidad', 'metodo', 'muestra',
        'rango_referencia', 'valor_defecto', 'orden'], fn ($t) => $t->boolean('visible')->default(true));
    $tabla('producto_laboratorio_formulas', ['producto_id', 'producto_laboratorio_dato_id', 'nombre', 'nombre_variable',
        'formula', 'unidad', 'orden', 'visible']);
    $tabla('producto_laboratorio_validaciones', ['producto_id', 'expresion', 'operador', 'valor', 'valor_hasta',
        'mensaje', 'activo', 'orden']);
    $tabla('servicio_laboratorio_reactivos', ['producto_id', 'reactivo_id', 'cantidad']);
    Schema::create('audits', function (Blueprint $table) {
        $table->id();
        $table->string('user_type')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('event');
        $table->morphs('auditable');
        $table->text('old_values')->nullable();
        $table->text('new_values')->nullable();
        $table->text('url')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->string('user_agent', 1023)->nullable();
        $table->string('tags')->nullable();
        $table->timestamps();
    });

    $padreLab = TipoProductoPadre::create(['nombre' => 'LABORATORIO', 'es_laboratorio' => true]);
    $padreFarmacia = TipoProductoPadre::create(['nombre' => 'FARMACIA', 'es_laboratorio' => false]);
    $this->area = TipoProducto::create(['nombre' => 'IONOGRAMA / ELECTROLITOS', 'tipo_producto_padre_id' => $padreLab->id]);
    $this->farmacia = TipoProducto::create(['nombre' => 'FARMACIA', 'tipo_producto_padre_id' => $padreFarmacia->id]);
    $examen = fn (string $nombre, $tipo = null) => Producto::create([
        'nombre' => $nombre, 'precio' => 43, 'tipo_producto_id' => ($tipo ?? $this->area)->id,
    ]);
    $this->sodio = $examen('SODIO');
    $this->potasio = $examen('POTASIO');
    $this->cloro = $examen('CLORO');
    $this->paracetamol = $examen('PARACETAMOL', $this->farmacia);
    $this->ionograma = $examen('IONOGRAMA');

    $usuario = Mockery::mock(User::class)->makePartial();
    $usuario->shouldReceive('hasPermissionTo')->andReturnTrue();
    $this->peticion = function (array $datos = [], string $metodo = 'PUT') use ($usuario) {
        $request = Request::create('/', $metodo, $datos);
        $request->setUserResolver(fn () => $usuario);

        return $request;
    };
    $this->guardarPerfil = fn (Producto $perfil, array $datos) => (new ProductoLaboratorioController)
        ->updatePerfil(($this->peticion)($datos), $perfil)->getData(true);
    $this->ids = fn (...$productos) => array_map(fn ($p) => $p->id, $productos);
});

/** Mensajes de la ValidationException que lanza $accion. */
function erroresDe(Closure $accion): array
{
    try {
        $accion();
    } catch (ValidationException $e) {
        return collect($e->errors())->flatten()->all();
    }
    throw new RuntimeException('Se esperaba una ValidationException');
}

it('crea el perfil, lo reordena y audita cada cambio de composición', function () {
    $datos = ($this->guardarPerfil)($this->ionograma, [
        'es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio, $this->cloro),
    ]);

    expect($datos['es_perfil'])->toBeTrue()
        ->and($datos['vendible'])->toBeTrue()
        ->and(array_column($datos['examenes'], 'nombre'))->toBe(['SODIO', 'POTASIO', 'CLORO'])
        ->and($this->ionograma->fresh()->examenes->pluck('nombre')->all())->toBe(['SODIO', 'POTASIO', 'CLORO'])
        ->and($this->sodio->perfiles->pluck('nombre')->all())->toBe(['IONOGRAMA']);

    // Reordenar y quitar uno: se actualiza el orden y se borra la fila quitada.
    $datos = ($this->guardarPerfil)($this->ionograma, [
        'es_perfil' => true, 'producto_ids' => ($this->ids)($this->cloro, $this->sodio),
    ]);
    expect(array_column($datos['examenes'], 'nombre'))->toBe(['CLORO', 'SODIO'])
        ->and(ProductoPerfilItem::count())->toBe(2);

    $eventos = Audit::where('auditable_type', ProductoPerfilItem::class)->pluck('event')->countBy()->all();
    expect($eventos)->toBe(['created' => 3, 'updated' => 2, 'deleted' => 1])
        ->and(Audit::where('auditable_type', Producto::class)->where('auditable_id', $this->ionograma->id)
            ->where('event', 'updated')->exists())->toBeTrue();
});

it('rechaza perfiles inválidos', function (Closure $preparar, Closure $datos, string $mensaje) {
    $preparar($this);
    $errores = erroresDe(fn () => ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, ...$datos($this)]));

    expect(implode(' | ', $errores))->toContain($mensaje)
        ->and($this->ionograma->fresh()->es_perfil)->toBeFalse()
        ->and(ProductoPerfilItem::count())->toBe(0);
})->with([
    'un solo examen' => [fn () => null, fn ($t) => ['producto_ids' => [$t->sodio->id]], 'al menos 2 exámenes'],
    'examen repetido' => [fn () => null, fn ($t) => ['producto_ids' => [$t->sodio->id, $t->sodio->id]], 'no puede repetirse'],
    'examen de farmacia' => [fn () => null, fn ($t) => ['producto_ids' => [$t->sodio->id, $t->paracetamol->id]], 'PARACETAMOL no es un examen de laboratorio'],
    'se incluye a sí mismo' => [fn () => null, fn ($t) => ['producto_ids' => [$t->sodio->id, $t->ionograma->id]], 'no puede incluirse a sí mismo'],
    'examen dado de baja' => [fn ($t) => $t->cloro->delete(), fn ($t) => ['producto_ids' => [$t->sodio->id, $t->cloro->id]], 'CLORO está dado de baja'],
    'examen inexistente' => [fn () => null, fn ($t) => ['producto_ids' => [$t->sodio->id, 999]], '#999 no existe'],
    'perfil dentro de otro' => [
        fn ($t) => $t->potasio->update(['es_perfil' => true]),
        fn ($t) => ['producto_ids' => [$t->sodio->id, $t->potasio->id]],
        'un perfil no puede incluir otro perfil',
    ],
    'producto con parámetros' => [
        fn ($t) => ProductoLaboratorioDato::create(['producto_id' => $t->ionograma->id, 'nombre' => 'RESULTADO', 'nombre_variable' => 'resultado']),
        fn ($t) => ['producto_ids' => [$t->sodio->id, $t->potasio->id]],
        'tiene parámetros propios',
    ],
]);

it('no marca como perfil a un examen que ya forma parte de un perfil', function () {
    ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio)]);

    $errores = erroresDe(fn () => ($this->guardarPerfil)($this->sodio, [
        'es_perfil' => true, 'producto_ids' => ($this->ids)($this->potasio, $this->cloro),
    ]));
    expect(implode(' ', $errores))->toContain('forma parte del perfil IONOGRAMA');
});

it('impide dar de baja un examen de un perfil vigente, aun con los perfiles apagados', function (bool $habilitado) {
    ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio)]);
    config(['laboratorio.perfiles_habilitado' => $habilitado]);

    expect(erroresDe(fn () => $this->sodio->delete())[0])
        ->toBe('No se puede dar de baja SODIO: forma parte del perfil IONOGRAMA. Quítelo primero del perfil.')
        ->and($this->sodio->fresh()->trashed())->toBeFalse();

    // Dar de baja el perfil no toca sus exámenes; después el examen ya se puede dar de baja.
    $this->ionograma->delete();
    expect($this->sodio->fresh()->trashed())->toBeFalse()
        ->and(ProductoPerfilItem::count())->toBe(2);
    $this->sodio->delete();
    expect($this->sodio->fresh()->trashed())->toBeTrue();
})->with([true, false]);

it('deja de ser vendible si un examen sale de laboratorio', function () {
    ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio)]);
    expect($this->ionograma->fresh()->esPerfilVendible())->toBeTrue();

    $this->potasio->update(['tipo_producto_id' => $this->farmacia->id]);
    expect($this->ionograma->fresh()->esPerfilVendible())->toBeFalse()
        ->and($this->sodio->fresh()->esPerfilVendible())->toBeFalse();
});

it('desmarcar el perfil quita sus exámenes', function () {
    ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio)]);
    $datos = ($this->guardarPerfil)($this->ionograma, ['es_perfil' => false]);

    expect($datos['es_perfil'])->toBeFalse()
        ->and($datos['examenes'])->toBe([])
        ->and(ProductoPerfilItem::count())->toBe(0);
});

it('un perfil no admite parámetros, fórmulas, validaciones ni reactivos propios', function () {
    ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio)]);
    $controller = new ProductoLaboratorioController;
    $peticion = ($this->peticion)(['nombre' => 'X', 'nombre_variable' => 'x'], 'POST');

    foreach (['storeDato', 'storeFormula', 'storeValidacion'] as $metodo) {
        expect(fn () => $controller->{$metodo}($peticion, $this->ionograma))
            ->toThrow(HttpException::class, 'Un perfil no tiene parámetros');
    }

    $reactivo = ($this->peticion)([
        'nombre' => 'R', 'unidad' => 'ML', 'stock_actual' => 0, 'stock_minimo' => 0, 'estado' => 'ACTIVO',
        'servicios' => [['producto_id' => $this->ionograma->id, 'cantidad' => 1]],
    ], 'POST');
    $validar = (new ReflectionMethod(ReactivoController::class, 'validated'));
    expect(erroresDe(fn () => $validar->invoke(new ReactivoController, $reactivo))[0])
        ->toContain('Un perfil no tiene reactivos propios: IONOGRAMA');
});

it('con los perfiles apagados el endpoint responde 404 y la configuración no expone el perfil', function () {
    config(['laboratorio.perfiles_habilitado' => false]);

    expect(fn () => ($this->guardarPerfil)($this->ionograma, ['es_perfil' => true, 'producto_ids' => ($this->ids)($this->sodio, $this->potasio)]))
        ->toThrow(NotFoundHttpException::class);
    expect($this->ionograma->fresh()->es_perfil)->toBeFalse();

    $show = (new ProductoLaboratorioController)->show(($this->peticion)([], 'GET'), $this->sodio)->getData(true);
    expect($show)->not->toHaveKey('perfil');
});
