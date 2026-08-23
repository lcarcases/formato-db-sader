<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPermisoPostgresSQLRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TipoPermisoPostgresSQLRepositoryIntegrationTest
 *
 * Integration tests for TipoPermisoPostgresSQLRepository.
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
final class TipoPermisoPostgresSQLRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TipoPermisoPostgresSQLRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TipoPermisoPostgresSQLRepository;
    }

    public function test_it_retrieves_all_active_tipos_permiso_ordered_by_id(): void
    {
        // Arrange - Insert test data
        \DB::table('tb_cat_tipo_permiso')->insert([
            ['ln_nombre' => 'Consulta', 'sn_descripcion' => 'Permiso de consulta', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ln_nombre' => 'Cambios', 'sn_descripcion' => 'Permiso de cambios', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ln_nombre' => 'Eliminación', 'sn_descripcion' => 'Permiso de eliminación', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertCount(3, $result);
        $this->assertIsArray($result);

        // Verify ordering by id ASC
        $this->assertTrue($result[0]->id_nu_tipo_permiso < $result[1]->id_nu_tipo_permiso);
        $this->assertTrue($result[1]->id_nu_tipo_permiso < $result[2]->id_nu_tipo_permiso);

        // Verify first record
        $this->assertSame('Consulta', $result[0]->ln_nombre);
        $this->assertSame('Permiso de consulta', $result[0]->sn_descripcion);
        $this->assertTrue($result[0]->ind_activo);
    }

    public function test_it_filters_out_inactive_tipos_permiso(): void
    {
        // Arrange - Insert active and inactive records
        \DB::table('tb_cat_tipo_permiso')->insert([
            ['ln_nombre' => 'Activo1', 'sn_descripcion' => 'Test activo', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ln_nombre' => 'Inactivo1', 'sn_descripcion' => 'Test inactivo', 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
            ['ln_nombre' => 'Activo2', 'sn_descripcion' => 'Test activo', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['ln_nombre' => 'Inactivo2', 'sn_descripcion' => 'Test inactivo', 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert - only active records
        $this->assertCount(2, $result);

        // Verify all returned records are active
        foreach ($result as $item) {
            $this->assertTrue($item->ind_activo);
        }

        // Verify names
        $nombres = array_map(fn ($item) => $item->ln_nombre, $result);
        $this->assertContains('Activo1', $nombres);
        $this->assertContains('Activo2', $nombres);
        $this->assertNotContains('Inactivo1', $nombres);
        $this->assertNotContains('Inactivo2', $nombres);
    }

    public function test_it_returns_empty_array_when_no_active_tipos_permiso_exist(): void
    {
        // Arrange - Insert only inactive records
        \DB::table('tb_cat_tipo_permiso')->insert([
            ['ln_nombre' => 'Consulta', 'sn_descripcion' => 'Test', 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_empty_array_when_table_is_empty(): void
    {
        // Arrange - empty table (RefreshDatabase ensures clean state)

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_handles_null_descripcion(): void
    {
        // Arrange
        \DB::table('tb_cat_tipo_permiso')->insert([
            ['ln_nombre' => 'Consulta', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->sn_descripcion);
    }

    public function test_it_finds_tipo_permiso_by_id(): void
    {
        // Arrange
        $id = \DB::table('tb_cat_tipo_permiso')->insertGetId([
            'ln_nombre' => 'FindById',
            'sn_descripcion' => 'Find by ID test',
            'ind_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_nu_tipo_permiso');

        // Act
        $result = $this->repository->buscarPorId($id);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($id, $result->id_nu_tipo_permiso);
        $this->assertSame('FindById', $result->ln_nombre);
        $this->assertSame('Find by ID test', $result->sn_descripcion);
        $this->assertTrue($result->ind_activo);
    }

    public function test_it_returns_null_when_tipo_permiso_not_found_by_id(): void
    {
        // Arrange - ID that doesn't exist
        $nonExistentId = 99999;

        // Act
        $result = $this->repository->buscarPorId($nonExistentId);

        // Assert
        $this->assertNull($result);
    }

    public function test_it_returns_stdclass_objects_with_correct_properties(): void
    {
        // Arrange
        \DB::table('tb_cat_tipo_permiso')->insert([
            ['ln_nombre' => 'Consulta', 'sn_descripcion' => 'Test', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $result = $this->repository->buscarTodos();

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(\stdClass::class, $result[0]);

        // Verify all expected properties exist
        $this->assertObjectHasProperty('id_nu_tipo_permiso', $result[0]);
        $this->assertObjectHasProperty('ln_nombre', $result[0]);
        $this->assertObjectHasProperty('sn_descripcion', $result[0]);
        $this->assertObjectHasProperty('ind_activo', $result[0]);
        $this->assertObjectHasProperty('created_at', $result[0]);
        $this->assertObjectHasProperty('updated_at', $result[0]);
    }
}
