<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Exception;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todas las Empresas con paginación.
     */
    public function index(Request $request)
    {
        try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta
            $empresasQuery = Empresa::query();

            // Aquí podrías añadir filtros si fueran necesarios para Empresas
            // Ejemplo: $empresasQuery->where('nombre', 'like', '%' . $request->input('searchTerm') . '%');

            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $empresasQuery->get()
                : $empresasQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page para consistencia
            ];

            // Obtener los elementos de la página actual
            $Empresas = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Empresas obtenida con éxito.', $Empresas, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea una nueva Empresa.
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

            // Crear la Empresa
            $Empresa = Empresa::create($request->all());

            DB::commit();
            return ResponseFormat::response(
                201,
                'Empresa creada exitosamente',
                ['Empresa_id' => $Empresa->getKey()]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra una Empresa específica.
     */
    public function show($id)
    {
        try {
            $Empresa = Empresa::findOrFail($id);
            return ResponseFormat::response(200, 'Empresa obtenida con éxito.', $Empresa);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Empresa no encontrada.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza una Empresa específica.
     */
    public function update(Request $request, $id)
    {
        try {
            $Empresa = Empresa::find($id);

            if (!$Empresa) {
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

            $Empresa->update($data);

            return ResponseFormat::response(200, 'Empresa actualizada con éxito.', $Empresa);
        }catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina una Empresa específica.
     */
    public function destroy($id)
    {
        try {
            $Empresa = Empresa::find($id);
            if (!$Empresa) {
                return ResponseFormat::response(404, 'Empresa no encontrada');
            }

            $Empresa->delete();
            return ResponseFormat::response(200, 'Empresa eliminada correctamente', null, [
                'deleted_id' => $id
            ]);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
