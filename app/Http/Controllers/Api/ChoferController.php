<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chofer;
use App\Models\Empresa;
use App\Models\TarjetaCombustible;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;

class ChoferController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Choferes con paginación y la relación empresa.
     */
    public function index(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // If no user is authenticated, return an unauthorized response
            if (!$user) {
                return ResponseFormat::response(401, 'No autenticado. Por favor, inicie sesión.');
            }

            // Get the empresa_id of the authenticated user
            // Assuming the User model has an 'empresa_id' column or a relationship to get it.
            $userEmpresaId = $user->empresa_id;

            // If the user doesn't have an associated company, they shouldn't see any choferes
            if (!$userEmpresaId) {
                return ResponseFormat::response(403, 'El usuario no tiene una empresa asociada.', []);
            }

            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);
            $search = $request->input("search"); // Assuming you have a search parameter

            // Construir la consulta con la relación 'empresa'
            $choferesQuery = Chofer::with('empresa');

            // Apply filter based on the authenticated user's empresa_id
            $choferesQuery->where('empresa_id', $userEmpresaId);

            // Add search filter if present
            if ($search) {
                $choferesQuery->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', '%' . $search . '%')
                        ->orWhere('apellidos', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            // Add sorting if needed (you can retrieve sortBy and orderBy from request)
            $sortBy = $request->input('sortBy', 'nombre'); // Default sort by nombre
            $orderBy = $request->input('orderBy', 'asc');   // Default order asc

            $choferesQuery->orderBy($sortBy, $orderBy);


            $paginated = $itemsPerPage == -1
                ? $choferesQuery->get()
                : $choferesQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];

            $choferes = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Choferes obtenida con éxito.', $choferes, $meta);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea un nuevo Chofer.
     */
    public function store(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // If no user is authenticated, return an unauthorized response
            if (!$user) {
                return ResponseFormat::response(401, 'No autenticado. Por favor, inicie sesión.');
            }

            // Get the empresa_id of the authenticated user
            $userEmpresaId = $user->empresa_id;

            // If the user doesn't have an associated company, they cannot create choferes
            if (!$userEmpresaId) {
                return ResponseFormat::response(403, 'El usuario no tiene una empresa asociada y no puede crear choferes.');
            }

            // Define validation rules
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:choferes,email',
                // 'empresa_id' is no longer validated from request, as it will be set by the authenticated user's ID
            ], [
                'nombre.required' => 'El nombre del chofer es obligatorio.',
                'apellidos.required' => 'Los apellidos del chofer son obligatorios.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico debe ser una dirección válida.',
                'email.unique' => 'El correo electrónico ya está registrado para otro chofer.',
            ]);

            if ($validator->fails()) {
                $message = ResponseFormat::validatorErrorMessage($validator);
                return ResponseFormat::response(422, $message, ['errors' => $validator->errors()]);
            }

            DB::beginTransaction();

            // Create the chofer, explicitly setting empresa_id from the authenticated user
            $chofer = Chofer::create([
                'nombre' => $request->input('nombre'),
                'apellidos' => $request->input('apellidos'),
                'email' => $request->input('email'),
                'empresa_id' => $userEmpresaId, // Set empresa_id from authenticated user
            ]);

            DB::commit();
            // Cargar la relación empresa para la respuesta
            $chofer->load('empresa');
            return ResponseFormat::response(201, 'Chofer creado exitosamente.', $chofer);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Obtener solo los nombres de los choferes.
     */
    public function getNames()
    {
        try {
            // Obtener el ID del usuario autenticado
            $userId = Auth::id();

            // Obtener la empresa_id del usuario autenticado (asumiendo que tienes una relación 'empresa' en el modelo User)
            $empresaId = DB::table('users')->where('id', $userId)->value('empresa_id');

            // Si no se encuentra la empresa_id, devolver un error o una lista vacía, según sea necesario
            if (!$empresaId) {
                return ResponseFormat::response(400, 'No se encontró la empresa del usuario.', []);
            }

            // Obtener solo los nombres de los choferes que pertenecen a la misma empresa
            $nombres = Chofer::select('id', 'nombre')
                ->where('empresa_id', $empresaId)
                ->get();

            return ResponseFormat::response(200, 'Lista de nombres obtenida correctamente.', $nombres);
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }


    /**
     * Display the specified resource.
     * Muestra un Chofer específico con su empresa.
     */
    public function show($id)
    {
        try {
            // Cargar la relación 'empresa'
            $chofer = Chofer::with('empresa')->findOrFail($id);
            return ResponseFormat::response(200, 'Chofer obtenido con éxito.', $chofer);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Chofer no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un Chofer específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $chofer = Chofer::find($id);

            if (!$chofer) {
                return ResponseFormat::response(404, 'Chofer no encontrado.');
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'sometimes|required|string|max:255',
                'apellidos' => 'sometimes|required|string|max:255',
                // Ignora el email actual del chofer al validar unicidad
                'email' => 'sometimes|required|string|email|max:255|unique:choferes,email,' . $id,
                'empresa_id' => 'sometimes|required|integer|exists:empresas,id',
            ], [
                // Mensajes de validación similares a store
                'nombre.required' => 'El nombre del chofer es obligatorio.',
                'apellidos.required' => 'Los apellidos del chofer son obligatorios.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El correo electrónico debe ser una dirección válida.',
                'email.unique' => 'El correo electrónico ya está registrado para otro chofer.',
                'empresa_id.required' => 'La empresa es obligatoria.',
                'empresa_id.integer' => 'El ID de la empresa debe ser un número entero.',
                'empresa_id.exists' => 'La empresa seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                $message = ResponseFormat::validatorErrorMessage($validator);
                return ResponseFormat::response(422, $message, ['errors' => $validator->errors()]);
            }

            DB::beginTransaction();

            $chofer->update($request->all());

            DB::commit();
            // Cargar la relación empresa para la respuesta actualizada
            $chofer->load('empresa');
            return ResponseFormat::response(200, 'Chofer actualizado con éxito.', $chofer);
        } catch (ModelNotFoundException $e) { // Específicamente para findOrFail si se usara
            DB::rollBack(); // Asegurar rollback si la transacción se inició
            return ResponseFormat::response(404, 'Chofer no encontrado.');
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un Chofer específico.
     */
    public function destroy($id)
    {
        try {
            $chofer = Chofer::find($id);
            if (!$chofer) {
                return ResponseFormat::response(404, 'Chofer no encontrado.');
            }

            DB::beginTransaction();
            $chofer->delete();
            DB::commit();

            return ResponseFormat::response(200, 'Chofer eliminado correctamente.', null, ['deleted_id' => $id]);
        } catch (ModelNotFoundException $e) { // Específicamente para findOrFail si se usara
            DB::rollBack();
            return ResponseFormat::response(404, 'Chofer no encontrado.');
        } catch (Exception $e) {
            DB::rollBack();
            // Considerar si hay dependencias que impidan eliminar (ej. viajes asignados)
            // Podrías capturar QueryException para errores de restricción de clave foránea
            // if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1451) {
            //     return ResponseFormat::response(409, 'No se puede eliminar el chofer porque tiene registros asociados.', null);
            // }
            return ResponseFormat::exceptionResponse($e);
        }
    }

    public function getChoferDetails($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // If no user is authenticated, return an unauthorized response
            if (!$user) {
                return ResponseFormat::response(401, 'No autenticado. Por favor, inicie sesión.');
            }

            // Find the chofer with specified relationships
            // Based on your Chofer model:
            // - 'vehiculo' is a HasOne relationship
            // - 'tarjetasCombustible' is a HasMany relationship, and we need to nest 'tipoCombustible' inside it.
            $chofer = Chofer::with([
                'empresa',
                'vehiculo', // Eager load the single associated vehicle
                'tarjetasCombustible.tipoCombustible' // Eager load all fuel cards and their respective fuel types
            ])->findOrFail($id);

            // Authorization check: Ensure the chofer belongs to the authenticated user's company
            if ($chofer->empresa_id !== $user->empresa_id) {
                return ResponseFormat::response(403, 'Acceso denegado. Este chofer no pertenece a su empresa.');
            }

            return ResponseFormat::response(200, 'Detalles del chofer obtenidos con éxito.', $chofer);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Chofer no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
