<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use App\Core\Admin\Application\UseCases\ObtenerEsquemasUseCase;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ObtenerEsquemasUseCase
 *
 * Tests use case logic with mocked OutPort
 */
final class ObtenerEsquemasUseCaseTest extends TestCase
{
    public function test_execute_invokes_out_port(): void
    {
        // Arrange
        $expectedEsquemas = [
            new EsquemaVO(id: 1, nombre: 'ap_activemq_pd'),
            new EsquemaVO(id: 2, nombre: 'ap_apoyos_pd'),
        ];

        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->expects($this->once())
            ->method('obtenerEsquemas')
            ->willReturn($expectedEsquemas);

        $useCase = new ObtenerEsquemasUseCase($esquemaOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($expectedEsquemas, $result);
    }

    public function test_execute_returns_array_of_esquema_vo(): void
    {
        // Arrange
        $expectedEsquemas = [
            new EsquemaVO(id: 1, nombre: 'ap_activemq_pd'),
            new EsquemaVO(id: 2, nombre: 'ap_apoyos_pd'),
            new EsquemaVO(id: 3, nombre: 'ap_biometricos_pd'),
        ];

        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->method('obtenerEsquemas')
            ->willReturn($expectedEsquemas);

        $useCase = new ObtenerEsquemasUseCase($esquemaOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(EsquemaVO::class, $result);
    }

    public function test_execute_handles_empty_array_from_out_port(): void
    {
        // Arrange
        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->method('obtenerEsquemas')
            ->willReturn([]);

        $useCase = new ObtenerEsquemasUseCase($esquemaOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_execute_returns_unmodified_array_from_out_port(): void
    {
        // Arrange
        $esquema1 = new EsquemaVO(id: 5, nombre: 'Esquema5');
        $esquema2 = new EsquemaVO(id: 10, nombre: 'Esquema10');
        $expectedEsquemas = [$esquema1, $esquema2];

        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->method('obtenerEsquemas')
            ->willReturn($expectedEsquemas);

        $useCase = new ObtenerEsquemasUseCase($esquemaOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($esquema1, $result[0]);
        $this->assertSame($esquema2, $result[1]);
    }
}
