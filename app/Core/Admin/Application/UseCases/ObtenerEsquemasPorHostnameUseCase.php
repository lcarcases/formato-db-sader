<?php

declare(strict_types=1);

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use App\Core\Admin\Domain\Exceptions\HostnameNotFoundException;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;

/**
 * Use Case: Obtener Esquemas de un Hostname
 *
 * Business logic: Retrieve the active esquemas associated to a given hostname.
 *
 * Responsibility:
 * - Orchestrate the retrieval of active esquemas associated to a hostname via OutPort
 * - Translate the port's null (hostname not found) into a domain exception
 * - Return raw array<EsquemaVO> for maximum reusability (the synthetic "Todos" entry
 *   is NOT added here — that's the OutDto's job)
 *
 * The InAdapter (REST controller) is responsible for:
 * - Creating ObtenerEsquemasPorHostnameOutDto from the result
 * - Transforming to JSON response format
 * - Catching HostnameNotFoundException and mapping it to HTTP 404
 */
final readonly class ObtenerEsquemasPorHostnameUseCase
{
    public function __construct(
        private EsquemaOutPort $esquemaOutPort,
    ) {}

    /**
     * Execute the use case
     *
     * @return list<EsquemaVO> Lista de esquemas reales asociados (sin "Todos")
     *
     * @throws HostnameNotFoundException si el hostname no existe
     */
    public function execute(int $idHostname): array
    {
        $esquemas = $this->esquemaOutPort->obtenerEsquemasPorHostname($idHostname);

        if ($esquemas === null) {
            throw new HostnameNotFoundException($idHostname);
        }

        return $esquemas;
    }
}
