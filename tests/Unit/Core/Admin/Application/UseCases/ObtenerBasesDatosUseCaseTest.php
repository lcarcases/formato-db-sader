<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\BaseDatosOutPort;
use App\Core\Admin\Application\UseCases\ObtenerBasesDatosUseCase;
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ObtenerBasesDatosUseCase
 *
 * Tests use case logic with mocked OutPort
 */
final class ObtenerBasesDatosUseCaseTest extends TestCase
{
    public function test_execute_invokes_out_port(): void
    {
        // Arrange
        $expectedBasesDatos = [
            new BaseDatosVO(id: 1, nombre: 'PPB'),
            new BaseDatosVO(id: 2, nombre: 'SURI'),
        ];

        $baseDatosOutPort = $this->createMock(BaseDatosOutPort::class);
        $baseDatosOutPort->expects($this->once())
            ->method('obtenerBasesDatos')
            ->willReturn($expectedBasesDatos);

        $useCase = new ObtenerBasesDatosUseCase($baseDatosOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($expectedBasesDatos, $result);
    }

    public function test_execute_returns_array_of_base_datos_vo(): void
    {
        // Arrange
        $expectedBasesDatos = [
            new BaseDatosVO(id: 1, nombre: 'PPB'),
            new BaseDatosVO(id: 2, nombre: 'SURI'),
            new BaseDatosVO(id: 3, nombre: 'XAMAN'),
        ];

        $baseDatosOutPort = $this->createMock(BaseDatosOutPort::class);
        $baseDatosOutPort->method('obtenerBasesDatos')
            ->willReturn($expectedBasesDatos);

        $useCase = new ObtenerBasesDatosUseCase($baseDatosOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(BaseDatosVO::class, $result);
    }

    public function test_execute_handles_empty_array_from_out_port(): void
    {
        // Arrange
        $baseDatosOutPort = $this->createMock(BaseDatosOutPort::class);
        $baseDatosOutPort->method('obtenerBasesDatos')
            ->willReturn([]);

        $useCase = new ObtenerBasesDatosUseCase($baseDatosOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_execute_returns_unmodified_array_from_out_port(): void
    {
        // Arrange
        $baseDatos1 = new BaseDatosVO(id: 5, nombre: 'BaseDatos5');
        $baseDatos2 = new BaseDatosVO(id: 10, nombre: 'BaseDatos10');
        $expectedBasesDatos = [$baseDatos1, $baseDatos2];

        $baseDatosOutPort = $this->createMock(BaseDatosOutPort::class);
        $baseDatosOutPort->method('obtenerBasesDatos')
            ->willReturn($expectedBasesDatos);

        $useCase = new ObtenerBasesDatosUseCase($baseDatosOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($baseDatos1, $result[0]);
        $this->assertSame($baseDatos2, $result[1]);
    }
}
