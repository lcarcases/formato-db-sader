<?php

namespace App\Core\Application\Services;

use App\Core\Application\Ports\Out\{{OutPort}};
use App\Core\Domain\Entities\{{Entity}};

class {{AppService}}
{
    private {{OutPort}} $outPort;

    public function __construct({{OutPort}} $outPort)
    {
        $this->outPort = $outPort;
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
