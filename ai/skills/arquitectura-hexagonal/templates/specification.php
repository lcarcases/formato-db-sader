<?php

namespace App\Core\Domain\Specifications;

use App\Core\Domain\Entities\{{Entity}};

class {{Specification}}
{
    private mixed $criterio;

    public function __construct(mixed $criterio)
    {
        $this->criterio = $criterio;
    }

    public function isSatisfiedBy({{Entity}} $entidad): bool
    {
        return $this->evaluarRegla($entidad);
    }

    private function evaluarRegla({{Entity}} $entidad): bool
    {
        // Implementar lógica de la especificación
        return true;
    }
}
