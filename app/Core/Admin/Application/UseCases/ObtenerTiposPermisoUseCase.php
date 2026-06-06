<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoPermisoOutPort;

/**
 * ObtenerTiposPermisoUseCase
 *
 * Caso de uso para obtener el catálogo completo de tipos de permiso
 * de base de datos disponibles en el sistema.
 */
final class ObtenerTiposPermisoUseCase
{
    private ITipoPermisoOutPort $tipoPermisoOutPort;

    /**
     * Constructor del caso de uso
     *
     * @param  ITipoPermisoOutPort  $tipoPermisoOutPort  Puerto de salida para tipos de permiso
     */
    public function __construct(ITipoPermisoOutPort $tipoPermisoOutPort)
    {
        $this->tipoPermisoOutPort = $tipoPermisoOutPort;
    }

    /**
     * Ejecuta el caso de uso para obtener todos los tipos de permiso
     *
     * @return array Array con los datos de tipos de permiso
     *
     * @throws \Exception Si ocurre un error al obtener los tipos de permiso
     */
    public function ejecutar(): array
    {
        return $this->tipoPermisoOutPort->obtenerTodos();
    }
}
