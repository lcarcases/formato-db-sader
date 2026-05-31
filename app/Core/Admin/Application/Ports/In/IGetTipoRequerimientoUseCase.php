<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\In;

use App\Core\Admin\Application\DTOs\Out\GetTipoRequerimientoOutDto;

/**
 * GetTipoRequerimiento Input Port
 *
 * Interface defining the contract for getting requirement types list.
 */
interface IGetTipoRequerimientoUseCase
{
    /**
     * Execute the use case to get all requirement types
     */
    public function execute(): GetTipoRequerimientoOutDto;
}
