<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;



Route::get('/test', function () {
    return response()->json(['message' => '¡Funciona!']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Rutas para users
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']); // Obtener todos los roles
    Route::get('/{id}', [UserController::class, 'show']); // Obtener un rol por ID
    Route::post('/', [UserController::class, 'store']); // Crear un nuevo rol
    Route::put('/{id}', [UserController::class, 'update']); // Actualizar un rol
    Route::delete('/{id}', [UserController::class, 'destroy']); // Eliminar un rol

     // Nueva ruta para asignar un rol a un usuario
     Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
});


// Rutas para roles
Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']); // Obtener todos los roles
    Route::get('/{id}', [RoleController::class, 'show']); // Obtener un rol por ID
    Route::post('/', [RoleController::class, 'store']); // Crear un nuevo rol
    Route::put('/{id}', [RoleController::class, 'update']); // Actualizar un rol
    Route::delete('/{id}', [RoleController::class, 'destroy']); // Eliminar un rol
});
