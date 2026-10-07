# InDto Review and Correction Checklist (Mandatory)

Use this checklist when reviewing or correcting any In DTO. The goal is to keep all DTOs
consistent, immutable by default, and easy for agents to validate.

## Mandatory Structural Rules

1. Every input field must have a dedicated private typed property.

```php
private string $atributo1;
private string $atributo2;
private ?int $atributoOpcional;
```

2. The constructor must receive exactly one argument: `\stdClass $data`.
3. Do not use constructor property promotion in In DTO templates.
4. For every property, implement one `obtener...()` getter.
5. `asignar...()` methods are optional and allowed only when a value is intentionally assigned later.

## Constructor Contract

### Required

```php
public function __construct(\stdClass $data)
```

### Forbidden

```php
public function __construct(
    public string $atributo1,
    public string $atributo2,
    public ?int $atributoOpcional = null,
)
```

## Mapping Rules Inside Constructor

1. Assign all properties from `$data`.
2. Use explicit fallback values where needed.
3. Keep mapping logic simple; no business rules in DTOs.

Example:

```php
$this->atributo1 = $data->atributo1 ?? '';
$this->atributo2 = $data->atributo2 ?? '';
$this->atributoOpcional = $data->atributoOpcional ?? null;
```

## Getter Rules

1. Getter name must follow `obtener{Atributo}()`.
2. Return type must match property type.
3. Getter methods must not mutate state.

## `asignar...()` Rules

1. Add `asignar...()` only when deferred assignment is required by the use case.
2. Do not add `asignar...()` methods by default.
3. If the DTO is `readonly`, avoid `asignar...()` because post-construction mutation is not allowed.

## Review Acceptance Criteria

An In DTO passes review only if all conditions are true:

1. Constructor has exactly one parameter (`\stdClass $data`).
2. All expected attributes exist as private typed properties.
3. All attributes are mapped in constructor.
4. All attributes expose a corresponding `obtener...()` getter.
5. No constructor property promotion exists.
6. No business logic is implemented in the DTO.