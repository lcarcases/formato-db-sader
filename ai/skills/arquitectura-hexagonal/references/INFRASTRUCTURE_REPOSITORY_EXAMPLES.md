## 7.3 Repository (DB queries - Laravel Eloquent/Query Builder allowed)

**Template:** Use [templates/repository.php](../templates/repository.php) as a starting structure.

## 🚨 CRITICAL Repository Rules

**Repositories are PURE data access layer:**

### ✅ What Repositories MUST DO:
1. **Return RAW data** (objects, arrays) as it comes from database
2. **Use Laravel Query Builder / Eloquent** for queries
3. **Be simple and focused** on data access only
4. **Have NO interface implementation** (OutAdapter implements the interface!)

### ❌ What Repositories MUST NOT DO:
1. **Implement OutPort interfaces** (that's OutAdapter's job!)
2. **Create/return Domain Entities or Value Objects** (that's OutAdapter's job! — this applies to
   Eloquent-based repositories too: mapping an Eloquent model to a VO/Entity belongs in the
   OutAdapter, never inline in the repository method)
3. **Have business logic** (that's Domain/UseCase job!)
4. **Know about Domain layer** (only about database!)

> See [INFRASTRUCTURE_OUTADAPTER_EXAMPLES.md](INFRASTRUCTURE_OUTADAPTER_EXAMPLES.md) for the naming
> convention OutAdapters must use when injecting a Repository (`${nameRepositoryClass}`, never
> `$repository`) and for where the raw-data → Domain mapping shown above actually belongs.

---

## Correct Repository Pattern

```php
// filepath: app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/TipoRequerimientoPostgresSQLRepository.php
<?php

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories;

use Illuminate\Support\Facades\DB;

// ✅ CORRECT: NO interface implementation!
final class TipoRequerimientoPostgresSQLRepository
{
    // ✅ Returns RAW data as it comes from database
    public function findAll(): array
    {
        $results = DB::table('tipos_requerimiento')->get();
        
        // ✅ Return raw data (NOT entities!)
        // Just convert Collection to array
        return $results->toArray();
    }
    
    // ✅ Returns raw object or null
    public function findById(int $id): ?object
    {
        return DB::table('tipos_requerimiento')
            ->where('id_requerimiento', $id)
            ->first();
    }
    
    // ✅ Inserts and returns raw data
    public function insertar(array $data): int
    {
        return DB::table('tipos_requerimiento')->insertGetId($data);
    }
    
    // ✅ Updates using raw data
    public function actualizar(int $id, array $data): bool
    {
        return DB::table('tipos_requerimiento')
            ->where('id_requerimiento', $id)
            ->update($data);
    }
    
    // ✅ Simple delete
    public function eliminar(int $id): bool
    {
        return DB::table('tipos_requerimiento')
            ->where('id_requerimiento', $id)
            ->delete();
    }
}
```

### ❌ WRONG Repository Pattern (Common Mistakes)

```php
// ❌ WRONG: Repository implementing OutPort interface
final class TipoRequerimientoPostgresSQLRepository implements ITipoRequerimientoOutPort
{
    // ❌ WRONG: Creating Domain Entities in Repository
    public function findAll(): array
    {
        $results = DB::table('tipos_requerimiento')->get();
        
        return $results->map(function ($row) {
            // ❌ Repository should NOT create entities!
            return new TipoRequerimientoEntity(
                idRequerimiento: (int) $row->id_requerimiento,
                requerimiento: (string) $row->requerimiento
            );
        })->toArray();
    }
}
```

### ❌ WRONG: Same Mistake with Eloquent (not just Query Builder)

The rule applies identically when the repository uses Eloquent instead of `DB::table()` —
mapping to a Value Object/Entity inside the repository is still wrong, only the query style differs:

```php
// ❌ WRONG: Repository maps Eloquent models to a domain Value Object
final class BaseDatosRepository
{
    public function obtenerBaseDatos(): array
    {
        return BaseDatosModel::query()
            ->where('ind_activo', 1)
            ->get()
            // ❌ Repository should NOT build VOs/Entities — return raw models instead
            ->map(fn (BaseDatosModel $model): BaseDatosVO => new BaseDatosVO(
                id: $model->id_nu_base_datos,
                nombre: $model->sn_nombre,
            ))
            ->all();
    }
}
```

```php
// ✅ CORRECT: Repository returns raw Eloquent models; OutAdapter maps to the VO
final class BaseDatosRepository
{
    public function obtenerBaseDatos(): array
    {
        return BaseDatosModel::query()
            ->where('ind_activo', 1)
            ->get()
            ->all(); // ✅ Raw Eloquent models, NOT VOs/Entities
    }
}
```

---

## Complete Example: Multiple Repositories

```php
// filepath: app/Core/Programa/Infrastructure/Adapters/Out/Persistence/MySQL/Repositories/SolicitudMySQLRepository.php
<?php

namespace App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\Repositories;

use Illuminate\Support\Facades\DB;

// ✅ NO interface - just data access
final class SolicitudMySQLRepository
{
    private string $table = 'solicitudes';

    // ✅ Returns raw ID
    public function insertar(array $data): int
    {
        return DB::table($this->table)->insertGetId($data);
    }
    
    // ✅ Returns raw object or null
    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }
    
    // ✅ Returns raw array of objects
    public function findByCurp(string $curp): array
    {
        return DB::table($this->table)
            ->join('personas', 'solicitudes.persona_id', '=', 'personas.id')
            ->where('personas.curp', $curp)
            ->get()
            ->toArray();  // ✅ Raw array, not entities!
    }
    
    // ✅ Complex query returning raw data
    public function obtenerSolicitudesPendientes(int $programaId): array
    {
        return DB::table($this->table)
            ->where('programa_id', $programaId)
            ->where('estatus', 'PENDIENTE')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();  // ✅ Raw data
    }
    
    // ✅ Executes custom query (for OutAdapter-built queries)
    public function ejecutarConsulta(string $query): array
    {
        return DB::select($query);
    }
    
    // ✅ Update with raw data
    public function actualizar(int $id, array $data): bool
    {
        return DB::table($this->table)
            ->where('id', $id)
            ->update($data);
    }
}
```

---

## Repository → OutAdapter → UseCase Flow

```
┌──────────────────────────────────────────────────────────┐
│                        UseCase                            │
│  (Application Layer)                                     │
│                                                          │
│  depends on ITipoRequerimientoOutPort (interface)       │
└───────────────────┬──────────────────────────────────────┘
                    │
                    ▼ injected via DI
┌──────────────────────────────────────────────────────────┐
│         TipoRequerimientoPostgresSQLOutAdapter            │
│  (Infrastructure Layer)                                  │
│                                                          │
│  ✅ Implements ITipoRequerimientoOutPort                 │
│  ✅ Uses Repository for data access                      │
│  ✅ Maps raw data ↔ Domain objects                       │
└───────────────────┬──────────────────────────────────────┘
                    │
                    ▼ uses (direct instantiation/DI)
┌──────────────────────────────────────────────────────────┐
│     TipoRequerimientoPostgresSQLRepository                │
│  (Infrastructure Layer)                                  │
│                                                          │
│  ✅ NO interface implementation                          │
│  ✅ Returns RAW data (objects/arrays)                    │
│  ✅ Pure data access with Laravel Query Builder          │
└───────────────────┬──────────────────────────────────────┘
                    │
                    ▼ queries
┌──────────────────────────────────────────────────────────┐
│                       Database                            │
└──────────────────────────────────────────────────────────┘
```

---

**Infrastructure Rules:**
```
✅ ALLOWED in Infrastructure:
   - Laravel classes (Request, Response, Facades)
   - Framework-specific code
   - Database queries (Eloquent, Query Builder)
   - External service SDKs (AWS, etc.)
   
❌ MUST NOT leak to Application/Domain:
   - Request objects
   - Eloquent Models (if used, convert to arrays)
   - Laravel Collections (convert to arrays)
   - Raw database structures
```

**Naming Convention:**
| Type | Format | Location |
|------|--------|----------|
| Repository | `{Concept}PostgresSQLRepository` or `{Concept}MySQLRepository` | `app/Core/{Module}/Infrastructure/Adapters/Out/{DB}/Repositories/` |
| OutAdapter | `{Concept}PostgresSQLOutAdapter` or `{Concept}MySQLOutAdapter` | `app/Core/{Module}/Infrastructure/Adapters/Out/{DB}/` |
| OutAdapter's injected Repository property | `${nameRepositoryClass}` (camelCase of the Repository class, never generic `$repository`) — e.g. `private BaseDatosRepository $baseDatosRepository` | Constructor of the `*OutAdapter` class |
