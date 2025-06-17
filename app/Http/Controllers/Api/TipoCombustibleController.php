<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoCombustible;
use App\Models\TarjetaCombustible;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;
use Illuminate\Support\Facades\Auth;

class TipoCombustibleController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Tipos de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            // 1. Get the authenticated user
            $user = Auth::user();

            // Check if there's an authenticated user and if they have an empresa_id
            if (!$user || !isset($user->empresa_id)) {
                return ResponseFormat::response(403, 'Acceso denegado. El usuario no tiene una empresa asignada o no está autenticado.', []);
            }

            $userEmpresaId = $user->empresa_id;

            // 2. Get the unique tipo_combustible_ids associated with the user's company
            //    through the TarjetaCombustible model.
            $tipoCombustibleIds = TarjetaCombustible::where('empresa_id', $userEmpresaId)
                ->pluck('tipo_combustible_id')
                ->unique()
                ->toArray();

            // 3. Pagination parameters
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            // 4. Build the query, filtering by the obtained tipo_combustible_ids
            $tiposQuery = TipoCombustible::whereIn('id', $tipoCombustibleIds);

            // You could add further filters here if needed
            // Example: $tiposQuery->where('nombre', 'like', '%' . $request->input('searchTerm') . '%');

            // 5. Apply pagination or get all results
            $paginated = $itemsPerPage == -1
                ? $tiposQuery->get()
                : $tiposQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // 6. Prepare pagination metadata
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];

            // 7. Get the items for the current page
            $tipos = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Tipos de Combustible obtenida con éxito.', $tipos, $meta);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea un nuevo Tipo de Combustible.
     */

    public function getNames()
    {
        try {
            $user = Auth::user();

            if (!$user || !isset($user->empresa_id)) {
                return ResponseFormat::response(403, 'Acceso denegado. El usuario no tiene una empresa asignada o no está autenticado.', []);
            }

            $userEmpresaId = $user->empresa_id;

            // --- CAMBIO CLAVE AQUÍ ---
            // Usamos el modelo TarjetaCombustible para obtener los IDs de tipo_combustible
            $tipoCombustibleIds = TarjetaCombustible::where('empresa_id', $userEmpresaId)
                ->pluck('tipo_combustible_id')
                ->unique()
                ->toArray();
            // --- FIN DEL CAMBIO ---

            $nombres = TipoCombustible::select('id', 'nombre')
                ->whereIn('id', $tipoCombustibleIds)
                ->get();

            return ResponseFormat::response(200, 'Lista de nombres de tipos de combustible obtenida correctamente.', $nombres);
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:tipo_combustibles',
                'unidad_medida' => 'required|string|max:50',
                'precio' => 'nullable|numeric|min:0',
            ], [
                'nombre.required' => 'El nombre del tipo de combustible es obligatorio.',
                'nombre.unique' => 'El nombre del tipo de combustible ya existe.',
                'unidad_medida.required' => 'La unidad de medida es obligatoria.',
                'precio.numeric' => 'El precio debe ser un número.',
                'precio.min' => 'El precio no puede ser menor a 0.',
            ]);

            if ($validator->fails()) {
                // Usar ResponseFormat para errores de validación
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $tipo = TipoCombustible::create($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(201, 'Tipo de Combustible creado con éxito.', $tipo);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra un Tipo de Combustible específico.
     */
    public function show($id)
    {
        try {
            $tipo = TipoCombustible::findOrFail($id);
            return ResponseFormat::response(200, 'Tipo de Combustible obtenido con éxito.', $tipo);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tipo de Combustible no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un Tipo de Combustible específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $tipo = TipoCombustible::findOrFail($id);

            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:tipo_combustibles,nombre,' . $id, // Ignora el nombre actual
                'unidad_medida' => 'required|string|max:50',
                'precio' => 'nullable|numeric|min:0',
            ], [
                'nombre.required' => 'El nombre del tipo de combustible es obligatorio.',
                'nombre.unique' => 'El nombre del tipo de combustible ya existe.',
                'unidad_medida.required' => 'La unidad de medida es obligatoria.',
                'precio.numeric' => 'El precio debe ser un número.',
                'precio.min' => 'El precio no puede ser menor a 0.',
            ]);

            if ($validator->fails()) {
                // Usar ResponseFormat para errores de validación
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $tipo->update($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Tipo de Combustible actualizado con éxito.', $tipo);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tipo de Combustible no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un Tipo de Combustible específico.
     */
    public function destroy($id)
    {
        try {
            $tipo = TipoCombustible::findOrFail($id);

            DB::beginTransaction(); // Iniciar transacción

            $tipo->delete(); // Usa soft delete

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Tipo de Combustible eliminado con éxito.', null);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tipo de Combustible no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            // Captura cualquier otra excepción, como restricciones de clave foránea
            return ResponseFormat::response(500, 'Error al eliminar el Tipo de Combustible. Puede tener elementos relacionados.', null);
            // return ResponseFormat::exceptionResponse($e); // Otra opción para ver detalles del error
        }
    }
}
