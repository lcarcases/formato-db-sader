<?php

namespace App\Core\Domain\Aggregates;

use App\Core\Domain\Entities\{{RootEntity}};
use App\Core\Domain\Entities\{{ChildEntity}};
use App\Core\Domain\Vo\{{ValueObject}};
use App\Core\Domain\Enums\{{Enum}};

class {{Aggregate}}
{
    private {{RootEntity}} $raiz;
    private array $entidadesHijas = [];
    private {{ValueObject}} $valueObject;
    private {{Enum}} $estado;

    public function __construct(
        {{RootEntity}} $raiz,
        {{ValueObject}} $valueObject,
        {{Enum}} $estado
    ) {
        $this->raiz = $raiz;
        $this->valueObject = $valueObject;
        $this->estado = $estado;
    }

    public function getRaiz(): {{RootEntity}}
    {
        return $this->raiz;
    }

    public function getId(): int
    {
        return $this->raiz->getId();
    }

    public function agregarEntidadHija({{ChildEntity}} $entidad): void
    {
        $this->validarAgregarEntidad($entidad);
        $this->entidadesHijas[] = $entidad;
    }

    public function getEntidadesHijas(): array
    {
        return $this->entidadesHijas;
    }

    private function validarAgregarEntidad({{ChildEntity}} $entidad): void
    {
        if (count($this->entidadesHijas) >= 10) {
            throw new \DomainException('Máximo 10 entidades permitidas');
        }
    }
}
