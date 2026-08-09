<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort;
use App\Core\Admin\Application\UseCases\ObtenerAmbientesUseCase;
use App\Core\Admin\Domain\ValueObjects\AmbienteVO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ObtenerAmbientesUseCase
 *
 * Tests use case logic with mocked OutPort
 */
final class ObtenerAmbientesUseCaseTest extends TestCase
{
    public function test_execute_invokes_out_port(): void
    {
        // Arrange
        $expectedAmbientes = [
            new AmbienteVO(id: 1, nombre: 'Desarrollo'),
            new AmbienteVO(id: 2, nombre: 'QA'),
        ];

        $ambienteDesarrolloOutPort = $this->createMock(AmbienteDesarrolloOutPort::class);
        $ambienteDesarrolloOutPort->expects($this->once())
            ->method('obtenerAmbientesDesarrollo')
            ->willReturn($expectedAmbientes);

        $useCase = new ObtenerAmbientesUseCase($ambienteDesarrolloOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($expectedAmbientes, $result);
    }

    public function test_execute_returns_array_of_ambiente_vo(): void
    {
        // Arrange
        $expectedAmbientes = [
            new AmbienteVO(id: 1, nombre: 'Test1'),
            new AmbienteVO(id: 2, nombre: 'Test2'),
            new AmbienteVO(id: 3, nombre: 'Test3'),
        ];

        $ambienteDesarrolloOutPort = $this->createMock(AmbienteDesarrolloOutPort::class);
        $ambienteDesarrolloOutPort->method('obtenerAmbientesDesarrollo')
            ->willReturn($expectedAmbientes);

        $useCase = new ObtenerAmbientesUseCase($ambienteDesarrolloOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(AmbienteVO::class, $result);
    }

    public function test_execute_handles_empty_array_from_out_port(): void
    {
        // Arrange
        $ambienteDesarrolloOutPort = $this->createMock(AmbienteDesarrolloOutPort::class);
        $ambienteDesarrolloOutPort->method('obtenerAmbientesDesarrollo')
            ->willReturn([]);

        $useCase = new ObtenerAmbientesUseCase($ambienteDesarrolloOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_execute_returns_unmodified_array_from_out_port(): void
    {
        // Arrange
        $ambiente1 = new AmbienteVO(id: 5, nombre: 'Ambiente5');
        $ambiente2 = new AmbienteVO(id: 10, nombre: 'Ambiente10');
        $expectedAmbientes = [$ambiente1, $ambiente2];

        $ambienteDesarrolloOutPort = $this->createMock(AmbienteDesarrolloOutPort::class);
        $ambienteDesarrolloOutPort->method('obtenerAmbientesDesarrollo')
            ->willReturn($expectedAmbientes);

        $useCase = new ObtenerAmbientesUseCase($ambienteDesarrolloOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($ambiente1, $result[0]);
        $this->assertSame($ambiente2, $result[1]);
    }
}
