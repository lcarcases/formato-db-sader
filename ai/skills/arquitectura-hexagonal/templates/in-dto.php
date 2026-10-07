<?php

namespace App\Core\Application\Dtos\In;

readonly class {{InDto}}
{
    private string $atributo1;
    private string $atributo2;
    private ?int $atributoOpcional;

    public function __construct(\stdClass $data) {
        $this->atributo1 = $data->atributo1 ?? '';
        $this->atributo2 = $data->atributo2 ?? '';
        $this->atributoOpcional = $data->atributoOpcional ?? null;
    }

    public function obtenerAtributo1(): string {
        return $this->atributo1;
    }

    public function obtenerAtributo2(): string {
        return $this->atributo2;
    }

    public function obtenerAtributoOpcional(): ?int {
        return $this->atributoOpcional;
    }
}
