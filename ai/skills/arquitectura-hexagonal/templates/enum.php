<?php

namespace App\Core\Domain\Enums;

enum {{Enum}}: string
{
    case VALOR1 = 'valor1';
    case VALOR2 = 'valor2';
    case VALOR3 = 'valor3';

    public function getDescripcion(): string
    {
        return match($this) {
            self::VALOR1 => 'Descripción del valor 1',
            self::VALOR2 => 'Descripción del valor 2',
            self::VALOR3 => 'Descripción del valor 3',
        };
    }

    public function esValorFinal(): bool
    {
        return $this === self::VALOR3;
    }

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
