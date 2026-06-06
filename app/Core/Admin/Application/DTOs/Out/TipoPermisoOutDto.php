<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * TipoPermisoOutDto
 *
 * DTO para transferir información individual de un tipo de permiso
 * desde la capa de aplicación hacia la infraestructura.
 */
final readonly class TipoPermisoOutDto
{
    /**
     * Constructor de TipoPermisoOutDto
     *
     * @param  int  $id  Identificador del tipo de permiso (id_nu_tipo_permiso)
     * @param  string  $nombre  Nombre del tipo de permiso (ln_nombre)
     * @param  bool  $activo  Estado del tipo de permiso (ind_activo)
     * @param  string|null  $descripcion  Descripción del tipo de permiso (sn_descripcion)
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public bool $activo,
        public ?string $descripcion
    ) {}
}
