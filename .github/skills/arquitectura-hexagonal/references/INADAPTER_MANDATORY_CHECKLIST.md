# InAdapter Mandatory Checklist

## ⚠️ This checklist MUST be followed for EVERY InAdapter implementation

InAdapters are the entry points to your use cases (API controllers, CLI commands, web components). They have specific patterns that MUST be followed consistently.

---

## 📋 Pre-Implementation Checklist

Before writing any InAdapter code, verify you understand:

- [ ] What is the business action? (e.g., "Get requirement types")
- [ ] What is the Spanish verb for this action? (e.g., "Obtener" not "Get")
- [ ] What module/bounded context does this belong to? (e.g., Admin, Programa, etc.)
- [ ] What type of adapter is this? (API, CLI, Web/Livewire)

---

## ✅ MANDATORY PATTERNS

### 1. Naming Convention ⚠️ CRITICAL

#### ✅ DO THIS:
- Name format: `{VerbSpanish}{NounSpanish}InAdapter`
- Use Spanish infinitive verb (Obtener, Crear, Actualizar, Eliminar, Listar, Generar, etc.)
- End with `InAdapter` suffix
- Examples:
  - `ObtenerTiposRequerimientosInAdapter` ✅
  - `CrearSolicitudInAdapter` ✅
  - `ActualizarBeneficiarioInAdapter` ✅
  - `EliminarDocumentoInAdapter` ✅

#### ❌ NEVER DO THIS:
- ❌ `GetTipoRequerimientoController` - English verb + Controller suffix
- ❌ `GetTiposRequerimientosInAdapter` - English verb
- ❌ `ObtenerTiposRequerimientosController` - Controller suffix
- ❌ `TipoRequerimientoController` - Missing verb + wrong suffix
- ❌ `FetchTiposRequerimientosInAdapter` - English verb

**Common Spanish Verbs:**
```
Obtener  = Get/Fetch/Retrieve
Crear    = Create
Actualizar = Update
Eliminar = Delete
Listar   = List
Generar  = Generate
Aprobar  = Approve
Rechazar = Reject
Validar  = Validate
Consultar = Query/Consult
Registrar = Register
Enviar   = Send
Procesar = Process
```

**Verification:**
- [ ] Name uses Spanish verb (not English)
- [ ] Name ends with `InAdapter` (not `Controller`)
- [ ] Name is in PascalCase
- [ ] Name accurately describes the action

---

### 2. Import Statements ⚠️ CRITICAL

#### ✅ DO THIS:
```php
use App\Core\Shared\Infraestructure\Respuesta;  // Note: Infraestructure with 'a'
use App\Core\{Module}\Application\UseCases\{UseCase}UseCase;
use App\Core\{Module}\Application\Dtos\In\{InDto};
use Illuminate\Http\Request;  // Only for web adapters
```

#### ❌ NEVER DO THIS:
```php
use App\Core\Shared\Infrastructure\Respuesta;  // ❌ Wrong: Infrastructure with 't'
use App\Core\{Module}\Application\Ports\In\I{UseCase}InPort;  // ❌ Don't import ports in adapters
```

**Verification:**
- [ ] Respuesta imported from `App\Core\Shared\Infraestructure\Respuesta` (with 'a')
- [ ] UseCase class imported (concrete class, not interface)
- [ ] InDto imported (if needed for request validation)
- [ ] No unnecessary Laravel imports in private methods

---

### 3. Class Structure ⚠️ CRITICAL

#### ✅ DO THIS:
```php
class ObtenerTiposRequerimientosInAdapter
{
    // 1. Private property declaration (separate from constructor)
    private ObtenerTiposRequerimientosUseCase $obtenerTiposRequerimientosUseCase;

    // 2. Constructor with app()->make() wrapped in try-catch
    public function __construct()
    {
        try {
            $this->obtenerTiposRequerimientosUseCase = app()->make(ObtenerTiposRequerimientosUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    // 3. Public method(s) for handling requests
    public function obtener()
    {
        try {
            // Method implementation...
        } catch (\Exception $ex) {
            // Error handling...
        }
    }
}
```

#### ❌ NEVER DO THIS:
```php
// ❌ WRONG: Dependency injection in constructor parameters
class ObtenerTiposRequerimientosInAdapter
{
    public function __construct(
        private readonly IGetTipoRequerimientoUseCase $getTipoRequerimientoUseCase
    ) {}
}

// ❌ WRONG: No property declaration
class ObtenerTiposRequerimientosInAdapter
{
    public function __construct()
    {
        $this->useCase = app()->make(SomeUseCase::class);
    }
}
```

**Verification:**
- [ ] Private property declared separately (not in constructor parameters)
- [ ] Property uses concrete UseCase class type (not interface)
- [ ] Constructor uses `app()->make()` to resolve dependency
- [ ] Constructor wrapped in try-catch
- [ ] No `private readonly` in constructor parameters

---

### 4. Constructor Pattern ⚠️ CRITICAL

#### ✅ DO THIS:
```php
private ObtenerTiposRequerimientosUseCase $obtenerTiposRequerimientosUseCase;

public function __construct()
{
    try {
        $this->obtenerTiposRequerimientosUseCase = app()->make(ObtenerTiposRequerimientosUseCase::class);
    } catch (\Exception $ex) {
        throw $ex;
    }
}
```

#### ❌ NEVER DO THIS:
```php
// ❌ WRONG: Dependency injection
public function __construct(
    private IGetTipoRequerimientoUseCase $useCase
) {}

// ❌ WRONG: No try-catch
public function __construct()
{
    $this->useCase = app()->make(SomeUseCase::class);
}

// ❌ WRONG: Using interface instead of concrete class
public function __construct()
{
    try {
        $this->useCase = app()->make(ISomeUseCase::class);
    } catch (\Exception $ex) {
        throw $ex;
    }
}
```

**Verification:**
- [ ] Constructor has no parameters
- [ ] Uses `app()->make({ConcreteUseCase}::class)`
- [ ] Wrapped in try-catch
- [ ] Assigns to properly declared private property
- [ ] Uses concrete UseCase class (not interface)

---

### 5. Method Implementation ⚠️ CRITICAL

#### ✅ DO THIS:
```php
public function obtener()  // or obtenerTipos(), create(), etc.
{
    try {
        // 1. Create Respuesta instance FIRST
        $respuesta = new Respuesta();
        
        // 2. Optional: Validate request, create DTOs, etc.
        // $inDto = new SomeInDto(...);
        
        // 3. Execute use case
        \Log::info("Executing ObtenerTiposRequerimientos use case");
        $outDto = $this->obtenerTiposRequerimientosUseCase->obtener();
        \Log::info("Use case executed successfully");
        
        // 4. Set up successful response
        $respuesta->setSuccess(true);
        $respuesta->setMessage("Se obtuvieron los tipos de requerimientos correctamente.");
        $respuesta->setData($outDto);
        
        // 5. Return standardized success response
        return $respuesta->successResponse();
        
    } catch (\Exception $ex) {
        // 6. Handle errors with standardized error response
        $respuesta->setSuccess(false);
        $respuesta->setData([]);
        $respuesta->setMessage("Error mientras se intentaba obtener los tipos de requerimientos.");
        return $respuesta->errorResponse($ex);
    }
}
```

#### ❌ NEVER DO THIS:
```php
// ❌ WRONG: No try-catch
public function obtener()
{
    $respuesta = new Respuesta();
    $outDto = $this->useCase->execute();
    $respuesta->setSuccess(true);
    return $respuesta->successResponse();
}

// ❌ WRONG: Not creating Respuesta first, incomplete setup
public function __invoke(): JsonResponse
{
    $result = $this->useCase->execute();
    return response()->json($result->toArray(), 200);
}

// ❌ WRONG: Missing error handling in catch
public function obtener()
{
    try {
        $respuesta = new Respuesta();
        $outDto = $this->useCase->execute();
        $respuesta->setSuccess(true);
        return $respuesta->successResponse();
    } catch (\Exception $ex) {
        return response()->json(['error' => $ex->getMessage()], 500);
    }
}
```

**Verification:**
- [ ] Entire method wrapped in try-catch
- [ ] `$respuesta = new Respuesta();` as first line in try block
- [ ] Use case executed and result captured
- [ ] Success response sets: success, message, data
- [ ] Returns `$respuesta->successResponse()`
- [ ] Catch block sets: success(false), data([]), message
- [ ] Catch block returns `$respuesta->errorResponse($ex)`
- [ ] All messages in Spanish

---

### 6. Respuesta Usage Pattern ⚠️ CRITICAL

#### ✅ DO THIS - Success Path:
```php
// 1. Create instance
$respuesta = new Respuesta();

// 2. Execute business logic
$outDto = $this->someUseCase->someMethod();

// 3. Set success properties
$respuesta->setSuccess(true);
$respuesta->setMessage("Operación exitosa en español.");
$respuesta->setData($outDto);

// 4. Return standardized response
return $respuesta->successResponse();
```

#### ✅ DO THIS - Error Path:
```php
catch (\Exception $ex) {
    // 1. Set failure properties
    $respuesta->setSuccess(false);
    $respuesta->setData([]);
    $respuesta->setMessage("Mensaje de error en español.");
    
    // 2. Return standardized error response
    return $respuesta->errorResponse($ex);
}
```

#### ❌ NEVER DO THIS:
```php
// ❌ WRONG: Creating Respuesta in catch (should be in try)
try {
    $outDto = $this->useCase->execute();
    return response()->json($outDto);
} catch (\Exception $ex) {
    $respuesta = new Respuesta();  // ❌ Too late!
    return $respuesta->errorResponse($ex);
}

// ❌ WRONG: Not setting all required properties
$respuesta->setSuccess(true);
return $respuesta->successResponse();  // ❌ Missing message and data!

// ❌ WRONG: Raw JSON response
return response()->json([
    'success' => true,
    'data' => $outDto
]);
```

**Verification:**
- [ ] Respuesta instance created in try block (not catch)
- [ ] Success path calls: setSuccess(true), setMessage(), setData()
- [ ] Success path returns: successResponse()
- [ ] Error path calls: setSuccess(false), setData([]), setMessage()
- [ ] Error path returns: errorResponse($ex)
- [ ] No raw JSON responses (response()->json())
- [ ] All messages in Spanish

---

## 📝 Complete Correct Example

```php
<?php

namespace App\Core\Admin\Infrastructure\Adapters\In\Api;

use App\Core\Admin\Application\UseCases\ObtenerTiposRequerimientosUseCase;
use App\Core\Shared\Infraestructure\Respuesta;  // ⚠️ Note: Infraestructure with 'a'

/**
 * ObtenerTiposRequerimientosInAdapter
 * 
 * Entry point for retrieving requirement types via REST API.
 */
class ObtenerTiposRequerimientosInAdapter
{
    // ✅ Private property declared separately
    private ObtenerTiposRequerimientosUseCase $obtenerTiposRequerimientosUseCase;

    /**
     * ✅ Constructor with app()->make() wrapped in try-catch
     */
    public function __construct()
    {
        try {
            $this->obtenerTiposRequerimientosUseCase = app()->make(ObtenerTiposRequerimientosUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * ✅ Method with complete Respuesta pattern
     */
    public function obtener()
    {
        try {
            // ✅ 1. Create Respuesta instance FIRST
            $respuesta = new Respuesta();
            
            \Log::info("Executing ObtenerTiposRequerimientos use case");
            
            // ✅ 2. Execute use case
            $obtenerTiposRequerimientosOutDto = $this->obtenerTiposRequerimientosUseCase->obtener();
            
            \Log::info("Use case executed successfully");
            
            // ✅ 3. Set up successful response
            $respuesta->setSuccess(true);
            $respuesta->setMessage("Se obtuvieron los tipos de requerimientos correctamente.");
            $respuesta->setData($obtenerTiposRequerimientosOutDto);
            
            // ✅ 4. Return standardized success response
            return $respuesta->successResponse();
            
        } catch (\Exception $ex) {
            // ✅ 5. Handle errors with standardized error response
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage("Error mientras se intentaba obtener los tipos de requerimientos.");
            return $respuesta->errorResponse($ex);
        }
    }
}
```

---

## 🔍 Final Verification Checklist

Before committing an InAdapter, verify ALL of these:

### Naming & Imports
- [ ] ✅ Class name uses Spanish verb (Obtener, Crear, etc.)
- [ ] ✅ Class name ends with `InAdapter` (never `Controller`)
- [ ] ✅ Respuesta imported from `App\Core\Shared\Infraestructure\Respuesta` (with 'a')
- [ ] ✅ UseCase concrete class imported (not interface)

### Class Structure
- [ ] ✅ Private property declared separately (not in constructor)
- [ ] ✅ Property uses concrete UseCase class type
- [ ] ✅ Constructor has no parameters
- [ ] ✅ Constructor uses `app()->make(ConcreteUseCase::class)`
- [ ] ✅ Constructor wrapped in try-catch

### Method Implementation
- [ ] ✅ Entire method wrapped in try-catch
- [ ] ✅ Creates `$respuesta = new Respuesta();` as first line
- [ ] ✅ Executes use case and captures result
- [ ] ✅ Success path: setSuccess(true), setMessage(), setData()
- [ ] ✅ Success path: returns successResponse()
- [ ] ✅ Catch block: setSuccess(false), setData([]), setMessage()
- [ ] ✅ Catch block: returns errorResponse($ex)
- [ ] ✅ All messages in Spanish
- [ ] ✅ No raw JSON responses

### Best Practices
- [ ] ✅ PHPDoc comments present
- [ ] ✅ Logging statements for debugging (optional but recommended)
- [ ] ✅ Request validation if accepting input
- [ ] ✅ InDto created if use case requires input
- [ ] ✅ No business logic in adapter

---

## 🚨 Common Mistakes Reference

| Mistake | Wrong Code | Correct Code |
|---------|-----------|--------------|
| **English verb** | `GetTipoRequerimientoInAdapter` | `ObtenerTiposRequerimientosInAdapter` |
| **Controller suffix** | `ObtenerTiposController` | `ObtenerTiposRequerimientosInAdapter` |
| **Wrong import** | `use App\Core\Shared\Infrastructure\Respuesta;` | `use App\Core\Shared\Infraestructure\Respuesta;` |
| **DI in constructor** | `public function __construct(IUseCase $u)` | `public function __construct() { app()->make() }` |
| **No try-catch** | `public function get() { $r = ... }` | `public function obtener() { try { } catch() {} }` |
| **Missing Respuesta** | `return response()->json(...)` | `return $respuesta->successResponse()` |
| **Incomplete setup** | `$r->setSuccess(true); return $r->successResponse();` | Set success, message, AND data |

---

## 📚 Related Documentation

- [INFRASTRUCTURE_INADAPTER_EXAMPLES.md](INFRASTRUCTURE_INADAPTER_EXAMPLES.md) - More examples
- [NAMING_CONVENTIONS.md](NAMING_CONVENTIONS.md) - Complete naming guide
- [templates/in-adapter.php](../templates/in-adapter.php) - Template file
- [ARCHITECTURAL_RULES.md](ARCHITECTURAL_RULES.md) - Overall architecture rules

---

**Last Updated**: 2026-04-11
**Version**: 1.0
**Status**: MANDATORY - Must be followed for all InAdapter implementations
