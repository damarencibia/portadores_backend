<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetiroCombustible;
use App\Models\TarjetaCombustible;
use App\Utils\ResponseFormat;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class RetiroCombustibleController extends Controller
{
    /**
     * Lista todos los Retiros de Combustible con paginación y filtros.
     */
    public function index(Request $request)
    {
        try {
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            $retirosQuery = RetiroCombustible::with([
                'tarjetaCombustible.tipoCombustible',
                'tarjetaCombustible.chofer.vehiculo',
                'registradoPor'
            ]);

            // Filtros opcionales
            if ($request->has('tarjeta_combustible_id')) {
                $retirosQuery->where('tarjeta_combustible_id', $request->input('tarjeta_combustible_id'));
            }
            if ($request->has('vehiculo_id')) {
                $retirosQuery->whereHas('tarjetaCombustible.chofer.vehiculo', function ($query) use ($request) {
                    $query->where('id', $request->input('vehiculo_id'));
                });
            }
            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $retirosQuery->whereBetween('fecha', [$request->input('fecha_inicio'), $request->input('fecha_fin')]);
            }

            $paginated = $itemsPerPage == -1
                ? $retirosQuery->latest()->get()
                : $retirosQuery->latest()->paginate($itemsPerPage, ['*'], 'page', $page);
            
            $items = $itemsPerPage == -1 ? $paginated : $paginated->items();

            $meta = $itemsPerPage == -1 ? null : [
                'total'     => $paginated->total(),
                'perPage'   => $paginated->perPage(),
                'page'      => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ];

            return ResponseFormat::response(200, 'Lista de Retiros de Combustible obtenida con éxito.', $items, $meta);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Crea un nuevo Retiro de Combustible, actualiza el saldo y guarda el historial.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha'                  => 'required|date',
            'hora'                   => 'nullable|date_format:H:i:s',
            'tarjeta_combustible_id' => 'required|exists:tarjeta_combustibles,id',
            'cantidad'               => 'required|numeric|min:0.01',
            'odometro'               => 'required|numeric|min:0',
            'lugar'                  => 'nullable|string|max:255',
            'no_chip'                => 'nullable|string|max:255',
            'registrado_por_id'      => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
        }

        try {
            // Iniciar una transacción para garantizar la consistencia de los datos
            $retiro = DB::transaction(function () use ($request) {
                // Bloquear la fila de la tarjeta para evitar race conditions
                $tarjeta = TarjetaCombustible::with('tipoCombustible')->where('id', $request->input('tarjeta_combustible_id'))->lockForUpdate()->firstOrFail();

                if (!$tarjeta->tipoCombustible || !isset($tarjeta->tipoCombustible->precio)) {
                    throw new Exception('El tipo de combustible asociado a la tarjeta no tiene un precio definido.', 400);
                }

                $cantidadRetiro = (float) $request->input('cantidad');

                if ($tarjeta->cantidad_actual < $cantidadRetiro) {
                    throw new Exception('Cantidad de combustible insuficiente en la tarjeta para este retiro.', 400);
                }

                // Guardar los saldos ANTES de la transacción
                $cantidadAnterior = $tarjeta->cantidad_actual;
                
                // Actualizar el saldo de la tarjeta
                $tarjeta->cantidad_actual -= $cantidadRetiro;
                $tarjeta->save();

                // El saldo DESPUÉS de la transacción
                $cantidadDespuesDelRetiro = $tarjeta->cantidad_actual;

                // Calcular el importe
                $calculatedImporte = round($cantidadRetiro * $tarjeta->tipoCombustible->precio, 2);

                // Crear el registro del retiro con el historial de saldos
                $dataToCreate = array_merge($request->all(), [
                    'importe' => $calculatedImporte,
                    'cantidad_combustible_anterior' => $cantidadAnterior,
                    'cantidad_combustible_al_momento_retiro' => $cantidadDespuesDelRetiro,
                ]);

                return RetiroCombustible::create($dataToCreate);
            });

            return ResponseFormat::response(201, 'Retiro de Combustible creado con éxito y saldo de combustible actualizado.', $retiro);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Tarjeta de Combustible no encontrada.', null);
        } catch (Exception $e) {
            // Si el código de error es el que lanzamos, usamos su mensaje
            $code = method_exists($e, 'getCode') && $e->getCode() === 400 ? 400 : 500;
            if ($code === 400) {
                 return ResponseFormat::response($code, $e->getMessage(), null);
            }
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Muestra un Retiro de Combustible específico.
     */
    public function show($id)
    {
        try {
            $retiro = RetiroCombustible::with(['tarjetaCombustible.tipoCombustible', 'tarjetaCombustible.chofer.vehiculo', 'registradoPor'])->findOrFail($id);
            return ResponseFormat::response(200, 'Retiro de Combustible obtenido con éxito.', $retiro);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Actualiza un Retiro de Combustible y ajusta el saldo de la tarjeta.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fecha'    => 'sometimes|required|date',
            'hora'     => 'nullable|date_format:H:i:s',
            'cantidad' => 'sometimes|required|numeric|min:0.01',
            'odometro' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return ResponseFormat::response(422, ResponseFormat::validatorErrorMessage($validator), $validator->errors());
        }
        
        try {
             $retiroActualizado = DB::transaction(function () use ($request, $id) {
                $retiro = RetiroCombustible::findOrFail($id);
                // Bloquear la tarjeta asociada para evitar inconsistencias
                $tarjeta = TarjetaCombustible::with('tipoCombustible')->where('id', $retiro->tarjeta_combustible_id)->lockForUpdate()->firstOrFail();

                if ($request->has('tarjeta_combustible_id') && $request->input('tarjeta_combustible_id') != $tarjeta->id) {
                    throw new Exception('No se permite cambiar la tarjeta de combustible de un retiro existente.', 400);
                }

                $oldCantidad = (float) $retiro->cantidad;
                $newCantidad = (float) $request->input('cantidad', $oldCantidad);

                // 1. Revertir el efecto del retiro original en la tarjeta
                $tarjeta->cantidad_actual += $oldCantidad;
                
                // 2. Validar si hay saldo suficiente para el NUEVO retiro
                if ($tarjeta->cantidad_actual < $newCantidad) {
                    throw new Exception('Cantidad de combustible insuficiente en la tarjeta para esta actualización.', 400);
                }

                // 3. Aplicar el nuevo retiro
                $tarjeta->cantidad_actual -= $newCantidad;
                $tarjeta->save();

                // 4. Recalcular el importe y actualizar el registro del retiro
                $newImporte = round($newCantidad * $tarjeta->tipoCombustible->precio, 2);
                
                // Los campos de historial no se actualizan, ya que reflejan la transacción original.
                // Si se necesitara un log de auditoría, se implementaría de otra forma.
                $dataToUpdate = $request->except(['cantidad_combustible_anterior', 'cantidad_combustible_al_momento_retiro']);
                $dataToUpdate['importe'] = $newImporte;
                
                $retiro->update($dataToUpdate);

                return $retiro;
            });
            
            return ResponseFormat::response(200, 'Retiro de Combustible actualizado con éxito y saldo ajustado.', $retiroActualizado);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado.', null);
        } catch (Exception $e) {
            $code = method_exists($e, 'getCode') && $e->getCode() === 400 ? 400 : 500;
            if ($code === 400) {
                 return ResponseFormat::response($code, $e->getMessage(), null);
            }
            return ResponseFormat::exceptionResponse($e);
        }
    }

    /**
     * Elimina un Retiro de Combustible y revierte el efecto en el saldo de la tarjeta.
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $retiro = RetiroCombustible::findOrFail($id);
                // Bloquear la tarjeta para asegurar la consistencia del saldo
                $tarjeta = TarjetaCombustible::where('id', $retiro->tarjeta_combustible_id)->lockForUpdate()->firstOrFail();

                // Revertir el efecto del retiro en el saldo de combustible de la tarjeta
                $tarjeta->cantidad_actual += (float) $retiro->cantidad;
                $tarjeta->save();

                // Eliminar el registro del retiro
                $retiro->delete();
            });

            return ResponseFormat::response(200, 'Retiro de Combustible eliminado con éxito y saldo de combustible restaurado.', null);

        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Retiro de Combustible no encontrado.', null);
        } catch (Exception $e) {
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
