## 4.1 InPort y Decorator Pattern

**Template:** Use [templates/in-port.php](../templates/in-port.php) as a starting structure.

### ¿Qué es un InPort?

Un **InPort** es una interfaz que describe un caso de uso desde la perspectiva del cliente. Define el contrato que el UseCase debe cumplir. El InAdapter invoca al InPort, nunca al UseCase directamente.

```
InAdapter → InPort (interface) ← UseCase (implements)
```

### ¿Cuándo usar el Decorator Pattern?

Utiliza un **Decorator** cuando un caso de uso realiza **múltiples escrituras** (inserciones, actualizaciones) que deben ejecutarse de forma **atómica**. El Decorator envuelve la ejecución del UseCase en una transacción de base de datos.

**Reglas:**
- Tanto el Decorator como el UseCase implementan la misma interfaz InPort.
- El InAdapter inyecta el Decorator (no el UseCase directamente).
- El Decorator es responsable del control transaccional; el UseCase nunca debe conocer las transacciones.

```
InAdapter → IEmpadronarProductorInPort ← EmpadronarProductorDecorator → EmpadronarProductorUseCase
```

---

### Ejemplo Completo

**1. Definir el InPort**

```php
// filepath: app/Core/Padron/Application/Ports/In/IEmpadronarProductorInPort.php
<?php

namespace App\Core\Padron\Application\Ports\In;

use App\Core\Padron\Application\Dtos\In\EmpadronarProductorInDto;
use App\Core\Padron\Application\Dtos\Out\EmpadronarProductorOutDto;

interface IEmpadronarProductorInPort
{
    public function ejecutar(EmpadronarProductorInDto $dto): EmpadronarProductorOutDto;
}
```

**2. Implementar el UseCase**

```php
// filepath: app/Core/Padron/Application/UseCases/EmpadronarProductorUseCase.php
<?php

namespace App\Core\Padron\Application\UseCases;

use App\Core\Padron\Application\Ports\In\IEmpadronarProductorInPort;
use App\Core\Padron\Application\Ports\Out\IPersonaOutPort;
use App\Core\Padron\Application\Ports\Out\IPredioOutPort;
use App\Core\Padron\Application\Dtos\In\EmpadronarProductorInDto;
use App\Core\Padron\Application\Dtos\Out\EmpadronarProductorOutDto;
use App\Core\Padron\Domain\Entities\ProductorEntity;
use App\Core\Padron\Domain\Vo\CurpVO;

class EmpadronarProductorUseCase implements IEmpadronarProductorInPort
{
    public function __construct(
        private IPersonaOutPort $personaOutPort,
        private IPredioOutPort  $predioOutPort,
    ) {}

    public function ejecutar(EmpadronarProductorInDto $dto): EmpadronarProductorOutDto
    {
        // 1. Transform DTO → Value Objects
        $curp = new CurpVO($dto->curp);

        // 2. Build domain object
        $productor = new ProductorEntity(
            curp:       $curp,
            nombre:     $dto->nombre,
            superficie: $dto->superficie,
        );

        // 3. Persist via OutPorts (multiple writes — wrapped by Decorator)
        $personaId  = $this->personaOutPort->persistir($productor->getPersona());
        $predioId   = $this->predioOutPort->persistir($productor->getPredio());

        // 4. Return OutDto
        return new EmpadronarProductorOutDto(
            personaId: $personaId,
            predioId:  $predioId,
            mensaje:   'Productor empadronado exitosamente',
        );
    }
}
```

**3. Implementar el Decorator (control transaccional)**

```php
// filepath: app/Core/Padron/Application/UseCases/EmpadronarProductorDecorator.php
<?php

namespace App\Core\Padron\Application\UseCases;

use App\Core\Padron\Application\Ports\In\IEmpadronarProductorInPort;
use App\Core\Padron\Application\Ports\Out\ITransaccionOutPort;
use App\Core\Padron\Application\Dtos\In\EmpadronarProductorInDto;
use App\Core\Padron\Application\Dtos\Out\EmpadronarProductorOutDto;

class EmpadronarProductorDecorator implements IEmpadronarProductorInPort
{
    public function __construct(
        private IEmpadronarProductorInPort $useCase,
        private ITransaccionOutPort        $transaccion,
    ) {}

    public function ejecutar(EmpadronarProductorInDto $dto): EmpadronarProductorOutDto
    {
        $this->transaccion->beginTransaction();

        try {
            $result = $this->useCase->ejecutar($dto);
            $this->transaccion->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->transaccion->rollback();
            throw $e;
        }
    }
}
```

**4. Registrar en el Service Container**

```php
// En el ServiceProvider del módulo Padron
$this->app->bind(IEmpadronarProductorInPort::class, function ($app) {
    return new EmpadronarProductorDecorator(
        useCase:     $app->make(EmpadronarProductorUseCase::class),
        transaccion: $app->make(ITransaccionOutPort::class),
    );
});
```

---

### Cuándo NO usar el Decorator

| Situación | Solución |
|-----------|----------|
| El caso de uso solo realiza una escritura | Bind directo del UseCase al InPort, sin Decorator |
| Solo se realiza lecturas (queries) | Bind directo del UseCase al InPort, sin Decorator |
| Múltiples escrituras en OutAdapters distintos de BD (ej: S3 + DB) | Evaluar Saga pattern o manejo de compensación manual |
