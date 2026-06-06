<?php

declare(strict_types=1);

namespace App\Core\Admin\Domain\Entities;

/**
 * TipoPermiso Entity
 *
 * Representa un tipo de permiso en el sistema de gestión de accesos
 * a bases de datos.
 */
final class TipoPermiso
{
    private int $id;

    private string $nombre;

    private bool $activo;

    private ?string $descripcion;

    /**
     * Constructor de TipoPermiso
     *
     * @param  int  $id  Identificador único del tipo de permiso
     * @param  string  $nombre  Nombre del tipo de permiso
     * @param  bool  $activo  Indica si el tipo de permiso está activo
     * @param  string|null  $descripcion  Descripción opcional del tipo de permiso
     *
     * @throws \InvalidArgumentException Si el nombre está vacío
     */
    public function __construct(
        int $id,
        string $nombre,
        bool $activo,
        ?string $descripcion = null
    ) {
        $this->validarNombre($nombre);

        $this->id = $id;
        $this->nombre = trim($nombre);
        $this->activo = $activo;
        $this->descripcion = $descripcion !== null ? trim($descripcion) : null;
    }

    /**
     * Valida que el nombre no esté vacío
     *
     * @throws \InvalidArgumentException
     */
    private function validarNombre(string $nombre): void
    {
        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre del tipo de permiso no puede estar vacío');
        }
    }

    /**
     * Obtiene el ID del tipo de permiso
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtiene el nombre del tipo de permiso
     */
    public function getNombre(): string
    {
        return $this->nombre;
    }

    /**
     * Verifica si el tipo de permiso está activo
     */
    public function isActivo(): bool
    {
        return $this->activo;
    }

    /**
     * Obtiene la descripción del tipo de permiso
     */
    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    /**
     * Activa el tipo de permiso
     */
    public function activar(): void
    {
        $this->activo = true;
    }

    /**
     * Desactiva el tipo de permiso
     */
    public function desactivar(): void
    {
        $this->activo = false;
    }
}
