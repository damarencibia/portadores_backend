<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoCombustible;
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;

class TipoCombustibleController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Tipos de Combustible con paginación.
     */
    public function index(Request $request)
    {
         try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta
            $tiposQuery = TipoCombustible::query();

            // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $tiposQuery->where('nombre', 'like', '%' . $request->input('searchTerm') . '%');


            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $tiposQuery->get()
                : $tiposQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                 'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page
            ];

            // Obtener los elementos de la página actual
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
             $nombres = TipoCombustible::select('id', 'nombre')->get();
             return ResponseFormat::response(200, 'Lista id-nombre obtenida correctamente.', $nombres);
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
