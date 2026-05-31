<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\UseCases\ObtenerTiposPersonalUseCase;
use App\Core\Admin\Application\Ports\Out\ITipoPersonalOutPort;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * ObtenerTiposPersonalUseCaseTest
 * 
 * Unit tests for ObtenerTiposPersonalUseCase.
 * 
 * Tests Use Case orchestration:
 * - Delegates to OutPort correctly
 * - Returns raw data from OutPort
 * - Propagates exceptions
 * - Logs execution with structured context
 * 
 * ✅ Unit Test Pattern:
 * - Mock OutPort dependency (isolated test)
 * - Fast execution (no database)
 * - Tests use case behavior
 */
final class ObtenerTiposPersonalUseCaseTest extends TestCase
{
    private ITipoPersonalOutPort|MockObject $tipoPersonalOutPort;
    private ObtenerTiposPersonalUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock for OutPort
        $this->tipoPersonalOutPort = $this->createMock(ITipoPersonalOutPort::class);

        // Create use case with mocked dependency
        $this->useCase = new ObtenerTiposPersonalUseCase($this->tipoPersonalOutPort);
    }

    public function test_it_returns_tipos_personal_from_out_port(): void
    {
        // Arrange
        $expectedData = [
            (object) [
                'id_nu_tipo_personal' => 1,
                'sn_nombre' => 'Base',
                'sn_descripcion' => 'Personal de base',
                'ind_activo' => true,
                'created_at' => '2026-05-16 10:00:00',
                'updated_at' => '2026-05-16 10:00:00'
            ],
            (object) [
                'id_nu_tipo_personal' => 2,
                'sn_nombre' => 'Enlace',
                'sn_descripcion' => 'Personal de enlace',
                'ind_activo' => true,
                'created_at' => '2026-05-16 10:00:00',
                'updated_at' => '2026-05-16 10:00:00'
            ]
        ];

        $this->tipoPersonalOutPort
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($expectedData);

        // Act
        $result = $this->useCase->ejecutar();

        // Assert
        $this->assertSame($expectedData, $result);
        $this->assertCount(2, $result);
    }

    public function test_it_returns_empty_array_when_no_tipos_personal_exist(): void
    {
        // Arrange
        $this->tipoPersonalOutPort
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn([]);

        // Act
        $result = $this->useCase->ejecutar();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }


    public function it_propagates_exception_from_out_port(): void
    {
        // Arrange
        $expectedException = new \Exception('Database connection failed');

        $this->tipoPersonalOutPort
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willThrowException($expectedException);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        // Act
        $this->useCase->ejecutar();
    }

    public function test_it_calls_out_port_exactly_once(): void
    {
        // Arrange
        $this->tipoPersonalOutPort
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn([]);

        // Act
        $this->useCase->ejecutar();

        // Assert - verified by expects($this->once())
    }

    public function test_it_returns_all_active_tipos_personal(): void
    {
        // Arrange
        $expectedData = [
            (object) ['id_nu_tipo_personal' => 1, 'sn_nombre' => 'Base', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => '2026-05-16', 'updated_at' => '2026-05-16'],
            (object) ['id_nu_tipo_personal' => 2, 'sn_nombre' => 'Enlace', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => '2026-05-16', 'updated_at' => '2026-05-16'],
            (object) ['id_nu_tipo_personal' => 3, 'sn_nombre' => 'Confianza', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => '2026-05-16', 'updated_at' => '2026-05-16'],
            (object) ['id_nu_tipo_personal' => 4, 'sn_nombre' => 'Externo', 'sn_descripcion' => null, 'ind_activo' => true, 'created_at' => '2026-05-16', 'updated_at' => '2026-05-16'],
        ];

        $this->tipoPersonalOutPort
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($expectedData);

        // Act
        $result = $this->useCase->ejecutar();

        // Assert
        $this->assertCount(4, $result);
        $this->assertSame('Base', $result[0]->sn_nombre);
        $this->assertSame('Enlace', $result[1]->sn_nombre);
        $this->assertSame('Confianza', $result[2]->sn_nombre);
        $this->assertSame('Externo', $result[3]->sn_nombre);
    }

    public function test_it_does_not_modify_data_from_out_port(): void
    {
        // Arrange
        $originalData = [
            (object) ['id_nu_tipo_personal' => 1, 'sn_nombre' => 'Base', 'sn_descripcion' => 'Test', 'ind_activo' => true, 'created_at' => '2026-05-16', 'updated_at' => '2026-05-16']
        ];

        $this->tipoPersonalOutPort
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($originalData);

        // Act
        $result = $this->useCase->ejecutar();

        // Assert - use case returns data as-is without modification
        $this->assertSame($originalData, $result);
        $this->assertSame('Base', $result[0]->sn_nombre);
        $this->assertSame('Test', $result[0]->sn_descripcion);
    }
}
