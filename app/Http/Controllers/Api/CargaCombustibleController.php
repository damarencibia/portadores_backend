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

class CargaCombustibleController extends Controller
{
    /**
     * Lista todas las Cargas de Combustible con paginación.
     */
    public function index(Request $request)
    {
        try {
            $itemsPerPage = $request->input("itemsPerPage", 20);
            $page = $request->input("page", 1);

            // Cargar las relaciones necesarias. tipoCombustible se obtiene a través de tarjetaCombustible
            $cargasQuery = CargaCombustible::with(['registradoPor', 'validadoPor', 'tarjetaCombustible.tipoCombustible']);

            $paginated = $itemsPerPage == -1
                ? $cargasQuery->get()
                : $cargasQuery->paginate($itemsPerPage, ['*'], 'page', $page);

            $meta = [
                'total'     => $itemsPerPage != -1 ? $paginated->total() : count($paginated),
                'perPage'   => $itemsPerPage != -1 ? $paginated->perPage() : count($paginated),
                'page'      => $itemsPerPage != -1 ? $paginated->currentPage() : 1,
                'last_page' => $itemsPerPage != -1 ? $paginated->lastPage() : 1,
            ];

            $cargas = $itemsPerPage != -1 ? $paginated->items() : $paginated;

            return ResponseFormat::response(200, 'Lista de Cargas de Combustible obtenida con éxito.', $cargas, $meta);

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
                'fecha'                  => 'required|date',
                'hora'                   => 'nullable|date_format:H:i:s',
                'cantidad'               => 'required|numeric|min:0.01', // Cantidad de combustible a agregar
                'odometro'               => 'required|numeric|min:0',
                'lugar'                  => 'nullable|string|max:255',
                'motivo'                 => 'required|string|max:255',
                'no_chip'                => 'nullable|string|max:255',
                'registrado_por_id'      => 'required|exists:users,id',
                'validado_por_id'        => 'nullable|exists:users,id',
                'fecha_validacion'       => 'nullable|date',
                'estado'                 => 'nullable|string|max:50',
                'tarjeta_combustible_id' => 'required|exists:tarjeta_combustibles,id',
            ], [
                'cantidad.min' => 'La cantidad de combustible a cargar debe ser mayor a 0.',
                'fecha.required' => 'La fecha de la carga es obligatoria.',
                'fecha.date' => 'La fecha de la carga debe ser una fecha válida.',
                'hora.date_format' => 'La hora debe tener el formato HH:MM:SS.',
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
                'saldo_monetario_anterior', // Asegurarse de excluir estos campos de la solicitud
                'cantidad_combustible_anterior'
            ]);
            $dataToCreate['importe'] = $calculatedImporte;

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
    public function show($id)
    {
        try {
            $carga = CargaCombustible::with(['registradoPor', 'validadoPor', 'tarjetaCombustible.tipoCombustible'])->findOrFail($id);
            return ResponseFormat::response(200, 'Carga de Combustible obtenida con éxito.', $carga);
        } catch (ModelNotFoundException $e) {
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
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
                'hora'                   => 'nullable|date_format:H:i:s',
                'cantidad'               => 'sometimes|required|numeric|min:0.01',
                'odometro'               => 'sometimes|required|numeric|min:0',
                'lugar'                  => 'nullable|string|max:255',
                'no_chip'                => 'nullable|string|max:255',
                'registrado_por_id'      => 'sometimes|required|exists:users,id',
                'validado_por_id'        => 'nullable|exists:users,id',
                'fecha_validacion'       => 'nullable|date',
                'estado'                 => 'nullable|string|max:50',
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
     * Elimina una Carga de Combustible específica y ajusta los saldos de la tarjeta,
     * revirtiendo el efecto de la carga.
     */
    public function destroy($id)
    {
        try {
            $carga = CargaCombustible::findOrFail($id);
            $tarjeta = $carga->tarjetaCombustible;

            DB::beginTransaction();

            // Revertir el efecto de la carga en los saldos de la tarjeta
            $tarjeta->saldo_monetario_actual += $carga->importe;
            $tarjeta->cantidad_actual -= $carga->cantidad;
            $tarjeta->consumo_cantidad_mensual_acumulado -= $carga->cantidad;
            $tarjeta->save();

            $carga->delete();

            DB::commit();
            return ResponseFormat::response(200, 'Carga de Combustible eliminada con éxito y saldos de tarjeta ajustados.', null);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return ResponseFormat::response(404, 'Carga de Combustible no encontrada.', null);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseFormat::exceptionResponse($e);
        }
    }
}
