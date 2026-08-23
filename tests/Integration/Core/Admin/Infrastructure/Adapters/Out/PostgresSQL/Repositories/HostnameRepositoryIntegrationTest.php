<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\HostnameRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HostnameRepositoryIntegrationTest
 *
 * Integration tests for HostnameRepository.
 *
 * Tests Database Integration:
 * - Query execution with PostgreSQL
 * - Active filtering (ind_activo = 1)
 * - Ordering (id ASC)
 * - Raw Eloquent model return (NOT HostnameVO — mapping happens in the OutAdapter)
 * - Edge cases (empty table, inactive records)
 *
 * Note: The migrations that create `tb_cat_hostname` also seed 11 active rows
 * via a data-seeding migration. Because RefreshDatabase re-runs all migrations
 * before every test, the table starts each test with those 11 rows already
 * present. Tests that need a clean slate delete them explicitly.
 */
final class HostnameRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private HostnameRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new HostnameRepository;
    }

    public function test_it_retrieves_all_active_hostnames_ordered_by_id(): void
    {
        // Arrange - start from a clean slate, insert deterministic test data
        HostnameModel::query()->delete();

        HostnameModel::create(['sn_nombre' => 'Host1', 'ind_activo' => 1]);
        HostnameModel::create(['sn_nombre' => 'Host2', 'ind_activo' => 1]);
        HostnameModel::create(['sn_nombre' => 'Host3', 'ind_activo' => 1]);

        // Act
        $result = $this->repository->obtenerHostnames();

        // Assert
        $this->assertCount(3, $result);
        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(HostnameModel::class, $result);

        // Verify ordering by id ASC
        $this->assertTrue($result[0]->id_nu_hostname < $result[1]->id_nu_hostname);
        $this->assertTrue($result[1]->id_nu_hostname < $result[2]->id_nu_hostname);

        $this->assertSame('Host1', $result[0]->sn_nombre);
        $this->assertSame('Host2', $result[1]->sn_nombre);
        $this->assertSame('Host3', $result[2]->sn_nombre);
    }

    public function test_it_returns_raw_hostname_model_instances_not_value_objects(): void
    {
        // Arrange - the migration-seeded 11 rows are already present
        // Act
        $result = $this->repository->obtenerHostnames();

        // Assert
        $this->assertNotEmpty($result);
        foreach ($result as $item) {
            $this->assertInstanceOf(HostnameModel::class, $item);
        }
    }

    public function test_it_returns_the_eleven_seeded_hostnames(): void
    {
        // Arrange - migration-seeded baseline, no extra arrange needed

        // Act
        $result = $this->repository->obtenerHostnames();

        // Assert
        $this->assertCount(11, $result);

        $nombres = array_map(fn (HostnameModel $model) => $model->sn_nombre, $result);
        $this->assertSame([
            'pgrdesbds09',
            'sridesbds09',
            'pgrprdbdsmz02',
            'sriprdbdsmz02',
            'divprdbds01',
            'pgrqabds08',
            'sriqabds08',
            '10.1.35.50',
            '10.1.21.95',
            '10.1.20.25',
            '10.54.49.100',
        ], $nombres);
    }

    public function test_it_filters_out_inactive_hostnames(): void
    {
        // Arrange - clean slate, then active + inactive records
        HostnameModel::query()->delete();

        HostnameModel::create(['sn_nombre' => 'Activo1', 'ind_activo' => 1]);
        HostnameModel::create(['sn_nombre' => 'Inactivo1', 'ind_activo' => 0]);
        HostnameModel::create(['sn_nombre' => 'Activo2', 'ind_activo' => 1]);
        HostnameModel::create(['sn_nombre' => 'Inactivo2', 'ind_activo' => 0]);

        // Act
        $result = $this->repository->obtenerHostnames();

        // Assert - only active records
        $this->assertCount(2, $result);

        $nombres = array_map(fn (HostnameModel $model) => $model->sn_nombre, $result);
        $this->assertContains('Activo1', $nombres);
        $this->assertContains('Activo2', $nombres);
        $this->assertNotContains('Inactivo1', $nombres);
        $this->assertNotContains('Inactivo2', $nombres);
    }

    public function test_it_returns_empty_array_when_table_is_empty(): void
    {
        // Arrange - explicitly delete the migration-seeded rows for a clean slate
        HostnameModel::query()->delete();

        // Act
        $result = $this->repository->obtenerHostnames();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_empty_array_when_no_active_hostnames_exist(): void
    {
        // Arrange - clean slate, insert only inactive records
        HostnameModel::query()->delete();
        HostnameModel::create(['sn_nombre' => 'SoloInactivo', 'ind_activo' => 0]);

        // Act
        $result = $this->repository->obtenerHostnames();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
