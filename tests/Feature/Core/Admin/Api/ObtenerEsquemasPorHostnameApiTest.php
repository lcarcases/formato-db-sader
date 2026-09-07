<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Admin\Api;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for GET /api/v1/admin/hostnames/{idHostname}/esquemas endpoint
 *
 * Tests API contract compliance and integration, per
 * specs/006-catalogo-esquemas-hostname/contracts/esquemas-api.md
 *
 * NOTE: No test in this class ever sends an Authorization header — this
 * documents FR-011 (the endpoint requires no authentication).
 */
final class ObtenerEsquemasPorHostnameApiTest extends TestCase
{
    use RefreshDatabase;

    private const NOMBRES_ESPERADOS = [
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
    ];

    private function endpoint(int $idHostname): string
    {
        return "/api/v1/admin/hostnames/{$idHostname}/esquemas";
    }

    private function assertTodosYDieciseisEsquemas(int $idHostname): void
    {
        // Act
        $response = $this->getJson($this->endpoint($idHostname));

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
        $response->assertJsonPath('message', 'Se obtuvieron los esquemas del hostname correctamente.');

        $data = $response->json('data');
        $this->assertCount(17, $data); // "Todos" + 16 esquemas

        $this->assertSame(['id' => 0, 'nombre' => 'Todos'], $data[0]);

        $nombresReales = array_column(array_slice($data, 1), 'nombre');
        $this->assertSame(self::NOMBRES_ESPERADOS, $nombresReales);
    }

    public function test_endpoint_returns_200_with_todos_and_sixteen_esquemas_for_sridesbds09(): void
    {
        $this->assertTodosYDieciseisEsquemas(2);
    }

    public function test_endpoint_returns_200_with_todos_and_sixteen_esquemas_for_sriprdbdsmz02(): void
    {
        $this->assertTodosYDieciseisEsquemas(4);
    }

    public function test_endpoint_returns_200_with_todos_and_sixteen_esquemas_for_sriqabds08(): void
    {
        $this->assertTodosYDieciseisEsquemas(7);
    }

    public function test_endpoint_returns_200_with_only_todos_for_hostname_without_associations(): void
    {
        // Arrange - hostname id 1 (pgrdesbds09) is seeded but has no esquema associations

        // Act
        $response = $this->getJson($this->endpoint(1));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data', [
            ['id' => 0, 'nombre' => 'Todos'],
        ]);
    }

    public function test_endpoint_returns_404_for_nonexistent_hostname(): void
    {
        // Act
        $response = $this->getJson($this->endpoint(999));

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'El hostname solicitado no existe.');
        $response->assertJsonPath('data', []);
    }

    public function test_db_failure_returns_500_with_generic_error_envelope(): void
    {
        // Arrange - force a failure by overriding the OutPort binding for this test only.
        $this->app->bind(EsquemaOutPort::class, fn () => new class implements EsquemaOutPort
        {
            public function obtenerEsquemas(): array
            {
                return [];
            }

            public function obtenerEsquemasPorHostname(int $idHostname): ?array
            {
                throw new \RuntimeException('DB down');
            }
        });

        // Act
        $response = $this->getJson($this->endpoint(2));

        // Assert
        $response->assertStatus(500);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Error mientras se intentaba obtener los esquemas del hostname.');
        $response->assertJsonPath('data', []);
    }

    public function test_post_method_returns_405(): void
    {
        $this->postJson($this->endpoint(2))->assertStatus(405);
    }

    public function test_put_method_returns_405(): void
    {
        $this->putJson($this->endpoint(2))->assertStatus(405);
    }

    public function test_delete_method_returns_405(): void
    {
        $this->deleteJson($this->endpoint(2))->assertStatus(405);
    }

    public function test_endpoint_does_not_require_authentication(): void
    {
        // Act - No Authorization header or token sent
        $response = $this->getJson($this->endpoint(2));

        // Assert - Should succeed without auth
        $response->assertStatus(200);
    }
}
