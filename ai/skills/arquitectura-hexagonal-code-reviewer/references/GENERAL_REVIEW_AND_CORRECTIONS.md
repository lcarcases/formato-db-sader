# General Review and Corrections Checklist (Mandatory)

Use this checklist to review and correct code across all layers. Rules are intentionally explicit so
an AI agent can apply them deterministically.

## 1. Readability and Clarity Rules

1. Prefer explicit loops (`for`, `foreach`) when they are clearer than dense functional chains.
2. Avoid `array_map` with nested constructors when it reduces readability.
3. Prioritize maintainable code over compact one-liners.

### Pattern to Avoid

```php
$obtenerEsquemasPorHostnameOutDto = new ObtenerEsquemasPorHostnameOutDto(
    array_map(
        fn (EsquemaVO $esquema): ObtenerEsquemaOutDto => new ObtenerEsquemaOutDto(
            id: $esquema->id,
            nombre: $esquema->nombre,
        ),
        $esquemas
    )
);
```

### Preferred Pattern

```php
$esquemasDtos = [];
foreach ($esquemas as $esquema) {
    $esquemasDtos[] = new ObtenerEsquemaOutDto(
        id: $esquema->id,
        nombre: $esquema->nombre,
    );
}

$obtenerEsquemasPorHostnameOutDto = new ObtenerEsquemasPorHostnameOutDto($esquemasDtos);
```

## 2. Layer Purity Rules (Hexagonal + DDD)

1. Domain layer must be framework-agnostic.
2. Application layer must not depend on framework or infrastructure code.
3. External libraries are allowed only when they do not violate layer boundaries and are justified.
4. Infrastructure layer is the only layer allowed to directly use framework-specific code.

## 3. Forbidden Dependencies by Layer

1. Domain classes must not import `Illuminate\\*`.
2. Application classes must not import controllers, Eloquent models, requests, or other adapter code.
3. Use cases must depend on ports/interfaces, not concrete infrastructure implementations.

## 4. Cross-module interaction Rules

1. Generated code must remain inside its own module.
2. The code may reference only:
   - classes in the same module, or
   - classes explicitly located in the `shared` module.
3. The code must not call, instantiate, or depend on classes from sibling modules.
4. Cross-module calls are forbidden unless the dependency is intentionally designed as shared infrastructure or shared domain logic.

### Correct Example

```php
// same module
$service = new CatalogoPermisosService();
$result = $service->ejecutar();

// allowed shared dependency
use App\Core\Shared\Domain\ValueObjects\FechaVO;
$fecha = new FechaVO('2026-09-15');
```

### Incorrect Example

```php
// WRONG: cross-module dependency
use App\Core\Admin\Infrastructure\Persistence\TipoPermisoRepository;

$repository = new TipoPermisoRepository();
$result = $repository->obtenerTodos();
```

5. If one module must interact with another module, the interaction must be implemented through a `Mediator` pattern.
6. Only Use Cases from another module may be invoked through that mediator.
7. Direct calls to repositories, services, entities, or adapters from sibling modules are forbidden.
8. The mediator is the only approved boundary mechanism for cross-module communication.

## 5. Mandatory Review Actions

For each file under review, the agent must:

1. Identify readability issues that harm maintainability.
2. Detect layer-boundary violations.
3. Replace unclear constructs with clearer equivalents when behavior is unchanged.
4. Keep refactors minimal and avoid unrelated formatting-only edits.
5. Verify module boundaries are respected.

## 6. Acceptance Criteria

A review passes only if all checks are true:

1. Code is readable and avoids unnecessarily complex transformations.
2. Domain and Application layers contain no forbidden framework coupling.
3. Dependencies respect direction: Infrastructure -> Application -> Domain.
4. Module boundaries are respected: same module or `shared` only.
5. Behavior remains equivalent after readability refactors.










