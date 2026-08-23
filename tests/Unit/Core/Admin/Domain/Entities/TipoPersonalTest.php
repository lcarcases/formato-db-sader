<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Admin\Domain\Entities;

use App\Core\Admin\Domain\Entities\TipoPersonal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * TipoPersonalTest
 *
 * Unit tests for TipoPersonal domain entity.
 *
 * Tests Entity behavior:
 * - Constructor validation (invariants)
 * - Business logic methods (isActive, getDisplayName)
 * - Immutability (readonly properties)
 *
 * ✅ Unit Test Pattern:
 * - No dependencies (pure PHP entity)
 * - Fast execution (no database or HTTP calls)
 * - Tests behavior, not implementation
 */
final class TipoPersonalTest extends TestCase
{
    public function test_it_creates_valid_tipo_personal_with_all_fields(): void
    {
        // Arrange & Act
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: 'Base',
            descripcion: 'Personal de base',
            activo: true
        );

        // Assert
        $this->assertSame(1, $tipoPersonal->id);
        $this->assertSame('Base', $tipoPersonal->nombre);
        $this->assertSame('Personal de base', $tipoPersonal->descripcion);
        $this->assertTrue($tipoPersonal->activo);
    }

    public function test_it_creates_tipo_personal_with_null_descripcion(): void
    {
        // Arrange & Act
        $tipoPersonal = new TipoPersonal(
            id: 2,
            nombre: 'Enlace',
            descripcion: null,
            activo: true
        );

        // Assert
        $this->assertSame(2, $tipoPersonal->id);
        $this->assertSame('Enlace', $tipoPersonal->nombre);
        $this->assertNull($tipoPersonal->descripcion);
        $this->assertTrue($tipoPersonal->activo);
    }

    public function test_it_throws_exception_when_nombre_is_empty(): void
    {
        // Arrange & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TipoPersonal nombre cannot be empty');

        // Act
        new TipoPersonal(
            id: 1,
            nombre: '',
            descripcion: 'Test',
            activo: true
        );
    }

    public function test_it_throws_exception_when_nombre_is_only_whitespace(): void
    {
        // Arrange & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TipoPersonal nombre cannot be empty');

        // Act
        new TipoPersonal(
            id: 1,
            nombre: '   ',
            descripcion: 'Test',
            activo: true
        );
    }

    public function test_it_returns_true_when_tipo_personal_is_active(): void
    {
        // Arrange
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: 'Base',
            descripcion: 'Personal de base',
            activo: true
        );

        // Act & Assert
        $this->assertTrue($tipoPersonal->isActive());
    }

    public function test_it_returns_false_when_tipo_personal_is_inactive(): void
    {
        // Arrange
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: 'Base',
            descripcion: 'Personal de base',
            activo: false
        );

        // Act & Assert
        $this->assertFalse($tipoPersonal->isActive());
    }

    public function test_it_returns_formatted_display_name(): void
    {
        // Arrange
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: 'base',
            descripcion: 'Personal de base',
            activo: true
        );

        // Act
        $displayName = $tipoPersonal->getDisplayName();

        // Assert
        $this->assertSame('Base', $displayName);
    }

    public function test_it_is_immutable(): void
    {
        // Arrange
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: 'Base',
            descripcion: 'Personal de base',
            activo: true
        );

        // Assert - readonly class prevents property modification
        // This test verifies the class is readonly at compile time
        $reflection = new \ReflectionClass($tipoPersonal);
        $this->assertTrue($reflection->isReadOnly(), 'TipoPersonal entity must be readonly (immutable)');
    }

    /**
     * @dataProvider activeTiposPersonalProvider
     */
    #[DataProvider('activeTiposPersonalProvider')]
    public function test_it_handles_various_active_estados(bool $activo, bool $expected): void
    {
        // Arrange
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: 'Base',
            descripcion: null,
            activo: $activo
        );

        // Act & Assert
        $this->assertSame($expected, $tipoPersonal->isActive());
    }

    /**
     * Data provider for active estado tests
     */
    public static function activeTiposPersonalProvider(): array
    {
        return [
            'active tipo personal' => [true, true],
            'inactive tipo personal' => [false, false],
        ];
    }

    /**
     * @dataProvider nombreFormattingProvider
     */
    #[DataProvider('nombreFormattingProvider')]
    public function test_it_formats_nombre_correctly(string $nombre, string $expected): void
    {
        // Arrange
        $tipoPersonal = new TipoPersonal(
            id: 1,
            nombre: $nombre,
            descripcion: null,
            activo: true
        );

        // Act
        $displayName = $tipoPersonal->getDisplayName();

        // Assert
        $this->assertSame($expected, $displayName);
    }

    /**
     * Data provider for nombre formatting tests
     */
    public static function nombreFormattingProvider(): array
    {
        return [
            'lowercase' => ['base', 'Base'],
            'uppercase' => ['BASE', 'Base'],
            'mixed case' => ['BaSe', 'Base'],
            'with trailing space' => ['Base ', 'Base'],
            'with leading space' => [' Base', 'Base'],
        ];
    }
}
