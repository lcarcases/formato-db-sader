<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\ITipoRequerimientoOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoRequerimientoPostgresSQLRepository;

/**
 * TipoRequerimientoPostgresSQLOutAdapter
 */
final class TipoRequerimientoPostgresSQLOutAdapter implements ITipoRequerimientoOutPort
{
    private TipoRequerimientoPostgresSQLRepository $tipoRequerimientoPostgresSQLRepository;

    public function __construct(
        TipoRequerimientoPostgresSQLRepository $tipoRequerimientoPostgresSQLRepository
    ) {
        $this->tipoRequerimientoPostgresSQLRepository = $tipoRequerimientoPostgresSQLRepository;
    }

    /**
     * Obtener todos los tipos de requerimientos
     *
     * Returns raw data as-is from Repository.
     * InAdapter (controller) will create the OutDTO.
     *
     * @return array Raw array from repository
     */
    public function obtenerTodos(): array
    {
        try {
            // Get raw data from Repository
            $rawData = $this->tipoRequerimientoPostgresSQLRepository->buscarTodos();

            // Return raw data as-is (simple pattern - no entity mapping needed for catalog data)
            return $rawData;

        } catch (\Exception $e) {
            // Log error and rethrow
            \Log::error('Error en TipoRequerimientoPostgresSQLOutAdapter::obtenerTodos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
