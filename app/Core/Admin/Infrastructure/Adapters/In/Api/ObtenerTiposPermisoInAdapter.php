<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\DTOs\Out\ObtenerTiposPermisoOutDto;
use App\Core\Admin\Application\DTOs\Out\TipoPermisoOutDto;
use App\Core\Admin\Application\UseCases\ObtenerTiposPermisoUseCase;
use App\Core\Shared\Infraestructure\Respuesta;
use Illuminate\Http\JsonResponse;

/**
 * ObtenerTiposPermisoInAdapter
 *
 * Adaptador de entrada (Controller) para el endpoint de obtención
 * del catálogo de tipos de permiso.
 */
final class ObtenerTiposPermisoInAdapter
{
    private ObtenerTiposPermisoUseCase $obtenerTiposPermisoUseCase;

    /**
     * Constructor del adaptador
     * Resuelve dependencias usando el contenedor de servicios de Laravel
     */
    public function __construct()
    {
        try {
            $this->obtenerTiposPermisoUseCase = app()->make(ObtenerTiposPermisoUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Maneja la petición HTTP para obtener todos los tipos de permiso
     *
     * @return JsonResponse
     */
    public function __invoke()
    {
        $respuesta = new Respuesta;

        try {
            // Ejecutar caso de uso
            $tiposPermisoData = $this->obtenerTiposPermisoUseCase->ejecutar();

            // Mapear datos a DTOs
            $tiposPermisoDto = array_map(
                fn ($tipo) => new TipoPermisoOutDto(
                    id: $tipo['id'],
                    nombre: $tipo['nombre'],
                    activo: $tipo['activo'],
                    descripcion: $tipo['descripcion'] ?? null
                ),
                $tiposPermisoData
            );

            // Crear DTO de respuesta
            $obtenerTiposPermisoOutDto = new ObtenerTiposPermisoOutDto(
                tiposPermiso: $tiposPermisoDto
            );

            $respuesta->setSuccess(true);
            $respuesta->setMessage('Se obtuvieron los tipos de permiso correctamente.');
            $respuesta->setData($obtenerTiposPermisoOutDto);

            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage('Error mientras se intentaba obtener los tipos de permiso.');

            return $respuesta->errorResponse($ex);
        }
    }
}
