<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SeguroController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\InternacionController;
use App\Http\Controllers\InternacionItemController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ────────────────────────────────────────────
Route::post('/login', [UserController::class, 'login']);

// ── Rutas protegidas ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',               [UserController::class, 'me']);
    Route::post('/logout',          [UserController::class, 'logout']);
    Route::put('/cambiar-password', [UserController::class, 'changePassword']);

    // CRUD usuarios
    Route::get('/users',                         [UserController::class, 'index']);
    Route::post('/users',                        [UserController::class, 'store']);
    Route::put('/users/{id}',                    [UserController::class, 'update']);
    Route::delete('/users/{id}',                 [UserController::class, 'destroy']);
    Route::put('/users/{id}/reset-password',     [UserController::class, 'resetPassword']);
    Route::post('/users/{id}/avatar',            [UserController::class, 'uploadAvatar']);

    // Permisos
    Route::get('/permissions',                   [UserController::class, 'permissions']);
    Route::get('/users/{id}/permissions',        [UserController::class, 'userPermissions']);
    Route::put('/users/{id}/permissions',        [UserController::class, 'updateUserPermissions']);

    // Farmacia
    Route::get('/farmacia/datos',               [ProductoController::class, 'datos']);
    Route::get('/farmacia/resumen',              [ProductoController::class, 'resumen']);

    // Catálogos - Fabricantes
    Route::get('/fabricantes/export-pdf',        [ProductoController::class, 'exportFabricantesPdf']);
    Route::get('/fabricantes/export-excel',      [ProductoController::class, 'exportFabricantesExcel']);
    Route::get('/fabricantes',                   [ProductoController::class, 'fabricantes']);
    Route::post('/fabricantes',                  [ProductoController::class, 'storeFabricante']);
    Route::put('/fabricantes/{id}',              [ProductoController::class, 'updateFabricante']);
    Route::delete('/fabricantes/{id}',           [ProductoController::class, 'destroyFabricante']);

    // Catálogos - Unidades
    Route::get('/unidades/export-pdf',           [ProductoController::class, 'exportUnidadesPdf']);
    Route::get('/unidades/export-excel',         [ProductoController::class, 'exportUnidadesExcel']);
    Route::get('/unidades',                      [ProductoController::class, 'unidades']);
    Route::post('/unidades',                     [ProductoController::class, 'storeUnidad']);
    Route::put('/unidades/{id}',                 [ProductoController::class, 'updateUnidad']);
    Route::delete('/unidades/{id}',              [ProductoController::class, 'destroyUnidad']);

    // Catálogos - Tipos de producto
    Route::get('/tipo-productos',                [ProductoController::class, 'tiposProducto']);
    Route::post('/tipo-productos',               [ProductoController::class, 'storeTipoProducto']);
    Route::put('/tipo-productos/{id}',           [ProductoController::class, 'updateTipoProducto']);
    Route::delete('/tipo-productos/{id}',        [ProductoController::class, 'destroyTipoProducto']);

    // Productos
    Route::get('/productos/export-pdf',          [ProductoController::class, 'exportProductosPdf']);
    Route::get('/productos/export-excel',        [ProductoController::class, 'exportProductosExcel']);
    Route::get('/productos',                     [ProductoController::class, 'index']);
    Route::post('/productos',                    [ProductoController::class, 'store']);
    Route::put('/productos/{id}',                [ProductoController::class, 'update']);
    Route::delete('/productos/{id}',             [ProductoController::class, 'destroy']);

    // Seguros
    Route::get('/seguros',                       [SeguroController::class, 'index']);
    Route::post('/seguros',                      [SeguroController::class, 'store']);
    Route::put('/seguros/{id}',                  [SeguroController::class, 'update']);
    Route::delete('/seguros/{id}',               [SeguroController::class, 'destroy']);

    // Pacientes
    Route::get('/pacientes',                     [PacienteController::class, 'index']);
    Route::get('/pacientes/{id}',                [PacienteController::class, 'show']);
    Route::post('/pacientes',                    [PacienteController::class, 'store']);
    Route::put('/pacientes/{id}',                [PacienteController::class, 'update']);
    Route::delete('/pacientes/{id}',             [PacienteController::class, 'destroy']);

    // Internaciones
    Route::get('/internaciones',                          [InternacionController::class, 'index']);
    Route::get('/internaciones/{id}/pdf',                 [InternacionController::class, 'pdf']);
    Route::post('/internaciones',                         [InternacionController::class, 'store']);
    Route::put('/internaciones/{id}',                     [InternacionController::class, 'update']);
    Route::delete('/internaciones/{id}',                  [InternacionController::class, 'destroy']);

    // Cargos de internación (productos/servicios)
    Route::post('/internaciones/{internacionId}/items',   [InternacionItemController::class, 'store']);
    Route::put('/internacion-items/{id}',                 [InternacionItemController::class, 'update']);
    Route::delete('/internacion-items/{id}',              [InternacionItemController::class, 'destroy']);

    // Proveedores
    Route::get('/proveedores',                   [ProveedorController::class, 'index']);
    Route::post('/proveedores',                  [ProveedorController::class, 'store']);
    Route::put('/proveedores/{id}',              [ProveedorController::class, 'update']);
    Route::delete('/proveedores/{id}',           [ProveedorController::class, 'destroy']);

    // Compras
    Route::get('/compras/export-excel',          [CompraController::class, 'exportExcel']);
    Route::get('/compras',                       [CompraController::class, 'index']);
    Route::get('/compras/{id}',                  [CompraController::class, 'show']);
    Route::post('/compras',                      [CompraController::class, 'store']);
    Route::delete('/compras/{id}',               [CompraController::class, 'destroy']);
});
