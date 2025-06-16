<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TarjetaCombustible;
use App\Models\CargaCombustible; // Asegúrate de importar CargaCombustible
use App\Models\RetiroCombustible; // Asegúrate de importar CargaCombustible
use App\Models\TipoCombustible;
use App\Models\Vehiculo;
use App\Models\Empresa;
use App\Models\Chofer;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use PDF;
use Illuminate\Support\Facades\Log; // Importar la fachada Log

class TarjetaCombustibleController extends Controller
{
    /**
     * Lista todas las Tarjetas de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $empresaId = $user->empresa_id;

            // Paginación
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            // Filtros
            $search             = $request->input('search');
            $filterCombustible  = $request->input('tipo_combustible_id');
            $filterChofer       = $request->input('chofer_id');
            $filterActiva       = $request->input('activa');

            // Base query
            $tarjetasQuery = TarjetaCombustible::with(['tipoCombustible', 'empresa', 'chofer', 'chofer.vehiculo'])
                ->where('empresa_id', $empresaId)
                ->when($search, function ($q, $search) {
                    $q->where(function ($q2) use ($search) {
                        $q2->where('numero',                               'like', "%{$search}%")
                            ->orWhere('saldo_monetario_actual',             'like', "%{$search}%")
                            ->orWhere('cantidad_actual',                    'like', "%{$search}%")
                            ->orWhere('saldo_maximo',                       'like', "%{$search}%")
                            ->orWhere('limite_consumo_mensual',             'like', "%{$search}%")
                            ->orWhere('consumo_cantidad_mensual_acumulado', 'like', "%{$search}%")
                            ->orWhere('fecha_vencimiento',                  'like', "%{$search}%");
                    });
                })
                ->when($filterCombustible, fn($q) => $q->where('tipo_combustible_id', $filterCombustible))
                ->when($filterChofer, fn($q) => $q->where('chofer_id', $filterChofer))
                ->when(!is_null($filterActiva), fn($q) => $q->where('activa', $filterActiva));

            // Datos + meta
            if ($itemsPerPage == -1) {
                $collection = $tarjetasQuery->get();
                $meta = [
                    'total'     => $collection->count(),
                    'perPage'   => $collection->count(),
                    'page'      => 1,
                    'last_page' => 1,
                ];
            } else {
                $paginated = $tarjetasQuery->paginate($itemsPerPage, ['*'], 'page', $page);
                $collection = $paginated->items();
                $meta = [
                    'total'     => $paginated->total(),
                    'perPage'   => $paginated->perPage(),
                    'page'      => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                ];
            }

            // Mapeo
            $tarjetas = collect($collection)->map(function ($v) {
                return [
                    'id'                                 => $v->id,
                    'numero'                             => $v->numero,
                    'saldo_monetario_actual'             => $v->saldo_monetario_actual,
                    'cantidad_actual'                    => $v->cantidad_actual,
                    'saldo_maximo'                       => $v->saldo_maximo,
                    'limite_consumo_mensual'             => $v->limite_consumo_mensual,
                    'consumo_cantidad_mensual_acumulado' => $v->consumo_cantidad_mensual_acumulado,
                    'fecha_vencimiento'                  => $v->fecha_vencimiento,
                    'tipo_combustible_id'                => optional($v->tipoCombustible)->nombre,
                    'empresa_id'                         => $v->empresa_id,
                    'activa'                             => $v->activa,
                    'chofer_id'                          => optional($v->chofer)->nombre,
                ];
            });

            return ResponseFormat::response(
                200,
                'Lista de Tarjetas de Combustible obtenida con éxito.',
                $tarjetas,
                $meta
            );
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }


    /**
     * Crea una nueva Tarjeta de Combustible.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'numero'                  => 'required|string|max:255|unique:tarjeta_combustibles',
                'saldo_maximo'            => 'nullable|numeric|min:0',
                'limite_consumo_mensual'  => 'nullable|numeric|min:0',
                'cantidad_actual'         => 'nullable|numeric|min:0',
                'saldo_monetario_actual'  => 'nullable|numeric|min:0',
                'tipo_combustible_id'     => 'required|exists:tipo_combustibles,id',
                'fecha_vencimiento'       => 'required|date',
                'activa'                  => 'nullable|boolean',
                'chofer_id'               => 'required|exists:choferes,id',
            ], [
                'numero.required'                  => 'El número de tarjeta es obligatorio.',
                'numero.unique'                    => 'El número de tarjeta ya existe.',
                'saldo_maximo.min'                 => 'El saldo máximo no puede ser negativo.',
                'limite_consumo_mensual.min'       => 'El límite de consumo mensual no puede ser negativo.',
                'cantidad_actual.min'              => 'La cantidad actual no puede ser negativa.',
                'saldo_monetario_actual.min'       => 'El saldo monetario actual no puede ser negativo.',
                'tipo_combustible_id.required'     => 'El tipo de combustible es obligatorio.',
                'tipo_combustible_id.exists'       => 'El tipo de combustible seleccionado no existe.',
                'fecha_vencimiento.required'       => 'La fecha de vencimiento es obligatoria.',
                'fecha_vencimiento.date'           => 'La fecha de vencimiento debe ser una fecha válida.',
                'chofer_id.required'               => 'El chofer es obligatorio.',
                'chofer_id.exists'                 => 'El chofer seleccionado no existe.',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction();

            // Forzar la empresa del usuario autenticado
            $empresaId = auth()->user()->empresa_id;

            $data = $request->all();
            $data['empresa_id'] = $empresaId;
            $data['cantidad_actual'] = $request->input('cantidad_actual', 0.00);
            $data['saldo_monetario_actual'] = $request->input('saldo_monetario_actual', 0.00);

            if ($request->has('saldo_maximo') && $data['saldo_maximo'] !== null) {
                if ($data['cantidad_actual'] > $data['saldo_maximo']) {
                    DB::rollBack();
                    return ResponseFormat::response(400, 'La cantidad actual inicial no puede ser mayor que el saldo máximo.', null);
                }
                if ($data['saldo_monetario_actual'] > $data['saldo_maximo']) {
                    DB::rollBack();
                    return ResponseFormat::response(400, 'El saldo monetario actual inicial no puede ser mayor que el saldo máximo.', null);
                }
            }

            $tarjeta = TarjetaCombustible::create($data);

            DB::commit();
            return ResponseFormat::response(201, 'Tarjeta de Combustible creada con éxito.', $tarjeta);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }


    /**
     * Muestra una Tarjeta de Combustible específica.
     */
    public function show($id)
    {
        try {
            $tarjeta = TarjetaCombustible::with(['tipoCombustible', 'empresa', 'chofer.vehiculo', 'cargas', 'retiros'])->findOrFail($id);
            return ResponseFormat::response(200, 'Tarjeta de Combustible obtenida con éxito.', $tarjeta);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
    public function getNames()
    {
        try {
            $numeros = TarjetaCombustible::select('id', 'numero')->get();
            return ResponseFormat::response(200, 'Lista de tarjetas obtenida correctamente.', $numeros);
        } catch (\Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Obtiene el precio del tipo de combustible asociado a una tarjeta.
     *
     * @param  int  $id El ID de la Tarjeta de Combustible.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrecioCombustiblePorTarjeta($id)
    {
        try {
            // Buscar la tarjeta por ID, incluyendo la relación con tipoCombustible para optimizar la consulta.
            // findOrFail lanzará una excepción ModelNotFoundException si no la encuentra.
            $tarjeta = TarjetaCombustible::with('tipoCombustible')->findOrFail($id);

            // Verificar si la tarjeta realmente tiene un tipo de combustible asociado.
            // Aunque las reglas de validación lo hacen muy probable, es una buena práctica verificar.
            if (!$tarjeta->tipoCombustible) {
                return ResponseFormat::response(404, 'La tarjeta no tiene un tipo de combustible asociado.', null);
            }

            // Obtener el precio del tipo de combustible.
            $precio = $tarjeta->tipoCombustible->precio;

            // Preparar la respuesta con datos contextuales útiles.
            $data = [
                'tarjeta_id' => (int) $id,
                'numero_tarjeta' => $tarjeta->numero,
                'tipo_combustible_nombre' => $tarjeta->tipoCombustible->nombre,
                'precio' => (float) $precio
            ];

            // Retornar la respuesta exitosa utilizando el formato estándar.
            return ResponseFormat::response(200, 'Precio del combustible obtenido con éxito.', $data);
        } catch (ModelNotFoundException $e) {
            // Capturar el error específico si findOrFail no encuentra la tarjeta.
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            // Capturar cualquier otro error inesperado.
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Actualiza una Tarjeta de Combustible específica.
     */
    public function update(Request $request, $id)
    {
        try {
            $tarjeta = TarjetaCombustible::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'numero'                  => 'sometimes|string|max:255|unique:tarjeta_combustibles,numero,' . $id,
                'saldo_maximo'            => [
                    'nullable',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($tarjeta, $request) {
                        $currentOrNewCantidadActual = $request->input('cantidad_actual', $tarjeta->cantidad_actual);
                        $currentOrNewSaldoMonetarioActual = $request->input('saldo_monetario_actual', $tarjeta->saldo_monetario_actual);
                        if ($request->has('saldo_maximo') && $value !== null) {
                            if ($value < $currentOrNewCantidadActual) {
                                $fail('El saldo máximo (cantidad) no puede ser menor que la cantidad de combustible actual (' . round($currentOrNewCantidadActual, 2) . ').');
                            }
                            if ($value < $currentOrNewSaldoMonetarioActual) {
                                $fail('El saldo máximo (monetario) no puede ser menor que el saldo monetario actual (' . round($currentOrNewSaldoMonetarioActual, 2) . ').');
                            }
                        }
                    },
                ],
                'limite_consumo_mensual'  => [
                    'nullable',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($tarjeta) {
                        if ($value !== null) {
                            $currentMonthConsumption = $tarjeta->retiros()
                                ->whereYear('fecha', Carbon::now()->year)
                                ->whereMonth('fecha', Carbon::now()->month)
                                ->sum('cantidad');
                            if ($value < $currentMonthConsumption) {
                                $fail('El límite de consumo mensual no puede ser menor que la cantidad ya consumida este mes (' . round($currentMonthConsumption, 2) . ').');
                            }
                        }
                    },
                ],
                'cantidad_actual'         => [
                    'nullable',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($tarjeta, $request) {
                        if ($value !== null) {
                            $targetSaldoMaximo = $request->input('saldo_maximo', $tarjeta->saldo_maximo);
                            if ($targetSaldoMaximo !== null && $value > $targetSaldoMaximo) {
                                $fail('La cantidad actual no puede ser mayor que el saldo máximo (' . round($targetSaldoMaximo, 2) . ').');
                            }
                        }
                    },
                ],
                'saldo_monetario_actual'  => [
                    'nullable',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($tarjeta, $request) {
                        if ($value !== null) {
                            $targetSaldoMaximo = $request->input('saldo_maximo', $tarjeta->saldo_maximo);
                            if ($targetSaldoMaximo !== null && $value > $targetSaldoMaximo) {
                                $fail('El saldo monetario actual no puede ser mayor que el saldo máximo (' . round($targetSaldoMaximo, 2) . ').');
                            }
                        }
                    },
                ],
                'tipo_combustible_id'     => 'sometimes|exists:tipo_combustibles,id',
                'fecha_vencimiento'       => 'sometimes|date',
                'activa'                  => 'sometimes|boolean',
                'chofer_id'               => 'sometimes|exists:choferes,id',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction();

            $data = $request->all();

            // Forzar el empresa_id al del usuario logueado
            $data['empresa_id'] = auth()->user()->empresa_id;

            $tarjeta->update($data);
            DB::commit();
            return ResponseFormat::response(200, 'Tarjeta de Combustible actualizada con éxito.', $tarjeta);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Elimina una Tarjeta de Combustible específica.
     */
    public function destroy($id)
    {
        try {
            $tarjeta = TarjetaCombustible::findOrFail($id);
            DB::beginTransaction();

            if ($tarjeta->cargas()->count() > 0 || $tarjeta->retiros()->count() > 0) {
                DB::rollBack();
                return ResponseFormat::response(400, 'No se puede eliminar la Tarjeta de Combustible porque tiene cargas o retiros asociados.', null);
            }

            $tarjeta->delete();
            DB::commit();
            return ResponseFormat::response(200, 'Tarjeta de Combustible eliminada con éxito.', null);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Genera un reporte de consumo de combustible para un mes y año específicos.
     * Puede filtrar por una tarjeta de combustible específica.
     */
    public function calculateConsumoCombustible(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'year' => 'required|numeric|digits:4',
                'month' => 'required|numeric|between:1,12',
                'tarjeta_id' => 'nullable|exists:tarjeta_combustibles,id',
            ], [
                'year.required' => 'El año es obligatorio.',
                'month.required' => 'El mes es obligatorio.',
                'year.numeric' => 'El año debe ser un número.',
                'month.numeric' => 'El mes debe ser un número.',
                'month.between' => 'El mes debe ser un valor entre 1 y 12.',
                'tarjeta_id.exists' => 'La tarjeta de combustible seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            $year = $request->input('year');
            $month = $request->input('month');
            $tarjetaId = $request->input('tarjeta_id');

            // 1. Obtener la(s) tarjeta(s) de combustible
            $tarjetasQuery = TarjetaCombustible::with(['chofer.vehiculo', 'tipoCombustible']);

            if ($tarjetaId) {
                $tarjetasQuery->where('id', $tarjetaId);
            }

            $tarjetas = $tarjetasQuery->get();
            $reportData = [];

            // Fecha de inicio y fin del mes solicitado
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Fecha del mes anterior para buscar el último saldo
            $previousMonthDate = $startDate->copy()->subMonth();

            foreach ($tarjetas as $tarjeta) {
                Log::info("--- Procesando tarjeta: {$tarjeta->numero} (ID: {$tarjeta->id}) para el mes {$month}/{$year} ---");

                // Initialize values to 'No disponible'
                $saldoMonetarioInicial = 'No disponible';
                $cantidadCombustibleInicial = 'No disponible';
                $saldoMonetarioFinalMesReporte = 'No disponible';
                $cantidadCombustibleFinalMesReporte = 'No disponible';


                // --- Logic for saldo_monetario_inicial (always the last Carga of the previous month) ---
                $lastCargaPreviousMonth = CargaCombustible::where('tarjeta_combustible_id', $tarjeta->id)
                    ->whereYear('fecha', $previousMonthDate->year)
                    ->whereMonth('fecha', $previousMonthDate->month)
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc')
                    ->first();

                if ($lastCargaPreviousMonth) {
                    $saldoMonetarioInicial = $lastCargaPreviousMonth->saldo_monetario_al_momento_carga;
                    Log::info("Última carga del mes anterior para {$tarjeta->numero}: ", $lastCargaPreviousMonth->toArray());
                } else {
                    Log::info("No se encontró carga en el mes anterior para {$tarjeta->numero}.");
                }

                // --- Logic for cantidad_combustible_inicial (last Retiro of previous month, then fallback to last Carga) ---
                $lastRetiroPreviousMonth = $tarjeta->retiros()
                    ->whereYear('fecha', $previousMonthDate->year)
                    ->whereMonth('fecha', $previousMonthDate->month)
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc')
                    ->first();

                if ($lastRetiroPreviousMonth) {
                    $cantidadCombustibleInicial = $lastRetiroPreviousMonth->cantidad_combustible_al_momento_retiro;
                    Log::info("Último retiro del mes anterior para {$tarjeta->numero}: ", $lastRetiroPreviousMonth->toArray());
                } else {
                    Log::info("No se encontró retiro en el mes anterior para {$tarjeta->numero}.");
                    // If no withdrawal, fall back to the last top-up for quantity from previous month
                    if ($lastCargaPreviousMonth) { // Reuse if already found
                        $cantidadCombustibleInicial = $lastCargaPreviousMonth->cantidad_combustible_al_momento_carga;
                    } else { // No movements in previous month
                        $cantidadCombustibleInicial = 'No disponible';
                    }
                }

                $saldoAnterior = [
                    'saldo_monetario' => $saldoMonetarioInicial,
                    'cantidad_combustible' => $cantidadCombustibleInicial,
                ];
                Log::info("Saldo anterior calculado para {$tarjeta->numero}: ", $saldoAnterior);

                // 3. Obtener todas las cargas y retiros del mes seleccionado
                $cargas = $tarjeta->cargas()->whereBetween('fecha', [$startDate, $endDate])->get()->map(function ($item) {
                    $item->tipo_movimiento = 'CARGA';
                    // Ya no forzamos 'no_chips' a null. Permitimos que el valor del modelo sea utilizado.
                    // Si la columna 'no_chip' existe en la tabla y está vacía, será null.
                    // Si no existe, este 'map' no la creará, pero la vista ya maneja `?? 'N/A'`.
                    return $item;
                });

                $retiros = $tarjeta->retiros()->whereBetween('fecha', [$startDate, $endDate])->get()->map(function ($item) {
                    $item->tipo_movimiento = 'RETIRO';
                    return $item;
                });
                Log::info("Cargas encontradas para el mes actual para {$tarjeta->numero}: ", $cargas->toArray());
                Log::info("Retiros encontrados para el mes actual para {$tarjeta->numero}: ", $retiros->toArray());


                // Combinar y ordenar movimientos por fecha y luego por hora
                $movimientos = $cargas->concat($retiros)->sortBy(function ($movimiento) {
                    return Carbon::parse($movimiento->fecha . ' ' . $movimiento->hora);
                })->values();
                Log::info("Movimientos combinados y ordenados para {$tarjeta->numero}: ", $movimientos->toArray());


                // Calcular totales
                $totalCargasCantidad = $cargas->sum('cantidad');
                $totalCargasImporte = $cargas->sum('importe');
                $totalRetirosCantidad = $retiros->sum('cantidad');
                $totalRetirosImporte = $retiros->sum('importe');
                Log::info("Totales calculados para {$tarjeta->numero}: ", [
                    'total_cargas_cantidad' => $totalCargasCantidad,
                    'total_cargas_importe' => $totalCargasImporte,
                    'total_retiros_cantidad' => $totalRetirosCantidad,
                    'total_retiros_importe' => $totalRetirosImporte,
                ]);

                // --- Logic for saldo_final (for the month being reported) ---

                // Last Carga of the current report month for final monetary balance
                $lastCargaCurrentMonth = CargaCombustible::where('tarjeta_combustible_id', $tarjeta->id)
                    ->whereYear('fecha', $year)
                    ->whereMonth('fecha', $month)
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc')
                    ->first();

                if ($lastCargaCurrentMonth) {
                    $saldoMonetarioFinalMesReporte = $lastCargaCurrentMonth->saldo_monetario_al_momento_carga;
                    Log::info("Última carga del mes actual para {$tarjeta->numero}: ", $lastCargaCurrentMonth->toArray());
                } else {
                    Log::info("No se encontró carga en el mes actual para {$tarjeta->numero}.");
                }

                // Last Retiro of the current report month for final quantity balance
                $lastRetiroCurrentMonth = $tarjeta->retiros()
                    ->whereYear('fecha', $year)
                    ->whereMonth('fecha', $month)
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc')
                    ->first();

                if ($lastRetiroCurrentMonth) {
                    $cantidadCombustibleFinalMesReporte = $lastRetiroCurrentMonth->cantidad_combustible_al_momento_retiro;
                    Log::info("Último retiro del mes actual para {$tarjeta->numero}: ", $lastRetiroCurrentMonth->toArray());
                } else {
                    Log::info("No se encontró retiro en el mes actual para {$tarjeta->numero}.");
                    // If no withdrawal, fall back to the last top-up for quantity from current month
                    if ($lastCargaCurrentMonth) { // Reuse if already found
                        $cantidadCombustibleFinalMesReporte = $lastCargaCurrentMonth->cantidad_combustible_al_momento_carga;
                    } else { // No movements in current month
                        $cantidadCombustibleFinalMesReporte = 'No disponible';
                    }
                }
                Log::info("Saldo final calculado para {$tarjeta->numero}: ", [
                    'saldo_monetario_final' => $saldoMonetarioFinalMesReporte,
                    'cantidad_combustible_final' => $cantidadCombustibleFinalMesReporte,
                ]);


                // 4. Construir la información de la tarjeta para el reporte
                $reportData[] = [
                    'tarjeta_info' => [
                        'tarjeta_id' => $tarjeta->id,
                        'numero' => $tarjeta->numero,
                        'chofer_nombre' => $tarjeta->chofer ? $tarjeta->chofer->nombre . ' ' . $tarjeta->chofer->apellidos : 'N/A',
                        'fecha_vencimiento' => $tarjeta->fecha_vencimiento,
                        'vehiculo_chapa' => $tarjeta->chofer && $tarjeta->chofer->vehiculo ? $tarjeta->chofer->vehiculo->chapa : 'N/A',
                        'tipo_combustible_nombre' => $tarjeta->tipoCombustible ? $tarjeta->tipoCombustible->nombre : 'N/A',
                        'tipo_combustible_precio' => $tarjeta->tipoCombustible ? $tarjeta->tipoCombustible->precio : 'N/A',
                        'mes_reporte' => $startDate->format('F Y'), // e.g., "June 2024"
                    ],
                    'saldo_anterior' => $saldoAnterior,
                    'movimientos' => $movimientos,
                    'totales_mes' => [
                        'total_cargas_cantidad' => round($totalCargasCantidad, 2),
                        'total_cargas_importe' => round($totalCargasImporte, 2),
                        'total_retiros_cantidad' => round($totalRetirosCantidad, 2),
                        'total_retiros_importe' => round($totalRetirosImporte, 2),
                    ],
                    'saldo_final' => [
                        // These are the ending balances for the specific month being reported
                        'saldo_monetario_final' => $saldoMonetarioFinalMesReporte,
                        'cantidad_combustible_final' => $cantidadCombustibleFinalMesReporte,
                    ]
                ];
            }

            // --- Generación del PDF ---
            // Puedes pasar la colección de reportData a la vista Blade
            $pdf = PDF::loadView('reporte_combustible', ['reportData' => $reportData]);

            // Define el nombre del archivo PDF
            $fileName = 'reporte_consumo_combustible_' . $year . '_' . $month . '.pdf';

            // Opciones:
            // return $pdf->download($fileName); // Para descargar el PDF
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (Exception $e) {
            Log::error("Error en calculateConsumoCombustible: " . $e->getMessage(), ['exception' => $e]); // Log del error
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
