# Research: Obtener Catálogo de Tipos de Personal

**Status**: TO BE COMPLETED (Phase 1 Tasks T001-T013)  
**Created**: 2026-05-16  
**Purpose**: Document architectural patterns, rate limiting, CORS, logging, and migration strategies before implementation

---

## Decision 1: Follow TipoRequerimiento Pattern

**Status**: ✅ COMPLETE (T002-T009)

**Rationale**: Existing TipoRequerimiento implementation in Admin bounded context provides proven hexagonal architecture template. Replicating pattern ensures consistency and reduces architectural risk.

**Pattern Summary**:
- **Inbound port**: NOT used for simple CRUD (only for Decorator pattern)
- **Outbound port**: Interface with single method `obtenerTodos(): array` returning raw data
- **DTO structure**: 
  - Item DTO with `readonly` properties, `toArray()`, and `static fromStdClass()` methods
  - Wrapper DTO with array of item DTOs, `static fromArray()` factory method
- **Use case**: 
  - No interface (simple CRUD doesn't need InPort)
  - Constructor injects OutPort interface
  - Single `ejecutar()` method returns raw array
  - Throws exceptions (not caught by use case)
- **InAdapter**: 
  - Uses `app()->make()` in constructor (NOT dependency injection)
  - Creates `Respuesta` instance
  - Calls use case `ejecutar()`, converts raw data to DTO
  - Returns `$respuesta->successResponse()` or `$respuesta->errorResponse($ex)`
- **OutAdapter**: 
  - Implements OutPort interface
  - Injects Repository in constructor
  - Delegates to repository and returns raw data
- **Repository**: 
  - Uses `DB::table()` (Query Builder, not Eloquent models)
  - Returns array of `stdClass` objects
  - No Eloquent model created
- **Route definition**: 
  - Module-specific file in `Infrastructure/Routes/AdminApiRoutes.php`
  - Versioned prefix `api/v1/admin`
  - Named route pattern: `api.admin.{resource}.{action}`
- **Service provider bindings**: 
  - Binds OutPort interface → OutAdapter implementation
  - Loads routes in `boot()` method

**Alternatives Considered**: Create new pattern from scratch (rejected: introduces unnecessary variation and potential architectural drift)

---

## Decision 2: Rate Limiting Implementation

**Status**: ✅ COMPLETE (T011)

**Chosen**: Apply `ThrottleRequests` middleware directly in route definition

**Implementation Details**:
- Middleware syntax: `->middleware('throttle:60,1')` (60 requests per minute per IP)
- Configuration location: `AdminApiRoutes.php` route definition
- Custom 429 response: Use Laravel's default handler (returns standard JSON response)

**Example**:
```php
Route::get('/tipos-personal', ObtenerTiposPersonalInAdapter::class)
    ->middleware('throttle:60,1')
    ->name('api.admin.tipos-personal.index');
```

**Rationale**: Laravel 13's built-in throttle middleware provides IP-based rate limiting without additional configuration. The `:60,1` syntax means 60 requests per 1 minute per IP address.

**Alternatives Considered**: 
- `RateLimiter` facade with custom limiter (rejected: unnecessarily complex for simple IP-based limiting)
- Custom middleware (rejected: reinventing built-in functionality)

**Laravel Docs Reference**: Routing > Rate Limiting, HTTP Middleware > Throttle Requests

---

## Decision 3: CORS Configuration

**Status**: ✅ COMPLETE (T012)

**Chosen**: Use Laravel's default CORS configuration (no config/cors.php file exists)

**Finding**: Laravel 13 handles CORS automatically for `/api/*` routes. No custom CORS configuration file found in project.

**Configuration**: Default Laravel CORS middleware is already applied to API routes globally.

**Security Note**: Default Laravel CORS configuration allows all origins (`*`) for API routes, which satisfies FR-014 requirement. This is a public API endpoint without authentication.

**Rationale**: Project uses Laravel's default CORS handling which allows all origins for `/api/*` paths. No additional configuration needed.

**Alternatives Considered**: 
- Create custom `config/cors.php` (rejected: default behavior already meets requirements)
- Restricted origins (rejected per user choice in clarifications Q4)

**Laravel Docs Reference**: Security > CORS

---

## Decision 4: Structured Logging

**Status**: ✅ COMPLETE (T013)

**Chosen**: Option C - Manual `Log::info()` calls in use case with context array

**Context Fields Required**:
1. **request_id** (UUID): Generate with `Illuminate\Support\Str::uuid()`
2. **action** (string): Fixed value: "ObtenerTiposPersonal"
3. **result** (success/error): "success" after successful execution, "error" in catch block
4. **user_ip** (IP address): Extract with `request()->ip()`
5. **timestamp** (ISO 8601): Generate with `now()->toIso8601String()`
6. **duration_ms** (integer): Calculate with `microtime(true)` before/after use case execution

**Implementation Strategy**: Add structured logging directly in use case `ejecutar()` method

**Example**:
```php
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

public function ejecutar(): array
{
    $startTime = microtime(true);
    $requestId = Str::uuid();
    
    try {
        $tiposPersonal = $this->tipoPersonalOutPort->obtenerTodos();
        
        Log::info('ObtenerTiposPersonal executed successfully', [
            'request_id' => $requestId,
            'action' => 'ObtenerTiposPersonal',
            'result' => 'success',
            'user_ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
        ]);
        
        return $tiposPersonal;
    } catch (\Exception $e) {
        Log::error('ObtenerTiposPersonal failed', [
            'request_id' => $requestId,
            'action' => 'ObtenerTiposPersonal',
            'result' => 'error',
            'user_ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

**Rationale**: 
- Simple to implement without creating new infrastructure
- Follows existing pattern in `TipoRequerimientoPostgresSQLOutAdapter`
- Use case layer can access request context via Laravel's `request()` helper
- All 6 required fields included in structured log context

**Alternatives Considered**: 
- Custom log context middleware (rejected: adds complexity, creates new infrastructure)
- Logging trait (rejected: premature optimization for single use case)

---

## Decision 5: Migration-Based Seeding

**Status**: ✅ DECIDED (User clarification Q5)

**Chosen**: Two migrations approach (schema + data)

**Migration Files**:
1. **Schema Migration**: `YYYY_MM_DD_HHMMSS_create_tb_cat_tipo_personal_table.php`
   - Creates table structure
   - Defines columns, indexes, constraints
2. **Data Migration**: `YYYY_MM_DD_HHMMSS_seed_tb_cat_tipo_personal_table.php`
   - Inserts 4 initial records (Base, Enlace, Confianza, Externo)
   - Uses `DB::table()->insert()` with explicit created_at/updated_at

**Rationale**: User-selected strategy from clarification session. Ensures data consistency across all environments (dev, staging, production). Version-controlled seeding.

**Alternatives Considered**: 
- Seeder class (rejected per user clarification)
- Manual SQL (not version controlled)
- Factory (inappropriate for static catalog data)

**Implementation Note**: Both migrations run via `php artisan migrate`, no separate seeder command needed.

---

## Decision 6: Response Format Standardization

**Status**: ✅ COMPLETE (T010)

**Format Confirmed**: `{success: bool, message: string, code: int, data: mixed}`

**Found**: `app/Core/Shared/Infrastructure/Respuesta.php` class exists ✓

**Current Pattern**: Create `Respuesta` instance, set properties, return `successResponse()` or `errorResponse($ex)`

**Implementation**:
```php
use App\Core\Shared\Infrastructure\Respuesta;

public function __invoke()
{
    $respuesta = new Respuesta();
    
    try {
        $rawData = $this->obtenerTiposPersonalUseCase->ejecutar();
        $outDto = ObtenerTiposPersonalOutDto::fromArray($rawData);
        
        $respuesta->setSuccess(true);
        $respuesta->setMessage("Tipos de personal obtenidos exitosamente.");
        $respuesta->setData($outDto);
        
        return $respuesta->successResponse();
        
    } catch (\Exception $ex) {
        $respuesta->setSuccess(false);
        $respuesta->setData([]);
        $respuesta->setMessage("Error al obtener los tipos de personal.");
        return $respuesta->errorResponse($ex);
    }
}
```

**Respuesta Class Methods**:
- `setSuccess(bool)`: Sets success flag
- `setMessage(string)`: Sets response message
- `setData(mixed)`: Sets response data (accepts DTO, array, or null)
- `setCode(int)`: Sets HTTP status code (optional, default 200)
- `successResponse()`: Returns `JsonResponse` with standard format
- `errorResponse(Exception)`: Returns `JsonResponse` with error details

**Rationale**: Project uses existing `Respuesta` class for standardized API responses. All InAdapters follow this pattern for consistency.

**Alternatives Considered**: 
- Laravel Resource class (rejected: project already has Respuesta standard)
- Manual `response()->json()` (rejected: violates hexagonal architecture patterns)
- RFC 7807 problem details (rejected per user choice)

---

## Naming Conventions Summary

**Standardization Decision**: ✅ RESOLVED (2026-05-16)

| Concept | Convention | Example |
|---------|-----------|---------|
| Domain Entity | Singular | `TipoPersonal` |
| Database Table | Plural with prefix | `tb_cat_tipo_personal` |
| API Endpoint | Plural kebab-case | `/tipos-personal` |
| Repository Interface | Singular with OutPort suffix | `ITipoPersonalOutPort` |
| Use Case Interface | **PLURAL** with UseCase suffix | `IObtenerTiposPersonalUseCase` ⚠️ |
| Use Case Implementation | **PLURAL** with UseCase suffix | `ObtenerTiposPersonalUseCase` |
| InAdapter | **PLURAL** matching use case | `ObtenerTiposPersonalInAdapter` |
| Item DTO | Singular with OutDto suffix | `TipoPersonalOutDto` |
| Wrapper DTO | **PLURAL** with verb prefix | `ObtenerTiposPersonalOutDto` |

**Rationale**: 
- Use case returns **collection** → use PLURAL (TiposPersonal) to indicate multiple items
- InAdapter matches use case naming → also PLURAL
- Entity and repository represent **single record** → use SINGULAR (TipoPersonal)

---

## Implementation Checklist

**Phase 0 Completion Criteria**:
- [ ] All TipoRequerimiento pattern files reviewed (T002-T009)
- [ ] Respuesta class usage confirmed (T010)
- [ ] Rate limiting approach documented (T011)
- [ ] CORS configuration documented (T012)
- [ ] Structured logging strategy selected (T013)
- [ ] All research decisions consolidated in this file
- [ ] Naming conventions standardized across spec.md, plan.md, tasks.md

**Output**: This research.md should be complete before starting Phase 2 (Foundational) tasks.

---

## Notes

- Research findings will inform Phase 3 implementation (T017-T034)
- All decisions should follow constitution v1.1.0 principles
- Use @Hexagonal Architecture Specialist agent for implementation (mandatory per constitution)
- Document any deviations from TipoRequerimiento pattern with justification
