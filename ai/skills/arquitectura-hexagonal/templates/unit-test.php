<?php

namespace App\Core\Infrastructure\Tests\Unit;

use App\Core\Application\Dtos\In\{{InDto}};
use App\Core\Application\Ports\Out\{{OutPort}};
use App\Core\Application\UseCases\{{UseCase}};
use App\Core\Domain\Entities\{{Entity}};
use PHPUnit\Framework\TestCase;

class {{UseCase}}Test extends TestCase
{
    /** @test */
    public function debe_{{descripcion}}()
    {
        // ========== GIVEN (Contexto) ==========
        $dto = new {{InDto}}(
            atributo1: 'valor1',
            atributo2: 'valor2',
        );

        $outPortMock = $this->createMock({{OutPort}}::class);
        $outPortMock->expects($this->once())
            ->method('persistir{{Entity}}')
            ->with($this->isInstanceOf({{Entity}}::class));

        $useCase = new {{UseCase}}($outPortMock);

        // ========== WHEN (Acción) ==========
        $resultado = $useCase->execute($dto);

        // ========== THEN (Verificación) ==========
        $this->assertNotNull($resultado);
        $this->assertEquals('valor_esperado', $resultado->atributo);
    }
}
