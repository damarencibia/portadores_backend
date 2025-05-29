<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CargaCombustible;
use App\Models\TipoCombustible; // Importar TipoCombustible para validación
use App\Models\User; // Importar User para validación
use App\Models\TarjetaCombustible; // Importar TarjetaCombustible para validación
use App\Utils\ResponseFormat; // Asegúrate de que la ruta sea correcta
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator; // Importar Validator
use Illuminate\Support\Facades\DB; // Importar DB
use Exception;

class CargaCombustibleController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista todas las Cargas de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            // Obtener parámetros de paginación de la solicitud
            $itemsPerPage = $request->input("itemsPerPage", 20); // Número de elementos por página, por defecto 20
            $page = $request->input("page", 1); // Número de página actual, por defecto 1

            // Construir la consulta con relaciones cargadas
            $cargasQuery = CargaCombustible::with(['tipoCombustible', 'registradoPor', 'validadoPor', 'tarjetaCombustible']);

             // Aquí podrías añadir filtros si fueran necesarios
            // Ejemplo: $cargasQuery->where('fecha', $request->input('fecha'));
            // Ejemplo: $cargasQuery->where('tarjeta_combustible_id', $request->input('tarjeta_id'));


            // Aplicar paginación o obtener todos los resultados
            $paginated = $itemsPerPage == -1
                ? $cargasQuery->get()
                : $cargasQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            // Preparar metadatos de paginación
            $meta = [
                'total' => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage' => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page' => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                 'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1, // Añadir last_page
            ];

            // Obtener los elementos de la página actual
            $cargas = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Cargas de Combustible obtenida con éxito.', $cargas, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea una nueva Carga de Combustible.
     */
    public function store(Request $request)
    {
        try {
            // Validación manual con Validator
            $validator = Validator::make($request->all(), [
                'fecha' => 'required|date',
                'hora' => 'nullable|date_format:H:i:s', // Valida formato de hora
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'cantidad' => 'required|numeric|min:0',
                'odometro' => 'required|numeric|min:0', // Asegúrate de que el tipo de columna en DB sea adecuado (decimal o big integer)
                'lugar' => 'nullable|string|max:255',
                'numero_tarjeta' => 'nullable|string|max:255', // Si aún usas este campo
                'no_chip' => 'nullable|string|max:255', // Si aún usas este campo
                'registrado_por_id' => 'required|exists:users,id',
                'validado_por_id' => 'nullable|exists:users,id',
                'fecha_validacion' => 'nullable|date',
                'estado' => 'nullable|string|max:50', // Puedes validar con un enum si lo usas
                'importe' => 'nullable|numeric|min:0',
                'tarjeta_combustible_id' => 'nullable|exists:tarjeta_combustibles,id', // Asegura que la tarjeta exista si se proporciona
            ], [
                 'fecha.required' => 'La fecha de la carga es obligatoria.',
                 'fecha.date' => 'La fecha de la carga debe ser una fecha válida.',
                 'hora.date_format' => 'La hora debe tener el formato HH:MM:SS.',
                 'tipo_combustible_id.required' => 'El tipo de combustible es obligatorio.',
                 'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',
                 'cantidad.required' => 'La cantidad de combustible es obligatoria.',
                 'cantidad.numeric' => 'La cantidad debe ser un número.',
                 'cantidad.min' => 'La cantidad no puede ser menor a 0.',
                 'odometro.required' => 'La lectura del odómetro es obligatoria.',
                 'odometro.numeric' => 'La lectura del odómetro debe ser un número.',
                 'odometro.min' => 'La lectura del odómetro no puede ser menor a 0.',
                 'registrado_por_id.required' => 'El usuario que registra es obligatorio.',
                 'registrado_por_id.exists' => 'El usuario que registra no existe.',
                 'validado_por_id.exists' => 'El usuario que valida no existe.',
                 'fecha_validacion.date' => 'La fecha de validación debe ser una fecha válida.',
                 'importe.numeric' => 'El importe debe ser un número.',
                 'importe.min' => 'El importe no puede ser menor a 0.',
                 'tarjeta_combustible_id.exists' => 'La tarjeta de combustible seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $carga = CargaCombustible::create($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(201, 'Carga de Combustible creada con éxito.', $carga);

        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Display the specified resource.
     * Muestra una Carga de Combustible específica.
     */
    public function show($id)
    {
        try {
            // Carga las relaciones 'tipoCombustible', 'registradoPor', 'validadoPor', y 'tarjetaCombustible'
            $carga = CargaCombustible::with(['tipoCombustible', 'registradoPor', 'validadoPor', 'tarjetaCombustible'])->findOrFail($id);
            return ResponseFormat::response(200, 'Carga de Combustible obtenida con éxito.', $carga);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza una Carga de Combustible específica.
     */
    public function update(Request $request, $id)
    {
        try {
            $carga = CargaCombustible::findOrFail($id);

            // Validación manual con Validator
             $validator = Validator::make($request->all(), [
                'fecha' => 'required|date',
                'hora' => 'nullable|date_format:H:i:s',
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'cantidad' => 'required|numeric|min:0',
                'odometro' => 'required|numeric|min:0', // Asegúrate de que el tipo de columna en DB sea adecuado
                'lugar' => 'nullable|string|max:255',
                'numero_tarjeta' => 'nullable|string|max:255',
                'no_chip' => 'nullable|string|max:255',
                'registrado_por_id' => 'required|exists:users,id',
                'validado_por_id' => 'nullable|exists:users,id',
                'fecha_validacion' => 'nullable|date',
                'estado' => 'nullable|string|max:50',
                'importe' => 'nullable|numeric|min:0',
                'tarjeta_combustible_id' => 'nullable|exists:tarjeta_combustibles,id',
            ], [
                 'fecha.required' => 'La fecha de la carga es obligatoria.',
                 'fecha.date' => 'La fecha de la carga debe ser una fecha válida.',
                 'hora.date_format' => 'La hora debe tener el formato HH:MM:SS.',
                 'tipo_combustible_id.required' => 'El tipo de combustible es obligatorio.',
                 'tipo_combustible_id.exists' => 'El tipo de combustible seleccionado no existe.',
                 'cantidad.required' => 'La cantidad de combustible es obligatoria.',
                 'cantidad.numeric' => 'La cantidad debe ser un número.',
                 'cantidad.min' => 'La cantidad no puede ser menor a 0.',
                 'odometro.required' => 'La lectura del odómetro es obligatoria.',
                 'odometro.numeric' => 'La lectura del odómetro debe ser un número.',
                 'odometro.min' => 'La lectura del odómetro no puede ser menor a 0.',
                 'registrado_por_id.required' => 'El usuario que registra es obligatorio.',
                 'registrado_por_id.exists' => 'El usuario que registra no existe.',
                 'validado_por_id.exists' => 'El usuario que valida no existe.',
                 'fecha_validacion.date' => 'La fecha de validación debe ser una fecha válida.',
                 'importe.numeric' => 'El importe debe ser un número.',
                 'importe.min' => 'El importe no puede ser menor a 0.',
                 'tarjeta_combustible_id.exists' => 'La tarjeta de combustible seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                 // Usar ResponseFormat para errores de validación
                 return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction(); // Iniciar transacción

            $carga->update($request->all());

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Carga de Combustible actualizada con éxito.', $carga);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack(); // Revertir transacción
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Elimina una Carga de Combustible específica.
     */
    public function destroy($id)
    {
        try {
            $carga = CargaCombustible::findOrFail($id);

            DB::beginTransaction(); // Iniciar transacción

            $carga->delete(); // Usa soft delete

            DB::commit(); // Confirmar transacción

            return ResponseFormat::response(200, 'Carga de Combustible eliminada con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
             DB::rollBack(); // Revertir transacción
             // Captura cualquier otra excepción si es necesario
             return ResponseFormat::response(500, 'Error al eliminar la Carga de Combustible.', null);
            // return ResponseFormat::exceptionResponse($e); // Otra opción para ver detalles del error
        }
    }
}
