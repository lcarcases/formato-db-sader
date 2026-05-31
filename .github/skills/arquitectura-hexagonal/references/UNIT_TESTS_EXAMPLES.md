## Step 8: Generate Unit Tests

**Template:** Use [templates/unit-test.php](../templates/unit-test.php) as a starting structure.

Use **Given-When-Then** approach for all tests.

**Structure:**
```php
// filepath: app/Core/Programa/Infrastructure/Tests/Units/GenerarSolicitudTest.php
<?php

namespace App\Core\Programa\Infrastructure\Tests\Units;

use PHPUnit\Framework\TestCase;
use App\Core\Programa\Application\UseCases\GenerarSolicitudUseCase;
use App\Core\Programa\Application\Dtos\In\GenerarSolicitudInDto;
use App\Core\Programa\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Programa\Application\Ports\Out\IPersonaOutPort;
use App\Core\Programa\Application\Ports\Out\IProgramaOutPort;
use App\Core\Programa\Domain\Entities\PersonaEntity;
use App\Core\Programa\Domain\Entities\ProgramaEntity;

class GenerarSolicitudTest extends TestCase
{
    private ISolicitudOutPort $solicitudOutPort;
    private IPersonaOutPort $personaOutPort;
    private IProgramaOutPort $programaOutPort;
    private GenerarSolicitudUseCase $useCase;

    protected function setUp(): void
    {
        // Mock OutPorts
        $this->solicitudOutPort = $this->createMock(ISolicitudOutPort::class);
        $this->personaOutPort = $this->createMock(IPersonaOutPort::class);
        $this->programaOutPort = $this->createMock(IProgramaOutPort::class);
        
        $this->useCase = new GenerarSolicitudUseCase(
            $this->solicitudOutPort,
            $this->personaOutPort,
            $this->programaOutPort
        );
    }

    /** @test */
    public function debe_generar_solicitud_cuando_datos_son_validos(): void
    {
        // GIVEN (Dado que)
        $inDto = new GenerarSolicitudInDto(
            curp: 'ROAA850101HDFRRL09',
            clavePrograma: 'PRONAFE',
            anio: 2026,
            estado: 'AS'
        );
        
        $personaMock = $this->createMock(PersonaEntity::class);
        $personaMock->method('estaActiva')->willReturn(true);
        
        $programaMock = $this->createMock(ProgramaEntity::class);
        
        $this->personaOutPort
            ->method('buscarPorCurp')
            ->willReturn($personaMock);
            
        $this->programaOutPort
            ->method('buscarPorClave')
            ->willReturn($programaMock);
            
        $this->solicitudOutPort
            ->method('persistir')
            ->willReturn(1);

        // WHEN (Cuando)
        $resultado = $this->useCase->ejecutar($inDto);

        // THEN (Entonces)
        $this->assertNotNull($resultado);
        $this->assertEquals(1, $resultado->id);
        $this->assertStringContainsString('PRONAFE', $resultado->folio);
    }

    /** @test */
    public function debe_lanzar_excepcion_cuando_persona_no_existe(): void
    {
        // GIVEN
        $inDto = new GenerarSolicitudInDto(
            curp: 'XXXX850101HDFRRL09',
            clavePrograma: 'PRONAFE',
            anio: 2026,
            estado: 'AS'
        );
        
        $this->personaOutPort
            ->method('buscarPorCurp')
            ->willReturn(null);

        // THEN (expect exception)
        $this->expectException(PersonaNoEncontradaException::class);

        // WHEN
        $this->useCase->ejecutar($inDto);
    }

    /** @test */
    public function debe_lanzar_excepcion_cuando_persona_no_esta_activa(): void
    {
        // GIVEN
        $inDto = new GenerarSolicitudInDto(
            curp: 'ROAA850101HDFRRL09',
            clavePrograma: 'PRONAFE',
            anio: 2026,
            estado: 'AS'
        );
        
        $personaMock = $this->createMock(PersonaEntity::class);
        $personaMock->method('estaActiva')->willReturn(false); // NOT active
        
        $this->personaOutPort
            ->method('buscarPorCurp')
            ->willReturn($personaMock);

        // THEN
        $this->expectException(PersonaNoActivaException::class);

        // WHEN
        $this->useCase->ejecutar($inDto);
    }
}
```

**Test Naming Convention:**
```
debe_{resultado_esperado}_cuando_{condicion}
```

**What to Test:**
```
✅ Happy path (successful execution)
✅ Each domain exception scenario
✅ Edge cases (null values, empty lists)
✅ Business rules enforcement
✅ OutPort interactions (verify calls)
```
