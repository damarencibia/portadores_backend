<?php

namespace App\Utils;

use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Validation\Validator; // Importar Validator para el tipo de hint

class ResponseFormat
{
    /**
     * Retorna una respuesta JSON formateada
     *
     * @param int $code Código HTTP de la respuesta
     * @param string|null $message Mensaje descriptivo
     * @param mixed $data Datos a incluir en la respuesta
     * @param mixed $meta Metadatos adicionales
     * @return \Illuminate\Http\JsonResponse
     */
    public static function response(int $code, ?string $message, $data = [], $meta = null): JsonResponse
    {
        // Asegurarse de que el código sea un entero válido para HTTP
        $statusCode = (int) $code;
        if ($statusCode < 100 || $statusCode > 599) {
             // Si el código no es un rango HTTP válido, usar 500 como fallback
             $statusCode = 500;
        }

        // Determinar si la respuesta es exitosa basándose en el código HTTP
        $success = ($statusCode >= 200 && $statusCode < 300);

        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $statusCode); // Usar el código de estado validado
    }

    /**
     * Retorna una respuesta de excepción formateada
     *
     * @param \Exception $e Excepción capturada
     * @return \Illuminate\Http\JsonResponse
     */
    public static function exceptionResponse(\Exception $e): JsonResponse
    {
        // Obtener el código de la excepción y asegurar que sea un entero
        $exceptionCode = (int) $e->getCode();

        // Usar el código de la excepción si es un código HTTP válido, de lo contrario usar 500
        $statusCode = ($exceptionCode >= 100 && $exceptionCode < 600) ? $exceptionCode : 500;

        return self::response(
            $statusCode, // Usar el código de estado determinado
            $e->getMessage(),
            null, // Normalmente no hay datos en una respuesta de error
            [
                'line' => $e->getLine(),
                'file' => $e->getFile(), // Añadir el archivo para mejor depuración
                'code' => $e->getCode() // Opcional: incluir el código original de la excepción para depuración
            ]
        );
    }

    /**
     * Formatea un mensaje de error a partir de un objeto Validator.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return string
     */
    public static function validatorErrorMessage(Validator $validator): string
    {
        $errors = $validator->errors()->all();
        return "Datos no válidos. " . implode(", ", $errors);
    }
}
