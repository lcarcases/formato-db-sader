<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Domain\ValueObjects\HostnameVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\HostnameOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\HostnameRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for HostnameOutAdapter
 *
 * Tests OutAdapter integration with Repository and database, verifying the
 * raw HostnameModel -> HostnameVO mapping happens correctly at this layer.
 */
final class HostnameOutAdapterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private HostnameOutAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new HostnameRepository;
        $this->adapter = new HostnameOutAdapter($repository);
    }

    public function test_obtener_hostnames_returns_active_records_from_database(): void
    {
        // Arrange: Clear seed data and insert test records
        HostnameModel::query()->delete();

        HostnameModel::create([
            'sn_nombre' => 'TestAdapterActivo1',
            'ind_activo' => 1,
        ]);
        HostnameModel::create([
            'sn_nombre' => 'TestAdapterInactivo',
            'ind_activo' => 0,
        ]);
        HostnameModel::create([
            'sn_nombre' => 'TestAdapterActivo2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerHostnames();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(HostnameVO::class, $result);

        $nombres = array_map(fn (HostnameVO $vo) => $vo->nombre, $result);
        $this->assertContains('TestAdapterActivo1', $nombres);
        $this->assertContains('TestAdapterActivo2', $nombres);
        $this->assertNotContains('TestAdapterInactivo', $nombres);
    }

    public function test_obtener_hostnames_returns_array_of_hostname_vo_correctly_mapped(): void
    {
        // Arrange: Clear seed data and insert test records
        HostnameModel::query()->delete();

        $model1 = HostnameModel::create([
            'sn_nombre' => 'TestAdapterVO1',
            'ind_activo' => 1,
        ]);
        $model2 = HostnameModel::create([
            'sn_nombre' => 'TestAdapterVO2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerHostnames();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        foreach ($result as $hostname) {
            $this->assertInstanceOf(HostnameVO::class, $hostname);
            $this->assertGreaterThan(0, $hostname->id);
            $this->assertNotEmpty($hostname->nombre);
        }

        $this->assertSame($model1->id_nu_hostname, $result[0]->id);
        $this->assertSame('TestAdapterVO1', $result[0]->nombre);
        $this->assertSame($model2->id_nu_hostname, $result[1]->id);
        $this->assertSame('TestAdapterVO2', $result[1]->nombre);
    }

    public function test_obtener_hostnames_returns_empty_array_when_no_active_records(): void
    {
        // Arrange: Clear seed data - no active records
        HostnameModel::query()->delete();

        // Act
        $result = $this->adapter->obtenerHostnames();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
