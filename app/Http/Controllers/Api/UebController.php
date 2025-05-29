<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ueb;
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Exception;

class UebController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todas las UEBs con paginación.
     */
    public function index(Request $request)
    {
        try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta
            $uebsQuery = Ueb::query();

            // Aquí podrías añadir filtros si fueran necesarios para UEBs
            // Ejemplo: $uebsQuery->where('nombre', 'like', '%' . $request->input('searchTerm') . '%');

            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $uebsQuery->get()
                : $uebsQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page para consistencia
            ];

            // Obtener los elementos de la página actual
            $uebs = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de UEBs obtenida con éxito.', $uebs, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea una nueva UEB.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'direccion' => 'required|string|max:255',

            ], [
                'nombre.required' => 'El nombre del producto es obligatorio',
                'direccion.required' => 'La direccion es requerida',
            ]);

            if ($validator->fails()) {
                $message = ResponseFormat::validatorErrorMessage($validator);
                throw new \Exception($message, 400);
            }

            // Excluir el campo code del request
            $data = $request->except('code');

            DB::beginTransaction();

            // Crear la Ueb
            $ueb = Ueb::create($request->all());

            DB::commit();
            return ResponseFormat::response(
                201,
                'Ueb creada exitosamente',
                ['ueb_id' => $ueb->getKey()]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra una UEB específica.
     */
    public function show($id)
    {
        try {
            $ueb = Ueb::findOrFail($id);
            return ResponseFormat::response(200, 'UEB obtenida con éxito.', $ueb);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'UEB no encontrada.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza una UEB específica.
     */
    public function update(Request $request, $id)
    {
        try {
            $ueb = Ueb::find($id);

            if (!$ueb) {
                return ResponseFormat::response(404, 'Producto no encontrado');
            }
            
            $validator = Validator::make($request->all(), [
                // 'code' => 'sometimes|string|unique:products,code,'.$id.'|max:255',
                'nombre' => 'sometimes|string|max:255',
                'direccion' => 'sometimes|string|max:255',

            ], [
                // Mensajes de validación (similares a store)
            ]);

            if ($validator->fails()) {
                $message = ResponseFormat::validatorErrorMessage($validator);
                throw new Exception($message, 400);
            }
            $data = $request->all();

            $ueb->update($data);

            return ResponseFormat::response(200, 'UEB actualizada con éxito.', $ueb);
        }catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina una UEB específica.
     */
    public function destroy($id)
    {
        try {
            $ueb = Ueb::find($id);
            if (!$ueb) {
                return ResponseFormat::response(404, 'Ueb no encontrada');
            }

            $ueb->delete();
            return ResponseFormat::response(200, 'Ueb eliminada correctamente', null, [
                'deleted_id' => $id
            ]);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
