<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerEsquemaOutDto;
use App\Core\Admin\Application\DTOs\Out\ObtenerEsquemasPorHostnameOutDto;
use App\Core\Admin\Application\UseCases\ObtenerEsquemasPorHostnameUseCase;
use App\Core\Admin\Domain\Exceptions\HostnameNotFoundException;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use App\Core\Shared\Infraestructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerEsquemasPorHostnameInAdapter
 *
 * Adaptador de entrada (Controller) para el endpoint de obtención de los
 * esquemas asociados a un hostname (incluyendo la opción sintética "Todos").
 */
final class ObtenerEsquemasPorHostnameInAdapter
{
    private ObtenerEsquemasPorHostnameUseCase $obtenerEsquemasPorHostnameUseCase;

    /**
     * Constructor del adaptador
     * Resuelve dependencias usando el contenedor de servicios de Laravel
     */
    public function __construct()
    {
        try {
            $this->obtenerEsquemasPorHostnameUseCase = app()->make(ObtenerEsquemasPorHostnameUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Maneja la petición HTTP para obtener los esquemas de un hostname
     *
     * @return JsonResponse
     */
    public function __invoke(int $idHostname)
    {
        $respuesta = new Respuesta;

        try {
            // Ejecutar caso de uso
            $esquemas = $this->obtenerEsquemasPorHostnameUseCase->execute($idHostname);

            // Mapear datos a DTOs
            $obtenerEsquemasPorHostnameOutDto = new ObtenerEsquemasPorHostnameOutDto(
                array_map(
                    fn (EsquemaVO $esquema): ObtenerEsquemaOutDto => new ObtenerEsquemaOutDto(
                        id: $esquema->id,
                        nombre: $esquema->nombre,
                    ),
                    $esquemas
                )
            );

            $respuesta->setSuccess(true);
            $respuesta->setMessage('Se obtuvieron los esquemas del hostname correctamente.');
            $respuesta->setData($obtenerEsquemasPorHostnameOutDto->toArray());

            return $respuesta->successResponse();

        } catch (HostnameNotFoundException $ex) {
            // Respuesta::errorResponse() siempre responde 500; el 404 se construye
            // manualmente con el mismo shape {success, message, data}, sin modificar
            // la clase compartida Respuesta.
            return response()->json([
                'success' => false,
                'message' => 'El hostname solicitado no existe.',
                'data' => [],
            ], 404);
        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error mientras se intentaba obtener los esquemas del hostname.');

            return $respuesta->errorResponse($ex);
        }
    }
}
