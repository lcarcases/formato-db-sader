<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\EsquemaOutPort;
use App\Core\Admin\Application\UseCases\ObtenerEsquemasPorHostnameUseCase;
use App\Core\Admin\Domain\Exceptions\HostnameNotFoundException;
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ObtenerEsquemasPorHostnameUseCase
 *
 * Tests use case logic with mocked OutPort:
 * - non-empty list<EsquemaVO> from the port is returned unchanged (no "Todos" here)
 * - empty [] from the port is returned as [] (no exception)
 * - null from the port raises HostnameNotFoundException carrying the same idHostname
 */
final class ObtenerEsquemasPorHostnameUseCaseTest extends TestCase
{
    public function test_execute_returns_esquemas_unchanged_when_port_returns_non_empty_list(): void
    {
        // Arrange
        $expectedEsquemas = [
            new EsquemaVO(id: 1, nombre: 'ap_activemq_pd'),
            new EsquemaVO(id: 2, nombre: 'ap_apoyos_pd'),
        ];

        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->expects($this->once())
            ->method('obtenerEsquemasPorHostname')
            ->with(2)
            ->willReturn($expectedEsquemas);

        $useCase = new ObtenerEsquemasPorHostnameUseCase($esquemaOutPort);

        // Act
        $result = $useCase->execute(2);

        // Assert
        $this->assertSame($expectedEsquemas, $result);
    }

    public function test_execute_returns_empty_array_when_port_returns_empty_array(): void
    {
        // Arrange
        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->method('obtenerEsquemasPorHostname')
            ->with(1)
            ->willReturn([]);

        $useCase = new ObtenerEsquemasPorHostnameUseCase($esquemaOutPort);

        // Act
        $result = $useCase->execute(1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_execute_throws_hostname_not_found_exception_when_port_returns_null(): void
    {
        // Arrange
        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->method('obtenerEsquemasPorHostname')
            ->with(999)
            ->willReturn(null);

        $useCase = new ObtenerEsquemasPorHostnameUseCase($esquemaOutPort);

        // Act & Assert
        $this->expectException(HostnameNotFoundException::class);

        $useCase->execute(999);
    }

    public function test_execute_exception_carries_the_same_id_hostname(): void
    {
        // Arrange
        $esquemaOutPort = $this->createMock(EsquemaOutPort::class);
        $esquemaOutPort->method('obtenerEsquemasPorHostname')
            ->with(999)
            ->willReturn(null);

        $useCase = new ObtenerEsquemasPorHostnameUseCase($esquemaOutPort);

        // Act & Assert
        try {
            $useCase->execute(999);
            $this->fail('Expected HostnameNotFoundException was not thrown.');
        } catch (HostnameNotFoundException $ex) {
            $this->assertStringContainsString('999', $ex->getMessage());
        }
    }
}
