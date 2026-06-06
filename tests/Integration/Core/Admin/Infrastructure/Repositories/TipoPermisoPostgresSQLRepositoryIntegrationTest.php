<?php

declare(strict_types=1);

namespace Tests\Integration\Core\Admin\Infrastructure\Repositories;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoPermisoPostgresSQLRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TipoPermisoPostgresSQLRepositoryIntegrationTest
 * 
 * Tests de integración para TipoPermisoPostgresSQLRepository
 */
final class TipoPermisoPostgresSQLRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TipoPermisoPostgresSQLRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TipoPermisoPostgresSQLRepository();
    }

    public function test_buscar_todos_retorna_solo_tipos_permiso_activos(): void
    {
        // Arrange
        $this->seedTiposPermiso();

        // Act
        $resultado = $this->repository->buscarTodos();

        // Assert
        $this->assertIsArray($resultado);
        $this->assertCount(4, $resultado);
        
        foreach ($resultado as $tipo) {
            $this->assertTrue($tipo['activo']);
        }
    }

    public function test_buscar_todos_retorna_array_vacio_si_no_hay_tipos_permiso(): void
    {
        // Act
        $resultado = $this->repository->buscarTodos();

        // Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_buscar_por_id_retorna_tipo_permiso_existente(): void
    {
        // Arrange
        $this->seedTiposPermiso();

        // Act
        $resultado = $this->repository->buscarPorId(1);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('id', $resultado);
        $this->assertArrayHasKey('nombre', $resultado);
        $this->assertArrayHasKey('activo', $resultado);
        $this->assertArrayHasKey('descripcion', $resultado);
        $this->assertEquals(1, $resultado['id']);
        $this->assertEquals('Consulta', $resultado['nombre']);
    }

    public function test_buscar_por_id_retorna_null_si_no_existe(): void
    {
        // Act
        $resultado = $this->repository->buscarPorId(999);

        // Assert
        $this->assertNull($resultado);
    }

    public function test_buscar_todos_incluyendo_inactivos_retorna_todos(): void
    {
        // Arrange
        $this->seedTiposPermisoConInactivos();

        // Act
        $resultado = $this->repository->buscarTodosIncluyendoInactivos();

        // Assert
        $this->assertIsArray($resultado);
        $this->assertCount(5, $resultado); // 4 activos + 1 inactivo
    }

    public function test_buscar_todos_ordena_por_nombre(): void
    {
        // Arrange
        $this->seedTiposPermiso();

        // Act
        $resultado = $this->repository->buscarTodos();

        // Assert
        $nombres = array_column($resultado, 'nombre');
        $this->assertEquals(['Cambios', 'Consulta', 'Consulta y Cambios', 'Eliminación'], $nombres);
    }

    /**
     * Seed de tipos de permiso de prueba (solo activos)
     */
    private function seedTiposPermiso(): void
    {
        DB::table('tb_cat_tipo_permiso')->insert([
            [
                'id_nu_tipo_permiso' => 1,
                'ln_nombre' => 'Consulta',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso de solo lectura'
            ],
            [
                'id_nu_tipo_permiso' => 2,
                'ln_nombre' => 'Cambios',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso de modificación'
            ],
            [
                'id_nu_tipo_permiso' => 3,
                'ln_nombre' => 'Eliminación',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso de eliminación'
            ],
            [
                'id_nu_tipo_permiso' => 4,
                'ln_nombre' => 'Consulta y Cambios',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso combinado'
            ]
        ]);
    }

    /**
     * Seed de tipos de permiso con inactivos
     */
    private function seedTiposPermisoConInactivos(): void
    {
        $this->seedTiposPermiso();
        
        DB::table('tb_cat_tipo_permiso')->insert([
            'id_nu_tipo_permiso' => 5,
            'ln_nombre' => 'Admin Total',
            'ind_activo' => false,
            'sn_descripcion' => 'Permiso deshabilitado'
        ]);
    }
}
