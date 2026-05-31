<?php

namespace App\Core\Application\Ports\In;

use App\Core\Application\Dtos\In\{{InDto}};
use App\Core\Application\Dtos\Out\{{OutDto}};

interface {{InPort}}
{
    /**
     * Descripción de lo que hace el caso de uso
     *
     * @param {{InDto}} $dto Datos de entrada
     * @return {{OutDto}} Resultado de la operación
     * @throws {{DomainException}} Si ocurre un error de negocio
     */
    public function execute({{InDto}} $dto): {{OutDto}};
}
