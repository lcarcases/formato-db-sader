<?php

declare(strict_types=1);

namespace App\Core\Admin\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object representing a Base de Datos
 *
 * Immutable domain concept representing a database catalog entry.
 * Identity is defined by its attributes (not by reference).
 *
 * Domain invariants:
 * - ID must be a positive integer (> 0)
 * - Nombre must not be empty after trimming
 */
final readonly class BaseDatosVO
{
    /**
     * Create a new Base de Datos Value Object
     *
     * @param  int  $id  Identificador único de la base de datos (must be > 0)
     * @param  string  $nombre  Nombre/código de la base de datos (non-empty)
     *
     * @throws InvalidArgumentException If domain invariants are violated
     */
    public function __construct(
        public int $id,
        public string $nombre,
    ) {
        $this->validateId($id);
        $this->validateNombre($nombre);
    }

    /**
     * Create BaseDatos from array (useful for mapping from database/config)
     *
     * @param  array<string, mixed>  $data  Array with 'id' and 'nombre' keys
     *
     * @throws InvalidArgumentException If required keys missing or invalid
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id'])) {
            throw new InvalidArgumentException('El array debe contener la clave "id"');
        }

        if (! isset($data['nombre'])) {
            throw new InvalidArgumentException('El array debe contener la clave "nombre"');
        }

        if (! is_int($data['id']) && ! is_numeric($data['id'])) {
            throw new InvalidArgumentException('La clave "id" debe ser un entero');
        }

        if (! is_string($data['nombre'])) {
            throw new InvalidArgumentException('La clave "nombre" debe ser una cadena');
        }

        return new self(
            id: (int) $data['id'],
            nombre: trim($data['nombre']),
        );
    }

    /**
     * Serialize to array (for JSON responses)
     *
     * @return array{id: int, nombre: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }

    /**
     * Validate ID is positive integer
     *
     * @throws InvalidArgumentException
     */
    private function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('El ID debe ser mayor a 0');
        }
    }

    /**
     * Validate nombre is not empty
     *
     * @throws InvalidArgumentException
     */
    private function validateNombre(string $nombre): void
    {
        if (trim($nombre) === '') {
            throw new InvalidArgumentException('El nombre no puede estar vacío');
        }
    }
}
