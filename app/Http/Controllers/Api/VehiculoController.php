<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Models\VehiculoInoperatividad;
use App\Models\Empresa;
use App\Models\TipoCombustible;
use App\Models\Chofer;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class VehiculoController extends Controller
{
    /**
     * Lista todos los Vehículos con paginación.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $empresaId = $user->empresa_id;
    
            // Paginación
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page         = $request->input("page", 1);
    
            // Filtros
            $search             = $request->input('search');
            $filterTipo         = $request->input('tipo_vehiculo');
            $filterCombustible  = $request->input('tipo_combustible_id');
            $filterEstado       = $request->input('estado_tecnico');
            $filterChofer       = $request->input('chofer_id');
    
            $vehiculosQuery = Vehiculo::with(['empresa', 'tipoCombustible', 'chofer'])
                ->where('empresa_id', $empresaId)
                ->when($search, function ($q, $search) {
                    $q->where(function ($q2) use ($search) {
                        $q2->where('numero_interno',    'like', "%{$search}%")
                           ->orWhere('marca',           'like', "%{$search}%")
                           ->orWhere('modelo',          'like', "%{$search}%")
                           ->orWhere('ano',             'like', "%{$search}%")
                           ->orWhere('indice_consumo',  'like', "%{$search}%")
                           ->orWhere('prueba_litro',    'like', "%{$search}%")
                           ->orWhere('ficav',           'like', "%{$search}%")
                           ->orWhere('capacidad_tanque','like', "%{$search}%")
                           ->orWhere('color',           'like', "%{$search}%")
                           ->orWhere('numero_motor',    'like', "%{$search}%")
                           ->orWhere('numero_chasis',   'like', "%{$search}%");
                    });
                })
                ->when($filterTipo, function ($q, $tipo) {
                    $q->where('tipo_vehiculo', $tipo);
                })
                ->when($filterCombustible, function ($q, $comb) {
                    $q->where('tipo_combustible_id', $comb);
                })
                ->when($filterEstado, function ($q, $estado) {
                    $q->where('estado_tecnico', $estado);
                })
                ->when($filterChofer, function ($q, $choferId) {
                    $q->where('chofer_id', $choferId);
                });
    
            // Obtener datos
            $paginated     = $itemsPerPage == -1
                ? $vehiculosQuery->get()
                : $vehiculosQuery->paginate($itemsPerPage, ['*'], 'page', $page);
            $vehiculosRaw  = $itemsPerPage != -1 ? $paginated->items() : $paginated;
    
            // Mapeo en el mismo orden que los headers
            $vehiculos = collect($vehiculosRaw)->map(function ($v) {
                return [
                    'id'                   => $v->id,
                    'numero_interno'       => $v->numero_interno,
                    'marca'                => $v->marca,
                    'modelo'               => $v->modelo,
                    'tipo_vehiculo'        => $v->tipo_vehiculo,
                    'ano'                  => $v->ano,
                    'tipo_combustible_id'  => optional($v->tipoCombustible)->nombre,
                    'indice_consumo'       => $v->indice_consumo,
                    'prueba_litro'         => $v->prueba_litro,
                    'ficav'                => $v->ficav,
                    'capacidad_tanque'     => $v->capacidad_tanque,
                    'color'                => $v->color,
                    'numero_motor'         => $v->numero_motor,
                    'empresa_id'           => optional($v->empresa)->nombre,
                    'numero_chasis'        => $v->numero_chasis,
                    'chofer_id'            => optional($v->chofer)->nombre,
                    'estado_tecnico'       => $v->estado_tecnico,
                ];
            });
    
            // Meta
            $meta = [
                'total'     => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage'   => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page'      => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];
    
            return ResponseFormat::response(200, 'Lista de Vehículos obtenida con éxito.', $vehiculos, $meta);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
    
    
    

    /**
     * Crea un nuevo Vehículo.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'numero_interno'      => 'nullable|string|max:255|unique:vehiculos',
                'marca'               => 'required|string|max:255',
                'modelo'              => 'required|string|max:255',
                'tipo_vehiculo'       => 'required|string|max:100',
                'ano'                 => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'tipo_combustible_id' => 'required|exists:tipo_combustibles,id',
                'indice_consumo'      => 'nullable|numeric|min:0',
                'prueba_litro'        => 'nullable|numeric|min:0',
                'ficav'               => 'nullable|date',
                'capacidad_tanque'    => 'nullable|numeric|min:0',
                'color'               => 'nullable|string|max:50',
                'chapa'               => 'required|string|max:20|unique:vehiculos',
                'numero_motor'        => 'nullable|string|max:255|unique:vehiculos',
                'numero_chasis'       => 'nullable|string|max:255|unique:vehiculos',
                'estado_tecnico'      => 'nullable|string|max:255',
                'chofer_id'           => 'nullable|exists:choferes,id',
            ], [
                // mensajes de error...
            ]);
    
            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }
    
            DB::beginTransaction();
    
            // Obtenemos la empresa del usuario logueado
            $empresaId = auth()->user()->empresa_id;
    
            // Creamos el vehículo mezclando la empresa_id fija
            $vehiculo = Vehiculo::create(array_merge(
                $request->only([
                    'numero_interno','marca','modelo','tipo_vehiculo','ano',
                    'tipo_combustible_id','indice_consumo','prueba_litro','ficav',
                    'capacidad_tanque','color','chapa','numero_motor',
                    'numero_chasis','estado_tecnico','chofer_id'
                ]),
                ['empresa_id' => $empresaId]
            ));
    
            DB::commit();
            return ResponseFormat::response(201, 'Vehículo creado con éxito.', $vehiculo);
    
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Muestra un Vehículo específico.
     */
    public function show($id)
    {
        try {
            $vehiculo = Vehiculo::with(['empresa', 'tipoCombustible', 'chofer'])
                ->findOrFail($id);

            return ResponseFormat::response(200, 'Vehículo obtenido con éxito.', $vehiculo);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Vehículo no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Actualiza un Vehículo específico.
     */
    public function update(Request $request, $id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);
    
            $validator = Validator::make($request->all(), [
                'numero_interno'      => 'nullable|string|max:255|unique:vehiculos,numero_interno,' . $id,
                'marca'               => 'sometimes|string|max:255',
                'modelo'              => 'sometimes|string|max:255',
                'tipo_vehiculo'       => 'sometimes|string|max:100',
                'ano'                 => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'tipo_combustible_id' => 'sometimes|exists:tipo_combustibles,id',
                'indice_consumo'      => 'nullable|numeric|min:0',
                'prueba_litro'        => 'nullable|numeric|min:0',
                'ficav'               => 'nullable|date',
                'capacidad_tanque'    => 'nullable|numeric|min:0',
                'color'               => 'nullable|string|max:50',
                'chapa'               => 'sometimes|string|max:20|unique:vehiculos,chapa,' . $id,
                'numero_motor'        => 'nullable|string|max:255|unique:vehiculos,numero_motor,' . $id,
                'numero_chasis'       => 'nullable|string|max:255|unique:vehiculos,numero_chasis,' . $id,
                'estado_tecnico'      => 'nullable|string|max:255',
                'chofer_id'           => 'nullable|exists:choferes,id',
            ], [
                // mensajes de error...
            ]);
    
            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }
    
            DB::beginTransaction();
    
            // Nos aseguramos de que la empresa no cambie
            $data = $request->only([
                'numero_interno','marca','modelo','tipo_vehiculo','ano',
                'tipo_combustible_id','indice_consumo','prueba_litro','ficav',
                'capacidad_tanque','color','chapa','numero_motor',
                'numero_chasis','estado_tecnico','chofer_id'
            ]);
    
            // Siempre forzamos la misma empresa del usuario
            $data['empresa_id'] = auth()->user()->empresa_id;
    
            $vehiculo->update($data);
    
            DB::commit();
            return ResponseFormat::response(200, 'Vehículo actualizado con éxito.', $vehiculo);
    
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Vehículo no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Elimina un Vehículo específico.
     */
    public function destroy($id)
    {
        try {
            $vehiculo = Vehiculo::findOrFail($id);

            DB::beginTransaction();

            $vehiculo->delete(); // SoftDeletes si está habilitado
            DB::commit();

            return ResponseFormat::response(200, 'Vehículo eliminado con éxito.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Vehículo no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::response(500, 'Error al eliminar el Vehículo. Puede tener elementos relacionados.', null);
        }
    }

    /**
     * Calcula el Coeficiente de Disponibilidad Técnica (CDT) para vehículos.
     * Puede calcular el CDT de un vehículo específico o de todos.
     * Al final, si es un reporte general, añade el CDT total del parque automotor.
     *
     * @param  \Illuminate\Http\Request  $request
     * - year (required): Año para el reporte.
     * - month (required): Mes para el reporte.
     * - vehiculo_id (optional): ID de un vehículo específico para filtrar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateCdt(Request $request)
    {
        try {
            // 1. Validar los parámetros de entrada
            $validator = Validator::make($request->all(), [
                'year'        => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'month'       => 'required|integer|min:1|max:12',
                'vehiculo_id' => 'nullable|integer|exists:vehiculos,id', // 'nullable' permite que sea opcional
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            $year = $request->input('year');
            $month = $request->input('month');
            $vehiculoId = $request->input('vehiculo_id'); // Obtener el ID del vehículo si se proporciona

            // Definir el inicio y fin del mes de reporte
            $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            $totalDaysInMonth = $startOfMonth->daysInMonth;

            // 2. Obtener los vehículos a procesar
            // Si se proporciona un vehiculo_id, obtener solo ese vehículo.
            // Si no, obtener todos los vehículos.
            if ($vehiculoId) {
                $vehicles = Vehiculo::where('id', $vehiculoId)->get();
            } else {
                $vehicles = Vehiculo::all();
            }

            $results = []; // Array para almacenar los resultados individuales de cada vehículo

            // Variables para el CDT total del parque automotor
            $totalOperationalDaysForAllVehicles = 0;
            $totalWorkingDaysForAllVehicles = 0;
            $totalParalyzedDaysForAllVehicles = 0;

            // 3. Iterar sobre cada vehículo para calcular su CDT individual
            foreach ($vehicles as $vehiculo) {
                $operationalDaysForVehicle = $totalDaysInMonth; // Días operativos para este vehículo en el mes
                $currentParalyzedDays = 0; // Días paralizados acumulados para este vehículo

                // Obtener las inoperatividades del vehículo
                $inoperatividades = $vehiculo->inoperatividades()->get();

                foreach ($inoperatividades as $inoperatividad) {
                    $salida = Carbon::parse($inoperatividad->fecha_salida_servicio)->startOfDay();

                    if (is_null($inoperatividad->fecha_reanudacion_servicio)) {
                        // Si la avería aún está activa, la fecha de reanudación es el inicio del día actual
                        // o el final del mes de reporte, lo que ocurra primero.
                        $reanudacion = Carbon::now()->startOfDay();
                        $reanudacion = $reanudacion->min($endOfMonth);
                    } else {
                        // Si la avería ya terminó, la fecha de reanudación es el final del día de reanudación.
                        $reanudacion = Carbon::parse($inoperatividad->fecha_reanudacion_servicio)->endOfDay();
                    }

                    // Calcular el período de superposición de la avería con el mes de reporte
                    $overlapStart = $salida->max($startOfMonth);
                    $overlapEnd = $reanudacion->min($endOfMonth);

                    if ($overlapStart->lte($overlapEnd)) {
                        // Aseguramos que ambas fechas están al inicio del día para un cálculo preciso de diffInDays
                        $startForDiff = $overlapStart->copy()->startOfDay();
                        $endForDiff = $overlapEnd->copy()->startOfDay();

                        // Calcular la diferencia en días, incluyendo el día de inicio
                        $daysInoperable = $startForDiff->diffInDays($endForDiff) + 1;
                        $currentParalyzedDays += $daysInoperable;
                    }
                }

                // Redondear los días paralizados a un número entero
                $paralyzedDaysForVehicle = round($currentParalyzedDays);

                // Asegurarse de que los días paralizados no excedan los días totales del mes
                $paralyzedDaysForVehicle = min($paralyzedDaysForVehicle, $totalDaysInMonth);

                // Calcular los días trabajando para este vehículo
                $workingDaysForVehicle = $operationalDaysForVehicle - $paralyzedDaysForVehicle;
                $workingDaysForVehicle = max(0, $workingDaysForVehicle); // Asegurar que no sea negativo

                // Calcular el CDT individual del vehículo
                $cdtForVehicle = ($operationalDaysForVehicle > 0) ? ($workingDaysForVehicle / $operationalDaysForVehicle) * 100 : 0;

                // Añadir los resultados individuales al array de resultados
                $results[] = [
                    'vehiculo_id'                   => $vehiculo->id,
                    'chapa'                         => $vehiculo->chapa,
                    'tipo_vehiculo'                 => $vehiculo->tipo_vehiculo,
                    'dias_operativos_mes'           => $operationalDaysForVehicle,
                    'dias_paralizado_por_averias'   => $paralyzedDaysForVehicle,
                    'dias_trabajando'               => $workingDaysForVehicle,
                    'CDT'                           => round($cdtForVehicle, 2),
                ];

                // 4. Acumular para el CDT total si no se está filtrando por un vehículo específico
                if (!$vehiculoId) {
                    $totalOperationalDaysForAllVehicles += $operationalDaysForVehicle;
                    $totalParalyzedDaysForAllVehicles += $paralyzedDaysForVehicle;
                    $totalWorkingDaysForAllVehicles += $workingDaysForVehicle;
                }
            }

            // 5. Calcular y añadir el CDT total si no se filtró por un vehículo específico
            if (!$vehiculoId) {
                $totalCdt = ($totalOperationalDaysForAllVehicles > 0)
                            ? ($totalWorkingDaysForAllVehicles / $totalOperationalDaysForAllVehicles) * 100
                            : 0;

                $responseMessage = 'Reporte CDT generado con éxito (basado en averías).';
                return ResponseFormat::response(200, $responseMessage, [
                    'vehiculos_data' => $results, // Los datos de cada vehículo individual
                    'reporte_total' => [
                        'dias_operativos_totales'           => $totalOperationalDaysForAllVehicles,
                        'dias_paralizado_por_averias_totales' => $totalParalyzedDaysForAllVehicles,
                        'dias_trabajando_totales'           => $totalWorkingDaysForAllVehicles,
                        'CDT_total_parque'                  => round($totalCdt, 2),
                    ]
                ]);

            } else {
                // Si se filtró por un vehículo específico, simplemente retornamos los resultados de ese vehículo.
                $responseMessage = 'Reporte CDT generado con éxito para vehículo ' . $vehiculo->chapa . '.';
                return ResponseFormat::response(200, $responseMessage, [
                    'vehiculos_data' => $results
                ]);
            }

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
    

}
