<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPersonalPostgresSQLRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TipoPersonalPostgresSQLRepositoryIntegrationTest
 * 
 * Integration tests for TipoPersonalPostgresSQLRepository.
 * 
 * Tests Database Integration:
 * - Query execution with PostgreSQL
 * - Active filtering (ind_activo = true)
 * - Ordering (id ASC)
 * - Field mapping
 * - Edge cases (empty table, inactive records)
 * 
 * ✅ Integration Test Pattern:
 * - Uses RefreshDatabase trait
 * - Tests real database queries
 * - Verifies SQL behavior
 */
final class TipoPersonalPostgresSQLRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TipoPersonalPostgresSQLRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TipoPersonalPostgresSQLRepository();
    }

    public function test_it_returns_all_active_tipos_personal_ordered_by_id(): void
    {
        // Arrange - Insert test data
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Base', 'sn_descripcion' => 'Personal de base', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Enlace', 'sn_descripcion' => 'Personal de enlace', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Confianza', 'sn_descripcion' => 'Personal de confianza', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertCount(3, $result);
        $this->assertIsArray($result);

        // Verify ordering by id ASC
        $this->assertTrue($result[0]->id_nu_tipo_personal < $result[1]->id_nu_tipo_personal);
        $this->assertTrue($result[1]->id_nu_tipo_personal < $result[2]->id_nu_tipo_personal);

        // Verify first record
        $this->assertSame('Base', $result[0]->sn_nombre);
        $this->assertSame('Personal de base', $result[0]->sn_descripcion);
        $this->assertTrue($result[0]->ind_activo);
    }

    public function test_it_filters_out_inactive_tipos_personal(): void
    {
        // Arrange - Insert active and inactive records
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Activo1', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Inactivo', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Activo2', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert - should only return 2 active records
        $this->assertCount(2, $result);
        $this->assertSame('Activo1', $result[0]->sn_nombre);
        $this->assertSame('Activo2', $result[1]->sn_nombre);

        // Verify inactive record is not included
        $nombres = array_map(fn($tipo) => $tipo->sn_nombre, $result);
        $this->assertNotContains('Inactivo', $nombres);
    }

    public function test_it_returns_empty_array_when_no_active_tipos_personal_exist(): void
    {
        // Arrange - Insert only inactive records
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Inactivo1', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Inactivo2', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_empty_array_when_table_is_empty(): void
    {
        // Arrange - no data inserted

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_all_six_required_fields(): void
    {
        // Arrange
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Test', 'sn_descripcion' => 'Test description', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertCount(1, $result);
        $tipo = $result[0];

        // Verify all 6 fields are present
        $this->assertObjectHasProperty('id_nu_tipo_personal', $tipo);
        $this->assertObjectHasProperty('sn_nombre', $tipo);
        $this->assertObjectHasProperty('sn_descripcion', $tipo);
        $this->assertObjectHasProperty('ind_activo', $tipo);
        $this->assertObjectHasProperty('created_at', $tipo);
        $this->assertObjectHasProperty('updated_at', $tipo);

        // Verify types
        $this->assertIsInt($tipo->id_nu_tipo_personal);
        $this->assertIsString($tipo->sn_nombre);
        $this->assertIsBool($tipo->ind_activo);
    }

    public function test_it_handles_null_descripcion_correctly(): void
    {
        // Arrange
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'SinDescripcion', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->sn_descripcion);
    }

    public function test_it_finds_tipo_personal_by_id(): void
    {
        // Arrange
        $id = \DB::table('tb_cat_tipo_personal')->insertGetId([
            'sn_nombre' => 'FindById',
            'sn_descripcion' => 'Find by ID test',
            'ind_activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Act
        $result = $this->repository->buscarPorId($id);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($id, $result->id_nu_tipo_personal);
        $this->assertSame('FindById', $result->sn_nombre);
        $this->assertSame('Find by ID test', $result->sn_descripcion);
        $this->assertTrue($result->ind_activo);
    }

    public function test_it_returns_null_when_tipo_personal_not_found_by_id(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $result = $this->repository->buscarPorId($nonExistentId);

        // Assert
        $this->assertNull($result);
    }

    public function test_it_counts_active_tipos_personal(): void
    {
        // Arrange
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Active1', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Active2', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Inactive', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $count = $this->repository->contarActivos();

        // Assert
        $this->assertSame(2, $count);
    }

    public function test_it_returns_zero_when_no_active_tipos_personal_to_count(): void
    {
        // Arrange - insert only inactive
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Inactive', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $count = $this->repository->contarActivos();

        // Assert
        $this->assertSame(0, $count);
    }
}
