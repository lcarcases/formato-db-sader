**Input:**
```
Caso de uso: Verificar elegibilidad de beneficiario para un programa
Módulo: Beneficiario
Actor: Sistema (proceso automático)

Reglas de negocio:
1. El beneficiario debe tener al menos 18 años
2. La superficie cultivable debe ser menor o igual a 50 hectáreas
3. No debe tener solicitudes rechazadas en el último año
4. Debe estar registrado en el padrón activo
```

**Output Generado:**

#### 🔵 Domain Layer

```php
// filepath: app/Core/Beneficiario/Domain/Entities/BeneficiarioEntity.php
<?php

namespace App\Core\Beneficiario\Domain\Entities;

use App\Core\Beneficiario\Domain\Vo\CurpVO;
use App\Core\Beneficiario\Domain\Vo\SuperficieVO;
use App\Core\Beneficiario\Domain\Enums\EstatusBeneficiarioEnum;

class BeneficiarioEntity
{
    public function __construct(
        private int $id,
        private CurpVO $curp,
        private string $nombre,
        private \DateTimeImmutable $fechaNacimiento,
        private SuperficieVO $superficie,
        private EstatusBeneficiarioEnum $estatus
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getCurp(): CurpVO
    {
        return $this->curp;
    }

    public function getEdad(): int
    {
        $hoy = new \DateTimeImmutable();
        return $hoy->diff($this->fechaNacimiento)->y;
    }

    public function getSuperficie(): float
    {
        return $this->superficie->valor();
    }

    public function estaActivo(): bool
    {
        return $this->estatus === EstatusBeneficiarioEnum::ACTIVO;
    }

    public function esMayorDeEdad(int $edadMinima = 18): bool
    {
        return $this->getEdad() >= $edadMinima;
    }
}
```

```php
// filepath: app/Core/Beneficiario/Domain/Vo/SuperficieVO.php
<?php

namespace App\Core\Beneficiario\Domain\Vo;

use App\Core\Beneficiario\Domain\Exceptions\SuperficieInvalidaException;

class SuperficieVO
{
    private float $hectareas;

    public function __construct(float $hectareas)
    {
        if ($hectareas < 0) {
            throw new SuperficieInvalidaException("La superficie no puede ser negativa: {$hectareas}");
        }
        $this->hectareas = $hectareas;
    }

    public function valor(): float
    {
        return $this->hectareas;
    }

    public function esMenorOIgualA(float $limite): bool
    {
        return $this->hectareas <= $limite;
    }

    public function igual(SuperficieVO $otra): bool
    {
        return $this->hectareas === $otra->valor();
    }
}
```

```php
// filepath: app/Core/Beneficiario/Domain/Enums/EstatusBeneficiarioEnum.php
<?php

namespace App\Core\Beneficiario\Domain\Enums;

enum EstatusBeneficiarioEnum: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case SUSPENDIDO = 'suspendido';
    case FALLECIDO = 'fallecido';
}
```

```php
// filepath: app/Core/Beneficiario/Domain/Specifications/EdadMinimaSpecification.php
<?php

namespace App\Core\Beneficiario\Domain\Specifications;

use App\Core\Beneficiario\Domain\Entities\BeneficiarioEntity;
use App\Core\Shared\Domain\Specifications\ISpecification;

class EdadMinimaSpecification implements ISpecification
{
    public function __construct(
        private int $edadMinima = 18
    ) {}

    public function isSatisfiedBy(BeneficiarioEntity $beneficiario): bool
    {
        return $beneficiario->esMayorDeEdad($this->edadMinima);
    }

    public function mensajeError(): string
    {
        return "El beneficiario debe tener al menos {$this->edadMinima} años";
    }
}
```

```php
// filepath: app/Core/Beneficiario/Domain/Specifications/SuperficieMaximaSpecification.php
<?php

namespace App\Core\Beneficiario\Domain\Specifications;

use App\Core\Beneficiario\Domain\Entities\BeneficiarioEntity;
use App\Core\Shared\Domain\Specifications\ISpecification;

class SuperficieMaximaSpecification implements ISpecification
{
    public function __construct(
        private float $superficieMaxima = 50.0
    ) {}

    public function isSatisfiedBy(BeneficiarioEntity $beneficiario): bool
    {
        return $beneficiario->getSuperficie() <= $this->superficieMaxima;
    }

    public function mensajeError(): string
    {
        return "La superficie cultivable debe ser menor o igual a {$this->superficieMaxima} hectáreas";
    }
}
```

```php
// filepath: app/Core/Beneficiario/Domain/Services/ElegibilidadDomainService.php
<?php

namespace App\Core\Beneficiario\Domain\Services;

use App\Core\Beneficiario\Domain\Entities\BeneficiarioEntity;
use App\Core\Beneficiario\Domain\Entities\ProgramaEntity;
use App\Core\Beneficiario\Domain\Specifications\EdadMinimaSpecification;
use App\Core\Beneficiario\Domain\Specifications\SuperficieMaximaSpecification;

class ElegibilidadDomainService
{
    public function verificarElegibilidad(
        BeneficiarioEntity $beneficiario,
        ProgramaEntity $programa,
        array $solicitudesRechazadas
    ): ElegibilidadResultado {
        $errores = [];

        // Verificar edad mínima
        $especEdad = new EdadMinimaSpecification($programa->getEdadMinima());
        if (!$especEdad->isSatisfiedBy($beneficiario)) {
            $errores[] = $especEdad->mensajeError();
        }

        // Verificar superficie máxima
        $especSuperficie = new SuperficieMaximaSpecification($programa->getSuperficieMaxima());
        if (!$especSuperficie->isSatisfiedBy($beneficiario)) {
            $errores[] = $especSuperficie->mensajeError();
        }

        // Verificar que esté activo
        if (!$beneficiario->estaActivo()) {
            $errores[] = "El beneficiario no está activo en el padrón";
        }

        // Verificar solicitudes rechazadas
        if ($this->tieneSolicitudesRechazadasRecientes($solicitudesRechazadas)) {
            $errores[] = "El beneficiario tiene solicitudes rechazadas en el último año";
        }

        return new ElegibilidadResultado(
            esElegible: empty($errores),
            errores: $errores
        );
    }

    private function tieneSolicitudesRechazadasRecientes(array $solicitudes): bool
    {
        $hace1Anio = new \DateTimeImmutable('-1 year');
        
        foreach ($solicitudes as $solicitud) {
            if ($solicitud->getFechaRechazo() >= $hace1Anio) {
                return true;
            }
        }
        
        return false;
    }
}
```

```php
// filepath: app/Core/Beneficiario/Domain/Exceptions/BeneficiarioNoElegibleException.php
<?php

namespace App\Core\Beneficiario\Domain\Exceptions;

use App\Core\Shared\Domain\Exceptions\DomainException;

class BeneficiarioNoElegibleException extends DomainException
{
    public function __construct(array $errores)
    {
        $mensaje = "El beneficiario no es elegible: " . implode(", ", $errores);
        parent::__construct($mensaje);
    }
}
```

#### 🟢 Application Layer

```php
// filepath: app/Core/Beneficiario/Application/Ports/In/IVerificarElegibilidadInPort.php
<?php

namespace App\Core\Beneficiario\Application\Ports\In;

use App\Core\Beneficiario\Application\Dtos\In\VerificarElegibilidadInDto;
use App\Core\Beneficiario\Application\Dtos\Out\VerificarElegibilidadOutDto;

interface IVerificarElegibilidadInPort
{
    public function ejecutar(VerificarElegibilidadInDto $dto): VerificarElegibilidadOutDto;
}
```

```php
// filepath: app/Core/Beneficiario/Application/Ports/Out/IBeneficiarioOutPort.php
<?php

namespace App\Core\Beneficiario\Application\Ports\Out;

use App\Core\Beneficiario\Domain\Entities\BeneficiarioEntity;
use App\Core\Beneficiario\Domain\Vo\CurpVO;

interface IBeneficiarioOutPort
{
    public function buscarPorCurp(CurpVO $curp): ?BeneficiarioEntity;
    public function buscarPorId(int $id): ?BeneficiarioEntity;
}
```

```php
// filepath: app/Core/Beneficiario/Application/Ports/Out/IProgramaOutPort.php
<?php

namespace App\Core\Beneficiario\Application\Ports\Out;

use App\Core\Beneficiario\Domain\Entities\ProgramaEntity;

interface IProgramaOutPort
{
    public function buscarPorClave(string $clave): ?ProgramaEntity;
}
```

```php
// filepath: app/Core/Beneficiario/Application/Ports/Out/ISolicitudOutPort.php
<?php

namespace App\Core\Beneficiario\Application\Ports\Out;

use App\Core\Beneficiario\Domain\Vo\CurpVO;

interface ISolicitudOutPort
{
    public function buscarRechazadasPorCurp(CurpVO $curp): array;
}
```

```php
// filepath: app/Core/Beneficiario/Application/Dtos/In/VerificarElegibilidadInDto.php
<?php

namespace App\Core\Beneficiario\Application\Dtos\In;

use App\Core\Shared\Application\Dto\IDto;

class VerificarElegibilidadInDto implements IDto
{
    public function __construct(
        public readonly string $curp,
        public readonly string $clavePrograma
    ) {}
}
```

```php
// filepath: app/Core/Beneficiario/Application/Dtos/Out/VerificarElegibilidadOutDto.php
<?php

namespace App\Core\Beneficiario\Application\Dtos\Out;

use App\Core\Shared\Application\Dto\IDto;

class VerificarElegibilidadOutDto implements IDto
{
    public function __construct(
        public readonly bool $esElegible,
        public readonly string $curp,
        public readonly string $nombreBeneficiario,
        public readonly string $clavePrograma,
        public readonly array $errores = [],
        public readonly string $mensaje = ''
    ) {}

    public function toArray(): array
    {
        return [
            'es_elegible' => $this->esElegible,
            'curp' => $this->curp,
            'nombre_beneficiario' => $this->nombreBeneficiario,
            'clave_programa' => $this->clavePrograma,
            'errores' => $this->errores,
            'mensaje' => $this->mensaje
        ];
    }
}
```

```php
// filepath: app/Core/Beneficiario/Application/UseCases/VerificarElegibilidadUseCase.php
<?php

namespace App\Core\Beneficiario\Application\UseCases;

use App\Core\Beneficiario\Application\Dtos\In\VerificarElegibilidadInDto;
use App\Core\Beneficiario\Application\Dtos\Out\VerificarElegibilidadOutDto;
use App\Core\Beneficiario\Application\Ports\In\IVerificarElegibilidadInPort;
use App\Core\Beneficiario\Application\Ports\Out\IBeneficiarioOutPort;
use App\Core\Beneficiario\Application\Ports\Out\IProgramaOutPort;
use App\Core\Beneficiario\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Beneficiario\Domain\Vo\CurpVO;
use App\Core\Beneficiario\Domain\Services\ElegibilidadDomainService;
use App\Core\Beneficiario\Domain\Exceptions\BeneficiarioNoEncontradoException;
use App\Core\Beneficiario\Domain\Exceptions\ProgramaNoEncontradoException;

class VerificarElegibilidadUseCase implements IVerificarElegibilidadInPort
{
    public function __construct(
        private IBeneficiarioOutPort $beneficiarioOutPort,
        private IProgramaOutPort $programaOutPort,
        private ISolicitudOutPort $solicitudOutPort,
        private ElegibilidadDomainService $elegibilidadService
    ) {}

    public function ejecutar(VerificarElegibilidadInDto $dto): VerificarElegibilidadOutDto
    {
        // 1. Transform DTO → Domain (Value Objects)
        $curp = new CurpVO($dto->curp);

        // 2. Call OutPorts to get data
        $beneficiario = $this->beneficiarioOutPort->buscarPorCurp($curp);
        if ($beneficiario === null) {
            throw new BeneficiarioNoEncontradoException($curp->valor());
        }

        $programa = $this->programaOutPort->buscarPorClave($dto->clavePrograma);
        if ($programa === null) {
            throw new ProgramaNoEncontradoException($dto->clavePrograma);
        }

        $solicitudesRechazadas = $this->solicitudOutPort->buscarRechazadasPorCurp($curp);

        // 3. Execute domain logic via Domain Service
        $resultado = $this->elegibilidadService->verificarElegibilidad(
            $beneficiario,
            $programa,
            $solicitudesRechazadas
        );

        // 4. Transform Domain → OutDto
        return new VerificarElegibilidadOutDto(
            esElegible: $resultado->esElegible,
            curp: $curp->valor(),
            nombreBeneficiario: $beneficiario->getNombre(),
            clavePrograma: $programa->getClave(),
            errores: $resultado->errores,
            mensaje: $resultado->esElegible 
                ? 'El beneficiario es elegible para el programa'
                : 'El beneficiario no cumple los requisitos de elegibilidad'
        );
    }
}
```

#### 🟠 Infrastructure Layer

```php
// filepath: app/Core/Beneficiario/Infrastructure/Adapters/In/Api/VerificarElegibilidadInAdapter.php
<?php

namespace App\Core\Beneficiario\Infrastructure\Adapters\In\Api;

use App\Core\Beneficiario\Application\Dtos\In\VerificarElegibilidadInDto;
use App\Core\Beneficiario\Application\Ports\In\IVerificarElegibilidadInPort;
use App\Core\Beneficiario\Domain\Exceptions\BeneficiarioNoEncontradoException;
use App\Core\Beneficiario\Domain\Exceptions\ProgramaNoEncontradoException;
use App\Core\Beneficiario\Domain\Exceptions\CurpInvalidaException;
use App\Core\Shared\Infrastructure\Respuesta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerificarElegibilidadInAdapter
{
    public function __construct(
        private IVerificarElegibilidadInPort $useCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            // 1. Extract data from Request (ONLY here)
            $inDto = new VerificarElegibilidadInDto(
                curp: $request->input('curp'),
                clavePrograma: $request->input('clave_programa')
            );

            // 2. Execute UseCase
            $outDto = $this->useCase->ejecutar($inDto);

            // 3. Return response
            return Respuesta::exito($outDto->toArray(), 200);

        } catch (CurpInvalidaException $e) {
            return Respuesta::error($e->getMessage(), 400);
        } catch (BeneficiarioNoEncontradoException $e) {
            return Respuesta::error($e->getMessage(), 404);
        } catch (ProgramaNoEncontradoException $e) {
            return Respuesta::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            return Respuesta::error('Error interno del servidor', 500);
        }
    }
}
```

```php
// filepath: app/Core/Beneficiario/Infrastructure/Adapters/Out/Persistence/MySQL/BeneficiarioMySQLOutAdapter.php
<?php

namespace App\Core\Beneficiario\Infrastructure\Adapters\Out\Persistence\MySQL;

use App\Core\Beneficiario\Application\Ports\Out\IBeneficiarioOutPort;
use App\Core\Beneficiario\Domain\Entities\BeneficiarioEntity;
use App\Core\Beneficiario\Domain\Vo\CurpVO;
use App\Core\Beneficiario\Domain\Vo\SuperficieVO;
use App\Core\Beneficiario\Domain\Enums\EstatusBeneficiarioEnum;
use App\Core\Beneficiario\Infrastructure\Adapters\Out\Persistence\MySQL\Repositories\BeneficiarioMySQLRepository;

class BeneficiarioMySQLOutAdapter implements IBeneficiarioOutPort
{
    public function __construct(
        private BeneficiarioMySQLRepository $repository
    ) {}

    public function buscarPorCurp(CurpVO $curp): ?BeneficiarioEntity
    {
        $data = $this->repository->findByCurp($curp->valor());
        
        if ($data === null) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function buscarPorId(int $id): ?BeneficiarioEntity
    {
        $data = $this->repository->findById($id);
        
        if ($data === null) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    private function mapToEntity(object $data): BeneficiarioEntity
    {
        return new BeneficiarioEntity(
            id: $data->id,
            curp: new CurpVO($data->curp),
            nombre: $data->nombre,
            fechaNacimiento: new \DateTimeImmutable($data->fecha_nacimiento),
            superficie: new SuperficieVO((float) $data->superficie_hectareas),
            estatus: EstatusBeneficiarioEnum::from($data->estatus)
        );
    }
}
```

```php
// filepath: app/Core/Beneficiario/Infrastructure/Adapters/Out/Persistence/MySQL/Repositories/BeneficiarioMySQLRepository.php
<?php

namespace App\Core\Beneficiario\Infrastructure\Adapters\Out\Persistence\MySQL\Repositories;

use Illuminate\Support\Facades\DB;

class BeneficiarioMySQLRepository
{
    private string $table = 'beneficiarios';

    public function findByCurp(string $curp): ?object
    {
        return DB::table($this->table)
            ->where('curp', $curp)
            ->first();
    }

    public function findById(int $id): ?object
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->first();
    }
}
```

#### 🧪 Test Layer

```php
// filepath: app/Core/Beneficiario/Infrastructure/Tests/Units/VerificarElegibilidadTest.php
<?php

namespace App\Core\Beneficiario\Infrastructure\Tests\Units;

use PHPUnit\Framework\TestCase;
use App\Core\Beneficiario\Application\UseCases\VerificarElegibilidadUseCase;
use App\Core\Beneficiario\Application\Dtos\In\VerificarElegibilidadInDto;
use App\Core\Beneficiario\Application\Ports\Out\IBeneficiarioOutPort;
use App\Core\Beneficiario\Application\Ports\Out\IProgramaOutPort;
use App\Core\Beneficiario\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Beneficiario\Domain\Entities\BeneficiarioEntity;
use App\Core\Beneficiario\Domain\Entities\ProgramaEntity;
use App\Core\Beneficiario\Domain\Services\ElegibilidadDomainService;
use App\Core\Beneficiario\Domain\Exceptions\BeneficiarioNoEncontradoException;

class VerificarElegibilidadTest extends TestCase
{
    private IBeneficiarioOutPort $beneficiarioOutPort;
    private IProgramaOutPort $programaOutPort;
    private ISolicitudOutPort $solicitudOutPort;
    private ElegibilidadDomainService $elegibilidadService;
    private VerificarElegibilidadUseCase $useCase;

    protected function setUp(): void
    {
        $this->beneficiarioOutPort = $this->createMock(IBeneficiarioOutPort::class);
        $this->programaOutPort = $this->createMock(IProgramaOutPort::class);
        $this->solicitudOutPort = $this->createMock(ISolicitudOutPort::class);
        $this->elegibilidadService = new ElegibilidadDomainService();

        $this->useCase = new VerificarElegibilidadUseCase(
            $this->beneficiarioOutPort,
            $this->programaOutPort,
            $this->solicitudOutPort,
            $this->elegibilidadService
        );
    }

    /** @test */
    public function debe_retornar_elegible_cuando_beneficiario_cumple_todos_los_requisitos(): void
    {
        // GIVEN
        $inDto = new VerificarElegibilidadInDto(
            curp: 'ROAA850101HDFRRL09',
            clavePrograma: 'PRONAFE'
        );

        $beneficiarioMock = $this->crearBeneficiarioElegible();
        $programaMock = $this->crearProgramaMock();

        $this->beneficiarioOutPort
            ->method('buscarPorCurp')
            ->willReturn($beneficiarioMock);

        $this->programaOutPort
            ->method('buscarPorClave')
            ->willReturn($programaMock);

        $this->solicitudOutPort
            ->method('buscarRechazadasPorCurp')
            ->willReturn([]);

        // WHEN
        $resultado = $this->useCase->ejecutar($inDto);

        // THEN
        $this->assertTrue($resultado->esElegible);
        $this->assertEmpty($resultado->errores);
        $this->assertStringContainsString('elegible', strtolower($resultado->mensaje));
    }

    /** @test */
    public function debe_retornar_no_elegible_cuando_beneficiario_es_menor_de_edad(): void
    {
        // GIVEN
        $inDto = new VerificarElegibilidadInDto(
            curp: 'ROAA100101HDFRRL09',
            clavePrograma: 'PRONAFE'
        );

        $beneficiarioMenor = $this->crearBeneficiarioMenorDeEdad();
        $programaMock = $this->crearProgramaMock();

        $this->beneficiarioOutPort
            ->method('buscarPorCurp')
            ->willReturn($beneficiarioMenor);

        $this->programaOutPort
            ->method('buscarPorClave')
            ->willReturn($programaMock);

        $this->solicitudOutPort
            ->method('buscarRechazadasPorCurp')
            ->willReturn([]);

        // WHEN
        $resultado = $this->useCase->ejecutar($inDto);

        // THEN
        $this->assertFalse($resultado->esElegible);
        $this->assertNotEmpty($resultado->errores);
        $this->assertStringContainsString('edad', strtolower($resultado->errores[0]));
    }

    /** @test */
    public function debe_lanzar_excepcion_cuando_beneficiario_no_existe(): void
    {
        // GIVEN
        $inDto = new VerificarElegibilidadInDto(
            curp: 'XXXX850101HDFRRL09',
            clavePrograma: 'PRONAFE'
        );

        $this->beneficiarioOutPort
            ->method('buscarPorCurp')
            ->willReturn(null);

        // THEN
        $this->expectException(BeneficiarioNoEncontradoException::class);

        // WHEN
        $this->useCase->ejecutar($inDto);
    }

    /** @test */
    public function debe_retornar_no_elegible_cuando_superficie_excede_maximo(): void
    {
        // GIVEN
        $inDto = new VerificarElegibilidadInDto(
            curp: 'ROAA850101HDFRRL09',
            clavePrograma: 'PRONAFE'
        );

        $beneficiarioConMuchaTierra = $this->crearBeneficiarioConSuperficieExcedida();
        $programaMock = $this->crearProgramaMock();

        $this->beneficiarioOutPort
            ->method('buscarPorCurp')
            ->willReturn($beneficiarioConMuchaTierra);

        $this->programaOutPort
            ->method('buscarPorClave')
            ->willReturn($programaMock);

        $this->solicitudOutPort
            ->method('buscarRechazadasPorCurp')
            ->willReturn([]);

        // WHEN
        $resultado = $this->useCase->ejecutar($inDto);

        // THEN
        $this->assertFalse($resultado->esElegible);
        $this->assertStringContainsString('superficie', strtolower($resultado->errores[0]));
    }

    // Helper methods para crear mocks
    private function crearBeneficiarioElegible(): BeneficiarioEntity
    {
        $mock = $this->createMock(BeneficiarioEntity::class);
        $mock->method('getEdad')->willReturn(35);
        $mock->method('getSuperficie')->willReturn(30.0);
        $mock->method('estaActivo')->willReturn(true);
        $mock->method('esMayorDeEdad')->willReturn(true);
        $mock->method('getNombre')->willReturn('Juan Pérez');
        return $mock;
    }

    private function crearBeneficiarioMenorDeEdad(): BeneficiarioEntity
    {
        $mock = $this->createMock(BeneficiarioEntity::class);
        $mock->method('getEdad')->willReturn(16);
        $mock->method('getSuperficie')->willReturn(30.0);
        $mock->method('estaActivo')->willReturn(true);
        $mock->method('esMayorDeEdad')->willReturn(false);
        return $mock;
    }

    private function crearBeneficiarioConSuperficieExcedida(): BeneficiarioEntity
    {
        $mock = $this->createMock(BeneficiarioEntity::class);
        $mock->method('getEdad')->willReturn(35);
        $mock->method('getSuperficie')->willReturn(100.0); // Excede 50 ha
        $mock->method('estaActivo')->willReturn(true);
        $mock->method('esMayorDeEdad')->willReturn(true);
        return $mock;
    }

    private function crearProgramaMock(): ProgramaEntity
    {
        $mock = $this->createMock(ProgramaEntity::class);
        $mock->method('getEdadMinima')->willReturn(18);
        $mock->method('getSuperficieMaxima')->willReturn(50.0);
        $mock->method('getClave')->willReturn('PRONAFE');
        return $mock;
    }
}
```