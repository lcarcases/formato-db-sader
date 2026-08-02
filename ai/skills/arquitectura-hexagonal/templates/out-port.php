<?php

namespace App\Core\Application\Ports\Out;

use App\Core\Domain\Entities\{{Entity}};

interface {{OutPort}}
{
    public function persistir{{Entity}}({{Entity}} $entidad): void;

    public function buscarPor{{Criterio}}(string $criterio): ?{{Entity}};

    public function eliminar{{Entity}}(int $id): void;
}
