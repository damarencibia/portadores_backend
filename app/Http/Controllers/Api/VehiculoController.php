<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Models\Ueb; // Importar Ueb para validación
use App\Models\TipoCombustible; // Importar TipoCombustible para validación
use App\Models\User; // Importar User para validación
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;

class VehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todos los Vehículos con paginación.
     */
    public function index(Request $request)
    {
        try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta con relaciones cargadas
            $vehiculosQuery = Vehiculo::with(['ueb', 'tipoCombustible', 'user']);

             // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $vehiculosQuery->where('chapa', 'like', '%' . $request->input('searchTerm') . '%');
            // Ejemplo: $vehiculosQuery->where('ueb_id', $request->input('ueb_id'));
            // Ejemplo: $vehiculosQuery->where('tipo_combustible_id', $request->input('tipo_combustible_id'));


            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $vehiculosQuery->get()
                : $vehiculosQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                 'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page
            ];

            // Obtener los elementos de la página actual
            $vehiculos = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Vehículos obtenida con éxito.', $vehiculos, $meta);

        } catch (Exception $e) {
            // Esta excepción ahora será manejada de forma más robusta por ResponseFormat::exceptionResponse
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea un nuevo Vehículo.
     */
    public function store(Request $request)
    {
        try {
            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'numero_interno' => 'nullable|string|max:255|unique:vehiculos',
                'marca' => 'required|string|max:255',
                'modelo' => 'required|string|max:255',
                'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'indice_consumo' => 'nullable|numeric|min:0',
                'prueba_litro' => 'nullable|numeric|min:0',
                'ficav' => 'nullable|boolean', // Según tu migración, es boolean
                'capacidad_tanque' => 'nullable|numeric|min:0',
                'color' => 'nullable|string|max:50',
                'chapa' => 'required|string|max:20|unique:vehiculos',
                'numero_motor' => 'nullable|string|max:255|unique:vehiculos',
                'activo' => 'nullable|boolean',
                'ueb_id' => 'required|exists:uebs,id',
                'numero_chasis' => 'nullable|string|max:255|unique:vehiculos',
                'estado_tecnico' => 'nullable|string|max:255',
                'user_id' => 'nullable|exists:users,id', // Asegura que el usuario exista si se proporciona
            ], [
                 'marca.required' => 'La marca del vehículo es obligatoria.',
                 'modelo.required' => 'El modelo del vehículo es obligatorio.',
                 'tipo_combustible_id.required' => 'El tipo de combustible es obligatorio.',
                 'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',
                 'chapa.required' => 'La chapa del vehículo es obligatoria.',
                 'chapa.unique' => 'La chapa del vehículo ya existe.',
                 'ueb_id.required' => 'La UEB es obligatoria.',
                 'ueb_id.exists' => 'La UEB seleccionada no existe.',
                 'numero_interno.unique' => 'El número interno ya existe.',
                 'numero_motor.unique' => 'El número de motor ya existe.',
                 'numero_chasis.unique' => 'El número de chasis ya existe.',
                 'user_id.exists' => 'El usuario asignado no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $vehiculo = Vehiculo::create($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(201, 'Vehículo creado con éxito.', $vehiculo);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra un Vehículo específico.
     */
    public function show($id)
    {
        try {
            // Carga las relaciones 'ueb', 'tipoCombustible', 'user', y 'tarjetasCombustible'
            $vehiculo = Vehiculo::with(['ueb', 'tipoCombustible', 'user', 'tarjetasCombustible'])->findOrFail($id);
            return ResponseFormat::response(200, 'Vehículo obtenido con éxito.', $vehiculo);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Vehículo no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un Vehículo específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);

            // Validación manual con Validator
             $validator = Validator::make($request->all(), [
                'numero_interno' => 'nullable|string|max:255|unique:vehiculos,numero_interno,' . $id,
                'marca' => 'required|string|max:255',
                'modelo' => 'required|string|max:255',
                'ano' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'indice_consumo' => 'nullable|numeric|min:0',
                'prueba_litro' => 'nullable|numeric|min:0',
                'ficav' => 'nullable|boolean',
                'capacidad_tanque' => 'nullable|numeric|min:0',
                'color' => 'nullable|string|max:50',
                'chapa' => 'required|string|max:20|unique:vehiculos,chapa,' . $id,
                'numero_motor' => 'nullable|string|max:255|unique:vehiculos,numero_motor,' . $id,
                'activo' => 'nullable|boolean',
                'ueb_id' => 'required|exists:uebs,id',
                'numero_chasis' => 'nullable|string|max:255|unique:vehiculos,numero_chasis,' . $id,
                'estado_tecnico' => 'nullable|string|max:255',
                'user_id' => 'nullable|exists:users,id',
            ], [
                 'marca.required' => 'La marca del vehículo es obligatoria.',
                 'modelo.required' => 'El modelo del vehículo es obligatorio.',
                 'tipo_combustible_id.required' => 'El tipo de combustible es obligatorio.',
                 'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',
                 'chapa.required' => 'La chapa del vehículo es obligatoria.',
                 'chapa.unique' => 'La chapa del vehículo ya existe.',
                 'ueb_id.required' => 'La UEB es obligatoria.',
                 'ueb_id.exists' => 'La UEB seleccionada no existe.',
                 'numero_interno.unique' => 'El número interno ya existe.',
                 'numero_motor.unique' => 'El número de motor ya existe.',
                 'numero_chasis.unique' => 'El número de chasis ya existe.',
                 'user_id.exists' => 'El usuario asignado no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $vehiculo->update($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Vehículo actualizado con éxito.', $vehiculo);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Vehículo no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un Vehículo específico.
     */
    public function destroy($id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);

            DB::beginTransaction(); // Iniciar transacción

            $vehiculo->delete(); // Usa soft delete

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Vehículo eliminado con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Vehículo no encontrado.', null);
        } catch (Exception $e) {
             DB::rollBack(); // Revertir transacción
             // Captura cualquier otra excepción, como restricciones de clave foránea (si tiene tarjetas o cargas asociadas)
             return ResponseFormat::response(500, 'Error al eliminar el Vehículo. Puede tener elementos relacionados.', null);
            // return ResponseFormat::exceptionResponse($e); // Otra opción para ver detalles del error
        }
    }
}
