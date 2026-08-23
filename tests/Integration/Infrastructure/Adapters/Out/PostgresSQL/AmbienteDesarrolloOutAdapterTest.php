<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Domain\ValueObjects\AmbienteVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\AmbienteDesarrolloOutAdapter;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\AmbienteDesarrolloModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\AmbienteDesarrolloRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for AmbienteDesarrolloOutAdapter
 *
 * Tests OutAdapter integration with Repository and database
 */
final class AmbienteDesarrolloOutAdapterTest extends TestCase
{
    use RefreshDatabase;

    private AmbienteDesarrolloOutAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new AmbienteDesarrolloRepository;
        $this->adapter = new AmbienteDesarrolloOutAdapter($repository);
    }

    public function test_obtener_ambientes_desarrollo_returns_active_records_from_database(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();

        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestAdapterActivo1',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestAdapterInactivo',
            'ind_activo' => 0,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestAdapterActivo2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(AmbienteVO::class, $result);

        $nombres = array_map(fn (AmbienteVO $vo) => $vo->nombre, $result);
        $this->assertContains('TestAdapterActivo1', $nombres);
        $this->assertContains('TestAdapterActivo2', $nombres);
        $this->assertNotContains('TestAdapterInactivo', $nombres);
    }

    public function test_obtener_ambientes_desarrollo_returns_array_of_ambiente_vo(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();

        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestAdapterVO1',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestAdapterVO2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->adapter->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        foreach ($result as $ambiente) {
            $this->assertInstanceOf(AmbienteVO::class, $ambiente);
            $this->assertGreaterThan(0, $ambiente->id);
            $this->assertNotEmpty($ambiente->nombre);
        }
    }

    public function test_obtener_ambientes_desarrollo_returns_empty_array_when_no_active_records(): void
    {
        // Arrange: Clear seed data - no active records
        AmbienteDesarrolloModel::query()->delete();

        // Act
        $result = $this->adapter->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
