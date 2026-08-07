<?php

namespace App\Core\Application\Dtos\In;

readonly class {{InDto}}
{
    public function __construct(
        public string $atributo1,
        public string $atributo2,
        public ?int $atributoOpcional = null,
    ) {
        if (empty($this->atributo1)) {
            throw new \InvalidArgumentException('atributo1 no puede estar vacío');
        }
    }
}
