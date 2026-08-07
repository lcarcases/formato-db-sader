## Step 5: Implement Use Case

**Template:** Use [templates/use-case.php](../templates/use-case.php) as a starting structure.

## 🚨 CRITICAL UseCase Patterns

### ✅ What UseCases MUST DO:
1. **Use Spanish verb names** (`Obtener`, `Crear`, `Actualizar`, not `Get`, `Create`, `Update`)
2. **Return RAW arrays** (for maximum reusability, not DTOs)
3. **Throw exceptions** (don't catch them - let InAdapter handle)
4. **Depend on OutPort interfaces** (never on concrete Repositories or OutAdapters)
5. **Use standard constructor** (NOT `private readonly`)
6. **Declare private property separately** from constructor

### ❌ What UseCases MUST NOT DO:
1. **Implement InPort interface** UNLESS using Decorator pattern (for multiple inserts/updates)
2. **Return DTOs** (reduces reusability - use raw arrays instead)
3. **Catch exceptions** (only InAdapter catches exceptions)
4. **Use `private readonly`** in constructor (use standard pattern)
5. **Use English verbs** in naming (`Get`, `Create`, etc.)
6. **Depend directly on Repositories** (use OutPort interfaces)

---

## ✅ Correct UseCase Pattern (Simple CRUD - NO Interface)

```php
// filepath: app/Core/Admin/Application/UseCases/ObtenerTiposRequerimientosUseCase.php
<?php

namespace App\Core\Admin\Application\UseCases;

use App\Core\Admin\Application\Ports\Out\ITipoRequerimientoOutPort;

// ✅ CORRECT: NO interface implementation for simple CRUD
final class ObtenerTiposRequerimientosUseCase
{
    // ✅ Declare private property separately
    private ITipoRequerimientoOutPort $tipoRequerimientoOutPort;

    // ✅ Standard constructor (NOT private readonly)
    public function __construct(
        ITipoRequerimientoOutPort $tipoRequerimientoOutPort
    ) {
        $this->tipoRequerimientoOutPort = $tipoRequerimientoOutPort;
    }

    // ✅ Returns RAW array (not DTO!)
    // ✅ Throws exceptions (doesn't catch them)
    public function obtener(): array
    {
        // ✅ Call OutPort interface (not Repository directly!)
        $tiposRequerimientos = $this->tipoRequerimientoOutPort->obtenerTodos();
        
        // ✅ Return raw array
        return $tiposRequerimientos;
        
        // OR if transformation needed:
        // return [
        //     'tipos_requerimientos' => $tiposRequerimientos,
        //     'total' => count($tiposRequerimientos)
        // ];
    }
}
```

---

## ❌ Common Mistakes in UseCases

### Mistake 1: Using English verbs + Interface when not needed

```php
// ❌ WRONG: English verb "Get"
final class GetTipoRequerimientoUseCase implements IGetTipoRequerimientoUseCase
{
    // ❌ Interface not needed for simple CRUD!
}

// ✅ CORRECT: Spanish verb "Obtener", no interface
final class ObtenerTiposRequerimientosUseCase
{
    // ✅ No interface for simple operations
}
```

### Mistake 2: Using `private readonly` pattern

```php
// ❌ WRONG: private readonly in constructor
public function __construct(
    private readonly ITipoRequerimientoOutPort $tipoRequerimientoRepository
) {}

// ✅ CORRECT: Standard constructor pattern
private ITipoRequerimientoOutPort $tipoRequerimientoOutPort;

public function __construct(
    ITipoRequerimientoOutPort $tipoRequerimientoOutPort
) {
    $this->tipoRequerimientoOutPort = $tipoRequerimientoOutPort;
}
```

### Mistake 3: Returning DTO instead of array

```php
// ❌ WRONG: Returns DTO (reduces reusability)
public function execute(): GetTipoRequerimientoOutDto
{
    $data = $this->repository->findAll();
    return new GetTipoRequerimientoOutDto(
        data: $data,
        success: true,
        message: 'Success'
    );
}

// ✅ CORRECT: Returns raw array (maximum reusability)
public function obtener(): array
{
    return $this->tipoRequerimientoOutPort->obtenerTodos();
}
```

### Mistake 4: Catching exceptions

```php
// ❌ WRONG: UseCase catches exceptions
public function execute(): array
{
    try {
        $data = $this->repository->findAll();
        return ['success' => true, 'data' => $data];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// ✅ CORRECT: UseCase throws exceptions (InAdapter catches them)
public function obtener(): array
{
    // Let exceptions bubble up!
    return $this->tipoRequerimientoOutPort->obtenerTodos();
}
```

### Mistake 5: Depending directly on Repository

```php
// ❌ WRONG: Direct dependency on Repository
private TipoRequerimientoPostgresSQLRepository $repository;

public function __construct(
    TipoRequerimientoPostgresSQLRepository $repository
) {
    $this->repository = $repository;
}

// ✅ CORRECT: Dependency on OutPort interface
private ITipoRequerimientoOutPort $tipoRequerimientoOutPort;

public function __construct(
    ITipoRequerimientoOutPort $tipoRequerimientoOutPort
) {
    $this->tipoRequerimientoOutPort = $tipoRequerimientoOutPort;
}
```

---

## When to Implement InPort Interface (Decorator Pattern)

**ONLY implement InPort interface when:**
- Multiple inserts/updates in sequence
- Transaction management needed
- Cross-cutting concerns (logging, caching, validation)
- Need to compose/decorate behavior

### Example WITH Decorator (Multiple Operations)

```php
// ✅ USE interface when Decorator pattern is needed
final class CrearSolicitudConDocumentosUseCase implements ICrearSolicitudInPort
{
    private ISolicitudOutPort $solicitudOutPort;
    private IDocumentoOutPort $documentoOutPort;

    public function __construct(
        ISolicitudOutPort $solicitudOutPort,
        IDocumentoOutPort $documentoOutPort
    ) {
        $this->solicitudOutPort = $solicitudOutPort;
        $this->documentoOutPort = $documentoOutPort;
    }

    public function ejecutar(CrearSolicitudInDto $dto): array
    {
        // Multiple operations - could be decorated with transaction, logging, etc.
        $solicitudId = $this->solicitudOutPort->crear($dto->solicitudData);
        
        foreach ($dto->documentos as $doc) {
            $this->documentoOutPort->adjuntar($solicitudId, $doc);
        }
        
        return ['solicitud_id' => $solicitudId];
    }
}
```

---

## Complete UseCase Example (Complex)

**Responsibilities (MUST DO):**
```
✅ Orchestrate domain objects (entities, VOs, services)
✅ Call OutPorts for external interactions
✅ Transform InDto → Domain objects
✅ Execute sequences of steps to achieve goal
✅ Use conditionals (IF) for flow control
✅ Use loops (FOR, WHILE) to process lists
✅ Throw domain exceptions on business errors
✅ Dispatch domain events when state changes
✅ Return raw arrays
```

**Restrictions (MUST NOT):**
```
❌ Use Laravel classes ($request, facades, etc.)
❌ Access database directly (use OutPorts)
❌ Contain domain logic that belongs to entities
❌ Import anything from Infrastructure layer
❌ Know about HTTP, CLI, or delivery mechanism
❌ Catch exceptions (let InAdapter handle)
❌ Return DTOs (use raw arrays)
```

**Complete Example:**
```php
// filepath: app/Core/Programa/Application/UseCases/GenerarSolicitudUseCase.php
<?php

namespace App\Core\Programa\Application\UseCases;

use App\Core\Programa\Application\Dtos\In\GenerarSolicitudInDto;
use App\Core\Programa\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Programa\Application\Ports\Out\IPersonaOutPort;
use App\Core\Programa\Application\Ports\Out\IProgramaOutPort;
use App\Core\Programa\Domain\Entities\SolicitudEntity;
use App\Core\Programa\Domain\Vo\CurpVO;
use App\Core\Programa\Domain\Vo\FolioVO;
use App\Core\Programa\Domain\Specifications\ExistePersonaSpecification;
use App\Core\Programa\Domain\Exceptions\PersonaNoEncontradaException;
use App\Core\Programa\Domain\Exceptions\PersonaNoActivaException;

// ✅ No interface unless Decorator pattern needed
final class GenerarSolicitudUseCase
{
    private ISolicitudOutPort $solicitudOutPort;
    private IPersonaOutPort $personaOutPort;
    private IProgramaOutPort $programaOutPort;

    public function __construct(
        ISolicitudOutPort $solicitudOutPort,
        IPersonaOutPort $personaOutPort,
        IProgramaOutPort $programaOutPort
    ) {
        $this->solicitudOutPort = $solicitudOutPort;
        $this->personaOutPort = $personaOutPort;
        $this->programaOutPort = $programaOutPort;
    }

    // ✅ Returns raw array
    // ✅ Throws exceptions (no try-catch)
    public function ejecutar(GenerarSolicitudInDto $dto): array
    {
        // 1. Transform DTO → Domain (Value Objects)
        $curp = new CurpVO($dto->curp);
        
        // 2. Call OutPorts to get data
        $persona = $this->personaOutPort->buscarPorCurp($curp);
        $programa = $this->programaOutPort->buscarPorClave($dto->clavePrograma);
        
        // 3. Use Specifications for validation
        $existePersona = new ExistePersonaSpecification();
        if (!$existePersona->isSatisfiedBy($persona)) {
            // ✅ Throw exception (InAdapter will catch)
            throw new PersonaNoEncontradaException($curp->valor());
        }
        
        // 4. Validate business rules (use entity behavior)
        if (!$persona->estaActiva()) {
            throw new PersonaNoActivaException($curp->valor());
        }
        
        // 5. Create domain objects
        $folio = FolioVO::generar($programa, $dto->estado);
        $solicitud = new SolicitudEntity(
            folio: $folio,
            persona: $persona,
            programa: $programa
        );
        
        // 6. Call OutPort to persist
        $id = $this->solicitudOutPort->persistir($solicitud);
        
        // 7. Return raw array (NOT DTO!)
        return [
            'id' => $id,
            'folio' => $folio->valor(),
            'estatus' => $solicitud->getEstatus()->value
        ];
    }
}
```

**Use Case Pattern:**
```
┌─────────────────────────────────────────────────────────────┐
│                        USE CASE                             │
├─────────────────────────────────────────────────────────────┤
│ 1. Receive InDto                                            │
│ 2. Transform InDto → Domain Objects (VOs, Entities)         │
│ 3. Call OutPorts to retrieve/persist data                   │
│ 4. Validate using Specifications                            │
│ 5. Execute Entity behavior (domain logic)                   │
│ 6. Call Domain Services if cross-entity logic needed        │
│ 7. Persist via OutPorts                                     │
│ 8. Dispatch Domain Events if needed                         │
│ 9. Return RAW array (NOT DTO!)                              │
│ 10. Throw exceptions (let InAdapter handle)                 │
└─────────────────────────────────────────────────────────────┘
```
