<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo; // Assuming Vehiculo model is in App\Models
use App\Models\VehiculoInoperatividad; // Assuming VehiculoInoperatividad model is in App\Models
use App\Models\TarjetaCombustible; // Needed for the example calculateConsumoCombustible
use App\Models\CargaCombustible;    // Needed for the example calculateConsumoCombustible
use App\Models\RetiroCombustible;   // Needed for the example calculateConsumoCombustible
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;
use PDF; // Asegúrate de haber configurado el alias en config/app.php para barryvdh/laravel-dompdf
use Illuminate\Support\Facades\Log; // For logging in the example method

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
                            ->orWhere('capacidad_tanque', 'like', "%{$search}%")
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
                    'numero_interno',
                    'marca',
                    'modelo',
                    'tipo_vehiculo',
                    'ano',
                    'tipo_combustible_id',
                    'indice_consumo',
                    'prueba_litro',
                    'ficav',
                    'capacidad_tanque',
                    'color',
                    'chapa',
                    'numero_motor',
                    'numero_chasis',
                    'estado_tecnico',
                    'chofer_id'
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
                'numero_interno',
                'marca',
                'modelo',
                'tipo_vehiculo',
                'ano',
                'tipo_combustible_id',
                'indice_consumo',
                'prueba_litro',
                'ficav',
                'capacidad_tanque',
                'color',
                'chapa',
                'numero_motor',
                'numero_chasis',
                'estado_tecnico',
                'chofer_id'
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
                'vehiculo_id' => 'nullable|integer|exists:vehiculos,id',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            $year       = $request->input('year');
            $month      = $request->input('month');
            $vehiculoId = $request->input('vehiculo_id');

            // Fechas del mes de reporte
            $startOfMonth     = Carbon::create($year, $month, 1)->startOfDay();
            $endOfMonth       = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            $totalDaysInMonth = $startOfMonth->daysInMonth;

            // Obtener los vehículos
            $vehicles = $vehiculoId
                ? Vehiculo::where('id', $vehiculoId)->get()
                : Vehiculo::all();

            $results = [];
            $totOp   = 0;
            $totPar  = 0;
            $totWork = 0;

            foreach ($vehicles as $vehiculo) {
                $operationalDays = $totalDaysInMonth;
                $paralyzedDays   = 0;

                $inops = $vehiculo->inoperatividades()
                    ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->where(function ($q2) use ($startOfMonth, $endOfMonth) {
                            $q2->whereBetween('fecha_salida_servicio',   [$startOfMonth, $endOfMonth])
                                ->whereBetween('fecha_reanudacion_servicio', [$startOfMonth, $endOfMonth]);
                        })->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                            $q2->where('fecha_salida_servicio', '<', $startOfMonth)
                                ->where(function ($sq) use ($startOfMonth, $endOfMonth) {
                                    $sq->whereNull('fecha_reanudacion_servicio')
                                        ->orWhereBetween('fecha_reanudacion_servicio', [$startOfMonth, $endOfMonth])
                                        ->orWhere('fecha_reanudacion_servicio', '>', $endOfMonth);
                                });
                        })->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                            $q2->whereBetween('fecha_salida_servicio', [$startOfMonth, $endOfMonth])
                                ->where(function ($sq) use ($endOfMonth) {
                                    $sq->whereNull('fecha_reanudacion_servicio')
                                        ->orWhere('fecha_reanudacion_servicio', '>', $endOfMonth);
                                });
                        });
                    })
                    ->get();

                foreach ($inops as $inop) {
                    $salida      = Carbon::parse($inop->fecha_salida_servicio)->startOfDay();
                    $reanudacion = $inop->fecha_reanudacion_servicio
                        ? Carbon::parse($inop->fecha_reanudacion_servicio)->startOfDay()
                        : Carbon::now()->startOfDay()->min($endOfMonth);

                    $overlapStart = $salida->max($startOfMonth);
                    $overlapEnd   = $reanudacion->min($endOfMonth);

                    if ($overlapStart->lte($overlapEnd)) {
                        $dias         = $overlapStart->diffInDays($overlapEnd) + 1;
                        $paralyzedDays += $dias;
                    }
                }

                $paralyzedDays = min($operationalDays, round($paralyzedDays));
                $workingDays   = max(0, $operationalDays - $paralyzedDays);
                $cdt           = $operationalDays > 0 ? ($workingDays / $operationalDays) * 100 : 0;

                $results[] = [
                    'vehiculo_id'                 => $vehiculo->id,
                    'chapa'                       => $vehiculo->chapa,
                    'tipo_vehiculo'               => $vehiculo->tipo_vehiculo,
                    'dias_operativos_mes'         => $operationalDays,
                    'dias_paralizado_por_averias' => $paralyzedDays,
                    'dias_trabajando'             => $workingDays,
                    'CDT'                         => round($cdt, 2),
                ];

                if (!$vehiculoId) {
                    $totOp   += $operationalDays;
                    $totPar  += $paralyzedDays;
                    $totWork += $workingDays;
                }
            }

            $reportData = [
                'vehiculos_data'  => $results,
                'mes_reporte_str' => Carbon::create($year, $month, 1)->locale('es')->monthName . ' ' . $year,
            ];

            if (!$vehiculoId) {
                $cdtTotal = $totOp > 0 ? ($totWork / $totOp) * 100 : 0;
                $reportData['reporte_total'] = [
                    'dias_operativos_totales'          => $totOp,
                    'dias_paralizado_por_averias_totales' => $totPar,
                    'dias_trabajando_totales'          => $totWork,
                    'CDT_total_parque'                 => round($cdtTotal, 2),
                ];
            } else {
                $reportData['vehiculo_chapa_reporte'] = $vehicles->first()->chapa ?? '';
            }

            // --- Generar el PDF ---
            $pdf = PDF::loadView('reporte_cdt', $reportData);

            $fileName = 'reporte_cdt_' . $year . '_' . $month
                . ($vehiculoId && $vehicles->isNotEmpty() ? '_' . $vehicles->first()->chapa : '')
                . '.pdf';

            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                // Crucial for exposing Content-Disposition to JavaScript
                ->header('Access-Control-Expose-Headers', 'Content-Disposition'); // <--- ADD THIS LINE
        } catch (Exception $e) {
            Log::error("Error en calculateCdt: " . $e->getMessage(), ['exception' => $e]);
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
