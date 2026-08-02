<?php

namespace App\Core\Domain\Vo;

use App\Core\Domain\Exceptions\{{DomainException}};

readonly class {{ValueObject}}
{
    private string $atributo1;
    private string $atributo2;

    public function __construct(string $atributo1, string $atributo2)
    {
        $this->validar($atributo1, $atributo2);
        $this->atributo1 = $atributo1;
        $this->atributo2 = $atributo2;
    }

    public function getAtributo1(): string
    {
        return $this->atributo1;
    }

    public function getAtributo2(): string
    {
        return $this->atributo2;
    }

    private function validar(string $atributo1, string $atributo2): void
    {
        if (empty($atributo1)) {
            throw new {{DomainException}}('Atributo1 no puede estar vacío');
        }
    }

    public function equals({{ValueObject}} $otro): bool
    {
        return $this->atributo1 === $otro->atributo1
            && $this->atributo2 === $otro->atributo2;
    }

    public function __toString(): string
    {
        return "{$this->atributo1} - {$this->atributo2}";
    }
}
