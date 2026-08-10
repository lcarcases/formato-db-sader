<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\BaseDatosModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\BaseDatosRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BaseDatosRepositoryIntegrationTest
 *
 * Integration tests for BaseDatosRepository.
 *
 * Tests Database Integration:
 * - Query execution with PostgreSQL
 * - Active filtering (ind_activo = 1)
 * - Ordering (id ASC)
 * - Raw Eloquent model return (NOT BaseDatosVO — mapping happens in the OutAdapter)
 * - Edge cases (empty table, inactive records)
 *
 * Note: The migrations that create `tb_cat_base_datos` also seed 4 active rows
 * (PPB, SURI, XAMAN, OTROS) via a data-seeding migration. Because RefreshDatabase
 * re-runs all migrations before every test, the table starts each test with those
 * 4 rows already present. Tests that need a clean slate delete them explicitly.
 */
final class BaseDatosRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private BaseDatosRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BaseDatosRepository;
    }

    public function test_it_retrieves_all_active_bases_datos_ordered_by_id(): void
    {
        // Arrange - start from a clean slate, insert deterministic test data
        BaseDatosModel::query()->delete();

        BaseDatosModel::create(['sn_nombre' => 'Base1', 'ind_activo' => 1]);
        BaseDatosModel::create(['sn_nombre' => 'Base2', 'ind_activo' => 1]);
        BaseDatosModel::create(['sn_nombre' => 'Base3', 'ind_activo' => 1]);

        // Act
        $result = $this->repository->obtenerBasesDatos();

        // Assert
        $this->assertCount(3, $result);
        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(BaseDatosModel::class, $result);

        // Verify ordering by id ASC
        $this->assertTrue($result[0]->id_nu_base_datos < $result[1]->id_nu_base_datos);
        $this->assertTrue($result[1]->id_nu_base_datos < $result[2]->id_nu_base_datos);

        $this->assertSame('Base1', $result[0]->sn_nombre);
        $this->assertSame('Base2', $result[1]->sn_nombre);
        $this->assertSame('Base3', $result[2]->sn_nombre);
    }

    public function test_it_returns_raw_base_datos_model_instances_not_value_objects(): void
    {
        // Arrange - the migration-seeded 4 rows are already present
        // Act
        $result = $this->repository->obtenerBasesDatos();

        // Assert
        $this->assertNotEmpty($result);
        foreach ($result as $item) {
            $this->assertInstanceOf(BaseDatosModel::class, $item);
        }
    }

    public function test_it_returns_the_four_seeded_bases_datos(): void
    {
        // Arrange - migration-seeded baseline (PPB, SURI, XAMAN, OTROS), no extra arrange needed

        // Act
        $result = $this->repository->obtenerBasesDatos();

        // Assert
        $this->assertCount(4, $result);

        $nombres = array_map(fn (BaseDatosModel $model) => $model->sn_nombre, $result);
        $this->assertSame(['PPB', 'SURI', 'XAMAN', 'OTROS'], $nombres);
    }

    public function test_it_filters_out_inactive_bases_datos(): void
    {
        // Arrange - clean slate, then active + inactive records
        BaseDatosModel::query()->delete();

        BaseDatosModel::create(['sn_nombre' => 'Activo1', 'ind_activo' => 1]);
        BaseDatosModel::create(['sn_nombre' => 'Inactivo1', 'ind_activo' => 0]);
        BaseDatosModel::create(['sn_nombre' => 'Activo2', 'ind_activo' => 1]);
        BaseDatosModel::create(['sn_nombre' => 'Inactivo2', 'ind_activo' => 0]);

        // Act
        $result = $this->repository->obtenerBasesDatos();

        // Assert - only active records
        $this->assertCount(2, $result);

        $nombres = array_map(fn (BaseDatosModel $model) => $model->sn_nombre, $result);
        $this->assertContains('Activo1', $nombres);
        $this->assertContains('Activo2', $nombres);
        $this->assertNotContains('Inactivo1', $nombres);
        $this->assertNotContains('Inactivo2', $nombres);
    }

    public function test_it_returns_empty_array_when_table_is_empty(): void
    {
        // Arrange - explicitly delete the migration-seeded rows for a clean slate
        BaseDatosModel::query()->delete();

        // Act
        $result = $this->repository->obtenerBasesDatos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_empty_array_when_no_active_bases_datos_exist(): void
    {
        // Arrange - clean slate, insert only inactive records
        BaseDatosModel::query()->delete();
        BaseDatosModel::create(['sn_nombre' => 'SoloInactivo', 'ind_activo' => 0]);

        // Act
        $result = $this->repository->obtenerBasesDatos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
