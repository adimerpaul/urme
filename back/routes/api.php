<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductoController;
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

    // Catálogos - Fabricantes
    Route::get('/fabricantes',                   [ProductoController::class, 'fabricantes']);
    Route::post('/fabricantes',                  [ProductoController::class, 'storeFabricante']);
    Route::put('/fabricantes/{id}',              [ProductoController::class, 'updateFabricante']);
    Route::delete('/fabricantes/{id}',           [ProductoController::class, 'destroyFabricante']);

    // Catálogos - Unidades
    Route::get('/unidades',                      [ProductoController::class, 'unidades']);
    Route::post('/unidades',                     [ProductoController::class, 'storeUnidad']);
    Route::put('/unidades/{id}',                 [ProductoController::class, 'updateUnidad']);
    Route::delete('/unidades/{id}',              [ProductoController::class, 'destroyUnidad']);

    // Productos
    Route::get('/productos',                     [ProductoController::class, 'index']);
    Route::post('/productos',                    [ProductoController::class, 'store']);
    Route::put('/productos/{id}',                [ProductoController::class, 'update']);
    Route::delete('/productos/{id}',             [ProductoController::class, 'destroy']);
});
