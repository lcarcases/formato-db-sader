<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoPermisoOutPort;
use App\Core\Admin\Application\UseCases\ObtenerTiposPermisoUseCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * ObtenerTiposPermisoUseCaseTest
 *
 * Tests unitarios para el caso de uso ObtenerTiposPermisoUseCase
 */
final class ObtenerTiposPermisoUseCaseTest extends TestCase
{
    private MockObject|ITipoPermisoOutPort $tipoPermisoOutPortMock;

    private ObtenerTiposPermisoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tipoPermisoOutPortMock = $this->createMock(ITipoPermisoOutPort::class);
        $this->useCase = new ObtenerTiposPermisoUseCase($this->tipoPermisoOutPortMock);
    }

    public function test_ejecutar_retorna_array_vacio_cuando_no_hay_tipos_permiso(): void
    {
        // Arrange
        $this->tipoPermisoOutPortMock
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn([]);

        // Act
        $resultado = $this->useCase->ejecutar();

        // Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    public function test_ejecutar_retorna_tipos_permiso_correctamente(): void
    {
        // Arrange
        $tiposPermisoEsperados = [
            [
                'id' => 1,
                'nombre' => 'Consulta',
                'activo' => true,
                'descripcion' => 'Permiso de solo lectura',
            ],
            [
                'id' => 2,
                'nombre' => 'Cambios',
                'activo' => true,
                'descripcion' => 'Permiso de modificación',
            ],
            [
                'id' => 3,
                'nombre' => 'Eliminación',
                'activo' => true,
                'descripcion' => 'Permiso de eliminación',
            ],
            [
                'id' => 4,
                'nombre' => 'Consulta y Cambios',
                'activo' => true,
                'descripcion' => 'Permiso combinado',
            ],
        ];

        $this->tipoPermisoOutPortMock
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($tiposPermisoEsperados);

        // Act
        $resultado = $this->useCase->ejecutar();

        // Assert
        $this->assertIsArray($resultado);
        $this->assertCount(4, $resultado);
        $this->assertEquals($tiposPermisoEsperados, $resultado);
    }

    public function test_ejecutar_propaga_excepcion_del_out_port(): void
    {
        // Arrange
        $excepcionEsperada = new \RuntimeException('Error de conexión a base de datos');

        $this->tipoPermisoOutPortMock
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willThrowException($excepcionEsperada);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error de conexión a base de datos');

        // Act
        $this->useCase->ejecutar();
    }

    public function test_ejecutar_solo_retorna_tipos_permiso_activos(): void
    {
        // Arrange
        $tiposPermisoActivos = [
            [
                'id' => 1,
                'nombre' => 'Consulta',
                'activo' => true,
                'descripcion' => null,
            ],
            [
                'id' => 2,
                'nombre' => 'Cambios',
                'activo' => true,
                'descripcion' => null,
            ],
        ];

        $this->tipoPermisoOutPortMock
            ->expects($this->once())
            ->method('obtenerTodos')
            ->willReturn($tiposPermisoActivos);

        // Act
        $resultado = $this->useCase->ejecutar();

        // Assert
        $this->assertCount(2, $resultado);
        foreach ($resultado as $tipo) {
            $this->assertTrue($tipo['activo']);
        }
    }
}
