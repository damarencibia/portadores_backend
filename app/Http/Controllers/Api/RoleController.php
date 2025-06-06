<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Roles con paginación.
     */
    public function index(Request $request)
    {
         try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta
            $rolesQuery = Role::query();

            // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $rolesQuery->where('name', 'like', '%' . $request->input('searchTerm') . '%');


            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $rolesQuery->get()
                : $rolesQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                 'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page
            ];

            // Obtener los elementos de la página actual
            $roles = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Roles obtenida con éxito.', $roles, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea un nuevo Rol.
     */
    public function store(Request $request)
    {
        try {
            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles',
            ], [
                 'name.required' => 'El nombre del rol es obligatorio.',
                 'name.unique' => 'El nombre del rol ya existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $role = Role::create($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(201, 'Rol creado con éxito.', $role);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra un Rol específico.
     */
    public function show($id)
    {
        try {
            $role = Role::findOrFail($id);
            return ResponseFormat::response(200, 'Rol obtenido con éxito.', $role);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Rol no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un Rol específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            // Validación manual con Validator
             $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles,name,' . $id, // Ignora el nombre actual
            ], [
                 'name.required' => 'El nombre del rol es obligatorio.',
                 'name.unique' => 'El nombre del rol ya existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $role->update($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Rol actualizado con éxito.', $role);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Rol no encontrado.', null);
        } catch (Exception $e) {
             DB::rollBack(); // Revertir transacción
             return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un Rol específico.
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);

            DB::beginTransaction(); // Iniciar transacción

            $role->delete(); // Usa soft delete si está habilitado (aunque tu migración de roles no lo tiene)

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Rol eliminado con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Rol no encontrado.', null);
        } catch (Exception $e) {
             DB::rollBack(); // Revertir transacción
             // Captura cualquier otra excepción, como restricciones de clave foránea (si el rol está asignado a usuarios)
             return ResponseFormat::response(500, 'Error al eliminar el Rol. Puede tener usuarios asignados.', null);
            // return ResponseFormat::exceptionResponse($e); // Otra opción para ver detalles del error
        }
    }
}
