<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

/**
 * ITipoPermisoOutPort
 *
 * Puerto de salida para persistencia y recuperación de tipos de permiso.
 * Define el contrato que debe implementar el adaptador de infraestructura.
 */
interface ITipoPermisoOutPort
{
    /**
     * Obtiene todos los tipos de permiso disponibles
     *
     * @return array Array de datos de tipos de permiso
     */
    public function obtenerTodos(): array;

    /**
     * Obtiene un tipo de permiso por su ID
     *
     * @param  int  $id  Identificador del tipo de permiso
     * @return array|null Datos del tipo de permiso o null si no existe
     */
    public function obtenerPorId(int $id): ?array;
}
