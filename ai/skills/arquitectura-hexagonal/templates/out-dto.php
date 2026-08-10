<?php

namespace App\Core\Application\Dtos\Out;

// Only primitive/scalar properties or nested OutDTOs here — never a Domain
// Value Object/Entity. Instantiate this class ONLY in the InAdapter, after
// calling the use case (never in the UseCase, Domain, or Infrastructure layers).
readonly class {{OutDto}}
{
    public function __construct(
        public int $id,
        public string $atributo1Formateado,
        public string $atributo2Formateado,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'atributo1' => $this->atributo1Formateado,
            'atributo2' => $this->atributo2Formateado,
        ];
    }
}
