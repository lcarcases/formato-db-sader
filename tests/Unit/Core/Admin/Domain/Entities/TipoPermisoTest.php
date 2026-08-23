<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Domain\Entities;

use App\Core\Admin\Domain\Entities\TipoPermiso;
use PHPUnit\Framework\TestCase;

/**
 * TipoPermisoTest
 *
 * Tests unitarios para la entidad TipoPermiso
 */
final class TipoPermisoTest extends TestCase
{
    public function test_puede_crear_tipo_permiso_valido(): void
    {
        // Arrange & Act
        $tipoPermiso = new TipoPermiso(
            id: 1,
            nombre: 'Consulta',
            activo: true,
            descripcion: 'Permiso de solo lectura'
        );

        // Assert
        $this->assertSame(1, $tipoPermiso->getId());
        $this->assertSame('Consulta', $tipoPermiso->getNombre());
        $this->assertTrue($tipoPermiso->isActivo());
        $this->assertSame('Permiso de solo lectura', $tipoPermiso->getDescripcion());
    }

    public function test_puede_crear_tipo_permiso_sin_descripcion(): void
    {
        // Arrange & Act
        $tipoPermiso = new TipoPermiso(
            id: 2,
            nombre: 'Cambios',
            activo: true
        );

        // Assert
        $this->assertSame(2, $tipoPermiso->getId());
        $this->assertSame('Cambios', $tipoPermiso->getNombre());
        $this->assertTrue($tipoPermiso->isActivo());
        $this->assertNull($tipoPermiso->getDescripcion());
    }

    public function test_lanza_excepcion_cuando_nombre_esta_vacio(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del tipo de permiso no puede estar vacío');

        // Act
        new TipoPermiso(
            id: 1,
            nombre: '',
            activo: true
        );
    }

    public function test_lanza_excepcion_cuando_nombre_solo_tiene_espacios(): void
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del tipo de permiso no puede estar vacío');

        // Act
        new TipoPermiso(
            id: 1,
            nombre: '   ',
            activo: true
        );
    }

    public function test_trimea_espacios_en_nombre(): void
    {
        // Arrange & Act
        $tipoPermiso = new TipoPermiso(
            id: 1,
            nombre: '  Consulta  ',
            activo: true
        );

        // Assert
        $this->assertSame('Consulta', $tipoPermiso->getNombre());
    }

    public function test_trimea_espacios_en_descripcion(): void
    {
        // Arrange & Act
        $tipoPermiso = new TipoPermiso(
            id: 1,
            nombre: 'Consulta',
            activo: true,
            descripcion: '  Descripción con espacios  '
        );

        // Assert
        $this->assertSame('Descripción con espacios', $tipoPermiso->getDescripcion());
    }

    public function test_puede_activar_tipo_permiso(): void
    {
        // Arrange
        $tipoPermiso = new TipoPermiso(
            id: 1,
            nombre: 'Consulta',
            activo: false
        );

        // Act
        $tipoPermiso->activar();

        // Assert
        $this->assertTrue($tipoPermiso->isActivo());
    }

    public function test_puede_desactivar_tipo_permiso(): void
    {
        // Arrange
        $tipoPermiso = new TipoPermiso(
            id: 1,
            nombre: 'Consulta',
            activo: true
        );

        // Act
        $tipoPermiso->desactivar();

        // Assert
        $this->assertFalse($tipoPermiso->isActivo());
    }
}
