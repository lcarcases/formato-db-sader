<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\DTOs\Out;

/**
 * ObtenerTipoRequerimientoOutDto
 *
 * DTO para representar un tipo de requerimiento individual en la respuesta.
 */
final readonly class ObtenerTipoRequerimientoOutDto
{
    /**
     * @param  int  $id  Identificador único del tipo de requerimiento
     * @param  string  $nombre  Nombre del tipo de requerimiento
     * @param  bool  $activo  Indica si el tipo de requerimiento está activo
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public bool $activo
    ) {}

    /**
     * Convierte el DTO a un array asociativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'activo' => $this->activo,
        ];
    }

    public static function fromStdClass(\stdClass $data): self
    {
        return new self(
            id: $data->id_nu_requerimiento,
            nombre: $data->sn_requerimiento,
            activo: true // Asumimos que solo se devuelven activos
        );
    }
}
