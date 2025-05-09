<?php
namespace App\Utils;

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
    public static function response($code, $message, $data = [], $meta = null)
    {
        // Determinar si la respuesta es exitosa basándose en el código HTTP
        $success = ($code >= 200 && $code < 300);
        
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $code);
    }

    /**
     * Retorna una respuesta de excepción formateada
     *
     * @param \Exception $e Excepción capturada
     * @return \Illuminate\Http\JsonResponse
     */
    public static function exceptionResponse(\Exception $e)
    {
        return self::response(
            $e->getCode() ?? 500,
            $e->getMessage(),
            null,
            ['line' => $e->getLine()]
        );
    }

    public static function validatorErrorMessage($validator)
    {
        $errors = $validator->errors()->all();
        return "Datos no válidos. " . implode(", ", $errors);
    }
}