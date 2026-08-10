<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\BaseDatosOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\BaseDatosModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\BaseDatosRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for BaseDatosOutAdapter
 *
 * Tests OutAdapter integration with Repository and database, verifying the
 * raw BaseDatosModel -> BaseDatosVO mapping happens correctly at this layer.
 */
final class BaseDatosOutAdapterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private BaseDatosOutAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new BaseDatosRepository;
        $this->adapter = new BaseDatosOutAdapter($repository);
    }

    public function test_obtener_bases_datos_returns_active_records_from_database(): void
    {
        // Arrange: Clear seed data and insert test records
        BaseDatosModel::query()->delete();

        BaseDatosModel::create([
            'sn_nombre' => 'TestAdapterActivo1',
            'ind_activo' => 1,
        ]);
        BaseDatosModel::create([
            'sn_nombre' => 'TestAdapterInactivo',
            'ind_activo' => 0,
        ]);
        BaseDatosModel::create([
            'sn_nombre' => 'TestAdapterActivo2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerBasesDatos();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(BaseDatosVO::class, $result);

        $nombres = array_map(fn (BaseDatosVO $vo) => $vo->nombre, $result);
        $this->assertContains('TestAdapterActivo1', $nombres);
        $this->assertContains('TestAdapterActivo2', $nombres);
        $this->assertNotContains('TestAdapterInactivo', $nombres);
    }

    public function test_obtener_bases_datos_returns_array_of_base_datos_vo_correctly_mapped(): void
    {
        // Arrange: Clear seed data and insert test records
        BaseDatosModel::query()->delete();

        $model1 = BaseDatosModel::create([
            'sn_nombre' => 'TestAdapterVO1',
            'ind_activo' => 1,
        ]);
        $model2 = BaseDatosModel::create([
            'sn_nombre' => 'TestAdapterVO2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerBasesDatos();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        foreach ($result as $baseDatos) {
            $this->assertInstanceOf(BaseDatosVO::class, $baseDatos);
            $this->assertGreaterThan(0, $baseDatos->id);
            $this->assertNotEmpty($baseDatos->nombre);
        }

        $this->assertSame($model1->id_nu_base_datos, $result[0]->id);
        $this->assertSame('TestAdapterVO1', $result[0]->nombre);
        $this->assertSame($model2->id_nu_base_datos, $result[1]->id);
        $this->assertSame('TestAdapterVO2', $result[1]->nombre);
    }

    public function test_obtener_bases_datos_returns_empty_array_when_no_active_records(): void
    {
        // Arrange: Clear seed data - no active records
        BaseDatosModel::query()->delete();

        // Act
        $result = $this->adapter->obtenerBasesDatos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
