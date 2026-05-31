<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Http;

use App\Core\Admin\Application\UseCases\ObtenerTiposRequerimientosUseCase;
use App\Core\Shared\Infraestructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerTiposRequerimientosInAdapter
 *
 * REST API entry point for retrieving requirement types.
 * This adapter translates HTTP requests into use case invocations.
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
     * Obtener todos los tipos de requerimientos
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        try {
            $respuesta = new Respuesta;

            $obtenerTiposRequerimientosOutDto = $this->obtenerTiposRequerimientosUseCase->execute();

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
