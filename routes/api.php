<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChoferController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\TipoCombustibleController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\VehiculoInoperatividadController; 
use App\Http\Controllers\Api\TarjetaCombustibleController;
use App\Http\Controllers\Api\CargaCombustibleController;
use App\Http\Controllers\Api\RetiroCombustibleController;

// Rutas de prueba y usuario autenticado
Route::get('/test', function () {
    return response()->json(['message' => '¡Funciona!']);
});

// Rutas públicas sin autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Rutas de autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::get('/user/is-provider', [AuthController::class, 'isProvider']);
    Route::get('/user/is-valid-buyer', [AuthController::class, 'isValidBuyer']);
    Route::get('/user/name', [AuthController::class, 'getAuthenticatedUserName']);

    // Rutas para la gestión de Usuarios
    Route::prefix('users')->group(function () {
        Route::get('/users-by-enterprise', [UserController::class, 'usersByEmpresa']); // Lista todos los usuarios cuya empresa sea la misma que el usuario autenticado
        Route::get('/', [UserController::class, 'index']); // Lista todos los usuarios
        Route::get('/{id}', [UserController::class, 'show']); // Muestra un usuario por ID
        Route::post('/', [UserController::class, 'store']); // Crea un nuevo usuario
        Route::put('/{id}', [UserController::class, 'update']); // Actualiza un usuario por ID
        Route::delete('/{id}', [UserController::class, 'destroy']); // Elimina un usuario por ID

        // Nueva ruta para asignar un rol a un usuario (asegúrate de tener este método en UserController)
        // Route::post('/{id}/assign-role', [UserController::class, 'assignRole']);
    });

    Route::prefix('choferes')->group(function () {
        Route::get('/get-names', [ChoferController::class, 'getnames']); // Lista todos los chofers
        Route::get('/', [ChoferController::class, 'index']); // Lista todos los chofers
        Route::get('/{id}', [ChoferController::class, 'show']); // Muestra un chofer por ID
        Route::post('/', [ChoferController::class, 'store']); // Crea un nuevo chofer
        Route::put('/{id}', [ChoferController::class, 'update']); // Actualiza un chofer por ID
        Route::delete('/{id}', [ChoferController::class, 'destroy']); // Elimina un chofer por ID
    });

    // Rutas para la gestión de Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']); // Lista todos los roles
        Route::get('/{id}', [RoleController::class, 'show']); // Muestra un rol por ID
        Route::post('/', [RoleController::class, 'store']); // Crea un nuevo rol
        Route::put('/{id}', [RoleController::class, 'update']); // Actualiza un rol por ID
        Route::delete('/{id}', [RoleController::class, 'destroy']); // Elimina un rol por ID
    });

    // Rutas Resource para la gestión de empresas
    Route::apiResource('empresas', EmpresaController::class);

    Route::get('/tipo-combustibles/get-names', [TipoCombustibleController::class, 'getNames']);
    // Rutas Resource para la gestión de Tipos de Combustible
    Route::apiResource('tipo-combustibles', TipoCombustibleController::class);

    // Rutas para Inoperatividades/Averías
    Route::apiResource('vehiculo-inoperatividades', VehiculoInoperatividadController::class);



    
    Route::get('/vehiculos/reportes/cdt', [VehiculoController::class, 'calculateCdt']);
    // Rutas Resource para la gestión de Vehículos
    Route::apiResource('vehiculos', VehiculoController::class);

    Route::get('tarjetas-combustible/reportes/consumo-mes', [TarjetaCombustibleController::class, 'calculateConsumoCombustible']);
    Route::get('tarjetas-combustible/get-numbers', [TarjetaCombustibleController::class, 'getNames']);
    Route::get('tarjetas-combustible/{id}/precio', [TarjetaCombustibleController::class, 'getPrecioCombustiblePorTarjeta']);
    // Rutas Resource para la gestión de Tarjetas de Combustible
    Route::apiResource('tarjetas-combustible', TarjetaCombustibleController::class);

    Route::get('/carga-combustibles/accessed-ids', [CargaCombustibleController::class, 'getAccessedChargeIds']);
    Route::post('/carga-combustibles/{id}/validar', [CargaCombustibleController::class, 'validar']); // Valida una carga de combustible
    // Rutas Resource para la gestión de Cargas de Combustible
    Route::apiResource('carga-combustibles', CargaCombustibleController::class);

    Route::post('/retiros-combustible/{id}/validar', [RetiroCombustibleController::class, 'validar']); // Valida una carga de combustible
    // Rutas Resource para la gestión de Retiros de Combustible
    Route::apiResource('retiros-combustible', RetiroCombustibleController::class);
    // Puedes agrupar rutas relacionadas por middleware si es necesario (ej: 'auth:sanctum', 'role:admin')
    /*
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('empresas', EmpresaController::class);
        // etc.
    });
    */


});
