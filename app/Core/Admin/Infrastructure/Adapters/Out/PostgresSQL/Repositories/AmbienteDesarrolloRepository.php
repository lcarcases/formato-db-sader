<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Domain\ValueObjects\AmbienteVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\AmbienteDesarrolloModel;

/**
 * Repository for Ambiente de Desarrollo data access
 *
 * Encapsulates database queries using Eloquent.
 * Maps Eloquent models to domain Value Objects.
 *
 * Responsibility:
 * - Execute database queries
 * - Transform Eloquent models to domain objects (AmbienteVO)
 * - Isolate Eloquent from the rest of the system
 */
final class AmbienteDesarrolloRepository
{
    /**
     * Retrieve all active ambientes de desarrollo from database
     *
     * Queries PostgreSQL for records with ind_activo = 1,
     * orders by ID ascending, and maps to AmbienteVO objects.
     *
     * @return list<AmbienteVO> Array of AmbienteVO (not Eloquent Collection)
     */
    public function obtenerAmbientesDesarrollo(): array
    {
        return array_values(
            AmbienteDesarrolloModel::query()
                ->where('ind_activo', 1)
                ->orderBy('id_nu_ambiente_desarrollo', 'asc')
                ->get(['id_nu_ambiente_desarrollo', 'sn_nombre'])
                ->map(fn (AmbienteDesarrolloModel $model): AmbienteVO => new AmbienteVO(
                    id: $model->id_nu_ambiente_desarrollo,
                    nombre: $model->sn_nombre,
                ))
                ->all() // Convert Collection to plain array
        ); // Ensure list<T> type (re-index to 0-based consecutive keys)
    }
}
