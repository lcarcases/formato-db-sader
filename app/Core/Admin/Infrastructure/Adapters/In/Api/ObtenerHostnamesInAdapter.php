<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerHostnameOutDto;
use App\Core\Admin\Application\DTOs\Out\ObtenerHostnamesOutDto;
use App\Core\Admin\Application\UseCases\ObtenerHostnamesUseCase;
use App\Core\Admin\Domain\ValueObjects\HostnameVO;
use App\Core\Shared\Infraestructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerHostnamesInAdapter
 *
 * Adaptador de entrada (Controller) para el endpoint de obtención
 * del catálogo de hostnames.
 */
final class ObtenerHostnamesInAdapter
{
    private ObtenerHostnamesUseCase $obtenerHostnamesUseCase;

    /**
     * Constructor del adaptador
     * Resuelve dependencias usando el contenedor de servicios de Laravel
     */
    public function __construct()
    {
        try {
            $this->obtenerHostnamesUseCase = app()->make(ObtenerHostnamesUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Maneja la petición HTTP para obtener todos los hostnames
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        $respuesta = new Respuesta;

        try {
            // Ejecutar caso de uso
            $hostnames = $this->obtenerHostnamesUseCase->execute();

            // Mapear datos a DTOs
            $obtenerHostnamesOutDto = new ObtenerHostnamesOutDto(
                array_map(
                    fn (HostnameVO $hostname): ObtenerHostnameOutDto => new ObtenerHostnameOutDto(
                        id: $hostname->id,
                        nombre: $hostname->nombre,
                    ),
                    $hostnames
                )
            );

            $respuesta->setSuccess(true);
            $respuesta->setMessage('Se obtuvieron los hostnames correctamente.');
            $respuesta->setData($obtenerHostnamesOutDto->toArray());

            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error mientras se intentaba obtener los hostnames.');

            return $respuesta->errorResponse($ex);
        }
    }
}
