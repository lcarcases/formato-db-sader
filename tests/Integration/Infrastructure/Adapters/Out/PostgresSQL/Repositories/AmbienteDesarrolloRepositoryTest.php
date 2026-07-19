<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Domain\ValueObjects\AmbienteVO;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\AmbienteDesarrolloModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\AmbienteDesarrolloRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for AmbienteDesarrolloRepository
 *
 * Tests repository interaction with PostgreSQL database
 */
final class AmbienteDesarrolloRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AmbienteDesarrolloRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AmbienteDesarrolloRepository();
    }

    public function test_obtener_ambientes_desarrollo_returns_only_active_records(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();
        
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoActivo1',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoInactivo',
            'ind_activo' => 0,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoActivo2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->repository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(AmbienteVO::class, $result);
        $nombres = array_map(fn(AmbienteVO $vo) => $vo->nombre, $result);
        $this->assertContains('TestRepoActivo1', $nombres);
        $this->assertContains('TestRepoActivo2', $nombres);
        $this->assertNotContains('TestRepoInactivo', $nombres);
    }

    public function test_obtener_ambientes_desarrollo_excludes_inactive_records(): void
    {
        // Arrange: Clear seed data and insert only inactive records
        AmbienteDesarrolloModel::query()->delete();
        
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoInactivo1',
            'ind_activo' => 0,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoInactivo2',
            'ind_activo' => 0,
        ]);

        // Act
        $result = $this->repository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertEmpty($result);
    }

    public function test_obtener_ambientes_desarrollo_orders_by_id_asc(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();
        
        $ambiente3 = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoOrder3',
            'ind_activo' => 1,
        ]);
        $ambiente1 = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoOrder1',
            'ind_activo' => 1,
        ]);
        $ambiente2 = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoOrder2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->repository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertCount(3, $result);
        // Results should be ordered by ID in ascending order
        $this->assertLessThan($result[1]->id, $result[0]->id);
        $this->assertLessThan($result[2]->id, $result[1]->id);
    }

    public function test_obtener_ambientes_desarrollo_returns_array_of_ambiente_vo(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();
        
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoVO1',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoVO2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->repository->obtenerAmbientesDesarrollo();

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
        $result = $this->repository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_obtener_ambientes_desarrollo_maps_correctly_to_value_object(): void
    {
        // Arrange: Clear seed data and insert test record
        AmbienteDesarrolloModel::query()->delete();
        
        $model = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoMapping',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->repository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertCount(1, $result);
        $ambiente = $result[0];
        $this->assertSame($model->id_nu_ambiente_desarrollo, $ambiente->id);
        $this->assertSame($model->sn_nombre, $ambiente->nombre);
    }
}
