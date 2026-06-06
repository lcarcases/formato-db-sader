<?php

declare(strict_types=1);

namespace App\Core\Admin\Domain\Exceptions;

/**
 * Excepción lanzada cuando no se encuentra un tipo de permiso
 */
final class TipoPermisoNotFoundException extends \Exception
{
    public function __construct(string $message = 'El tipo de permiso no fue encontrado', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function porId(int $id): self
    {
        return new self("El tipo de permiso con ID {$id} no fue encontrado");
    }
}
