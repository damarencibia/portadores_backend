<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Empresa; // Importar Empresa para validación
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash; // Para hashear la contraseña
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Usuarios con paginación.
     */
    public function index(Request $request)
    {
        try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta con relaciones cargadas
            $usersQuery = User::with(['empresa', 'roles']);

             // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $usersQuery->where('name', 'like', '%' . $request->input('searchTerm') . '%');
            // Ejemplo: $usersQuery->where('Empresa_id', $request->input('Empresa_id'));


            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $usersQuery->get()
                : $usersQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                 'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page
            ];

            // Obtener los elementos de la página actual
            $users = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Usuarios obtenida con éxito.', $users, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea un nuevo Usuario.
     */
    public function store(Request $request)
    {
        try {
            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'empresa_id' => 'required|exists:empresas,id', // Asegura que la Empresa exista
                // Puedes añadir validación para roles si los asignas aquí
            ], [
                 'name.required' => 'El nombre del usuario es obligatorio.',
                 'email.required' => 'El correo electrónico es obligatorio.',
                 'email.email' => 'El correo electrónico debe ser una dirección válida.',
                 'email.unique' => 'El correo electrónico ya está registrado.',
                 'password.required' => 'La contraseña es obligatoria.',
                 'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                 'empresa_id.required' => 'La Empresa es obligatoria.',
                 'empresa_id.exists' => 'La Empresa seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                $message = ResponseFormat::validatorErrorMessage($validator);
                throw new \Exception($message, 400);
            }

            DB::beginTransaction(); // Iniciar transacción

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Hashear la contraseña
                'empresa_id' => $request->empresa_id,
            ]);

            // Si manejas la asignación de roles al crear usuario, hazlo aquí
            // if ($request->has('role_ids')) {
            //     $user->roles()->attach($request->role_ids);
            // }

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(201, 'Usuario creado con éxito.', $user);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra un Usuario específico.
     */
    public function show($id)
    {
        try {
            // Carga la relación 'Empresa' y 'roles'
            $user = User::with(['empresa', 'roles'])->findOrFail($id);
            return ResponseFormat::response(200, 'Usuario obtenido con éxito.', $user);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Usuario no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un Usuario específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // Validación manual con Validator
             $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id, // Ignora el email actual
                'password' => 'nullable|string|min:8', // Contraseña opcional al actualizar
                'empresa_id' => 'required|exists:empresas,id', // Asegura que la Empresa exista
                 // Puedes añadir validación para roles si los actualizas aquí
            ], [
                 'name.required' => 'El nombre del usuario es obligatorio.',
                 'email.required' => 'El correo electrónico es obligatorio.',
                 'email.email' => 'El correo electrónico debe ser una dirección válida.',
                 'email.unique' => 'El correo electrónico ya está registrado.',
                 'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                 'empresa_id.required' => 'La Empresa es obligatoria.',
                 'empresa_id.exists' => 'La Empresa seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $userData = $request->only(['name', 'email', 'empresa_id']);

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password); // Hashear la nueva contraseña
            }

            $user->update($userData);

             // Si manejas la actualización de roles, hazlo aquí
            // if ($request->has('role_ids')) {
            //     $user->roles()->sync($request->role_ids);
            // }

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Usuario actualizado con éxito.', $user);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Usuario no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un Usuario específico.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction(); // Iniciar transacción

            $user->delete(); // Usa soft delete

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Usuario eliminado con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Usuario no encontrado.', null);
        } catch (Exception $e) {
             DB::rollBack(); // Revertir transacción
             // Captura cualquier otra excepción, como restricciones de clave foránea (si el usuario está asignado a vehículos, tarjetas, cargas)
             return ResponseFormat::response(500, 'Error al eliminar el Usuario. Puede tener elementos relacionados.', null);
            // return ResponseFormat::exceptionResponse($e); // Otra opción para ver detalles del error
        }
    }

    // Si tienes un método assignRole, asegúrate de que también use ResponseFormat
    // public function assignRole(Request $request, $id)
    // {
    //     try {
    //         $user = User::findOrFail($id);
    //         // ... lógica de asignación de rol ...
    //         return ResponseFormat::response(200, 'Rol asignado con éxito.', $user);
    //     } catch (ModelNotFoundException $e) {
    //         return ResponseFormat::response(404, 'Usuario no encontrado.', null);
    //     } catch (Exception $e) {
    //         return ResponseFormat::exceptionResponse($e);
    //     }
    // }
}
