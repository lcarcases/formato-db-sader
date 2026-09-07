<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Domain\ValueObjects;

use App\Core\Admin\Domain\ValueObjects\EsquemaVO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EsquemaVO Value Object
 *
 * Tests domain invariants:
 * - ID must be positive integer
 * - Nombre must not be empty
 * - Object is immutable (readonly properties)
 */
final class EsquemaVOTest extends TestCase
{
    public function test_constructor_creates_valid_esquema(): void
    {
        // Arrange & Act
        $esquema = new EsquemaVO(id: 1, nombre: 'ap_activemq_pd');

        // Assert
        $this->assertSame(1, $esquema->id);
        $this->assertSame('ap_activemq_pd', $esquema->nombre);
    }

    public function test_constructor_validates_id_greater_than_zero(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID debe ser mayor a 0');

        new EsquemaVO(id: 0, nombre: 'Test');
    }

    public function test_constructor_validates_id_is_positive(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID debe ser mayor a 0');

        new EsquemaVO(id: -1, nombre: 'Test');
    }

    public function test_constructor_validates_nombre_not_empty(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre no puede estar vacío');

        new EsquemaVO(id: 1, nombre: '');
    }

    public function test_constructor_validates_nombre_not_only_whitespace(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre no puede estar vacío');

        new EsquemaVO(id: 1, nombre: '   ');
    }

    public function test_from_array_creates_esquema_from_array(): void
    {
        // Arrange
        $data = ['id' => 2, 'nombre' => 'ap_apoyos_pd'];

        // Act
        $esquema = EsquemaVO::fromArray($data);

        // Assert
        $this->assertSame(2, $esquema->id);
        $this->assertSame('ap_apoyos_pd', $esquema->nombre);
    }

    public function test_from_array_throws_exception_if_id_missing(): void
    {
        // Arrange
        $data = ['nombre' => 'Test'];

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);

        EsquemaVO::fromArray($data);
    }

    public function test_from_array_throws_exception_if_nombre_missing(): void
    {
        // Arrange
        $data = ['id' => 1];

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);

        EsquemaVO::fromArray($data);
    }

    public function test_to_array_serializes_correctly(): void
    {
        // Arrange
        $esquema = new EsquemaVO(id: 3, nombre: 'ap_biometricos_pd');

        // Act
        $array = $esquema->toArray();

        // Assert
        $this->assertSame([
            'id' => 3,
            'nombre' => 'ap_biometricos_pd',
        ], $array);
    }

    public function test_immutability_properties_are_readonly(): void
    {
        // Arrange
        $esquema = new EsquemaVO(id: 1, nombre: 'Test');

        // Act & Assert
        // Attempting to modify readonly properties should cause a fatal error
        // We verify this by using reflection to check the readonly property
        $reflection = new \ReflectionClass($esquema);
        $idProperty = $reflection->getProperty('id');
        $nombreProperty = $reflection->getProperty('nombre');

        $this->assertTrue($idProperty->isReadOnly());
        $this->assertTrue($nombreProperty->isReadOnly());
    }

    public function test_from_array_trims_nombre(): void
    {
        // Arrange
        $data = ['id' => 1, 'nombre' => '  ap_activemq_pd  '];

        // Act
        $esquema = EsquemaVO::fromArray($data);

        // Assert
        $this->assertSame('ap_activemq_pd', $esquema->nombre);
    }
}
