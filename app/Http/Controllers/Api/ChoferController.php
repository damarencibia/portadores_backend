<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chofer;
use App\Models\Empresa;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class ChoferController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Choferes con paginación y la relación empresa.
     */
    public function index(Request $request)
    {
        try {
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            // Construir la consulta con la relación 'empresa'
            $choferesQuery = Chofer::with('empresa');

            // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $choferesQuery->where('nombre', 'like', '%' . $request->input('searchTerm') . '%');
            // Ejemplo: $choferesQuery->where('empresa_id', $request->input('empresa_id'));

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
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:choferes,email',
                'empresa_id' => 'required|integer|exists:empresas,id', // Asegura que la empresa exista
            ], [
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
                // Devolver un código 422 para errores de validación es más estándar
                return ResponseFormat::response(422, $message, ['errors' => $validator->errors()]);
            }

            DB::beginTransaction();

            $chofer = Chofer::create($request->all());

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
}
