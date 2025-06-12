<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Empresa;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            $usersQuery = User::with(['empresa']); // Eliminado 'roles'

            $paginated = $itemsPerPage == -1
                ? $usersQuery->get()
                : $usersQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];

            $users = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Usuarios obtenida con éxito.', $users, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

/**
 * Obtener usuarios de la misma empresa que el usuario autenticado.
 */
public function usersByEmpresa(Request $request)
{
    try {
        $authUser   = auth()->user();
        $empresaId  = $authUser->empresa_id;

        // Parámetros de paginación
        $itemsPerPage = $request->input("itemsPerPage", 20);
        $page         = $request->input("page", 1);

        // Query restringida a la misma empresa
        $query = User::with('empresa')
            ->where('empresa_id', $empresaId);

        // Ejecutar paginación o traer todos
        $paginated = $itemsPerPage == -1
            ? $query->get()
            : $query->paginate($itemsPerPage, ['*'], 'page', $page);

        // Construir meta uniforme
        $meta = [
            'total'     => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
            'perPage'   => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
            'page'      => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
            'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
        ];

        // Obtener colección de usuarios
        $users = $itemsPerPage != -1 ? $paginated->items() : $paginated;

        return ResponseFormat::response(
            200,
            'Usuarios de tu misma empresa obtenidos con éxito.',
            $users,
            $meta
        );

    } catch (Exception $e) {
        return ResponseFormat::exceptionResponse($e);
    }
}


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'       => 'required|string|max:255',
                'email'      => 'required|string|email|max:255|unique:users',
                'password'   => 'required|string|min:8',
                'empresa_id' => 'required|exists:empresas,id',
                'roles'      => 'required|in:admin,operador,supervisor', // Asegura valor válido para enum
            ], [
                'roles.required' => 'El rol es obligatorio.',
                'roles.in'       => 'El rol seleccionado no es válido.',
            ]);

            if ($validator->fails()) {
                throw new \Exception(ResponseFormat::validatorErrorMessage($validator), 400);
            }

            DB::beginTransaction();

            $user = User::create([
                'name'       => $request->name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'empresa_id' => $request->empresa_id,
                'roles'      => $request->roles,
            ]);

            DB::commit();

            return ResponseFormat::response(201, 'Usuario creado con éxito.', $user);

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['empresa'])->findOrFail($id); // Eliminado 'roles'
            return ResponseFormat::response(200, 'Usuario obtenido con éxito.', $user);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Usuario no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'       => 'required|string|max:255',
                'email'      => 'required|string|email|max:255|unique:users,email,' . $id,
                'password'   => 'nullable|string|min:8',
                'empresa_id' => 'required|exists:empresas,id',
                'roles'      => 'required|in:admin,operador,supervisor',
            ], [
                'roles.required' => 'El rol es obligatorio.',
                'roles.in'       => 'El rol seleccionado no es válido.',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction();

            $userData = $request->only(['name', 'email', 'empresa_id', 'roles']);

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            DB::commit();

            return ResponseFormat::response(200, 'Usuario actualizado con éxito.', $user);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Usuario no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            DB::beginTransaction();

            $user->delete(); // Usa soft delete si tienes `use SoftDeletes`

            DB::commit();

            return ResponseFormat::response(200, 'Usuario eliminado con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Usuario no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::response(500, 'Error al eliminar el Usuario. Puede tener elementos relacionados.', null);
        }
    }
}
