<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPersonalPostgresSQLRepository;

/**
 * TipoPersonalPostgresSQLOutAdapter
 *
 * Implements ITipoPersonalOutPort for PostgreSQL database access.
 * Orchestrates Repository and provides data to Application layer.
 *
 * 🔴 OUTADAPTER PATTERN:
 * ✅ Implements OutPort interface from Application layer
 * ✅ Depends on Repository for actual database queries
 * ✅ Located in Infrastructure layer (PostgreSQL-specific)
 * ✅ Returns raw data as-is from Repository (no entity mapping for catalog data)
 * ✅ Handles exceptions and logs errors
 * ✅ Constructor injection for Repository dependency
 *
 * Pattern: UseCase → OutPort (interface) → OutAdapter → Repository → Database
 *
 * Why no entity mapping?
 * - For simple catalog data (id + nombre), raw stdClass is sufficient
 * - DTO creation happens in InAdapter for better separation
 * - Reduces complexity when no business logic is needed on entities
 */
final class TipoPersonalPostgresSQLOutAdapter implements ITipoPersonalOutPort
{
    private TipoPersonalPostgresSQLRepository $repository;

    /**
     * @param  TipoPersonalPostgresSQLRepository  $repository  Repository for database access
     */
    public function __construct(
        TipoPersonalPostgresSQLRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Obtener todos los tipos de personal activos
     *
     * Returns raw data as-is from Repository.
     * InAdapter (controller) will create the OutDTO.
     *
     * @return array Raw array of stdClass objects from repository
     *
     * @throws \Exception If database query fails
     */
    public function obtenerTodos(): array
    {
        try {
            // Get raw data from Repository
            $rawData = $this->repository->buscarTodos();

            // Return raw data as-is (simple pattern - no entity mapping needed for catalog data)
            return $rawData;

        } catch (\Exception $e) {
            // Log error with context
            \Log::error('Error en TipoPersonalPostgresSQLOutAdapter::obtenerTodos', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'adapter' => self::class,
            ]);

            // Rethrow exception - UseCase will catch and log with structured context
            throw $e;
        }
    }
}
