<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UebController;
use App\Http\Controllers\Api\TipoCombustibleController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\TarjetaCombustibleController;
use App\Http\Controllers\Api\CargaCombustibleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rutas de prueba y usuario autenticado
Route::get('/test', function () {
    return response()->json(['message' => '¡Funciona!']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para la gestión de Usuarios
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']); // Lista todos los usuarios
    Route::get('/{id}', [UserController::class, 'show']); // Muestra un usuario por ID
    Route::post('/', [UserController::class, 'store']); // Crea un nuevo usuario
    Route::put('/{id}', [UserController::class, 'update']); // Actualiza un usuario por ID
    Route::delete('/{id}', [UserController::class, 'destroy']); // Elimina un usuario por ID

    // Nueva ruta para asignar un rol a un usuario (asegúrate de tener este método en UserController)
    // Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
});

// Rutas para la gestión de Roles
Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']); // Lista todos los roles
    Route::get('/{id}', [RoleController::class, 'show']); // Muestra un rol por ID
    Route::post('/', [RoleController::class, 'store']); // Crea un nuevo rol
    Route::put('/{id}', [RoleController::class, 'update']); // Actualiza un rol por ID
    Route::delete('/{id}', [RoleController::class, 'destroy']); // Elimina un rol por ID
});

// Rutas Resource para la gestión de UEBs
Route::apiResource('uebs', UebController::class);

// Rutas Resource para la gestión de Tipos de Combustible
Route::apiResource('tipo-combustibles', TipoCombustibleController::class);

// Rutas Resource para la gestión de Vehículos
Route::apiResource('vehiculos', VehiculoController::class);

// Rutas Resource para la gestión de Tarjetas de Combustible
Route::apiResource('tarjeta-combustibles', TarjetaCombustibleController::class);

// Rutas Resource para la gestión de Cargas de Combustible
Route::apiResource('carga-combustibles', CargaCombustibleController::class);

// Puedes agrupar rutas relacionadas por middleware si es necesario (ej: 'auth:sanctum', 'role:admin')
/*
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('uebs', UebController::class);
    // etc.
});
*/
