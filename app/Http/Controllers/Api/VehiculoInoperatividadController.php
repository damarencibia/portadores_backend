<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehiculoInoperatividad;
use App\Models\Vehiculo; // Necesario para validar vehiculo_id
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Exception;

class VehiculoInoperatividadController extends Controller
{
    /**
     * Lista todas las inoperatividades con paginación.
     */
    public function index(Request $request)
    {
        try {
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            $inoperatividadesQuery = VehiculoInoperatividad::with('vehiculo');

            // Opcional: Filtrar por vehículo
            if ($request->has('vehiculo_id')) {
                $inoperatividadesQuery->where('vehiculo_id', $request->input('vehiculo_id'));
            }

            $paginated = $itemsPerPage == -1
                ? $inoperatividadesQuery->get()
                : $inoperatividadesQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            $meta = [
                'total'     => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage'   => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page'      => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];

            $inoperatividades = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de inoperatividades obtenida con éxito.', $inoperatividades, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Crea un nuevo registro de inoperatividad.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vehiculo_id'                => 'required|exists:vehiculos,id',
                'fecha_salida_servicio'      => 'required|date',
                'fecha_reanudacion_servicio' => 'nullable|date|after_or_equal:fecha_salida_servicio',
                'motivo_averia'              => 'required|string|max:255',
            ], [
                'vehiculo_id.required'                   => 'El ID del vehículo es obligatorio.',
                'vehiculo_id.exists'                     => 'El vehículo especificado no existe.',
                'fecha_salida_servicio.required'         => 'La fecha de salida de servicio es obligatoria.',
                'fecha_salida_servicio.date'             => 'La fecha de salida de servicio debe ser una fecha válida.',
                'fecha_reanudacion_servicio.date'        => 'La fecha de reanudación de servicio debe ser una fecha válida.',
                'fecha_reanudacion_servicio.after_or_equal' => 'La fecha de reanudación no puede ser anterior a la fecha de salida.',
                'motivo_averia.required'                 => 'El motivo de la avería es obligatorio.',
                'motivo_averia.string'                   => 'El motivo de la avería debe ser texto.',
                'motivo_averia.max'                      => 'El motivo de la avería no puede exceder 255 caracteres.',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            // Crear la inoperatividad
            $inoperatividad = VehiculoInoperatividad::create($request->all());

            // --- Lógica para actualizar el estado del vehículo ---
            $vehiculo = Vehiculo::find($inoperatividad->vehiculo_id);
            if ($vehiculo) {
                // Si la fecha_reanudacion_servicio es null, el vehículo está paralizado
                if (is_null($inoperatividad->fecha_reanudacion_servicio)) {
                    $vehiculo->estado_tecnico = 'paralizado';
                    $vehiculo->save();
                } else {
                    // Si se registra una inoperatividad que ya tiene fecha de reanudación,
                    // podría ser que el vehículo ya estaba operativo, o que esta avería no lo paralizó (raro).
                    // Para ser seguros, verificamos si hay otras averías activas.
                    $activeInoperatividades = VehiculoInoperatividad::where('vehiculo_id', $vehiculo->id)
                                                ->whereNull('fecha_reanudacion_servicio')
                                                ->count();
                    if ($activeInoperatividades === 0 && $vehiculo->estado_tecnico !== 'operativo') {
                        $vehiculo->estado_tecnico = 'operativo'; // Asume 'operativo' como estado por defecto
                        $vehiculo->save();
                    }
                }
            }
            // --- Fin de la lógica de actualización del estado ---

            return ResponseFormat::response(201, 'Registro de inoperatividad creado con éxito.', $inoperatividad);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Muestra un registro de inoperatividad específico.
     */
    public function show($id)
    {
        try {
            $inoperatividad = VehiculoInoperatividad::with('vehiculo')->findOrFail($id);
            return ResponseFormat::response(200, 'Registro de inoperatividad obtenido con éxito.', $inoperatividad);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Registro de inoperatividad no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Actualiza un registro de inoperatividad específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $inoperatividad = VehiculoInoperatividad::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'vehiculo_id'                => 'sometimes|exists:vehiculos,id',
                'fecha_salida_servicio'      => 'sometimes|date',
                'fecha_reanudacion_servicio' => 'nullable|date|after_or_equal:fecha_salida_servicio',
                'motivo_averia'              => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            // Guardar el estado original de fecha_reanudacion_servicio antes de la actualización
            $oldFechaReanudacion = $inoperatividad->fecha_reanudacion_servicio;

            // Actualizar la inoperatividad
            $inoperatividad->update($request->all());

            // --- Lógica para actualizar el estado del vehículo ---
            $vehiculo = Vehiculo::find($inoperatividad->vehiculo_id);
            if ($vehiculo) {
                // Caso 1: La avería se reanuda (fecha_reanudacion_servicio pasa de null a un valor)
                if (is_null($oldFechaReanudacion) && !is_null($inoperatividad->fecha_reanudacion_servicio)) {
                    // Verificar si existen OTRAS inoperatividades activas para este vehículo
                    $activeInoperatividades = VehiculoInoperatividad::where('vehiculo_id', $vehiculo->id)
                                                ->whereNull('fecha_reanudacion_servicio')
                                                ->count();
                    if ($activeInoperatividades === 0) {
                        // Si NO hay otras inoperatividades activas, el vehículo pasa a operativo
                        $vehiculo->estado_tecnico = 'operativo'; // Asume 'operativo' como estado por defecto
                        $vehiculo->save();
                    }
                    // Si SÍ hay otras activas, el estado del vehículo permanece 'paralizado'
                }
                // Caso 2: La avería se establece como activa nuevamente (fecha_reanudacion_servicio pasa de valor a null)
                elseif (!is_null($oldFechaReanudacion) && is_null($inoperatividad->fecha_reanudacion_servicio)) {
                    $vehiculo->estado_tecnico = 'paralizado';
                    $vehiculo->save();
                }
                // Caso 3: La avería se crea directamente como activa (fecha_reanudacion_servicio es null)
                // Esto ya se cubre implícitamente si se actualiza un campo diferente a fecha_reanudacion_servicio
                // y el vehículo ya estaba paralizado por esta misma avería.
                // Sin embargo, si se actualiza la fecha de salida de servicio y se deja nulo
                // se asegura que el vehículo este paralizado.
                elseif (is_null($inoperatividad->fecha_reanudacion_servicio) && $vehiculo->estado_tecnico !== 'paralizado') {
                     $vehiculo->estado_tecnico = 'paralizado';
                     $vehiculo->save();
                }
            }
            // --- Fin de la lógica de actualización del estado ---

            return ResponseFormat::response(200, 'Registro de inoperatividad actualizado con éxito.', $inoperatividad);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Registro de inoperatividad no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Elimina un registro de inoperatividad específico.
     */
    public function destroy($id)
    {
        try {
            $inoperatividad = VehiculoInoperatividad::findOrFail($id);
            $vehiculo_id = $inoperatividad->vehiculo_id; // Guardar ID del vehículo

            $inoperatividad->delete();

            // Después de eliminar, verificar si hay otras inoperatividades activas para ese vehículo
            $vehiculo = Vehiculo::find($vehiculo_id);
            if ($vehiculo) {
                $activeInoperatividades = VehiculoInoperatividad::where('vehiculo_id', $vehiculo->id)
                                            ->whereNull('fecha_reanudacion_servicio')
                                            ->count();
                if ($activeInoperatividades === 0 && $vehiculo->estado_tecnico !== 'operativo') {
                    $vehiculo->estado_tecnico = 'operativo';
                    $vehiculo->save();
                }
            }

            return ResponseFormat::response(200, 'Registro de inoperatividad eliminado con éxito.', null);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Registro de inoperatividad no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::response(500, 'Error al eliminar el registro de inoperatividad.', null);
        }
    }
}