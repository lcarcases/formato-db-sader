<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\AmbienteDesarrolloModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\AmbienteDesarrolloRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration tests for AmbienteDesarrolloRepository
 *
 * Tests repository interaction with PostgreSQL database.
 * Repository returns RAW data (Eloquent models) — mapping to domain
 * objects is the OutAdapter's responsibility.
 */
final class AmbienteDesarrolloRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AmbienteDesarrolloRepository $ambienteDesarrolloRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ambienteDesarrolloRepository = new AmbienteDesarrolloRepository;
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
        $result = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(AmbienteDesarrolloModel::class, $result);
        $nombres = array_map(fn (AmbienteDesarrolloModel $model) => $model->sn_nombre, $result);
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
        $result = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertEmpty($result);
    }

    public function test_obtener_ambientes_desarrollo_orders_by_id_asc(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();

        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoOrder3',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoOrder1',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoOrder2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertCount(3, $result);
        // Results should be ordered by ID in ascending order
        $this->assertLessThan($result[1]->id_nu_ambiente_desarrollo, $result[0]->id_nu_ambiente_desarrollo);
        $this->assertLessThan($result[2]->id_nu_ambiente_desarrollo, $result[1]->id_nu_ambiente_desarrollo);
    }

    public function test_obtener_ambientes_desarrollo_returns_array_of_models(): void
    {
        // Arrange: Clear seed data and insert test records
        AmbienteDesarrolloModel::query()->delete();

        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoModel1',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoModel2',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        foreach ($result as $ambiente) {
            $this->assertInstanceOf(AmbienteDesarrolloModel::class, $ambiente);
            $this->assertGreaterThan(0, $ambiente->id_nu_ambiente_desarrollo);
            $this->assertNotEmpty($ambiente->sn_nombre);
        }
    }

    public function test_obtener_ambientes_desarrollo_returns_empty_array_when_no_active_records(): void
    {
        // Arrange: Clear seed data - no active records
        AmbienteDesarrolloModel::query()->delete();

        // Act
        $result = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_obtener_ambientes_desarrollo_returns_raw_model_data(): void
    {
        // Arrange: Clear seed data and insert test record
        AmbienteDesarrolloModel::query()->delete();

        $model = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestRepoRaw',
            'ind_activo' => 1,
        ]);

        // Act
        $result = $this->ambienteDesarrolloRepository->obtenerAmbientesDesarrollo();

        // Assert
        $this->assertCount(1, $result);
        $ambiente = $result[0];
        $this->assertSame($model->id_nu_ambiente_desarrollo, $ambiente->id_nu_ambiente_desarrollo);
        $this->assertSame($model->sn_nombre, $ambiente->sn_nombre);
    }
}
