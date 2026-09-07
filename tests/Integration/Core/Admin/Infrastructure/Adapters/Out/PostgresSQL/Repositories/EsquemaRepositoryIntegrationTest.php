<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\EsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameEsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\EsquemaRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EsquemaRepositoryIntegrationTest
 *
 * Integration tests for EsquemaRepository.
 *
 * Tests Database Integration:
 * - Query execution with PostgreSQL
 * - Active filtering (ind_activo = 1)
 * - Ordering (id ASC)
 * - Raw Eloquent model return (NOT EsquemaVO — mapping happens in the OutAdapter)
 * - Edge cases (empty table, inactive records, hostname existence)
 *
 * Note: The migrations also seed 16 active esquema rows and 48 hostname-esquema
 * associations (hostnames 2, 4, 7). Because RefreshDatabase re-runs all migrations
 * before every test, tables start each test with that seed data already present.
 * Tests that need a clean slate delete rows explicitly.
 */
final class EsquemaRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private EsquemaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EsquemaRepository;
    }

    // -----------------------------------------------------------------
    // obtenerEsquemas()
    // -----------------------------------------------------------------

    public function test_it_retrieves_all_active_esquemas_ordered_by_id(): void
    {
        // Arrange - start from a clean slate, insert deterministic test data
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        EsquemaModel::create(['sn_nombre' => 'Esquema1', 'ind_activo' => 1]);
        EsquemaModel::create(['sn_nombre' => 'Esquema2', 'ind_activo' => 1]);
        EsquemaModel::create(['sn_nombre' => 'Esquema3', 'ind_activo' => 1]);

        // Act
        $result = $this->repository->obtenerEsquemas();

        // Assert
        $this->assertCount(3, $result);
        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(EsquemaModel::class, $result);

        // Verify ordering by id ASC
        $this->assertTrue($result[0]->id_nu_esquema < $result[1]->id_nu_esquema);
        $this->assertTrue($result[1]->id_nu_esquema < $result[2]->id_nu_esquema);

        $this->assertSame('Esquema1', $result[0]->sn_nombre);
        $this->assertSame('Esquema2', $result[1]->sn_nombre);
        $this->assertSame('Esquema3', $result[2]->sn_nombre);
    }

    public function test_it_returns_raw_esquema_model_instances_not_value_objects(): void
    {
        // Arrange - the migration-seeded 16 rows are already present
        // Act
        $result = $this->repository->obtenerEsquemas();

        // Assert
        $this->assertNotEmpty($result);
        foreach ($result as $item) {
            $this->assertInstanceOf(EsquemaModel::class, $item);
        }
    }

    public function test_it_returns_the_sixteen_seeded_esquemas(): void
    {
        // Arrange - migration-seeded baseline, no extra arrange needed

        // Act
        $result = $this->repository->obtenerEsquemas();

        // Assert
        $this->assertCount(16, $result);

        $nombres = array_map(fn (EsquemaModel $model) => $model->sn_nombre, $result);
        $this->assertSame([
            'ap_activemq_pd',
            'ap_apoyos_pd',
            'ap_biometricos_pd',
            'ap_gestion_doc',
            'ap_interfaz',
            'ap_inventario_pd',
            'ap_movil_pd',
            'ap_proagro_pd',
            'ap_reportes_suri',
            'ap_supervision_pd',
            'ap_suri_pd',
            'ap_svc',
            'ap_tramites_pd',
            'ap_viaticos',
            'tr_seguridad_pd',
            'tr_suri_pd',
        ], $nombres);
    }

    public function test_it_filters_out_inactive_esquemas(): void
    {
        // Arrange - clean slate, then active + inactive records
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        EsquemaModel::create(['sn_nombre' => 'Activo1', 'ind_activo' => 1]);
        EsquemaModel::create(['sn_nombre' => 'Inactivo1', 'ind_activo' => 0]);
        EsquemaModel::create(['sn_nombre' => 'Activo2', 'ind_activo' => 1]);
        EsquemaModel::create(['sn_nombre' => 'Inactivo2', 'ind_activo' => 0]);

        // Act
        $result = $this->repository->obtenerEsquemas();

        // Assert - only active records
        $this->assertCount(2, $result);

        $nombres = array_map(fn (EsquemaModel $model) => $model->sn_nombre, $result);
        $this->assertContains('Activo1', $nombres);
        $this->assertContains('Activo2', $nombres);
        $this->assertNotContains('Inactivo1', $nombres);
        $this->assertNotContains('Inactivo2', $nombres);
    }

    public function test_it_returns_empty_array_when_table_is_empty(): void
    {
        // Arrange - explicitly delete the migration-seeded rows for a clean slate
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        // Act
        $result = $this->repository->obtenerEsquemas();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_empty_array_when_no_active_esquemas_exist(): void
    {
        // Arrange - clean slate, insert only inactive records
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();
        EsquemaModel::create(['sn_nombre' => 'SoloInactivo', 'ind_activo' => 0]);

        // Act
        $result = $this->repository->obtenerEsquemas();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -----------------------------------------------------------------
    // obtenerEsquemasPorHostname()
    // -----------------------------------------------------------------

    public function test_it_returns_active_esquemas_associated_to_a_hostname_ordered_by_id(): void
    {
        // Arrange - hostname id 2 (sridesbds09) is seeded with all 16 associations

        // Act
        $result = $this->repository->obtenerEsquemasPorHostname(2);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(16, $result);
        $this->assertContainsOnlyInstancesOf(EsquemaModel::class, $result);

        $ids = array_map(fn (EsquemaModel $model) => $model->id_nu_esquema, $result);
        $sortedIds = $ids;
        sort($sortedIds);
        $this->assertSame($sortedIds, $ids);
    }

    public function test_it_returns_null_when_hostname_does_not_exist(): void
    {
        // Act
        $result = $this->repository->obtenerEsquemasPorHostname(999);

        // Assert
        $this->assertNull($result);
    }

    public function test_it_returns_empty_array_for_existing_hostname_without_associations(): void
    {
        // Arrange - hostname id 1 (pgrdesbds09) is seeded but has no esquema associations

        // Act
        $result = $this->repository->obtenerEsquemasPorHostname(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_excludes_inactive_associations_for_hostname(): void
    {
        // Arrange - create a fresh hostname and an active esquema, then an inactive association
        $hostname = HostnameModel::create(['sn_nombre' => 'TestHostAssoc', 'ind_activo' => 1]);
        $esquema = EsquemaModel::create(['sn_nombre' => 'EsquemaAssocInactiva', 'ind_activo' => 1]);

        HostnameEsquemaModel::create([
            'id_nu_hostname' => $hostname->id_nu_hostname,
            'id_nu_esquema' => $esquema->id_nu_esquema,
            'ind_activo' => 0,
        ]);

        // Act
        $result = $this->repository->obtenerEsquemasPorHostname($hostname->id_nu_hostname);

        // Assert - hostname exists, but the inactive association is excluded
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_excludes_associations_pointing_to_inactive_esquemas(): void
    {
        // Arrange - create a fresh hostname and an inactive esquema, with an active association
        $hostname = HostnameModel::create(['sn_nombre' => 'TestHostInactEsquema', 'ind_activo' => 1]);
        $esquema = EsquemaModel::create(['sn_nombre' => 'EsquemaInactiva', 'ind_activo' => 0]);

        HostnameEsquemaModel::create([
            'id_nu_hostname' => $hostname->id_nu_hostname,
            'id_nu_esquema' => $esquema->id_nu_esquema,
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->repository->obtenerEsquemasPorHostname($hostname->id_nu_hostname);

        // Assert - hostname exists, but the association to an inactive esquema is excluded
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
