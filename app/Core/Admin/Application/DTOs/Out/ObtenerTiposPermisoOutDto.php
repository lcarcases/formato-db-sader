<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * ObtenerTiposPermisoOutDto
 *
 * DTO para transferir la colección de tipos de permiso
 * desde la capa de aplicación hacia la infraestructura.
 */
final readonly class ObtenerTiposPermisoOutDto
{
    /**
     * Constructor de ObtenerTiposPermisoOutDto
     *
     * @param  TipoPermisoOutDto[]  $tiposPermiso  Colección de tipos de permiso
     */
    public function __construct(
        public array $tiposPermiso
    ) {}
}
