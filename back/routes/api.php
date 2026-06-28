<?php

use App\Http\Controllers\UserController;
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

    // Permisos
    Route::get('/permissions',                   [UserController::class, 'permissions']);
    Route::get('/users/{id}/permissions',        [UserController::class, 'userPermissions']);
    Route::put('/users/{id}/permissions',        [UserController::class, 'updateUserPermissions']);
});
