<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Admin\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ObtenerTiposPermisoApiTest
 *
 * Tests de feature para el endpoint GET /api/v1/admin/tipos-permiso
 */
final class ObtenerTiposPermisoApiTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/admin/tipos-permiso';

    public function test_obtener_tipos_permiso_retorna_200_con_datos_correctos(): void
    {
        // Arrange
        $this->seedTiposPermiso();

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'tiposPermiso' => [
                    '*' => [
                        'id',
                        'nombre',
                        'activo',
                        'descripcion',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Se obtuvieron los tipos de permiso correctamente.');

        $data = $response->json('data.tiposPermiso');
        $this->assertCount(4, $data);
    }

    public function test_obtener_tipos_permiso_retorna_solo_activos(): void
    {
        // Arrange
        $this->seedTiposPermisoConInactivos();

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);

        $tiposPermiso = $response->json('data.tiposPermiso');
        $this->assertCount(4, $tiposPermiso);

        foreach ($tiposPermiso as $tipo) {
            $this->assertTrue($tipo['activo']);
        }
    }

    public function test_obtener_tipos_permiso_retorna_datos_ordenados_por_nombre(): void
    {
        // Arrange
        $this->seedTiposPermiso();

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);

        $tiposPermiso = $response->json('data.tiposPermiso');
        $ids = array_column($tiposPermiso, 'id');

        // BR-002: Results must be ordered by ID ascending
        $this->assertEquals([1, 2, 3, 4], $ids);

        // Verify the names match the seed data in ID order
        $nombres = array_column($tiposPermiso, 'nombre');
        $this->assertEquals([
            'Consulta',           // ID 1
            'Cambios',            // ID 2
            'Eliminación',        // ID 3
            'Consulta y Cambios',  // ID 4
        ], $nombres);
    }

    public function test_obtener_tipos_permiso_retorna_200_con_array_vacio_si_no_hay_datos(): void
    {
        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.tiposPermiso', []);
    }

    public function test_obtener_tipos_permiso_incluye_todos_los_campos_requeridos(): void
    {
        // Arrange
        DB::table('tb_cat_tipo_permiso')->insert([
            'id_nu_tipo_permiso' => 1,
            'ln_nombre' => 'Consulta',
            'ind_activo' => true,
            'sn_descripcion' => 'Permiso de solo lectura',
        ]);

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);

        $tipoPermiso = $response->json('data.tiposPermiso.0');

        $this->assertEquals(1, $tipoPermiso['id']);
        $this->assertEquals('Consulta', $tipoPermiso['nombre']);
        $this->assertTrue($tipoPermiso['activo']);
        $this->assertEquals('Permiso de solo lectura', $tipoPermiso['descripcion']);
    }

    public function test_obtener_tipos_permiso_acepta_descripcion_null(): void
    {
        // Arrange
        DB::table('tb_cat_tipo_permiso')->insert([
            'id_nu_tipo_permiso' => 1,
            'ln_nombre' => 'Consulta',
            'ind_activo' => true,
            'sn_descripcion' => null,
        ]);

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);

        $tipoPermiso = $response->json('data.tiposPermiso.0');
        $this->assertNull($tipoPermiso['descripcion']);
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
                'sn_descripcion' => 'Permiso de solo lectura',
            ],
            [
                'id_nu_tipo_permiso' => 2,
                'ln_nombre' => 'Cambios',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso de modificación',
            ],
            [
                'id_nu_tipo_permiso' => 3,
                'ln_nombre' => 'Eliminación',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso de eliminación',
            ],
            [
                'id_nu_tipo_permiso' => 4,
                'ln_nombre' => 'Consulta y Cambios',
                'ind_activo' => true,
                'sn_descripcion' => 'Permiso combinado',
            ],
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
            'sn_descripcion' => 'Permiso deshabilitado',
        ]);
    }
}
