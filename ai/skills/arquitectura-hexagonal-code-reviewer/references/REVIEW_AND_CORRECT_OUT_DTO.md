# OutDto Review and Correction Checklist (Mandatory)

Use this checklist when reviewing or correcting any Out DTO. The goal is to keep Out DTOs
consistent, explicit, and easy for agents to validate.

## Mandatory Structural Rules

1. Do not use constructor property promotion.
2. The constructor must receive exactly one parameter: `\stdClass $data`.
3. Define class attributes explicitly as `public` typed properties.

## Forbidden Constructor Pattern

```php
public function __construct(
    public int $id,
    public string $atributo1Formateado,
    public string $atributo2Formateado,
)
```

## Required Constructor Signature

```php
public function __construct(\stdClass $data)
```

## Required Property Style

```php
public int $id;
public string $atributo1Formateado;
public string $atributo2Formateado;
```

## Mapping Rules in Constructor

1. Map each property from `$data` explicitly.
2. Keep constructor logic limited to data assignment and formatting required by the Out DTO.
3. Do not add domain or business logic inside the DTO.

Example:

```php
$this->id = $data->id;
$this->atributo1Formateado = $data->atributo1Formateado;
$this->atributo2Formateado = $data->atributo2Formateado;
```

## Mandatory Review Actions

For each Out DTO under review, the agent must verify:

1. Constructor has exactly one parameter (`\stdClass $data`).
2. No constructor property promotion exists.
3. All expected output fields are declared as public typed properties.
4. All declared properties are assigned in the constructor.
5. No business rules are implemented in the Out DTO.

## Acceptance Criteria

An Out DTO passes review only if all checks are true:

1. Constructor contract is respected (`\stdClass $data` only).
2. Property declarations are explicit and public.
3. Mapping is complete and deterministic.
4. Behavior is limited to data transport/format output shape.