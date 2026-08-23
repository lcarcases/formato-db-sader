<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Admin\Api;

use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ObtenerTiposPersonalApiTest
 *
 * Feature tests for GET /api/v1/admin/tipos-personal endpoint.
 *
 * Tests API Contract:
 * - HTTP 200 response with data
 * - HTTP 429 rate limiting (60 req/min)
 * - Response structure validation
 * - Edge cases (empty data)
 * - Error scenarios
 *
 * ✅ Feature Test Pattern:
 * - Uses RefreshDatabase
 * - Tests complete request/response flow
 * - Validates JSON contract
 */
final class ObtenerTiposPersonalApiTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/admin/tipos-personal';

    public function test_it_returns_200_with_tipos_personal_list(): void
    {
        // Arrange - insert test data
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Base', 'sn_descripcion' => 'Personal de base', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Enlace', 'sn_descripcion' => 'Personal de enlace', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'code',
                'data' => [
                    'tiposPersonal' => [
                        '*' => ['id', 'nombre'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tipos de personal obtenidos exitosamente.',
                'code' => 200,
            ]);

        // Verify data content
        $this->assertCount(2, $response->json('data.tiposPersonal'));
        $this->assertSame('Base', $response->json('data.tiposPersonal.0.nombre'));
        $this->assertSame('Enlace', $response->json('data.tiposPersonal.1.nombre'));
    }

    public function test_it_returns_only_active_tipos_personal(): void
    {
        // Arrange - insert active and inactive
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Activo', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Inactivo', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(200);
        $tiposPersonal = $response->json('data.tiposPersonal');

        $this->assertCount(1, $tiposPersonal);
        $this->assertSame('Activo', $tiposPersonal[0]['nombre']);

        // Verify inactive not included
        $nombres = array_column($tiposPersonal, 'nombre');
        $this->assertNotContains('Inactivo', $nombres);
    }

    public function test_it_returns_empty_array_when_no_active_tipos_personal_exist(): void
    {
        // Arrange - insert only inactive
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Inactivo', 'sn_descripcion' => null, 'ind_activo' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tipos de personal obtenidos exitosamente.',
                'code' => 200,
                'data' => [
                    'tiposPersonal' => [],
                ],
            ]);
    }

    public function test_it_returns_tipos_personal_ordered_by_id_ascending(): void
    {
        // Arrange
        $id1 = \DB::table('tb_cat_tipo_personal')->insertGetId([
            'sn_nombre' => 'Tercero',
            'sn_descripcion' => null,
            'ind_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_nu_tipo_personal');
        $id2 = \DB::table('tb_cat_tipo_personal')->insertGetId([
            'sn_nombre' => 'Primero',
            'sn_descripcion' => null,
            'ind_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_nu_tipo_personal');
        $id3 = \DB::table('tb_cat_tipo_personal')->insertGetId([
            'sn_nombre' => 'Segundo',
            'sn_descripcion' => null,
            'ind_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_nu_tipo_personal');

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(200);
        $tiposPersonal = $response->json('data.tiposPersonal');

        $this->assertSame($id1, $tiposPersonal[0]['id']);
        $this->assertSame($id2, $tiposPersonal[1]['id']);
        $this->assertSame($id3, $tiposPersonal[2]['id']);

        // Verify ordered by id (not by nombre)
        $this->assertSame('Tercero', $tiposPersonal[0]['nombre']);
        $this->assertSame('Primero', $tiposPersonal[1]['nombre']);
        $this->assertSame('Segundo', $tiposPersonal[2]['nombre']);
    }

    public function it_returns_correct_response_structure(): void
    {
        // Arrange
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Test', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert - validate exact structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'code',
                'data' => [
                    'tiposPersonal',
                ],
            ]);

        $json = $response->json();

        // Verify root level keys
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('code', $json);
        $this->assertArrayHasKey('data', $json);

        // Verify data level keys
        $this->assertArrayHasKey('tiposPersonal', $json['data']);

        // Verify tipos personal item structure
        $this->assertArrayHasKey('id', $json['data']['tiposPersonal'][0]);
        $this->assertArrayHasKey('nombre', $json['data']['tiposPersonal'][0]);

        // Verify no extra fields in item
        $this->assertCount(2, $json['data']['tiposPersonal'][0]);
    }

    public function test_it_returns_json_content_type(): void
    {
        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_it_applies_rate_limiting_after_60_requests(): void
    {
        // Arrange - insert test data
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Test', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act - make 60 requests (should succeed)
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson($this->endpoint);
            $response->assertStatus(200);
        }

        // Act - 61st request should be rate limited
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(429); // Too Many Requests
    }

    public function test_it_returns_500_when_database_connection_fails(): void
    {
        // Arrange - simulate DB failure at the OutPort level
        $this->mock(
            ITipoPersonalOutPort::class,
            function ($mock) {
                $mock->shouldReceive('obtenerTodos')
                    ->andThrow(new \RuntimeException('Database connection error'));
            }
        );

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'code' => 500,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'code',
            ]);
    }

    public function test_it_uses_named_route_api_admin_tipos_personal_index(): void
    {
        // Act
        $url = route('api.admin.tipos-personal.index');

        // Assert
        $this->assertSame('http://localhost/api/v1/admin/tipos-personal', $url);
    }

    public function test_it_returns_all_four_catalog_tipos_personal(): void
    {
        // Arrange - seed with production catalog
        \DB::table('tb_cat_tipo_personal')->insert([
            ['sn_nombre' => 'Base', 'sn_descripcion' => 'Personal de base', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Enlace', 'sn_descripcion' => 'Personal de enlace', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Confianza', 'sn_descripcion' => 'Personal de confianza', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['sn_nombre' => 'Externo', 'sn_descripcion' => 'Personal externo', 'ind_activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Act
        $response = $this->getJson($this->endpoint);

        // Assert
        $response->assertStatus(200);
        $tiposPersonal = $response->json('data.tiposPersonal');

        $this->assertCount(4, $tiposPersonal);

        $nombres = array_column($tiposPersonal, 'nombre');
        $this->assertContains('Base', $nombres);
        $this->assertContains('Enlace', $nombres);
        $this->assertContains('Confianza', $nombres);
        $this->assertContains('Externo', $nombres);
    }
}
