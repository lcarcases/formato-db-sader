<?php

namespace App\Core\Application\Dtos\Out;


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
