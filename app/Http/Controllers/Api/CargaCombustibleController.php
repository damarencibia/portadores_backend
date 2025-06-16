<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CargaCombustible;
use App\Models\TarjetaCombustible;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CargaCombustibleController extends Controller
{
    /**
     * Lista todas las Cargas de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            $itemsPerPage        = $request->input("itemsPerPage", 20);
            $page                = $request->input("page", 1);
            $tarjetaId           = $request->input("tarjeta_combustible_id");
            $choferId            = $request->input("chofer_id");
            $tipoCombustibleId   = $request->input("tipo_combustible_id");
            $registradorId       = $request->input("registrado_por_id");
            $search              = $request->input("search");
            $withTrashed         = filter_var($request->input("with_trashed", false), FILTER_VALIDATE_BOOLEAN);

            $cargasQuery = CargaCombustible::query()
                ->when($withTrashed, fn($q) => $q->withTrashed()) // 👈 incluye eliminados si se pide
                ->with([
                    'registradoPor',
                    'validadoPor',
                    'tarjetaCombustible.tipoCombustible',
                    'tarjetaCombustible.chofer'
                ])
                ->when($tarjetaId, fn($q) => $q->where('tarjeta_combustible_id', $tarjetaId))
                ->when(
                    $choferId,
                    fn($q) =>
                    $q->whereHas('tarjetaCombustible', fn($q2) => $q2->where('chofer_id', $choferId))
                )
                ->when(
                    $tipoCombustibleId,
                    fn($q) =>
                    $q->whereHas(
                        'tarjetaCombustible.tipoCombustible',
                        fn($q2) =>
                        $q2->where('id', $tipoCombustibleId)
                    )
                )
                ->when(
                    $registradorId,
                    fn($q) =>
                    $q->where('registrado_por_id', $registradorId)
                )
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('lugar', 'like', "%{$search}%")
                            ->orWhere('motivo', 'like', "%{$search}%")
                            ->orWhere('no_chip', 'like', "%{$search}%")
                            ->orWhere('importe', 'like', "%{$search}%")
                            ->orWhere('cantidad', 'like', "%{$search}%")
                            ->orWhere('odometro', 'like', "%{$search}%")
                            ->orWhereHas(
                                'tarjetaCombustible',
                                fn($q2) =>
                                $q2->where('numero', 'like', "%{$search}%")
                            );
                    });
                });

            // Paginación
            if ($itemsPerPage == -1) {
                $collection = $cargasQuery->get();
                $meta = [
                    'total'     => $collection->count(),
                    'perPage'   => $collection->count(),
                    'page'      => 1,
                    'last_page' => 1,
                ];
            } else {
                $paginated = $cargasQuery->paginate($itemsPerPage, ['*'], 'page', $page);
                $collection = $paginated->items();
                $meta = [
                    'total'     => $paginated->total(),
                    'perPage'   => $paginated->perPage(),
                    'page'      => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                ];
            }

            $cargas = collect($collection)->map(function ($carga) {
                return [
                    'id'                                => $carga->id,
                    'accesed'                           => $carga->accesed,
                    'fecha'                             => $carga->fecha,
                    'hora'                              => $carga->hora,
                    'tarjeta_combustible'               => optional($carga->tarjetaCombustible)->numero,
                    'tipo_combustible'                  => optional($carga->tarjetaCombustible?->tipoCombustible)->nombre,
                    'chofer'                            => optional($carga->tarjetaCombustible?->chofer)->nombre,
                    'cantidad'                          => $carga->cantidad,
                    'importe'                           => $carga->importe,
                    'odometro'                          => $carga->odometro,
                    'lugar'                             => $carga->lugar,
                    'motivo'                            => $carga->motivo,
                    'no_chip'                           => $carga->no_chip,
                    'registrado_por'                    => optional($carga->registradoPor)->name,
                    'validado_por'                      => optional($carga->validadoPor)->name,
                    'fecha_validacion'                  => $carga->fecha_validacion,
                    'estado'                            => $carga->estado,
                    'motivo_rechazo'                    => $carga->motivo_rechazo,
                    'saldo_monetario_anterior'          => $carga->saldo_monetario_anterior,
                    'cantidad_combustible_anterior'     => $carga->cantidad_combustible_anterior,
                    'saldo_monetario_al_momento_carga'  => $carga->saldo_monetario_al_momento_carga,
                    'cantidad_combustible_al_momento_carga' => $carga->cantidad_combustible_al_momento_carga,
                    'eliminado'                         => $carga->trashed(),
                    'deleted_at'                        => $carga->deleted_at,
                    'deletion_reason'                   => $carga->deletion_reason,
                ];
            });

            return ResponseFormat::response(200, 'Lista de Cargas de Combustible obtenida con éxito.', $cargas, $meta);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    public function getAccessedChargeIds()
    {
        try {
            // Get all CargaCombustible records where 'accesed' is true
            $accessedCharges = CargaCombustible::where('accesed', false)
                ->pluck('id') // Get only the 'id' column
                ->toArray(); // Convert the collection to a plain PHP array

            $count = count($accessedCharges);

            // You can log these IDs or use them as needed
            // For now, we'll return them in the response.
            // You might want to save them to a file, cache, or another database table
            // depending on your application's requirements.

            return ResponseFormat::response(
                200,
                "Se encontraron {$count} cargas con 'accesed' en verdadero.",
                [
                    'count' => $count,
                    'ids'   => $accessedCharges,
                ]
            );
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Crea una nueva Carga de Combustible.
     * El importe se calcula automáticamente basado en la cantidad y el precio del tipo de combustible de la tarjeta.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha'                 => 'required|date',
                'hora'                  => 'required',
                'cantidad'              => 'required|numeric|min:0.01', // Cantidad de combustible a agregar
                'odometro'              => 'required|numeric|min:0',
                'lugar'                 => 'nullable|string|max:255',
                'motivo'                => 'required|string|max:255',
                'no_chip'               => 'nullable|string|max:255',
                'registrado_por_id'     => 'required|exists:users,id',
                'validado_por_id'       => 'nullable|exists:users,id',
                'fecha_validacion'      => 'nullable|date',
                'estado'                => 'nullable|string|max:50',
                'motivo_rechazo'        => 'nullable|string|max:255',
                'tarjeta_combustible_id' => 'required|exists:tarjeta_combustibles,id',
            ], [
                'cantidad.min' => 'La cantidad de combustible a cargar debe ser mayor a 0.',
                'fecha.required' => 'La fecha de la carga es obligatoria.',
                'fecha.date' => 'La fecha de la carga debe ser una fecha válida.',
                'odometro.required' => 'La lectura del odómetro es obligatoria.',
                'odometro.numeric' => 'La lectura del odómetro debe ser un número.',
                'odometro.min' => 'La lectura del odómetro no puede ser menor a 0.',
                'motivo.required' => 'El motivo por el que se hace la transacción es requerido.',
                'registrado_por_id.required' => 'El usuario que registra es obligatorio.',
                'registrado_por_id.exists' => 'El usuario que registra no existe.',
                'validado_por_id.exists' => 'El usuario que valida no existe.',
                'fecha_validacion.date' => 'La fecha de validación debe ser una fecha válida.',
                'tarjeta_combustible_id.exists' => 'La tarjeta de combustible seleccionada no existe.',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction();

            $tarjeta = TarjetaCombustible::with('tipoCombustible')->findOrFail($request->input('tarjeta_combustible_id'));

            if (!$tarjeta->tipoCombustible || !isset($tarjeta->tipoCombustible->precio)) {
                DB::rollBack();
                return ResponseFormat::response(400, 'El tipo de combustible asociado a la tarjeta no tiene un precio definido.', null);
            }

            $calculatedImporte = round($request->input('cantidad') * $tarjeta->tipoCombustible->precio, 2);

            // Validaciones previas a la modificación del saldo
            if ($tarjeta->saldo_monetario_actual < $calculatedImporte) {
                DB::rollBack();
                return ResponseFormat::response(400, 'Saldo monetario insuficiente en la tarjeta para cubrir el importe de la carga.', null);
            }

            $projectedConsumoMensual = $tarjeta->consumo_cantidad_mensual_acumulado + $request->input('cantidad');
            if ($tarjeta->limite_consumo_mensual !== null && $projectedConsumoMensual > $tarjeta->limite_consumo_mensual) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La carga excede el límite de consumo mensual acumulado para esta tarjeta.', null);
            }

            $nuevaCantidadProyectada = $tarjeta->cantidad_actual + $request->input('cantidad');
            if ($tarjeta->saldo_maximo !== null && $nuevaCantidadProyectada > $tarjeta->saldo_maximo) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La cantidad de combustible a cargar excede el saldo máximo permitido para esta tarjeta.', null);
            }

            // Capturar los saldos actuales de la tarjeta ANTES de que se realice la carga
            $saldoMonetarioAntes = $tarjeta->saldo_monetario_actual;
            $cantidadCombustibleAntes = $tarjeta->cantidad_actual;

            $dataToCreate = $request->except([
                'saldo_monetario_al_momento_carga',
                'cantidad_combustible_al_momento_carga',
                'saldo_monetario_anterior',
                'cantidad_combustible_anterior',
                // 'accesed' // You might want to remove 'accesed' from here if it's always set by the backend
            ]);

            $dataToCreate['importe'] = $calculatedImporte;

            // --- Lógica para establecer 'accesed' basada en el rol del usuario autenticado ---
            $user = Auth::user();
            if ($user && $user->roles === 'supervisor') {
                $dataToCreate['accesed'] = true; // Set to true (or 1) for supervisors
            } else {
                $dataToCreate['accesed'] = false; // Set to false (or 0) for others by default
            }
            // --- Fin de la lógica de 'accesed' ---

            $carga = CargaCombustible::create($dataToCreate);

            // Asignar los saldos ANTERIORES capturados
            $carga->saldo_monetario_anterior = $saldoMonetarioAntes;
            $carga->cantidad_combustible_anterior = $cantidadCombustibleAntes;

            // Actualizar la tarjeta con los nuevos saldos
            $tarjeta->saldo_monetario_actual -= $calculatedImporte;
            $tarjeta->cantidad_actual += $request->input('cantidad');
            $tarjeta->consumo_cantidad_mensual_acumulado += $request->input('cantidad');
            $tarjeta->save();

            // Asignar los saldos FINALES de la tarjeta (después de la carga)
            $carga->saldo_monetario_al_momento_carga = $tarjeta->saldo_monetario_actual;
            $carga->cantidad_combustible_al_momento_carga = $tarjeta->cantidad_actual;
            $carga->save(); // Guardar la carga con todos los saldos actualizados

            DB::commit();
            return ResponseFormat::response(201, 'Carga de Combustible registrada con éxito y saldos de tarjeta actualizados.', $carga);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Muestra una Carga de Combustible específica.
     */
    public function show(Request $request, $id)
    {
        try {
            $tarjetaId         = $request->input("tarjeta_combustible_id");
            $choferId          = $request->input("chofer_id");
            $tipoCombustibleId = $request->input("tipo_combustible_id");
            $registradorId     = $request->input("registrado_por_id");

            $query = CargaCombustible::withTrashed()
                ->with([
                    'registradoPor',
                    'validadoPor',
                    'tarjetaCombustible.tipoCombustible',
                    'tarjetaCombustible.chofer'
                ])
                ->where('id', $id)
                ->when($tarjetaId, fn($q) => $q->where('tarjeta_combustible_id', $tarjetaId))
                ->when(
                    $choferId,
                    fn($q) =>
                    $q->whereHas('tarjetaCombustible', fn($q2) => $q2->where('chofer_id', $choferId))
                )
                ->when(
                    $tipoCombustibleId,
                    fn($q) =>
                    $q->whereHas(
                        'tarjetaCombustible.tipoCombustible',
                        fn($q2) =>
                        $q2->where('id', $tipoCombustibleId)
                    )
                )
                ->when(
                    $registradorId,
                    fn($q) =>
                    $q->where('registrado_por_id', $registradorId)
                );

            $carga = $query->firstOrFail();

            // 👉 Lógica para cambiar accesed según el rol
            $user = Auth::user();
            if ($user && $user->roles === 'supervisor') {
                $carga->accesed = true;
            } else {
                $carga->accesed = false;
            }

            $carga->save();

            $responseData = [
                'id'                                    => $carga->id,
                'accesed'                               => $carga->accesed,
                'fecha'                                 => $carga->fecha,
                'hora'                                  => $carga->hora,
                'tarjeta_combustible'                   => optional($carga->tarjetaCombustible)->numero,
                'tipo_combustible'                      => optional($carga->tarjetaCombustible?->tipoCombustible)->nombre,
                'precio_combustible'                    => optional($carga->tarjetaCombustible?->tipoCombustible)->precio,
                'chofer'                                => optional($carga->tarjetaCombustible?->chofer)->nombre,
                'cantidad'                              => $carga->cantidad,
                'importe'                               => $carga->importe,
                'odometro'                              => $carga->odometro,
                'lugar'                                 => $carga->lugar,
                'motivo'                                => $carga->motivo,
                'no_chip'                               => $carga->no_chip,
                'registrado_por'                        => optional($carga->registradoPor)->name,
                'validado_por'                          => optional($carga->validadoPor)->name,
                'fecha_validacion'                      => $carga->fecha_validacion,
                'estado'                                => $carga->estado,
                'motivo_rechazo'                        => $carga->motivo_rechazo,
                'saldo_monetario_anterior'              => $carga->saldo_monetario_anterior,
                'cantidad_combustible_anterior'         => $carga->cantidad_combustible_anterior,
                'saldo_monetario_al_momento_carga'      => $carga->saldo_monetario_al_momento_carga,
                'cantidad_combustible_al_momento_carga' => $carga->cantidad_combustible_al_momento_carga,
                'eliminado'                             => $carga->trashed(),
                'deleted_at'                            => $carga->deleted_at,
                'deletion_reason'                       => $carga->deletion_reason,
            ];

            return ResponseFormat::response(200, 'Carga de Combustible obtenida con éxito.', $responseData);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada con los filtros aplicados.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }




    /**
     * Actualiza una Carga de Combustible específica y ajusta los saldos de la tarjeta.
     * El importe se recalcula automáticamente si la cantidad cambia.
     */
    public function update(Request $request, $id)
    {
        try {
            $carga = CargaCombustible::findOrFail($id);
            $tarjeta = $carga->tarjetaCombustible;

            $oldCantidad = $carga->cantidad;
            $oldImporte = $carga->importe;
            // No es necesario capturar los saldos _anterior de la carga aquí,
            // ya que estos campos representan el estado ANTES de la carga ORIGINAL.
            // Los saldos actuales de la tarjeta son los que se revertirán y luego se aplicarán nuevos.

            $validator = Validator::make($request->all(), [
                'fecha'                  => 'sometimes|required|date',
                'hora'                   => 'sometimes|date_format:H:i:s',
                'cantidad'               => 'sometimes|required|numeric|min:0.01',
                'odometro'               => 'sometimes|required|numeric|min:0',
                'lugar'                  => 'sometimes|string|max:255',
                'no_chip'                => 'sometimes|string|max:255',
                'registrado_por_id'      => 'sometimes|required|exists:users,id',
                'validado_por_id'        => 'sometimes|exists:users,id',
                'fecha_validacion'       => 'sometimes|date',
                'estado'                 => 'sometimes|string|max:50',
                'motivo_rechazo'         => 'sometimes|string|max:50',
                'tarjeta_combustible_id' => 'sometimes|required|exists:tarjeta_combustibles,id',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction();

            if ($request->has('tarjeta_combustible_id') && $request->input('tarjeta_combustible_id') != $tarjeta->id) {
                DB::rollBack();
                return ResponseFormat::response(400, 'No se permite cambiar la tarjeta de combustible de una carga existente directamente. Elimine y cree una nueva carga.', null);
            }

            $tarjeta->load('tipoCombustible');
            if (!$tarjeta->tipoCombustible || !isset($tarjeta->tipoCombustible->precio)) {
                DB::rollBack();
                return ResponseFormat::response(400, 'El tipo de combustible asociado a la tarjeta no tiene un precio definido.', null);
            }

            $newCantidad = $request->input('cantidad', $oldCantidad);
            $newImporte = round($newCantidad * $tarjeta->tipoCombustible->precio, 2);

            // Revertir los efectos de la carga antigua en la tarjeta antes de aplicar los nuevos
            $tarjeta->saldo_monetario_actual += $oldImporte;
            $tarjeta->cantidad_actual -= $oldCantidad;
            $tarjeta->consumo_cantidad_mensual_acumulado -= $oldCantidad;

            // En este punto, los saldos de la tarjeta reflejan el estado justo ANTES de la carga original.
            // Si los campos 'saldo_monetario_anterior' y 'cantidad_combustible_anterior'
            // de la carga están nulos (por ejemplo, en registros legacy), los inicializamos ahora.
            // Si ya tienen valores (por registros nuevos), los respetamos porque representan un punto histórico fijo.
            if ($carga->saldo_monetario_anterior === null) {
                $carga->saldo_monetario_anterior = $tarjeta->saldo_monetario_actual;
            }
            if ($carga->cantidad_combustible_anterior === null) {
                $carga->cantidad_combustible_anterior = $tarjeta->cantidad_actual;
            }


            // Aplicar los efectos de la nueva carga (o los valores actualizados)
            $projectedSaldoMonetarioActual = $tarjeta->saldo_monetario_actual - $newImporte;
            $projectedCantidadActual = $tarjeta->cantidad_actual + $newCantidad;
            $projectedConsumoMensual = $tarjeta->consumo_cantidad_mensual_acumulado + $newCantidad;

            if ($projectedSaldoMonetarioActual < 0) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La actualización excede el saldo monetario disponible en la tarjeta.', null);
            }

            if ($tarjeta->saldo_maximo !== null && $projectedCantidadActual > $tarjeta->saldo_maximo) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La actualización excede la cantidad máxima de combustible permitida para esta tarjeta.', null);
            }

            if ($tarjeta->limite_consumo_mensual !== null && $projectedConsumoMensual > $tarjeta->limite_consumo_mensual) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La actualización excede el límite de consumo mensual acumulado para esta tarjeta.', null);
            }

            // Excluir los campos de solo lectura de la solicitud antes de actualizar
            $dataToUpdate = $request->except([
                'saldo_monetario_al_momento_carga',
                'cantidad_combustible_al_momento_carga',
                'saldo_monetario_anterior',
                'cantidad_combustible_anterior'
            ]);
            $dataToUpdate['importe'] = $newImporte;

            $carga->update($dataToUpdate); // Se actualiza la carga con los datos de la solicitud

            // Actualizar los saldos de la tarjeta con los valores proyectados
            $tarjeta->saldo_monetario_actual = $projectedSaldoMonetarioActual;
            $tarjeta->cantidad_actual = $projectedCantidadActual;
            $tarjeta->consumo_cantidad_mensual_acumulado = $projectedConsumoMensual;
            $tarjeta->save();

            // Guardar los saldos FINALES de la tarjeta en el registro de carga actualizado
            $carga->saldo_monetario_al_momento_carga = $tarjeta->saldo_monetario_actual;
            $carga->cantidad_combustible_al_momento_carga = $tarjeta->cantidad_actual;
            $carga->save(); // Guardar la carga con los saldos actualizados

            DB::commit();
            return ResponseFormat::response(200, 'Carga de Combustible actualizada con éxito y saldos de tarjeta ajustados.', $carga);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Valida o rechaza una Carga de Combustible.
     * Si 'valid' es false, revierte saldos y elimina la carga.
     */
    /**
     * Valida o rechaza una Carga de Combustible.
     * Si 'valid' es true, marca la carga como 'validada'.
     * Si 'valid' es false, revierte los saldos, marca la carga como 'rechazada' y requiere un motivo.
     */
    public function validar(Request $request, $id)
    {
        // --- 1. VALIDACIÓN ACTUALIZADA ---
        $validator = Validator::make($request->all(), [
            'valid'           => 'required|boolean',
            'validado_por_id' => 'required|exists:users,id', // Se requiere siempre para saber quién hace la acción
            'motivo_rechazo'  => 'required_if:valid,false|string|max:255', // Obligatorio si se rechaza
        ], [
            'validado_por_id.required' => 'El usuario que realiza la validación/rechazo es obligatorio.',
            'motivo_rechazo.required_if' => 'El motivo del rechazo es obligatorio cuando se rechaza una carga.',
        ]);

        if ($validator->fails()) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
        }

        try {
            DB::beginTransaction();

            $carga   = CargaCombustible::findOrFail($id);
            $tarjeta = $carga->tarjetaCombustible;

            // --- 2. PROTECCIÓN CONTRA DOBLE ACCIÓN ---
            // Evitar que una carga ya procesada se vuelva a procesar.
            if (in_array($carga->estado, ['validada', 'rechazada'])) {
                DB::rollBack();
                return ResponseFormat::response(400, 'Esta carga ya ha sido ' . $carga->estado . ' y no puede ser modificada.', null);
            }

            $isValid = $request->input('valid');

            if ($isValid) {
                // --- LÓGICA DE VALIDACIÓN (sin cambios) ---
                $carga->estado           = 'validada';
                $carga->validado_por_id  = $request->input('validado_por_id');
                $carga->fecha_validacion = Carbon::now();
                $carga->motivo_rechazo   = null; // Limpiar motivo de rechazo si se valida
                $carga->save();

                DB::commit();
                return ResponseFormat::response(200, 'Carga validada con éxito.', $carga);
            } else {
                // --- 3. NUEVA LÓGICA DE RECHAZO ---

                // a. Revertimos los saldos en la tarjeta
                $tarjeta->saldo_monetario_actual       += $carga->importe;
                $tarjeta->cantidad_actual              -= $carga->cantidad;
                $tarjeta->consumo_cantidad_mensual_acumulado -= $carga->cantidad;
                $tarjeta->save();

                // b. En lugar de eliminar, actualizamos el estado de la carga
                $carga->estado           = 'rechazada';
                $carga->motivo_rechazo   = $request->input('motivo_rechazo');
                $carga->validado_por_id  = $request->input('validado_por_id'); // Quién la rechazó
                $carga->fecha_validacion = Carbon::now()->toDateString();      // Cuándo se rechazó
                $carga->save(); // Guardamos la carga con el nuevo estado

                DB::commit();
                // Devolvemos la carga actualizada en la respuesta
                return ResponseFormat::response(200, 'Carga rechazada con éxito, saldos revertidos.', $carga);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }


    /**
     * "Elimina" (lógicamente, usando soft deletes) una Carga de Combustible específica
     * SÓLO si su estado es 'rechazada', registrando quién y por qué.
     * Guarda el ID del usuario y su nombre, junto con el motivo.
     */
    public function destroy(Request $request, $id)
    {
        // 1. Validar motivo de eliminación
        $validator = Validator::make($request->all(), [
            'deletion_reason' => 'required|string|max:255',
        ], [
            'deletion_reason.required' => 'El motivo de la eliminación es obligatorio.',
        ]);

        if ($validator->fails()) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
        }

        // Verificar usuario autenticado (seguridad extra)
        if (!Auth::check()) {
            return ResponseFormat::response(401, 'Usuario no autenticado. Inicie sesión para realizar esta acción.', null);
        }

        try {
            DB::beginTransaction();

            $carga = CargaCombustible::withTrashed()->findOrFail($id);

            // Verificaciones antes de eliminar
            if ($carga->trashed()) {
                DB::rollBack();
                return ResponseFormat::response(400, 'Esta carga de combustible ya ha sido eliminada previamente.', null);
            }

            if ($carga->estado !== 'rechazada') {
                DB::rollBack();
                return ResponseFormat::response(400, 'Solo se pueden eliminar cargas de combustible que se encuentren en estado "rechazada". Esta carga tiene el estado: "' . $carga->estado . '".', null);
            }

            // Guardar motivo de eliminación antes del delete (si el campo existe)
            $carga->deletion_reason = $request->input('deletion_reason');
            $carga->save(); // 👈 Esto es clave
            // Soft delete
            $carga->delete();

            DB::commit();

            return ResponseFormat::response(200, 'Carga de Combustible rechazada eliminada lógicamente con éxito.', null);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
