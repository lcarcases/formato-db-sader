<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\HostnameOutPort;
use App\Core\Admin\Application\UseCases\ObtenerHostnamesUseCase;
use App\Core\Admin\Domain\ValueObjects\HostnameVO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ObtenerHostnamesUseCase
 *
 * Tests use case logic with mocked OutPort
 */
final class ObtenerHostnamesUseCaseTest extends TestCase
{
    public function test_execute_invokes_out_port(): void
    {
        // Arrange
        $expectedHostnames = [
            new HostnameVO(id: 1, nombre: 'pgrdesbds09'),
            new HostnameVO(id: 2, nombre: 'sridesbds09'),
        ];

        $hostnameOutPort = $this->createMock(HostnameOutPort::class);
        $hostnameOutPort->expects($this->once())
            ->method('obtenerHostnames')
            ->willReturn($expectedHostnames);

        $useCase = new ObtenerHostnamesUseCase($hostnameOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($expectedHostnames, $result);
    }

    public function test_execute_returns_array_of_hostname_vo(): void
    {
        // Arrange
        $expectedHostnames = [
            new HostnameVO(id: 1, nombre: 'pgrdesbds09'),
            new HostnameVO(id: 2, nombre: 'sridesbds09'),
            new HostnameVO(id: 3, nombre: 'pgrprdbdsmz02'),
        ];

        $hostnameOutPort = $this->createMock(HostnameOutPort::class);
        $hostnameOutPort->method('obtenerHostnames')
            ->willReturn($expectedHostnames);

        $useCase = new ObtenerHostnamesUseCase($hostnameOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(HostnameVO::class, $result);
    }

    public function test_execute_handles_empty_array_from_out_port(): void
    {
        // Arrange
        $hostnameOutPort = $this->createMock(HostnameOutPort::class);
        $hostnameOutPort->method('obtenerHostnames')
            ->willReturn([]);

        $useCase = new ObtenerHostnamesUseCase($hostnameOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_execute_returns_unmodified_array_from_out_port(): void
    {
        // Arrange
        $hostname1 = new HostnameVO(id: 5, nombre: 'Hostname5');
        $hostname2 = new HostnameVO(id: 10, nombre: 'Hostname10');
        $expectedHostnames = [$hostname1, $hostname2];

        $hostnameOutPort = $this->createMock(HostnameOutPort::class);
        $hostnameOutPort->method('obtenerHostnames')
            ->willReturn($expectedHostnames);

        $useCase = new ObtenerHostnamesUseCase($hostnameOutPort);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertSame($hostname1, $result[0]);
        $this->assertSame($hostname2, $result[1]);
    }
}
