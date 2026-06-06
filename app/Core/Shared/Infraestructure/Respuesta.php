<?php

declare(strict_types=1);

namespace App\Core\Shared\Infraestructure;

use Illuminate\Http\JsonResponse;

/**
 * Respuesta
 * 
 * Clase utilitaria para estandarizar las respuestas HTTP JSON
 * en toda la aplicación.
 */
final class Respuesta
{
    private bool $success = false;
    private string $message = '';
    private mixed $data = [];

    /**
     * Establece el estado de éxito de la respuesta
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * Establece el mensaje de la respuesta
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Establece los datos de la respuesta
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * Genera una respuesta JSON exitosa
     */
    public function successResponse(): JsonResponse
    {
        return response()->json([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data
        ], 200);
    }

    /**
     * Genera una respuesta JSON de error
     * 
     * @param \Exception|null $exception Excepción opcional para logging
     */
    public function errorResponse(?\Exception $exception = null): JsonResponse
    {
        $statusCode = $this->success ? 200 : 500;
        
        $response = [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data
        ];

        // En modo debug, incluir detalles de la excepción
        if ($exception && config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return response()->json($response, $statusCode);
    }
}
