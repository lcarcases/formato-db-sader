<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\AmbienteDesarrolloModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for GET /api/v1/admin/ambientes-desarrollo endpoint
 *
 * Tests API contract compliance and integration
 */
final class ObtenerAmbientesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_returns_200_status(): void
    {
        // Arrange
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestEnv1',
            'ind_activo' => 1,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertStatus(200);
    }

    public function test_response_has_correct_json_structure(): void
    {
        // Arrange
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestEnv2',
            'ind_activo' => 1,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre'],
            ],
            'message',
            'code',
            'success',
        ]);
    }

    public function test_data_array_contains_objects_with_id_and_nombre_fields(): void
    {
        // Arrange
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestEnv3',
            'ind_activo' => 1,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        foreach ($data as $ambiente) {
            $this->assertArrayHasKey('id', $ambiente);
            $this->assertArrayHasKey('nombre', $ambiente);
            $this->assertIsInt($ambiente['id']);
            $this->assertIsString($ambiente['nombre']);
            $this->assertGreaterThan(0, $ambiente['id']);
            $this->assertNotEmpty($ambiente['nombre']);
        }
    }

    public function test_only_active_ambientes_are_returned(): void
    {
        // Arrange
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestActive',
            'ind_activo' => 1,
        ]);
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestInactive',
            'ind_activo' => 0,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $nombres = array_column($data, 'nombre');
        $this->assertContains('TestActive', $nombres);
        $this->assertNotContains('TestInactive', $nombres);
    }

    public function test_ambientes_are_ordered_by_id(): void
    {
        // Arrange
        $ambiente1 = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestOrder1',
            'ind_activo' => 1,
        ]);
        $ambiente2 = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestOrder2',
            'ind_activo' => 1,
        ]);
        $ambiente3 = AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestOrder3',
            'ind_activo' => 1,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertGreaterThanOrEqual(3, count($data));
        
        // Verify IDs are in ascending order
        $ids = array_column($data, 'id');
        $sortedIds = $ids;
        sort($sortedIds);
        $this->assertEquals($sortedIds, $ids);
    }

    public function test_empty_data_array_when_no_active_ambientes_exist(): void
    {
        // Arrange
        // Create only inactive ambientes
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestInactive1',
            'ind_activo' => 0,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [],
            'success' => true,
        ]);
    }

    public function test_response_format_matches_api_contract(): void
    {
        // Arrange
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestContract',
            'ind_activo' => 1,
        ]);

        // Act
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Ambientes obtenidos exitosamente',
            'code' => '200',
            'success' => true,
        ]);

        $this->assertIsArray($response->json('data'));
    }

    public function test_endpoint_accepts_get_method_only(): void
    {
        // Act & Assert
        $this->postJson('/api/v1/admin/ambientes-desarrollo')
            ->assertStatus(405);

        $this->putJson('/api/v1/admin/ambientes-desarrollo')
            ->assertStatus(405);

        $this->deleteJson('/api/v1/admin/ambientes-desarrollo')
            ->assertStatus(405);
    }

    public function test_endpoint_does_not_require_authentication(): void
    {
        // Arrange
        AmbienteDesarrolloModel::create([
            'sn_nombre' => 'TestAuth',
            'ind_activo' => 1,
        ]);

        // Act - No authentication headers or tokens
        $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');

        // Assert - Should succeed without auth
        $response->assertStatus(200);
    }
}
