<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\EsquemaOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\EsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameEsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\EsquemaRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for EsquemaOutAdapter
 *
 * Tests OutAdapter integration with Repository and database, verifying the
 * raw EsquemaModel -> EsquemaVO mapping happens correctly at this layer, and
 * that null/[] hostname-lookup semantics propagate through unchanged.
 */
final class EsquemaOutAdapterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private EsquemaOutAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new EsquemaRepository;
        $this->adapter = new EsquemaOutAdapter($repository);
    }

    // -----------------------------------------------------------------
    // obtenerEsquemas()
    // -----------------------------------------------------------------

    public function test_obtener_esquemas_returns_active_records_from_database(): void
    {
        // Arrange: Clear seed data and insert test records
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        EsquemaModel::create(['sn_nombre' => 'TestAdapterActivo1', 'ind_activo' => 1]);
        EsquemaModel::create(['sn_nombre' => 'TestAdapterInactivo', 'ind_activo' => 0]);
        EsquemaModel::create(['sn_nombre' => 'TestAdapterActivo2', 'ind_activo' => 1]);

        // Act
        $result = $this->adapter->obtenerEsquemas();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(EsquemaVO::class, $result);

        $nombres = array_map(fn (EsquemaVO $vo) => $vo->nombre, $result);
        $this->assertContains('TestAdapterActivo1', $nombres);
        $this->assertContains('TestAdapterActivo2', $nombres);
        $this->assertNotContains('TestAdapterInactivo', $nombres);
    }

    public function test_obtener_esquemas_returns_array_of_esquema_vo_correctly_mapped(): void
    {
        // Arrange: Clear seed data and insert test records
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        $model1 = EsquemaModel::create(['sn_nombre' => 'TestAdapterVO1', 'ind_activo' => 1]);
        $model2 = EsquemaModel::create(['sn_nombre' => 'TestAdapterVO2', 'ind_activo' => 1]);

        // Act
        $result = $this->adapter->obtenerEsquemas();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        foreach ($result as $esquema) {
            $this->assertInstanceOf(EsquemaVO::class, $esquema);
            $this->assertGreaterThan(0, $esquema->id);
            $this->assertNotEmpty($esquema->nombre);
        }

        $this->assertSame($model1->id_nu_esquema, $result[0]->id);
        $this->assertSame('TestAdapterVO1', $result[0]->nombre);
        $this->assertSame($model2->id_nu_esquema, $result[1]->id);
        $this->assertSame('TestAdapterVO2', $result[1]->nombre);
    }

    public function test_obtener_esquemas_returns_empty_array_when_no_active_records(): void
    {
        // Arrange: Clear seed data - no active records
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        // Act
        $result = $this->adapter->obtenerEsquemas();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -----------------------------------------------------------------
    // obtenerEsquemasPorHostname()
    // -----------------------------------------------------------------

    public function test_obtener_esquemas_por_hostname_returns_null_for_nonexistent_hostname(): void
    {
        // Act
        $result = $this->adapter->obtenerEsquemasPorHostname(999);

        // Assert
        $this->assertNull($result);
    }

    public function test_obtener_esquemas_por_hostname_returns_empty_array_when_no_associations(): void
    {
        // Arrange - hostname id 1 (pgrdesbds09) is seeded but has no esquema associations

        // Act
        $result = $this->adapter->obtenerEsquemasPorHostname(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_obtener_esquemas_por_hostname_returns_mapped_esquema_vo_array(): void
    {
        // Arrange - hostname id 2 (sridesbds09) is seeded with 16 active associations

        // Act
        $result = $this->adapter->obtenerEsquemasPorHostname(2);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(16, $result);
        $this->assertContainsOnlyInstancesOf(EsquemaVO::class, $result);
    }

    public function test_obtener_esquemas_por_hostname_correctly_maps_id_and_nombre(): void
    {
        // Arrange - fresh hostname + esquema + active association
        $hostname = HostnameModel::create(['sn_nombre' => 'TestAdapterHost', 'ind_activo' => 1]);
        $esquema = EsquemaModel::create(['sn_nombre' => 'TestAdapterEsquema', 'ind_activo' => 1]);

        HostnameEsquemaModel::create([
            'id_nu_hostname' => $hostname->id_nu_hostname,
            'id_nu_esquema' => $esquema->id_nu_esquema,
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerEsquemasPorHostname($hostname->id_nu_hostname);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(EsquemaVO::class, $result[0]);
        $this->assertSame($esquema->id_nu_esquema, $result[0]->id);
        $this->assertSame('TestAdapterEsquema', $result[0]->nombre);
    }
}
