<?php

use App\Models\AlmacenItem;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Grupo;
use App\Models\Partida;
use App\Models\Proveedor;
use App\Models\Subpartida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('lista productos vencidos con existencia', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'Módulo inventario']);
    $user->givePermissionTo('Módulo inventario');

    Sanctum::actingAs($user);

    $grupo = Grupo::create(['num' => 1, 'codigo' => '01', 'nombre' => 'Grupo']);
    $partida = Partida::create(['grupo_id' => $grupo->id, 'num' => 1, 'codigo' => '01-01', 'nombre' => 'Partida']);
    $subpartida = Subpartida::create(['partida_id' => $partida->id, 'num' => 1, 'codigo' => '01-01-01', 'nombre' => 'Subpartida']);
    $producto = AlmacenItem::create([
        'subpartida_id' => $subpartida->id,
        'nombre' => 'Reactivo X',
        'unidad_medida' => 'u',
        'precio_unitario' => 10,
    ]);

    $proveedor = Proveedor::create(['nombre' => 'Proveedor 1']);

    $compra = Compra::create([
        'user_id' => $user->id,
        'proveedor_id' => $proveedor->id,
        'fecha_hora' => now(),
        'tipo_registro' => 'ENTRADA',
        'motivo_registro' => 'COMPRA',
        'estado' => 'ACTIVO',
        'total' => 100,
        'tipo_pago' => 'EFECTIVO',
        'nro_factura' => 'F-1',
    ]);

    // Vencido con existencia
    CompraDetalle::create([
        'compra_id' => $compra->id,
        'user_id' => $user->id,
        'proveedor_id' => $proveedor->id,
        'producto_id' => $producto->id,
        'nombre' => $producto->nombre,
        'cantidad' => 10,
        'cantidad_venta' => 4,
        'estado' => 'ACTIVO',
        'lote' => 'L-01',
        'fecha_vencimiento' => now()->subDays(2)->toDateString(),
        'nro_factura' => 'F-1',
    ]);

    // No vencido
    CompraDetalle::create([
        'compra_id' => $compra->id,
        'user_id' => $user->id,
        'proveedor_id' => $proveedor->id,
        'producto_id' => $producto->id,
        'nombre' => $producto->nombre,
        'cantidad' => 10,
        'cantidad_venta' => 0,
        'estado' => 'ACTIVO',
        'fecha_vencimiento' => now()->addDays(3)->toDateString(),
    ]);

    // Vencido sin existencia
    CompraDetalle::create([
        'compra_id' => $compra->id,
        'user_id' => $user->id,
        'proveedor_id' => $proveedor->id,
        'producto_id' => $producto->id,
        'nombre' => $producto->nombre,
        'cantidad' => 2,
        'cantidad_venta' => 2,
        'estado' => 'ACTIVO',
        'fecha_vencimiento' => now()->subDays(1)->toDateString(),
    ]);

    $response = $this->getJson('/api/almacen/productos-vencidos');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.existencia', 6);
    $response->assertJsonPath('data.0.compra.nro_factura', 'F-1');
    $response->assertJsonPath('data.0.compra.user.name', $user->name);
});

