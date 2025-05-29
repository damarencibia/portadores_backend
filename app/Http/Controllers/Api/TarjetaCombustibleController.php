<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TarjetaCombustible;
use App\Models\TipoCombustible; // Importar TipoCombustible para validación
use App\Models\Vehiculo; // Importar Vehiculo para validación
use App\Models\Ueb; // Importar Ueb para validación
use App\Models\User; // Importar User para validación
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;

class TarjetaCombustibleController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todas las Tarjetas de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta con relaciones cargadas
            $tarjetasQuery = TarjetaCombustible::with(['tipoCombustible', 'vehiculo', 'ueb', 'user']);

             // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $tarjetasQuery->where('numero', 'like', '%' . $request->input('searchTerm') . '%');
            // Ejemplo: $tarjetasQuery->where('ueb_id', $request->input('ueb_id'));


            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $tarjetasQuery->get()
                : $tarjetasQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                 'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page
            ];

            // Obtener los elementos de la página actual
            $tarjetas = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Tarjetas de Combustible obtenida con éxito.', $tarjetas, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea una nueva Tarjeta de Combustible.
     */
    public function store(Request $request)
    {
        try {
            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'numero' => 'required|string|max:255|unique:tarjeta_combustibles',
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'fecha_vencimiento' => 'required|date',
                'vehiculo_id' => 'nullable|exists:vehiculos,id', // Asegura que el vehículo exista si se proporciona
                'ueb_id' => 'required|exists:uebs,id',
                'activa' => 'nullable|boolean',
                'user_id' => 'required|exists:users,id', // Asegura que el usuario exista
            ], [
                 'numero.required' => 'El número de tarjeta es obligatorio.',
                 'numero.unique' => 'El número de tarjeta ya existe.',
                 'tipo_combustible_id.required' => 'El tipo de combustible es obligatorio.',
                 'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',
                 'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
                 'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida.',
                 'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
                 'ueb_id.required' => 'La UEB es obligatoria.',
                 'ueb_id.exists' => 'La UEB seleccionada no existe.',
                 'user_id.required' => 'El usuario es obligatorio.',
                 'user_id.exists' => 'El usuario seleccionado no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $tarjeta = TarjetaCombustible::create($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(201, 'Tarjeta de Combustible creada con éxito.', $tarjeta);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra una Tarjeta de Combustible específica.
     */
    public function show($id)
    {
        try {
            // Carga las relaciones 'tipoCombustible', 'vehiculo', 'ueb', 'user', y 'cargasCombustible'
            $tarjeta = TarjetaCombustible::with(['tipoCombustible', 'vehiculo', 'ueb', 'user', 'cargasCombustible'])->findOrFail($id);
            return ResponseFormat::response(200, 'Tarjeta de Combustible obtenida con éxito.', $tarjeta);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza una Tarjeta de Combustible específica.
     */
    public function update(Request $request, $id)
    {
        try {
            $tarjeta = TarjetaCombustible::findOrFail($id);

            // Validación manual con Validator
             $validator = Validator::make($request->all(), [
                'numero' => 'required|string|max:255|unique:tarjeta_combustibles,numero,' . $id,
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'fecha_vencimiento' => 'required|date',
                'vehiculo_id' => 'nullable|exists:vehiculos,id',
                'ueb_id' => 'required|exists:uebs,id',
                'activa' => 'nullable|boolean',
                'user_id' => 'required|exists:users,id',
            ], [
                 'numero.required' => 'El número de tarjeta es obligatorio.',
                 'numero.unique' => 'El número de tarjeta ya existe.',
                 'tipo_combustible_id.required' => 'El tipo de combustible es obligatorio.',
                 'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',
                 'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
                 'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida.',
                 'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
                 'ueb_id.required' => 'La UEB es obligatoria.',
                 'ueb_id.exists' => 'La UEB seleccionada no existe.',
                 'user_id.required' => 'El usuario es obligatorio.',
                 'user_id.exists' => 'El usuario seleccionado no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $tarjeta->update($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Tarjeta de Combustible actualizada con éxito.', $tarjeta);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina una Tarjeta de Combustible específica.
     */
    public function destroy($id)
    {
        try {
            $tarjeta = TarjetaCombustible::findOrFail($id);

            DB::beginTransaction(); // Iniciar transacción

            $tarjeta->delete(); // Usa soft delete

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Tarjeta de Combustible eliminada con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
             DB::rollBack(); // Revertir transacción
             // Captura cualquier otra excepción, como restricciones de clave foránea (si tiene cargas asociadas)
             return ResponseFormat::response(500, 'Error al eliminar la Tarjeta de Combustible. Puede tener cargas asociadas.', null);
            // return ResponseFormat::exceptionResponse($e); // Otra opción para ver detalles del error
        }
    }
}
