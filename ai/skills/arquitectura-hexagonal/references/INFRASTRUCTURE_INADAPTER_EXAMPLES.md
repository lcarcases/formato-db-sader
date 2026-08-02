## 7.1 InAdapter (Entry point - HTTP/CLI/Web → InDto → UseCase)

**Template:** Use [templates/in-adapter.php](../templates/in-adapter.php) as a starting structure.

### ⚠️ CRITICAL PATTERNS FOR INADAPTERS

#### ✅ MANDATORY PATTERNS (Non-negotiable)

1. **Naming Convention**:
   - ✅ CORRECT: `{VerbSpanish}{NounSpanish}InAdapter`
   - ❌ NEVER USE: `{Anything}Controller`, `{English}InAdapter`, `Get{Anything}InAdapter`
   - Examples:
     - ✅ `ObtenerTiposRequerimientosInAdapter` (Obtener = Get in Spanish)
     - ✅ `CrearSolicitudInAdapter` (Crear = Create)
     - ✅ `ActualizarBeneficiarioInAdapter` (Actualizar = Update)
     - ❌ WRONG: `GetTipoRequerimientoController`
     - ❌ WRONG: `CreateSolicitudInAdapter`

2. **Constructor Pattern**:
   - ✅ ALWAYS use `app()->make()` for dependency resolution
   - ✅ ALWAYS wrap in try-catch
   - ✅ ALWAYS declare private property separately (NOT in constructor parameters)
   - ❌ NEVER use dependency injection in constructor parameters
   - ❌ NEVER use `private readonly` in constructor
   
3. **Respuesta Import**:
   - ✅ CORRECT: `use App\Core\Shared\Infraestructure\Respuesta;`
   - ❌ WRONG: `use App\Core\Shared\Infrastructure\Respuesta;` (note the 'a' in Infraestructure)

4. **Method Pattern**:
   - ✅ ALWAYS create Respuesta instance as first line
   - ✅ ALWAYS wrap entire method in try-catch
   - ✅ ALWAYS set success, message, and data on Respuesta
   - ✅ ALWAYS return `successResponse()` or `errorResponse($ex)`
   - ❌ NEVER return raw JSON responses

### 📝 Complete Example - Correct Pattern

```php
// filepath: app/Core/Fletes/DiceDebeDecir/Infrastructure/Adapters/In/Web/ObtenerCatalogoEstatusRecibidoFleteInAdapter.php
<?php

namespace App\Core\Fletes\DiceDebeDecir\Infrastructure\Adapters\In\Web;

use App\Core\Fletes\DiceDebeDecir\Application\UseCases\ObtenerCatalogoEstatusRecibidoFleteUseCase;
use App\Core\Shared\Infraestructure\Respuesta;  // ⚠️ Note: "Infraestructure" with 'a'
use App\Core\Fletes\DiceDebeDecir\Application\Dtos\In\FiltroAnioTaxonomiaInDto;

/**
 * ✅ Correct InAdapter Pattern
 * 
 * - Spanish verb "Obtener" (not "Get")
 * - InAdapter suffix (not Controller)
 * - app()->make() in constructor
 * - Respuesta usage with try-catch
 */
class ObtenerCatalogoEstatusRecibidoFleteInAdapter
{
    // ✅ Private property declared separately
    private ObtenerCatalogoEstatusRecibidoFleteUseCase $obtenerCatalogoEstatusRecibidoFleteUseCase;

    /**
     * ✅ Constructor uses app()->make() wrapped in try-catch
     * ❌ NEVER inject dependencies in constructor parameters like:
     *    public function __construct(IUseCase $useCase) { ... }
     */
    public function __construct()
    {
        try {
            $this->obtenerCatalogoEstatusRecibidoFleteUseCase = app()->make(ObtenerCatalogoEstatusRecibidoFleteUseCase::class);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * ✅ Method wrapped in try-catch with proper Respuesta usage
     */
    public function obtenerCatalagoEstatusRecibidoFlete()
    {
        try {
            // ✅ 1. Create Respuesta instance FIRST
            $respuesta = new Respuesta();
            
            \Log::info("Entro en el adapter. Antes de llamar al use case");

            // ✅ 2. Execute use case
            $catalogoEstatusRecibidoFleteOutDto = $this->obtenerCatalogoEstatusRecibidoFleteUseCase->obtenerCatalogoEstatusRecibidoFlete();
            
            \Log::info("Entro en el adapter. Despues de llamar al use case");
            
            // ✅ 3. Set up successful response
            $respuesta->setSuccess(true);
            $respuesta->setMessage("Se obtuvo el catalago de estatus recibido flete correctamente.");
            $respuesta->setData($catalogoEstatusRecibidoFleteOutDto);

            // ✅ 4. Return standardized success response
            return $respuesta->successResponse();

        } catch (\Exception $ex) {
            // ✅ 5. Handle errors with standardized error response
            $respuesta->setSuccess(false);
            $respuesta->setData([]);
            $respuesta->setMessage("Error mientras se intentaba obtener el catalago de estatus recibido flete.");
            return $respuesta->errorResponse($ex);
        }
    }
}
```

### ❌ COMMON MISTAKES TO AVOID

#### Mistake 1: Wrong Naming
```php
// ❌ WRONG - Using "Get" (English) and "Controller" suffix
class GetTipoRequerimientoController { ... }

// ✅ CORRECT - Using "Obtener" (Spanish) and "InAdapter" suffix
class ObtenerTiposRequerimientosInAdapter { ... }
```

#### Mistake 2: Wrong Constructor Pattern
```php
// ❌ WRONG - Dependency injection in constructor parameters
public function __construct(
    private readonly IGetTipoRequerimientoUseCase $getTipoRequerimientoUseCase
) {}

// ✅ CORRECT - app()->make() with separate property
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

#### Mistake 3: Wrong Import Path
```php
// ❌ WRONG - "Infrastructure" with 't'
use App\Core\Shared\Infrastructure\Respuesta;

// ✅ CORRECT - "Infraestructure" with 'a'
use App\Core\Shared\Infraestructure\Respuesta;
```

#### Mistake 4: Missing Respuesta Setup
```php
// ❌ WRONG - Not using Respuesta or incomplete setup
public function __invoke(): JsonResponse
{
    $result = $this->useCase->execute();
    return response()->json($result->toArray(), 200);
}

// ✅ CORRECT - Complete Respuesta setup
public function obtenerTiposRequerimientos()
{
    try {
        $respuesta = new Respuesta();
        
        $obtenerTiposRequerimientosOutDto = $this->obtenerTiposRequerimientosUseCase->obtener();
        
        $respuesta->setSuccess(true);
        $respuesta->setMessage("Se obtuvieron los tipos de requerimientos correctamente.");
        $respuesta->setData($obtenerTiposRequerimientosOutDto);
        
        return $respuesta->successResponse();
        
    } catch (\Exception $ex) {
        $respuesta->setSuccess(false);
        $respuesta->setData([]);
        $respuesta->setMessage("Error mientras se intentaba obtener los tipos de requerimientos.");
        return $respuesta->errorResponse($ex);
    }
}
```

### 📋 InAdapter Checklist

Before finalizing an InAdapter, verify:

- [ ] ✅ Name uses Spanish verb (Obtener, Crear, Actualizar, Eliminar, etc.)
- [ ] ✅ Name ends with `InAdapter` suffix (NEVER `Controller`)
- [ ] ✅ Import is `use App\Core\Shared\Infraestructure\Respuesta;` (with 'a')
- [ ] ✅ Private property declared separately (not in constructor parameters)
- [ ] ✅ Constructor uses `app()->make()` wrapped in try-catch
- [ ] ✅ Method creates `$respuesta = new Respuesta();` as first line
- [ ] ✅ Entire method wrapped in try-catch
- [ ] ✅ Success path calls `setSuccess(true)`, `setMessage()`, `setData()`
- [ ] ✅ Success path returns `$respuesta->successResponse()`
- [ ] ✅ Catch block calls `setSuccess(false)`, `setData([])`, `setMessage()`
- [ ] ✅ Catch block returns `$respuesta->errorResponse($ex)`
- [ ] ✅ No direct Laravel framework usage except Request and Response

### 🔄 Additional Examples

#### Example: REST API Adapter with Request Validation

```php
