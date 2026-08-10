<?php

namespace App\Core\Application\Services;

use App\Core\Application\Ports\Out\{{OutPort}};
use App\Core\Domain\Entities\{{Entity}};

class {{AppService}}
{
    // ✅ Property named after the OutPort interface, NOT generic "$outPort"
    private {{OutPort}} ${{outPortCamelCase}};

    public function __construct({{OutPort}} ${{outPortCamelCase}})
    {
        $this->{{outPortCamelCase}} = ${{outPortCamelCase}};
    }

    public function ejecutarLogicaCompartida({{Entity}} $entidad): void
    {
        // Lógica reutilizable entre múltiples casos de uso
        if (!$this->validar($entidad)) {
            throw new \InvalidArgumentException('Validación fallida');
        }

        $resultado = $this->calcular($entidad);
        $entidad->setResultado($resultado);
    }

    private function validar({{Entity}} $entidad): bool
    {
        return true;
    }

    private function calcular({{Entity}} $entidad): mixed
    {
        return null;
    }
}
