# Research: Obtener Catálogo de Tipos de Permiso

**Feature**: `002-catalogo-tipos-permiso`  
**Date**: 2026-05-31  
**Purpose**: Document technical decisions and patterns for implementing the TipoPermiso catalog endpoint

## Research Questions & Answers

### 1. Existing Pattern Analysis: TipoPersonal

**Question**: How is the TipoPersonal catalog currently implemented?

**Answer**: TipoPersonal follows hexagonal architecture with clear layer separation:

- **Domain Entity** (`TipoPersonal.php`):
  - Pure PHP with zero Laravel dependencies
  - Readonly properties: `id`, `nombre`, `descripcion`, `activo`
  - Includes validation (protects invariant: nombre cannot be empty)
  - Documented as aggregate root with business rules

- **Application Layer**:
  - **UseCase** (`ObtenerTiposPersonalUseCase`): 
    - Returns raw array (NOT DTO) for reusability
    - Depends on `ITipoPersonalOutPort` interface
    - No try-catch (exceptions propagate to InAdapter)
    - Spanish naming: "Obtener" (verb), "TiposPersonal" (plural)
  - **OutPort** (`ITipoPersonalOutPort`): Framework-agnostic interface
  - **OutDTO** (`TipoPersonalOutDto`): 
    - Immutable readonly properties
    - Contains `fromStdClass()` factory for DB mapping
    - Maps DB columns to domain names in factory method

- **Infrastructure Layer**:
  - **InAdapter** (`ObtenerTiposPersonalInAdapter`):
    - Uses `app()->make()` to resolve UseCase (6 mandatory rules pattern)
    - Wraps execution in try-catch
    - Uses `Respuesta` class for response formatting
    - Returns `successResponse()` or `errorResponse($ex)`
  - **OutAdapter** (`TipoPersonalPostgresSQLOutAdapter`):
    - Implements OutPort interface
    - Delegates to Repository
    - Returns raw data without entity mapping
  - **Repository** (`TipoPersonalPostgresSQLRepository`):
    - Executes actual database queries
    - Returns array of stdClass objects
  - **Routes** (`AdminApiRoutes.php`):
    - Grouped under `/api/v1/admin`
    - Applied `throttle:60,1` middleware for rate limiting
    - Named route: `api.admin.tipos-personal.index`

**Decision**: Replicate this exact pattern for TipoPermiso, substituting names appropriately.

---

### 2. Database Column Naming Strategy

**Question**: How to map between domain attributes and prefixed database columns?

**Answer**: The `fromStdClass()` factory method in OutDTO performs the mapping:

**TipoPersonal Example**:
```php
public static function fromStdClass(\stdClass $data): self
{
    return new self(
        id: $data->id_nu_tipo_personal,    // DB column → DTO property
        nombre: $data->sn_nombre            // DB column → DTO property
    );
}
```

**TipoPermiso Mapping** (following user clarification):
- Domain: `id`, `nombre`, `activo`, `descripcion`
- Database: `id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`, `sn_descripcion`

**Migration Pattern** (from TipoPersonal migration):
```php
$table->id('id_nu_tipo_personal')->comment('Auto-increment identifier');
$table->string('sn_nombre', 50)->unique()->comment('Personnel type name');
$table->boolean('ind_activo')->default(true)->comment('Indicates if tipo is available');
$table->text('sn_descripcion')->nullable()->comment('Optional description');
$table->timestamps(); // created_at, updated_at
```

**Decision**: 
- Database table: `tb_cat_tipo_permiso`
- Primary key: `id_nu_tipo_permiso` (bigint, auto-increment)
- Name column: `ln_nombre` (varchar(50), unique, not null)
- Active flag: `ind_activo` (boolean, default true)
- Description: `sn_descripcion` (text, nullable)
- Timestamps: `created_at`, `updated_at` (auto-managed)
- Indexes: on `ind_activo` and `ln_nombre`

---

### 3. Response Format Standard

**Question**: What is the standard JSON response format?

**Answer**: The `Respuesta` class (located at `App\Core\Shared\Infrastructure\Respuesta`) provides standardized response formatting:

**Structure**:
```json
{
  "success": boolean,
  "message": string,
  "code": integer,
  "data": object|array|null
}
```

**Usage Pattern in InAdapter**:
```php
$respuesta = new Respuesta();

// Success path
$respuesta->setSuccess(true);
$respuesta->setMessage('Tipos de permiso obtenidos exitosamente.');
$respuesta->setData($obtenerTiposPermisoOutDto);
return $respuesta->successResponse();

// Error path
$respuesta->setSuccess(false);
$respuesta->setData([]);
$respuesta->setMessage('Error al obtener los tipos de permiso.');
return $respuesta->errorResponse($ex);
```

**Decision**: Use `Respuesta` class with setSuccess(), setMessage(), setData() pattern. Message in Spanish following ubiquitous language principle.

---

### 4. Rate Limiting Implementation

**Question**: How is rate limiting applied to catalog endpoints?

**Answer**: Laravel's built-in throttle middleware is applied in route definition:

```php
Route::get('/tipos-personal', ObtenerTiposPersonalInAdapter::class)
    ->middleware('throttle:60,1')  // 60 requests per 1 minute per IP
    ->name('api.admin.tipos-personal.index');
```

**Parameters**: `throttle:{requests},{minutes}`
- First parameter: max requests (60)
- Second parameter: time window in minutes (1)
- Enforcement: per IP address

**HTTP 429 Response** (auto-generated by Laravel when limit exceeded):
```json
{
  "message": "Too Many Attempts.",
  "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

**Decision**: Apply `throttle:60,1` middleware to `/api/v1/admin/tipos-permiso` route to match specification requirement (FR-008).

---

### 5. Structured Logging Pattern

**Question**: How are requests logged with structured context?

**Answer**: Logging occurs in OutAdapter with error context:

```php
\Log::error('Error en TipoPermisoPostgresSQLOutAdapter::obtenerTodos', [
    'message' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
    'adapter' => self::class,
]);
```

**Additional Context** (from spec requirement FR-009):
- `request_id`: Can be added via middleware or generated in InAdapter
- `action`: Use class/method name (e.g., "ObtenerTiposPermisoInAdapter::__invoke")
- `result`: "success" or "error"
- `user_ip`: `request()->ip()`
- `duration_ms`: Calculate using microtime before/after execution

**Decision**: 
- Log errors in OutAdapter (database failures)
- Log request/response in InAdapter with structured JSON format
- Include: request_id, action, result, user_ip, duration_ms (per FR-009)

---

### 6. Service Provider Registration

**Question**: How are dependencies bound in the service container?

**Answer**: `AdminServiceProvider` registers all Admin context bindings:

```php
$this->app->bind(
    ITipoPersonalOutPort::class,
    TipoPersonalPostgresSQLOutAdapter::class
);
```

**Pattern**:
1. Bind OutPort interface to OutAdapter implementation
2. Repository is injected into OutAdapter via constructor
3. UseCase is resolved via `app()->make()` in InAdapter

**Decision**: Add binding for `ITipoPermisoOutPort` → `TipoPermisoPostgresSQLOutAdapter` in `AdminServiceProvider`.

---

### 7. Seeder Data Strategy

**Question**: How are catalog entries seeded?

**Answer**: Separate migration file for data seeding (from TipoPersonal pattern):

```php
// File: 2026_05_16_205525_seed_tb_cat_tipo_personal_table.php
public function up(): void
{
    DB::table('tb_cat_tipo_personal')->insert([
        ['id_nu_tipo_personal' => 1, 'sn_nombre' => 'Base', ...],
        ['id_nu_tipo_personal' => 2, 'sn_nombre' => 'Enlace', ...],
        // etc.
    ]);
}
```

**TipoPermiso Seed Data** (from FR-007):
1. Consulta
2. Cambios
3. Eliminación
4. Consulta y Cambios

**Decision**: Create separate migration `2026_05_31_000002_seed_tb_cat_tipo_permiso_table.php` with 4 entries, all with `ind_activo = true`.

---

## Summary of Technical Decisions

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| **Bounded Context** | Admin | Consistent with TipoPersonal/TipoRequerimiento |
| **Entity Name** | TipoPermiso | Follows ubiquitous language (Spanish domain term) |
| **UseCase Name** | ObtenerTiposPermisoUseCase | Spanish verb + plural noun |
| **Table Name** | tb_cat_tipo_permiso | Follows naming convention prefix |
| **Primary Key** | id_nu_tipo_permiso | Matches pattern with _nu_ (numeric) prefix |
| **Column Prefix** | ln_ (nombre), ind_ (activo), sn_ (descripcion) | ln=long name, ind=indicator, sn=short name |
| **Response Wrapper** | Respuesta class | Standardized {success, message, code, data} |
| **Rate Limit** | throttle:60,1 | 60 requests per minute per IP |
| **Route Prefix** | /api/v1/admin | API versioning + module grouping |
| **Route Name** | api.admin.tipos-permiso.index | Consistent naming convention | 
| **InAdapter Resolution** | app()->make() | 6 mandatory rules pattern |
| **OutPort Interface** | ITipoPermisoOutPort | Framework-agnostic contract |
| **OutAdapter** | TipoPermisoPostgresSQLOutAdapter | PostgreSQL-specific implementation |
| **Repository** | TipoPermisoPostgresSQLRepository | Actual DB query execution |
| **DTO Mapping** | fromStdClass() factory method | DB columns → domain properties |
| **Logging** | Structured JSON with context | request_id, action, result, ip, duration_ms |

---

## Implementation Checklist

- [ ] Create TipoPermiso domain entity with validation
- [ ] Create ITipoPermisoOutPort interface (framework-agnostic)
- [ ] Create TipoPermisoOutDto with fromStdClass() mapping
- [ ] Create ObtenerTiposPermisoOutDto collection wrapper
- [ ] Create ObtenerTiposPermisoUseCase (returns raw array)
- [ ] Create TipoPermisoPostgresSQLRepository (DB query)
- [ ] Create TipoPermisoPostgresSQLOutAdapter (implements OutPort)
- [ ] Create ObtenerTiposPermisoInAdapter (app()->make() + Respuesta)
- [ ] Create migration: create_tb_cat_tipo_permiso_table
- [ ] Create migration: seed_tb_cat_tipo_permiso_table (4 entries)
- [ ] Add route in AdminApiRoutes.php with throttle middleware
- [ ] Bind ITipoPermisoOutPort → OutAdapter in AdminServiceProvider
- [ ] Add unit tests for UseCase
- [ ] Add integration tests for Repository
- [ ] Add contract/feature tests for API endpoint
- [ ] Verify PHPStan level 9 passing
- [ ] Verify PSR-12 (Pint) compliance

**Next Phase**: Generate data-model.md with entity definitions and relationships.
