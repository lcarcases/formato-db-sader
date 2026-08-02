## 6.1 InDto (Input from client)

**Template:** Use [templates/in-dto.php](../templates/in-dto.php) as a starting structure.

Purpose: Carry data from InAdapter to UseCase

```php
// filepath: app/Core/Programa/Application/Dtos/In/GenerarSolicitudInDto.php
<?php

namespace App\Core\Programa\Application\Dtos\In;

use App\Core\Shared\Application\Dto\IDto;

class GenerarSolicitudInDto implements IDto
{
    public function __construct(
        public readonly string $curp,
        public readonly string $clavePrograma,
        public readonly int $anio,
        public readonly string $estado,
        public readonly ?float $montoSolicitado = null
    ) {}
}
```
