<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetiroCombustible; // Use RetiroCombustible model
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

class RetiroCombustibleController extends Controller
{
    /**
     * Lista todos los Retiros de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            $itemsPerPage      = $request->input("itemsPerPage", 20);
            $page              = $request->input("page", 1);
            $tarjetaId         = $request->input("tarjeta_combustible_id");
            $choferId          = $request->input("chofer_id");
            $tipoCombustibleId = $request->input("tipo_combustible_id");
            $registradorId     = $request->input("registrado_por_id");
            $search            = $request->input("search");
            $withTrashed       = filter_var($request->input("with_trashed", false), FILTER_VALIDATE_BOOLEAN);

            $retirosQuery = RetiroCombustible::query()
                ->when($withTrashed, fn($q) => $q->withTrashed()) // Incluye eliminados si se pide
                ->with([
                    'registradoPor',
                    'validadoPor',
                    'tarjetaCombustible.tipoCombustible',
                    'tarjetaCombustible.chofer'
                ])
                ->when($tarjetaId, fn($q) => $q->where('tarjeta_combustible_id', $tarjetaId))
                ->when($choferId, fn($q) =>
                    $q->whereHas('tarjetaCombustible', fn($q2) => $q2->where('chofer_id', $choferId))
                )
                ->when($tipoCombustibleId, fn($q) =>
                    $q->whereHas('tarjetaCombustible.tipoCombustible', fn($q2) =>
                        $q2->where('id', $tipoCombustibleId)
                    )
                )
                ->when($registradorId, fn($q) =>
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
                            ->orWhereHas('tarjetaCombustible', fn($q2) =>
                                $q2->where('numero', 'like', "%{$search}%")
                            );
                    });
                });

            // Paginación
            if ($itemsPerPage == -1) {
                $collection = $retirosQuery->get();
                $meta = [
                    'total'     => $collection->count(),
                    'perPage'   => $collection->count(),
                    'page'      => 1,
                    'last_page' => 1,
                ];
            } else {
                $paginated = $retirosQuery->paginate($itemsPerPage, ['*'], 'page', $page);
                $collection = $paginated->items();
                $meta = [
                    'total'     => $paginated->total(),
                    'perPage'   => $paginated->perPage(),
                    'page'      => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                ];
            }

            $retiros = collect($collection)->map(function ($retiro) {
                return [
                    'id'                               => $retiro->id,
                    'fecha'                            => $retiro->fecha,
                    'hora'                             => $retiro->hora,
                    'tarjeta_combustible'              => optional($retiro->tarjetaCombustible)->numero,
                    'tipo_combustible'                 => optional($retiro->tarjetaCombustible?->tipoCombustible)->nombre,
                    'chofer'                           => optional($retiro->tarjetaCombustible?->chofer)->nombre,
                    'cantidad'                         => $retiro->cantidad,
                    'importe'                          => $retiro->importe,
                    'odometro'                         => $retiro->odometro,
                    'lugar'                            => $retiro->lugar,
                    'motivo'                           => $retiro->motivo,
                    'no_chip'                          => $retiro->no_chip,
                    'registrado_por'                   => optional($retiro->registradoPor)->name,
                    'validado_por'                     => optional($retiro->validadoPor)->name,
                    'fecha_validacion'                 => $retiro->fecha_validacion,
                    'estado'                           => $retiro->estado,
                    'motivo_rechazo'                   => $retiro->motivo_rechazo,
                    'cantidad_combustible_anterior'    => $retiro->cantidad_combustible_anterior,
                    'cantidad_combustible_al_momento_retiro' => $retiro->cantidad_combustible_al_momento_retiro,
                    'eliminado'                        => $retiro->trashed(),
                    'deleted_at'                       => $retiro->deleted_at,
                    'deletion_reason'                  => $retiro->deletion_reason,
                ];
            });

            return ResponseFormat::response(200, 'Lista de Retiros de Combustible obtenida con éxito.', $retiros, $meta);

        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }


    /**
     * Crea un nuevo Retiro de Combustible.
     * El importe se calcula automáticamente basado en la cantidad y el precio del tipo de combustible de la tarjeta.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha'                    => 'required|date',
                'hora'                     => 'nullable|date_format:H:i:s', // Hora es opcional en Retiro según migración
                'cantidad'                 => 'required|numeric|min:0.01', // Cantidad de combustible a retirar
                'odometro'                 => 'required|numeric|min:0',
                'lugar'                    => 'nullable|string|max:255',
                'motivo'                   => 'required|string|max:255',
                'no_chip'                  => 'nullable|string|max:255',
                'registrado_por_id'        => 'required|exists:users,id',
                'validado_por_id'          => 'nullable|exists:users,id',
                'fecha_validacion'         => 'nullable|date',
                'estado'                   => 'nullable|string|max:50',
                'motivo_rechazo'           => 'nullable|string|max:255',
                'tarjeta_combustible_id'   => 'required|exists:tarjeta_combustibles,id',
            ], [
                'cantidad.min' => 'La cantidad de combustible a retirar debe ser mayor a 0.',
                'fecha.required' => 'La fecha del retiro es obligatoria.',
                'fecha.date' => 'La fecha del retiro debe ser una fecha válida.',
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

            // Validar que haya suficiente cantidad de combustible en la tarjeta
            if ($tarjeta->cantidad_actual < $request->input('cantidad')) {
                DB::rollBack();
                return ResponseFormat::response(400, 'Cantidad de combustible insuficiente en la tarjeta para este retiro.', null);
            }

            // Validar el límite diario de consumo
            // Obtener todos los retiros de hoy para esta tarjeta
            $retirosHoy = RetiroCombustible::where('tarjeta_combustible_id', $tarjeta->id)
                                          ->whereDate('fecha', Carbon::today())
                                          ->sum('cantidad');
            $projectedConsumoDiario = $retirosHoy + $request->input('cantidad');
            if ($tarjeta->limite_consumo_diario !== null && $projectedConsumoDiario > $tarjeta->limite_consumo_diario) {
                DB::rollBack();
                return ResponseFormat::response(400, 'El retiro excede el límite de consumo diario para esta tarjeta.', null);
            }

            // Capturar la cantidad de combustible actual de la tarjeta ANTES del retiro
            $cantidadCombustibleAntes = $tarjeta->cantidad_actual;

            $dataToCreate = $request->except([
                'cantidad_combustible_al_momento_retiro',
                'cantidad_combustible_anterior' // Asegurarse de excluir estos campos de la solicitud
            ]);
            $dataToCreate['importe'] = $calculatedImporte;

            $retiro = RetiroCombustible::create($dataToCreate);

            // Asignar la cantidad de combustible ANTERIOR capturada
            $retiro->cantidad_combustible_anterior = $cantidadCombustibleAntes;

            // Actualizar la tarjeta con los nuevos saldos
            $tarjeta->cantidad_actual -= $request->input('cantidad');
            $tarjeta->saldo_monetario_actual -= $calculatedImporte; // Descontar el importe monetario
            $tarjeta->consumo_cantidad_mensual_acumulado += $request->input('cantidad'); // Acumular para el límite mensual
            $tarjeta->save();

            // Asignar los saldos FINALES de la tarjeta (después del retiro)
            $retiro->cantidad_combustible_al_momento_retiro = $tarjeta->cantidad_actual;
            $retiro->save(); // Guardar el retiro con todos los saldos actualizados

            DB::commit();
            return ResponseFormat::response(201, 'Retiro de Combustible registrado con éxito y saldos de tarjeta actualizados.', $retiro);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }


    /**
     * Muestra un Retiro de Combustible específico.
     */
    public function show(Request $request, $id)
    {
        try {
            $tarjetaId         = $request->input("tarjeta_combustible_id");
            $choferId          = $request->input("chofer_id");
            $tipoCombustibleId = $request->input("tipo_combustible_id");
            $registradorId     = $request->input("registrado_por_id");

            $query = RetiroCombustible::withTrashed() // Esto permite incluir eliminados
                ->with([
                    'registradoPor',
                    'validadoPor',
                    'tarjetaCombustible.tipoCombustible',
                    'tarjetaCombustible.chofer'
                ])
                ->where('id', $id)
                ->when($tarjetaId, fn($q) => $q->where('tarjeta_combustible_id', $tarjetaId))
                ->when($choferId, fn($q) =>
                    $q->whereHas('tarjetaCombustible', fn($q2) => $q2->where('chofer_id', $choferId))
                )
                ->when($tipoCombustibleId, fn($q) =>
                    $q->whereHas('tarjetaCombustible.tipoCombustible', fn($q2) =>
                        $q2->where('id', $tipoCombustibleId)
                    )
                )
                ->when($registradorId, fn($q) =>
                    $q->where('registrado_por_id', $registradorId)
                );

            $retiro = $query->firstOrFail();

            $responseData = [
                'id'                               => $retiro->id,
                'fecha'                            => $retiro->fecha,
                'hora'                             => $retiro->hora,
                'tarjeta_combustible'              => optional($retiro->tarjetaCombustible)->numero,
                'tipo_combustible'                 => optional($retiro->tarjetaCombustible?->tipoCombustible)->nombre,
                'precio_combustible'               => optional($retiro->tarjetaCombustible?->tipoCombustible)->precio,
                'chofer'                           => optional($retiro->tarjetaCombustible?->chofer)->nombre,
                'cantidad'                         => $retiro->cantidad,
                'importe'                          => $retiro->importe,
                'odometro'                         => $retiro->odometro,
                'lugar'                            => $retiro->lugar,
                'motivo'                           => $retiro->motivo,
                'no_chip'                          => $retiro->no_chip,
                'registrado_por'                   => optional($retiro->registradoPor)->name,
                'validado_por'                     => optional($retiro->validadoPor)->name,
                'fecha_validacion'                 => $retiro->fecha_validacion,
                'estado'                           => $retiro->estado,
                'motivo_rechazo'                   => $retiro->motivo_rechazo,
                'cantidad_combustible_anterior'    => $retiro->cantidad_combustible_anterior,
                'cantidad_combustible_al_momento_retiro' => $retiro->cantidad_combustible_al_momento_retiro,
                'eliminado'                        => $retiro->trashed(),
                'deleted_at'                       => $retiro->deleted_at,
                'deletion_reason'                  => $retiro->deletion_reason,
            ];

            return ResponseFormat::response(200, 'Retiro de Combustible obtenido con éxito.', $responseData);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado con los filtros aplicados.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }



    /**
     * Actualiza un Retiro de Combustible específico y ajusta los saldos de la tarjeta.
     * El importe se recalcula automáticamente si la cantidad cambia.
     */
    public function update(Request $request, $id)
    {
        try {
            $retiro = RetiroCombustible::findOrFail($id);
            $tarjeta = $retiro->tarjetaCombustible;

            $oldCantidad = $retiro->cantidad;
            $oldImporte = $retiro->importe;

            $validator = Validator::make($request->all(), [
                'fecha'                    => 'sometimes|required|date',
                'hora'                     => 'sometimes|nullable|date_format:H:i:s',
                'cantidad'                 => 'sometimes|required|numeric|min:0.01',
                'odometro'                 => 'sometimes|required|numeric|min:0',
                'lugar'                    => 'sometimes|string|max:255',
                'no_chip'                  => 'sometimes|string|max:255',
                'registrado_por_id'        => 'sometimes|required|exists:users,id',
                'validado_por_id'          => 'sometimes|nullable|exists:users,id',
                'fecha_validacion'         => 'sometimes|date',
                'estado'                   => 'sometimes|string|max:50',
                'motivo_rechazo'           => 'sometimes|nullable|string|max:50',
                'tarjeta_combustible_id'   => 'sometimes|required|exists:tarjeta_combustibles,id',
            ]);

            if ($validator->fails()) {
                return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
            }

            DB::beginTransaction();

            if ($request->has('tarjeta_combustible_id') && $request->input('tarjeta_combustible_id') != $tarjeta->id) {
                DB::rollBack();
                return ResponseFormat::response(400, 'No se permite cambiar la tarjeta de combustible de un retiro existente directamente. Elimine y cree un nuevo retiro.', null);
            }

            $tarjeta->load('tipoCombustible');
            if (!$tarjeta->tipoCombustible || !isset($tarjeta->tipoCombustible->precio)) {
                DB::rollBack();
                return ResponseFormat::response(400, 'El tipo de combustible asociado a la tarjeta no tiene un precio definido.', null);
            }

            $newCantidad = $request->input('cantidad', $oldCantidad);
            $newImporte = round($newCantidad * $tarjeta->tipoCombustible->precio, 2);

            // Revertir los efectos del retiro antiguo en la tarjeta antes de aplicar los nuevos
            $tarjeta->cantidad_actual += $oldCantidad;
            $tarjeta->saldo_monetario_actual += $oldImporte;
            $tarjeta->consumo_cantidad_mensual_acumulado -= $oldCantidad;

            // En este punto, los saldos de la tarjeta reflejan el estado justo ANTES del retiro original.
            if ($retiro->cantidad_combustible_anterior === null) {
                 $retiro->cantidad_combustible_anterior = $tarjeta->cantidad_actual;
            }

            // Aplicar los efectos del nuevo retiro (o los valores actualizados)
            $projectedCantidadActual = $tarjeta->cantidad_actual - $newCantidad;
            $projectedSaldoMonetarioActual = $tarjeta->saldo_monetario_actual - $newImporte;
            $projectedConsumoMensual = $tarjeta->consumo_cantidad_mensual_acumulado + $newCantidad; // SUMA para el consumo mensual

            if ($projectedCantidadActual < 0) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La actualización excede la cantidad de combustible disponible en la tarjeta.', null);
            }

            if ($projectedSaldoMonetarioActual < 0) {
                 DB::rollBack();
                 return ResponseFormat::response(400, 'La actualización excede el saldo monetario disponible en la tarjeta.', null);
            }

            // Validar el límite diario de consumo para la actualización
            $retirosHoyExceptCurrent = RetiroCombustible::where('tarjeta_combustible_id', $tarjeta->id)
                                                        ->whereDate('fecha', Carbon::today())
                                                        ->where('id', '!=', $retiro->id) // Excluir el retiro actual
                                                        ->sum('cantidad');
            $projectedConsumoDiario = $retirosHoyExceptCurrent + $newCantidad;
            if ($tarjeta->limite_consumo_diario !== null && $projectedConsumoDiario > $tarjeta->limite_consumo_diario) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La actualización excede el límite de consumo diario para esta tarjeta.', null);
            }

            if ($tarjeta->limite_consumo_mensual !== null && $projectedConsumoMensual > $tarjeta->limite_consumo_mensual) {
                DB::rollBack();
                return ResponseFormat::response(400, 'La actualización excede el límite de consumo mensual acumulado para esta tarjeta.', null);
            }


            // Excluir los campos de solo lectura de la solicitud antes de actualizar
            $dataToUpdate = $request->except([
                'cantidad_combustible_al_momento_retiro',
                'cantidad_combustible_anterior'
            ]);
            $dataToUpdate['importe'] = $newImporte;

            $retiro->update($dataToUpdate); // Se actualiza el retiro con los datos de la solicitud

            // Actualizar los saldos de la tarjeta con los valores proyectados
            $tarjeta->cantidad_actual = $projectedCantidadActual;
            $tarjeta->saldo_monetario_actual = $projectedSaldoMonetarioActual;
            $tarjeta->consumo_cantidad_mensual_acumulado = $projectedConsumoMensual;
            $tarjeta->save();

            // Guardar los saldos FINALES de la tarjeta en el registro de retiro actualizado
            $retiro->cantidad_combustible_al_momento_retiro = $tarjeta->cantidad_actual;
            $retiro->save(); // Guardar el retiro con los saldos actualizados

            DB::commit();
            return ResponseFormat::response(200, 'Retiro de Combustible actualizado con éxito y saldos de tarjeta ajustados.', $retiro);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Valida o rechaza un Retiro de Combustible.
     * Si 'valid' es true, marca el retiro como 'validado'.
     * Si 'valid' es false, revierte los saldos, marca el retiro como 'rechazado' y requiere un motivo.
     */
    public function validar(Request $request, $id)
    {
        // --- 1. VALIDACIÓN ---
        $validator = Validator::make($request->all(), [
            'valid'           => 'required|boolean',
            'validado_por_id' => 'required|exists:users,id', // Se requiere siempre para saber quién hace la acción
            'motivo_rechazo'  => 'required_if:valid,false|string|max:255', // Obligatorio si se rechaza
        ], [
            'validado_por_id.required' => 'El usuario que realiza la validación/rechazo es obligatorio.',
            'motivo_rechazo.required_if' => 'El motivo del rechazo es obligatorio cuando se rechaza un retiro.',
        ]);

        if ($validator->fails()) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
        }

        try {
            DB::beginTransaction();

            $retiro  = RetiroCombustible::findOrFail($id);
            $tarjeta = $retiro->tarjetaCombustible;

            // --- 2. PROTECCIÓN CONTRA DOBLE ACCIÓN ---
            // Evitar que un retiro ya procesado se vuelva a procesar.
            if (in_array($retiro->estado, ['validada', 'rechazada'])) {
                DB::rollBack();
                return ResponseFormat::response(400, 'Este retiro ya ha sido ' . $retiro->estado . ' y no puede ser modificado.', null);
            }

            $isValid = $request->input('valid');

            if ($isValid) {
                // --- LÓGICA DE VALIDACIÓN ---
                $retiro->estado           = 'validada';
                $retiro->validado_por_id  = $request->input('validado_por_id');
                $retiro->fecha_validacion = Carbon::now();
                $retiro->motivo_rechazo   = null; // Limpiar motivo de rechazo si se valida
                $retiro->save();

                DB::commit();
                return ResponseFormat::response(200, 'Retiro validado con éxito.', $retiro);
            } else {
                // --- 3. LÓGICA DE RECHAZO ---

                // a. Revertimos los saldos en la tarjeta
                $tarjeta->cantidad_actual            += $retiro->cantidad;
                $tarjeta->saldo_monetario_actual     += $retiro->importe;
                $tarjeta->consumo_cantidad_mensual_acumulado -= $retiro->cantidad; // Revertir acumulación mensual
                $tarjeta->save();

                // b. Actualizamos el estado del retiro a 'rechazada'
                $retiro->estado           = 'rechazada';
                $retiro->motivo_rechazo   = $request->input('motivo_rechazo');
                $retiro->validado_por_id  = $request->input('validado_por_id'); // Quién lo rechazó
                $retiro->fecha_validacion = Carbon::now()->toDateString(); // Cuándo se rechazó
                $retiro->save(); // Guardamos el retiro con el nuevo estado

                DB::commit();
                // Devolvemos el retiro actualizado en la respuesta
                return ResponseFormat::response(200, 'Retiro rechazado con éxito, saldos revertidos.', $retiro);
            }
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * "Elimina" (lógicamente, usando soft deletes) un Retiro de Combustible específico
     * SÓLO si su estado es 'rechazada', registrando quién y por qué.
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

            $retiro = RetiroCombustible::withTrashed()->findOrFail($id);

            // Verificaciones antes de eliminar
            if ($retiro->trashed()) {
                DB::rollBack();
                return ResponseFormat::response(400, 'Este retiro de combustible ya ha sido eliminado previamente.', null);
            }

            if ($retiro->estado !== 'rechazada') {
                DB::rollBack();
                return ResponseFormat::response(400, 'Solo se pueden eliminar retiros de combustible que se encuentren en estado "rechazada". Este retiro tiene el estado: "' . $retiro->estado . '".', null);
            }

            // Guardar motivo de eliminación antes del delete
            $retiro->deletion_reason = $request->input('deletion_reason');
            $retiro->save(); // Esto es clave para guardar el motivo antes del soft delete

            // Soft delete
            $retiro->delete();

            DB::commit();

            return ResponseFormat::response(200, 'Retiro de Combustible rechazado eliminado lógicamente con éxito.', null);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }
}