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
            $search = $request->input('search'); // Get the search query

            $usersQuery = User::with(['empresa']);

            // Apply search filter
            $usersQuery->when($search, function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%") // Assuming 'phone' field exists
                        ->orWhere('roles', 'like', "%{$search}%") // Search by role
                        // Add search for related company name
                        ->orWhereHas('empresa', function ($q3) use ($search) {
                            $q3->where('nombre', 'like', "%{$search}%");
                        });
                });
            });

            $paginated = $itemsPerPage == -1
                ? $usersQuery->get()
                : $usersQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];

            // It's good practice to map the user data for consistent output,
            // especially if you want to include company name at the top level
            $usersRaw = $itemsPerPage != -1 ? $paginated->items() : $paginated;
            $users = collect($usersRaw)->map(function ($u) {
                $userData = $u->toArray();
                if ($u->empresa) {
                    $userData['empresa_nombre'] = $u->empresa->nombre;
                    // Keep empresa object if frontend needs it for other details
                    // $userData['empresa'] = $u->empresa->toArray();
                } else {
                    $userData['empresa_nombre'] = null;
                }
                return $userData;
            });


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
            // Get the authenticated user's empresa_id
            $authUser = auth()->user();
            $empresaId = $authUser->empresa_id;

            // 'roles' and 'empresa_id' validation removed as they will be hardcoded/derived
            $validator = Validator::make($request->all(), [
                'name'       => 'required|string|max:255',
                'lastname'   => 'nullable|string|max:255', // Assuming lastname can be null
                'phone'      => 'nullable|string|max:20',   // Assuming phone can be null
                'email'      => 'required|string|email|max:255|unique:users',
                'password'   => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                throw new \Exception(ResponseFormat::validatorErrorMessage($validator), 400);
            }

            DB::beginTransaction();

            $user = User::create([
                'name'       => $request->name,
                'lastname'   => $request->lastname,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'empresa_id' => $empresaId, // Set to the authenticated user's company ID
                'roles'      => 'operador', // Hardcoded to 'operador'
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
            // Eager load the 'empresa' relationship
            $user = User::with('empresa')->findOrFail($id);

            // Prepare the data for the response
            // We want all user fields, plus the company's id and name explicitly
            $userData = $user->toArray(); // Get all user attributes as an array

            // Add company_id and company_name to the top level if the company exists
            if ($user->empresa) {
                $userData['empresa_id'] = $user->empresa->id;
                $userData['empresa_nombre'] = $user->empresa->nombre;
            } else {
                $userData['empresa_id'] = null;
                $userData['empresa_nombre'] = null;
            }

            return ResponseFormat::response(200, 'Usuario obtenido con éxito.', $userData);
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
                'name'       => 'sometimes|string|max:255',
                'email'      => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password'   => 'sometimes|string|min:8',
                'empresa_id' => 'sometimes|exists:empresas,id',
                'roles'      => 'sometimes|in:admin,operador,supervisor',
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
