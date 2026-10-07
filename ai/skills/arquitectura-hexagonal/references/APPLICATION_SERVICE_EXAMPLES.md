Core principle

Use Cases define the goal; Application Services provide reusable application capabilities to achieve those goals; the Domain encapsulates the business knowledge and rules required to make the correct decisions. An Application Service is a reusable application capability that is normally invoked by other application components, especially Use Cases, rather than being a direct user-facing application goal. Application Services and Use Cases can look structurally very similar because both can contain orchestration logic.

When implementing or reviewing a Use Case, the AI agent should ask:

"Is this code implementing a capability that another Use Case could reasonably need?"

If the answer is yes, determine whether that capability is application-level behavior. If multiple Use Cases already implement it—or are likely to require the same capability—it should be promoted to an Application Service rather than duplicated.


Application Service design rules:

- Application Services belong to the Application Layer.
- Application Services represent reusable application capabilitie
- Are invoked or used for use cases
- A Use Case may invoke one or more Application Services
- An Application Service may be invoked by multiple Use Cases
- When multiple Use Cases duplicate the same application-level behavior, extract that behavior into an Application Service
- Application Services may orchestrate Domain Entities, Aggregates, Domain Services, Specifications, and Ports
- Application Services may coordinate multiple steps required to accomplish an application capability
- Application Services just do one thing

- Application Services must not contain infrastructure implementation details. They should interact with infrastructure through Ports.

- Application Services should be cohesive. Each service should represent one meaningful application capability.


# Application Service Examples

## Step 5: Implement Application Service

**Template:** Use the application service pattern when the behavior is reusable across multiple use cases and is not itself the user-facing goal.

## 🚨 CRITICAL Application Service Patterns

### ✅ What Application Services MUST DO:
1. **Represent reusable application capabilities**
2. **Be invoked by one or more Use Cases**
3. **Contain orchestration and coordination logic** for application-level operations
4. **Use domain objects, specifications, and services** when needed
5. **Depend on OutPorts or other application contracts** instead of concrete repositories
6. **Keep a single responsibility**: one meaningful capability per service
7. **Stay in the Application layer** and avoid infrastructure details

### ❌ What Application Services MUST NOT DO:
1. **Implement the user-facing goal directly** if the behavior is a Use Case responsibility
2. **Contain database access logic directly**
3. **Depend on concrete infrastructure implementations**
4. **Return HTTP/transport responses**
5. **Duplicate logic already owned by a Domain Service or Entity**
6. **Do too many unrelated things in one service**

---

## ✅ Correct Application Service Pattern (Reusable Capability)

```php
<?php

namespace App\Core\Shared\Application\Services;

use App\Core\Shared\Application\DTOs\In\EnviarCorreoInDto;
use App\Core\Shared\Application\Ports\Out\CorreoSenderOutPort;

final class EnviarCorreoService
{
    private CorreoSenderOutPort $correoSenderOutPort;

    public function __construct(CorreoSenderOutPort $correoSenderOutPort)
    {
        $this->correoSenderOutPort = $correoSenderOutPort;
    }

    public function enviar(EnviarCorreoInDto $dto): void
    {
        $override = config('correo.destinatario_override');

        if (is_string($override) && $override !== '') {
            $dto = $dto->conPara([$override]);
        }

        $this->correoSenderOutPort->enviar($dto);
    }
}
```

### Why this is correct
- It represents a reusable application capability.
- It is reusable across multiple Use Cases.
- It coordinates application behavior without knowing about HTTP.
- It depends on a port, not a concrete implementation.
- It performs one clear responsibility: sending mail.

---

## ❌ Common Mistakes in Application Services

### Mistake 1: Turning an Application Service into a Use Case

```php
// ❌ WRONG: this is a user goal, not a reusable capability
final class ObtenerTiposPermisoService
{
    public function ejecutar(): array
    {
        return ['tipos' => []];
    }
}
```

```php
// ✅ CORRECT: this is a reusable capability, not the primary business goal
final class ValidarPermisoService
{
    public function validar(string $permiso): bool
    {
        return $permiso !== '';
    }
}
```

### Mistake 2: Putting infrastructure details directly in the service

```php
// ❌ WRONG: direct Eloquent / framework use in Application layer
final class ObtenerPermisosService
{
    public function ejecutar(): array
    {
        return DB::table('tb_cat_tipo_permiso')->get()->toArray();
    }
}
```

```php
// ✅ CORRECT: service delegates to OutPort
final class ObtenerPermisosService
{
    private ITipoPermisoOutPort $tipoPermisoOutPort;

    public function __construct(ITipoPermisoOutPort $tipoPermisoOutPort)
    {
        $this->tipoPermisoOutPort = $tipoPermisoOutPort;
    }

    public function ejecutar(): array
    {
        return $this->tipoPermisoOutPort->obtenerTodos();
    }
}
```

### Mistake 3: Doing too much in one service

```php
// ❌ WRONG: mixing multiple capabilities in one service
final class GestionarCatalogoService
{
    public function obtenerTiposPermiso(): array
    {
        // ...
    }

    public function crearTipoPermiso(): array
    {
        // ...
    }

    public function enviarCorreo(): void
    {
        // ...
    }
}
```

```php
// ✅ CORRECT: one capability per service
final class ObtenerTiposPermisoService
{
    // one responsibility
}

final class CrearTipoPermisoService
{
    // one responsibility
}

final class EnviarCorreoService
{
    // one responsibility
}
```

### Mistake 4: Keeping business rules inside the Application Service when they belong in Domain

```php
// ❌ WRONG: domain rule belongs in entity or specification
final class ValidarClienteService
{
    public function validar(string $edad): bool
    {
        return (int) $edad >= 18;
    }
}
```

```php
// ✅ CORRECT: domain logic lives in the domain model
final class Cliente
{
    public function puedeAcceder(): bool
    {
        return $this->edad >= 18;
    }
}
```

---

## When to Promote Logic to an Application Service

Promote behavior to an Application Service when:
- multiple Use Cases need the same capability,
- the capability is reusable and not a primary user goal,
- the logic is application-level coordination rather than core domain policy,
- the task requires orchestration across domain objects and ports.

### Decision rule

Ask:
- "Would several Use Cases reasonably reuse this behavior?"
- "Does this represent a capability, not a business goal?"
- "Does this belong in the Application layer rather than the Domain?"

If the answer is yes, create or extract an Application Service.

---

---

## Final Review Checklist for Application Services

An Application Service passes review only if all checks are true:

1. It represents a reusable application capability.
2. It is not the primary user-facing business goal.
3. It depends on interfaces/ports, not concrete repositories.
4. It avoids infrastructure leakage.
5. It has a single responsible duty.
6. Domain rules stay in Domain, not in the service.
7. It is invoked by one or more Use Cases and not directly by HTTP code.