<?php

declare(strict_types=1);

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\ITipoPermisoOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPermisoPostgresSQLRepository;

/**
 * TipoPermisoPostgresSQLOutAdapter
 *
 * Adaptador de salida que implementa ITipoPermisoOutPort
 * para interactuar con la base de datos PostgreSQL.
 */
final class TipoPermisoPostgresSQLOutAdapter implements ITipoPermisoOutPort
{
    private TipoPermisoPostgresSQLRepository $tipoPermisoPostgresSQLRepository;

    /**
     * Constructor del adaptador
     *
     * @param  TipoPermisoPostgresSQLRepository  $tipoPermisoPostgresSQLRepository  Repositorio de PostgreSQL
     */
    public function __construct(TipoPermisoPostgresSQLRepository $tipoPermisoPostgresSQLRepository)
    {
        $this->tipoPermisoPostgresSQLRepository = $tipoPermisoPostgresSQLRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerTodos(): array
    {
        try {
            $rawData = $this->tipoPermisoPostgresSQLRepository->buscarTodos();

            // Map database columns to domain-friendly names
            return array_map(function ($row) {
                return [
                    'id' => $row->id_nu_tipo_permiso,
                    'nombre' => $row->ln_nombre,
                    'activo' => $row->ind_activo,
                    'descripcion' => $row->sn_descripcion,
                ];
            }, $rawData);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error al obtener tipos de permiso: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $row = $this->tipoPermisoPostgresSQLRepository->buscarPorId($id);

            if ($row === null) {
                return null;
            }

            // Map database columns to domain-friendly names
            return [
                'id' => $row->id_nu_tipo_permiso,
                'nombre' => $row->ln_nombre,
                'activo' => $row->ind_activo,
                'descripcion' => $row->sn_descripcion,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Error al obtener tipo de permiso con ID {$id}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
