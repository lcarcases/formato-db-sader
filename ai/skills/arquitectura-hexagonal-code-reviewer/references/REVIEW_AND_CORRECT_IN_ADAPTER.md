# InAdapter Review and Correction Checklist (Mandatory)

Use this checklist when reviewing or correcting any InAdapter. The goal is to keep all
InAdapters consistent, predictable, and aligned with the architecture rules.

## Required Flow in `__invoke()`

The `__invoke()` method must follow this order:

1. Instantiate `Respuesta` at the beginning of the method.
2. Validate input using `Validator`.
3. Build a plain input structure (for example, `$data`) from request values.
4. Create the corresponding In DTO from `$data`.
5. Invoke the Use Case and pass the In DTO.
6. Build a response structure from the Use Case result.
7. Create the Out DTO using the structure from step 6.
8. Return the success response through `Respuesta`.
9. Handle exceptions in `catch` blocks using `Respuesta`.

## Exception Handling Rules

### Mandatory

1. Do not return raw JSON responses directly in `catch` blocks.
2. Do not call `logger()` in the InAdapter.
3. Use `Respuesta` as the single mechanism for error responses and logging.

### Correct Pattern

```php
catch (\Exception $ex) {
    $respuesta->setSuccess(false);
    $respuesta->setData([]);
    $respuesta->setMessage('Error mientras se intentaba obtener los esquemas del hostname.');

    return $respuesta->errorResponse($ex);
}
```

### Pattern to Avoid

```php
return response()->json([
    'success' => false,
    'message' => 'El hostname solicitado no existe.',
    'data' => [],
], 404);
```

```php
logger()->error('Error al obtener ambientes', [
    'exception' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
]);
```

## Review Outcome Criteria

A reviewed InAdapter is considered valid only if:

1. The `__invoke()` flow follows the required order.
2. Input validation is explicit and early.
3. DTO boundaries are respected (In DTO and Out DTO are both present).
4. Error handling is centralized through `Respuesta`.
5. No direct `response()->json(...)` or `logger()` calls exist in the InAdapter.

   




   



