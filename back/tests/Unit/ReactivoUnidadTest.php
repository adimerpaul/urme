<?php

use App\Models\Reactivo;

/*
 * Mutador de la unidad de reactivos. No toca la base: el modelo se instancia sin
 * constructor (sin arrancar la app ni los traits) y se lee el atributo guardado.
 */
function unidadGuardada(string $valor): string
{
    $reactivo = (new ReflectionClass(Reactivo::class))->newInstanceWithoutConstructor();
    $reactivo->setUnidadAttribute($valor);

    return $reactivo->getAttributes()['unidad'];
}

it('guarda la unidad normalizada', function (string $entrada, string $esperado, string $hex) {
    $guardado = unidadGuardada($entrada);

    expect($guardado)->toBe($esperado)
        ->and(strtoupper(bin2hex($guardado)))->toBe($hex);
})->with([
    'µL (signo micro)' => ["\u{00B5}L", "\u{00B5}L", 'C2B54C'],
    'μL (mu griega)' => ["\u{03BC}L", "\u{00B5}L", 'C2B54C'],
    'µl (minúscula)' => ["\u{00B5}l", "\u{00B5}L", 'C2B54C'],
    'ml' => ['ml', 'ML', '4D4C'],
    'determinación' => ['determinación', 'DETERMINACIÓN', '44455445524D494E414349C3934E'],
    'taco' => ['taco', 'TACO', '5441434F'],
]);
