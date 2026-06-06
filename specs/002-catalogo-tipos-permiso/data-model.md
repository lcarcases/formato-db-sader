# Data Model: Obtener Catálogo de Tipos de Permiso

**Feature**: `002-catalogo-tipos-permiso`  
**Date**: 2026-05-31  
**Domain**: Admin Bounded Context

## Entity Definitions

###TipoPermiso (Domain Entity)

**Purpose**: Represents a permission type that can be assigned to users for database access

**Type**: Entity (has identity via `id`)

**Aggregate Root**: Yes (simple aggregate with no child entities)

**Attributes**:
| Attribute | Type | Required | Description | Invariant |
|-----------|------|----------|-------------|-----------|
| id | int | Yes | Unique identifier | > 0 |
| nombre | string | Yes | Permission type name (Consulta, Cambios, etc.) | Non-empty, max 50 chars |
| descripcion | string\|null | No | Optional description | Max 500 chars |
| activo | bool | Yes | Indicates if available for selection | Must be boolean |

**Invariants** (Business Rules):
- BR-001: `nombre` MUST NOT be empty
- BR-002: `nombre` MUST be unique across all TipoPermiso records
- BR-003: Only TipoPermiso with `activo = true` should be exposed via API
- BR-004: `id` MUST be positive integer

**Behavior**:
```php
// Check if TipoPermiso is active and available
public function isActive(): bool

// Validate all invariants during construction
private function validate(): void
```

**Validation Rules**:
- nombre: required, string, max:50, unique
- descripcion: nullable, string, max:500
- activo: required, boolean

**Domain Events**: None (simple catalog entity, no state changes trigger events)

**Value Objects**: None (all attributes are primitives)

---

## Database Schema

### Table: `tb_cat_tipo_permiso`

**Purpose**: Stores catalog of database permission types

**Columns**:
| Column Name | Type | Constraints | Default | Description |
|-------------|------|-------------|---------|-------------|
| id_nu_tipo_permiso | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Unique identifier |
| ln_nombre | VARCHAR(50) | NOT NULL, UNIQUE | - | Permission type name |
| sn_descripcion | TEXT | NULL | NULL | Optional description |
| ind_activo | BOOLEAN | NOT NULL | true | Active flag |
| created_at | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Indexes**:
- PRIMARY KEY on `id_nu_tipo_permiso`
- UNIQUE INDEX on `ln_nombre` (`idx_tb_cat_tipo_permiso_nombre`)
- INDEX on `ind_activo` (`idx_tb_cat_tipo_permiso_activo`) for filtering active records

**Constraints**:
- `ln_nombre` UNIQUE constraint ensures no duplicate permission types
- `ind_activo` defaults to `true` for new records

**Table Comment**: "Catalog of database permission types for SADER system"

---

## Domain-to-Database Mapping

**Domain Model** (pure PHP, framework-agnostic):
```php
final readonly class TipoPermiso
{
    public int $id;
    public string $nombre;
    public ?string $descripcion;
    public bool $activo;
}
```

**Database Columns** (PostgreSQL with prefixed names):
```sql
id_nu_tipo_permiso  → id
ln_nombre           → nombre
sn_descripcion      → descripcion
ind_activo          → activo
created_at          → (not exposed in domain)
updated_at          → (not exposed in domain)
```

**Mapping Strategy**:
- **Domain Layer**: Uses clean attribute names (`id`, `nombre`, `activo`)
- **Infrastructure Layer**: OutDTO's `fromStdClass()` method performs mapping from DB columns to domain properties
- **Rationale**: Keeps domain pure and isolated from database naming conventions

**Example Mapping** (in `TipoPermisoOutDto::fromStdClass()`):
```php
public static function fromStdClass(\stdClass $data): self
{
    return new self(
        id: $data->id_nu_tipo_permiso,
        nombre: $data->ln_nombre
    );
}
```

---

## Seed Data

**Initial Records** (all with `ind_activo = true`):

| id_nu_tipo_permiso | ln_nombre | sn_descripcion | ind_activo |
|--------------------|-----------|----------------|------------|
| 1 | Consulta | Permiso de solo lectura (SELECT) | true |
| 2 | Cambios | Permiso  de modificación (INSERT, UPDATE) | true |
| 3 | Eliminación | Permiso de eliminación (DELETE) | true |
| 4 | Consulta y Cambios | Permiso combinado de lectura y modificación | true |

**Seeding Strategy**:
- Separate migration file: `2026_05_31_000002_seed_tb_cat_tipo_permiso_table.php`
- Uses `DB::table()->insert()` with explicit id values
- All records start as active (`ind_activo = true`)

---

## Relationships

**No Foreign Keys**: TipoPermiso is a standalone catalog table with no direct relationships to other tables within this feature scope.

**Future Relationships** (out of scope for this feature):
- `FormatoBaseDatos` table may reference `tb_cat_tipo_permiso.id_nu_tipo_permiso` as foreign key when permission assignment is implemented
- Relationship would be: One TipoPermiso → Many FormatoPermiso entries

---

## Data Flow Diagram

```
HTTP GET /api/v1/admin/tipos-permiso
         ↓
[ObtenerTiposPermisoInAdapter]  ← Infrastructure Layer (Laravel)
         ↓
[ObtenerTiposPermisoUseCase]    ← Application Layer (pure PHP)
         ↓
[ITipoPermisoOutPort interface] ← Application Layer (contract)
         ↓
[TipoPermisoPostgresSQLOutAdapter] ← Infrastructure Layer (implements OutPort)
         ↓
[TipoPermisoPostgresSQLRepository] ← Infrastructure Layer (DB queries)
         ↓
[PostgreSQL: tb_cat_tipo_permiso] ← Database
         ↓
     stdClass[]  (raw DB data with prefixed columns)
         ↓
[TipoPermisoPostgresSQLOutAdapter] ← Returns raw array
         ↓
[ObtenerTiposPermisoUseCase] ← Returns raw array
         ↓
[ObtenerTiposPermisoInAdapter] ← Converts to OutDTO
         ↓
[Respuesta::successResponse()] ← Wraps in standard response
         ↓
JSON: { success, message, code, data: [{id, nombre}] }
```

---

## DTO Definitions

### TipoPermisoOutDto

**Purpose**: Transfer single TipoPermiso data from Application to Infrastructure layer

**Attributes**:
```php
final readonly class TipoPermisoOutDto
{
    public int $id;        // Mapped from id_nu_tipo_permiso
    public string $nombre; // Mapped from ln_nombre
}
```

**Methods**:
- `toArray(): array` - Convert to associative array for JSON serialization
- `static fromStdClass(\stdClass $data): self` - Factory method to create from DB row

**JSON Output** (per FR-005):
```json
{
  "id": 1,
  "nombre": "Consulta"
}
```

---

### ObtenerTiposPermisoOutDto

**Purpose**: Collection wrapper for multiple TipoPermisoOutDto items

**Attributes**:
```php
final readonly class ObtenerTiposPermisoOutDto
{
    /** @param TipoPermisoOutDto[] $tiposPermiso */
    public function __construct(
        public array $tiposPermiso
    ) {}
}
```

**Methods**:
- `toArray(): array` - Convert collection to array structure
- `static fromArray(array $rawData): self` - Factory method to create from raw DB results

**JSON Output** (per FR-004):
```json
{
  "success": true,
  "message": "Tipos de permiso obtenidos exitosamente.",
  "code": 200,
  "data": {
    "tiposPermiso": [
      {"id": 1, "nombre": "Consulta"},
      {"id": 2, "nombre": "Cambios"},
      {"id": 3, "nombre": "Eliminación"},
      {"id": 4, "nombre": "Consulta y Cambios"}
    ]
  }
}
```

---

## Query Specifications

### Query: Get All Active TipoPermiso

**SQL**:
```sql
SELECT 
    id_nu_tipo_permiso,
    ln_nombre,
    sn_descripcion,
    ind_activo,
    created_at,
    updated_at
FROM tb_cat_tipo_permiso
WHERE ind_activo = true
ORDER BY id_nu_tipo_permiso ASC;
```

**Performance Expectations**:
- Query should use index on `ind_activo` for filtering
- Result set size: ~4 rows (static catalog)
- Expected execution time: <5ms

**Repository Method Signature**:
```php
/**
 * @return array<int, \stdClass> Array of stdClass objects with DB column names
 */
public function buscarTodos(): array
```

---

## Data Validation

**At Domain Layer** (TipoPermiso entity):
- nombre: non-empty, max 50 characters
- activo: must be boolean
- id: must be positive integer

**At Infrastructure Layer** (Eloquent model):
- Database constraints enforce UNIQUE on ln_nombre
- Database constraints enforce NOT NULL where required
- Default values applied by database

**At API Layer** (InAdapter):
- No input validation needed (GET endpoint with no parameters)
- Output validation: ensure all items match TipoPermisoOutDto structure

---

## Error Scenarios

| Scenario | Behavior | HTTP Status | Response |
|----------|----------|-------------|----------|
| All tipos inactive | Return empty array | 200 OK | `data: []` with message "No hay tipos de permiso activos" |
| Database connection failure | Log error, return error response | 500 | `{success: false, message: "Error al obtener los tipos de permiso.", data: []}` |
| Query timeout | Log error, return error response | 500 | Standard error format |
| Rate limit exceeded | Laravel throttle middleware | 429 | Standard Laravel throttle response |

---

## Migration Files

### File 1: Schema
**Filename**: `2026_05_31_000001_create_tb_cat_tipo_permiso_table.php`  
**Purpose**: Create table structure with indexes  
**Dependencies**: None

### File 2: Data
**Filename**: `2026_05_31_000002_seed_tb_cat_tipo_permiso_table.php`  
**Purpose**: Insert 4 initial permission types  
**Dependencies**: Must run after schema migration

---

## Testing Strategy

**Unit Tests**:
- Test `TipoPermiso` entity validation (nombre required, activo validation)
- Test `TipoPermisoOutDto::fromStdClass()` mapping
- Test `ObtenerTiposPermisoUseCase::ejecutar()` with mocked OutPort

**Integration Tests**:
- Test `TipoPermisoPostgresSQLRepository::buscarTodos()` against real PostgreSQL
- Verify index usage with EXPLAIN ANALYZE
- Test filtering by `ind_activo = true`

**Contract/Feature Tests**:
- Test GET `/api/v1/admin/tipos-permiso` returns 200 with 4 items
- Test response structure matches {success, message, code, data: [{id, nombre}]}
- Test empty result when all tipos inactive
- Test rate limiting (61st request returns 429)

**Next Step**: Generate API contract specification (OpenAPI YAML).
