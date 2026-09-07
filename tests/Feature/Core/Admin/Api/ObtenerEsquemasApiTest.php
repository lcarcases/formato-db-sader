<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Admin\Api;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\EsquemaModel;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Models\HostnameEsquemaModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for GET /api/v1/admin/esquemas endpoint
 *
 * Tests API contract compliance and integration, per
 * specs/006-catalogo-esquemas-hostname/contracts/esquemas-api.md
 *
 * NOTE: No test in this class ever sends an Authorization header — this
 * documents FR-011 (the endpoint requires no authentication).
 */
final class ObtenerEsquemasApiTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/v1/admin/esquemas';

    public function test_endpoint_returns_200_with_the_sixteen_seeded_entries_and_correct_envelope(): void
    {
        // Arrange - the 16 seeded rows already exist via migration

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre'],
            ],
            'message',
            'success',
        ]);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Se obtuvieron los esquemas correctamente.');

        $data = $response->json('data');
        $this->assertCount(16, $data);

        $nombres = array_column($data, 'nombre');
        $this->assertSame([
            'ap_activemq_pd',
            'ap_apoyos_pd',
            'ap_biometricos_pd',
            'ap_gestion_doc',
            'ap_interfaz',
            'ap_inventario_pd',
            'ap_movil_pd',
            'ap_proagro_pd',
            'ap_reportes_suri',
            'ap_supervision_pd',
            'ap_suri_pd',
            'ap_svc',
            'ap_tramites_pd',
            'ap_viaticos',
            'tr_seguridad_pd',
            'tr_suri_pd',
        ], $nombres);
    }

    public function test_data_is_ordered_by_id_ascending(): void
    {
        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');

        $ids = array_column($data, 'id');
        $sortedIds = $ids;
        sort($sortedIds);
        $this->assertSame($sortedIds, $ids);
    }

    public function test_empty_catalog_returns_empty_data_array(): void
    {
        // Arrange
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data', []);
    }

    public function test_post_method_returns_405(): void
    {
        $this->postJson(self::ENDPOINT)->assertStatus(405);
    }

    public function test_put_method_returns_405(): void
    {
        $this->putJson(self::ENDPOINT)->assertStatus(405);
    }

    public function test_delete_method_returns_405(): void
    {
        $this->deleteJson(self::ENDPOINT)->assertStatus(405);
    }

    public function test_inactive_esquemas_are_excluded(): void
    {
        // Arrange
        HostnameEsquemaModel::query()->delete();
        EsquemaModel::query()->delete();
        EsquemaModel::create(['sn_nombre' => 'ActivaTest', 'ind_activo' => 1]);
        EsquemaModel::create(['sn_nombre' => 'InactivaTest', 'ind_activo' => 0]);

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $nombres = array_column($data, 'nombre');

        $this->assertContains('ActivaTest', $nombres);
        $this->assertNotContains('InactivaTest', $nombres);
    }

    public function test_db_failure_returns_500_with_generic_error_envelope(): void
    {
        // Arrange - force a failure by overriding the OutPort binding for this test only.
        // Laravel resets the container automatically between test methods via TestCase,
        // so this override does not leak into other tests.
        $this->app->bind(EsquemaOutPort::class, fn () => new class implements EsquemaOutPort
        {
            public function obtenerEsquemas(): array
            {
                throw new \RuntimeException('DB down');
            }

            public function obtenerEsquemasPorHostname(int $idHostname): ?array
            {
                return null;
            }
        });

        // Act
        $response = $this->getJson(self::ENDPOINT);

        // Assert
        $response->assertStatus(500);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Error mientras se intentaba obtener los esquemas.');
        $response->assertJsonPath('data', []);
    }

    public function test_endpoint_does_not_require_authentication(): void
    {
        // Act - No Authorization header or token sent
        $response = $this->getJson(self::ENDPOINT);

        // Assert - Should succeed without auth
        $response->assertStatus(200);
    }
}
