<?php

namespace App\Core\Domain\Services;

use App\Core\Domain\Entities\{{Entity1}};
use App\Core\Domain\Entities\{{Entity2}};
use App\Core\Domain\Vo\{{ValueObject}};
use App\Core\Domain\Exceptions\{{DomainException}};

class {{DomainService}}
{
    public function ejecutarRegla(
        {{Entity1}} $entidad1,
        {{Entity2}} $entidad2,
        {{ValueObject}} $valueObject
    ): bool {
        if (!$this->cumpleCondicion($entidad1, $valueObject)) {
            throw new {{DomainException}}('No cumple la condición');
        }

        return $this->validarEntidades($entidad1, $entidad2);
    }

    private function cumpleCondicion({{Entity1}} $entidad1, {{ValueObject}} $vo): bool
    {
        return true;
    }

    private function validarEntidades({{Entity1}} $e1, {{Entity2}} $e2): bool
    {
        return true;
    }
}
