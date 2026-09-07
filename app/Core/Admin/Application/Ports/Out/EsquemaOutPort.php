<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\Ports\Out;

use App\Core\Admin\Domain\ValueObjects\EsquemaVO;

/**
 * Port Out (Repository Interface) for Esquema
 *
 * Defines the contract for retrieving esquemas from storage.
 * Implementation lives in Infrastructure layer (Out Adapter).
 *
 * Dependency Inversion Principle:
 * - Application layer defines the interface
 * - Infrastructure layer implements it
 */
interface EsquemaOutPort
{
    /**
     * Retrieve all active esquemas (ind_activo = 1) from storage, ordered by ID.
     *
     * @return list<EsquemaVO>
     */
    public function obtenerEsquemas(): array;

    /**
     * Retrieve the active esquemas associated to a hostname (via tb_r_hostname_esquema),
     * ordered by id_nu_esquema ascending.
     *
     * @return list<EsquemaVO>|null null if the hostname does not exist in tb_cat_hostname;
     *                              [] if it exists but has no active associations.
     */
    public function obtenerEsquemasPorHostname(int $idHostname): ?array;
}
