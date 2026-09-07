<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerEsquemaOutDto;
use App\Core\Admin\Application\DTOs\Out\ObtenerEsquemasOutDto;
use App\Core\Admin\Application\UseCases\ObtenerEsquemasUseCase;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use App\Core\Shared\Infraestructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerEsquemasInAdapter
 *
 * Adaptador de entrada (Controller) para el endpoint de obtención
 * del catálogo completo de esquemas.
 */
final class ObtenerEsquemasInAdapter
{
    private ObtenerEsquemasUseCase $obtenerEsquemasUseCase;

    /**
     * Constructor del adaptador
     * Resuelve dependencias usando el contenedor de servicios de Laravel
     */
    public function __construct()
    {
        try {
            $this->obtenerEsquemasUseCase = app()->make(ObtenerEsquemasUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Maneja la petición HTTP para obtener todos los esquemas
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        $respuesta = new Respuesta;

        try {
            // Ejecutar caso de uso
            $esquemas = $this->obtenerEsquemasUseCase->execute();

            // Mapear datos a DTOs
            $obtenerEsquemasOutDto = new ObtenerEsquemasOutDto(
                array_map(
                    fn (EsquemaVO $esquema): ObtenerEsquemaOutDto => new ObtenerEsquemaOutDto(
                        id: $esquema->id,
                        nombre: $esquema->nombre,
                    ),
                    $esquemas
                )
            );

            $respuesta->setSuccess(true);
            $respuesta->setMessage('Se obtuvieron los esquemas correctamente.');
            $respuesta->setData($obtenerEsquemasOutDto->toArray());

            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error mientras se intentaba obtener los esquemas.');

            return $respuesta->errorResponse($ex);
        }
    }
}
