<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerTiposRequerimientosOutDto;
use App\Core\Admin\Application\UseCases\ObtenerTiposRequerimientosUseCase;
use App\Core\Shared\Infrastructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerTiposRequerimientosInAdapter
 *
 * REST API entry point (InAdapter) for retrieving Tipos de Requerimientos.
 */
final class ObtenerTiposRequerimientosInAdapter
{
    private ObtenerTiposRequerimientosUseCase $obtenerTiposRequerimientosUseCase;

    public function __construct()
    {
        try {
            $this->obtenerTiposRequerimientosUseCase = app()->make(ObtenerTiposRequerimientosUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Handle the incoming request
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        $respuesta = new Respuesta;

        try {
            // Execute use case - returns raw array
            $rawData = $this->obtenerTiposRequerimientosUseCase->ejecutar();

            // Convert to OutDTO for type safety
            $obtenerTiposRequerimientosOutDto = ObtenerTiposRequerimientosOutDto::fromArray($rawData);

            // Set response properties
            $respuesta->setSuccess(true);
            $respuesta->setMessage('Se obtuvieron los tipos de requerimientos correctamente.');
            $respuesta->setData($obtenerTiposRequerimientosOutDto);

            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error mientras se intentaba obtener los tipos de requerimientos.');

            return $respuesta->errorResponse($ex);
        }
    }
}
