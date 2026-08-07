## 7.2 OutAdapter (Implements OutPorts - interacts with external systems)

**Template:** Use [templates/out-adapter.php](../templates/out-adapter.php) as a starting structure.

## 🚨 CRITICAL OutAdapter Pattern

**OutAdapters MUST ALWAYS be created - they are NON-OPTIONAL!**

### ✅ What OutAdapters MUST DO:
1. **Implement OutPort interface** (defines contract for Application layer)
2. **Use Repository** for data access (never access DB directly!)
3. **Map raw data ↔ Domain objects** (Repository returns raw, OutAdapter transforms)
4. **Be injected in UseCases** (via OutPort interface)
5. **Be bound in ServiceProvider** (OutPort → OutAdapter binding)

### ❌ What OutAdapters MUST NOT DO:
1. **Access database directly** (use Repository!)
2. **Be skipped/omitted** (OutAdapter is MANDATORY!)
3. **Contain business logic** (that's Domain/UseCase job!)

---

## Complete OutAdapter Example

```php
// filepath: app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/TipoRequerimientoPostgresSQLOutAdapter.php
<?php

namespace App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL;

use App\Core\Admin\Application\Ports\Out\ITipoRequerimientoOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\Repositories\TipoRequerimientoPostgresSQLRepository;

// ✅ CORRECT: OutAdapter implements OutPort interface
final class TipoRequerimientoPostgresSQLOutAdapter implements ITipoRequerimientoOutPort
{
    // ✅ Uses Repository (NOT extends, just uses)
    public function __construct(
        private TipoRequerimientoPostgresSQLRepository $repository
    ) {}

    // ✅ Calls Repository and returns data (with optional transformation)
    public function obtenerTodos(): array
    {
        // Get raw data from Repository
        $rawData = $this->repository->findAll();
        
        // ✅ Option 1: Return as-is (if no transformation needed)
        return $rawData;
        
        // ✅ Option 2: Transform/map data
        // return array_map(
        //     fn($row) => [
        //         'id' => (int) $row->id_requerimiento,
        //         'nombre' => (string) $row->requerimiento
        //     ],
        //     $rawData
        // );
    }
    
    public function obtenerPorId(int $id): ?array
    {
        $rawData = $this->repository->findById($id);
        
        if (!$rawData) {
            return null;
        }
        
        // ✅ Return raw data or transform as needed
        return [
            'id' => (int) $rawData->id_requerimiento,
            'nombre' => (string) $rawData->requerimiento
        ];
    }
}
```

---

## OutAdapter with Domain Entity Mapping

When you need to work with Domain Entities:

```php
// filepath: app/Core/Programa/Infrastructure/Adapters/Out/Persistence/MySQL/SolicitudMySQLOutAdapter.php
<?php

namespace App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL;

use App\Core\Programa\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Programa\Domain\Entities\SolicitudEntity;
use App\Core\Programa\Domain\Vo\CurpVO;
use App\Core\Programa\Domain\Vo\FolioVO;
use App\Core\Programa\Domain\Enums\EstatusSolicitudEnum;
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\Repositories\SolicitudMySQLRepository;

// ✅ OutAdapter implements OutPort
final class SolicitudMySQLOutAdapter implements ISolicitudOutPort
{
    public function __construct(
        private SolicitudMySQLRepository $repository
    ) {}

    // ✅ Receives Domain Entity, transforms to array, passes to Repository
    public function persistir(SolicitudEntity $solicitud): int
    {
        $data = [
            'folio' => $solicitud->getFolio()->valor(),
            'persona_id' => $solicitud->getPersona()->getId(),
            'programa_id' => $solicitud->getPrograma()->getId(),
            'estatus' => $solicitud->getEstatus()->value,
            'created_at' => now()
        ];
        
        // ✅ Repository handles the insert
        return $this->repository->insertar($data);
    }
    
    // ✅ Gets raw data from Repository, maps to Domain Entity
    public function buscarPorId(int $id): ?SolicitudEntity
    {
        // Repository returns raw data
        $rawData = $this->repository->findById($id);
        
        if (!$rawData) {
            return null;
        }
        
        // ✅ OutAdapter maps raw data → Domain Entity
        return $this->mapToEntity($rawData);
    }
    
    // ✅ Maps raw data to Domain Entity
    private function mapToEntity(object $rawData): SolicitudEntity
    {
        return new SolicitudEntity(
            id: $rawData->id,
            folio: new FolioVO($rawData->folio),
            estatus: EstatusSolicitudEnum::from($rawData->estatus),
            // ... map other fields
        );
    }
}
```

---

## OutAdapter vs Repository: Responsibility Separation

### OutAdapter Responsibilities

**OutAdapter** = Business-oriented interface for the Application layer

✅ Implements OutPort interface
✅ Maps Domain objects ↔ Database data
✅ Orchestrates Repository calls
✅ Handles business-level data operations
✅ Is injected in UseCases (via interface)

### Repository Responsibilities

**Repository** = Pure data access

✅ Returns RAW data (objects/arrays from DB)
✅ NO interface implementation
✅ NO domain knowledge
✅ Simple CRUD operations
✅ Executes SQL queries

### The Critical Difference

```
┌────────────────────────────────────────────────────────┐
│                       UseCase                          │
│  (depends on ITipoRequerimientoOutPort interface)     │
└────────────────────┬───────────────────────────────────┘
                     │
                     ▼ DI injection
┌────────────────────────────────────────────────────────┐
│      TipoRequerimientoPostgresSQLOutAdapter             │
│  ✅ Implements ITipoRequerimientoOutPort               │
│  ✅ Uses Repository                                    │
│  ✅ Maps data ↔ Domain                                 │
│  ✅ THIS is what gets BOUND in ServiceProvider         │
└────────────────────┬───────────────────────────────────┘
                     │ uses
                     ▼
┌────────────────────────────────────────────────────────┐
│   TipoRequerimientoPostgresSQLRepository                │
│  ✅ NO interface                                       │
│  ✅ Returns RAW data                                   │
│  ✅ Pure data access                                   │
└────────────────────┬───────────────────────────────────┘
                     │
                     ▼ queries
                 Database
```

---

## ❌ Common Mistake: No OutAdapter

```php
// ❌ WRONG: Repository implementing OutPort (skipping OutAdapter)
final class TipoRequerimientoPostgresSQLRepository implements ITipoRequerimientoOutPort
{
    // ❌ This is WRONG! Repository should NOT implement interfaces!
}

// ❌ WRONG: UseCase depending directly on Repository
final class ObtenerTiposRequerimientosUseCase
{
    public function __construct(
        private TipoRequerimientoPostgresSQLRepository $repository  // ❌ Direct dependency!
    ) {}
}
```

**Why this is wrong:**
1. ❌ Violates Dependency Inversion Principle (UseCase depends on concrete class)
2. ❌ Cannot mock/test easily
3. ❌ Cannot swap implementations
4. ❌ Mixes data access with business data operations

---

## ✅ Correct Pattern: Always Use OutAdapter

```php
// ✅ 1. OutPort interface (Application layer)
interface ITipoRequerimientoOutPort
{
    public function obtenerTodos(): array;
}

// ✅ 2. OutAdapter (Infrastructure - implements OutPort)
final class TipoRequerimientoPostgresSQLOutAdapter implements ITipoRequerimientoOutPort
{
    public function __construct(
        private TipoRequerimientoPostgresSQLRepository $repository
    ) {}
    
    public function obtenerTodos(): array
    {
        return $this->repository->findAll();
    }
}

// ✅ 3. Repository (Infrastructure - NO interface)
final class TipoRequerimientoPostgresSQLRepository
{
    public function findAll(): array
    {
        return DB::table('tipos_requerimiento')->get()->toArray();
    }
}

// ✅ 4. UseCase depends on OutPort interface
final class ObtenerTiposRequerimientosUseCase
{
    public function __construct(
        private ITipoRequerimientoOutPort $tipoRequerimientoOutPort  // ✅ Interface!
    ) {}
}

// ✅ 5. ServiceProvider binds OutPort → OutAdapter
$this->app->bind(
    ITipoRequerimientoOutPort::class,
    TipoRequerimientoPostgresSQLOutAdapter::class  // ✅ OutAdapter, NOT Repository!
);
```

---

## Complex Query Building in OutAdapters

When you need **bulk operations** or **dynamic SQL**, build the query in the **OutAdapter** and pass it to the **Repository** for execution.

### Example: Bulk Update with Dynamic CASE

**Scenario:** Update delivery dates for multiple shipments in a single query.

**OutAdapter (builds dynamic query):**

```php
// filepath: app/Core/Inventario/Infrastructure/Adapters/Out/Persistence/MySQL/FleteMySQLOutAdapter.php
<?php

namespace App\Core\Inventario\Infrastructure\Adapters\Out\Persistence\MySQL;

use App\Core\Inventario\Application\Ports\Out\IFleteOutPort;
use App\Core\Inventario\Infrastructure\Adapters\Out\Persistence\MySQL\Repositories\FleteMySQLRepository;

class FleteMySQLOutAdapter implements IFleteOutPort
{
    public function __construct(
        private FleteMySQLRepository $repository
    ) {}

    public function actualizarFechasLlegada(array $correccionesFecha): void
    {
        if (empty($correccionesFecha)) {
            return;
        }

        // Build dynamic CASE query for bulk update
        $idSolicitudesFletes = [];
        $query = "UPDATE ap_inventario_pd.tb_control_flete 
                  SET dtm_fecha_llegada = CASE id_nu_solicitud_flete";

        foreach ($correccionesFecha as $correccion) {
            $idSolicitudFlete = (int) $correccion->idSolicitudFlete;
            $fechaLlegada = $correccion->fechaLlegadaDebeDecir;
            
            $idSolicitudesFletes[] = $idSolicitudFlete;
            $query .= " WHEN {$idSolicitudFlete} THEN '{$fechaLlegada}'";
        }

        $query .= " END WHERE id_nu_solicitud_flete IN (" 
                . implode(',', $idSolicitudesFletes) . ")";

        // Pass built query to Repository for execution
        $this->repository->ejecutarActualizacion($query);
    }
}
```

### Best Practices for Query Building

✅ DO

```php
// OutAdapter builds query based on domain objects
public function buscarSolicitudesPorCriterios(CriteriosBusquedaVO $criterios): array
{
    $query = "SELECT * FROM solicitudes WHERE 1=1";
    
    if ($criterios->hasFechaInicio()) {
        $fecha = $criterios->getFechaInicio()->format('Y-m-d');
        $query .= " AND fecha >= '{$fecha}'";
    }
    
    if ($criterios->hasEstado()) {
        $estado = $criterios->getEstado()->value;
        $query .= " AND estado = '{$estado}'";
    }
    
    return $this->repository->ejecutarConsulta($query);
}
```

✅ DO - Use parameterized queries for safety

```php
public function actualizarFechasLlegada(array $correccionesFecha): void
{
    $cases = [];
    $ids = [];
    $bindings = [];

    foreach ($correccionesFecha as $index => $correccion) {
        $idParam = ":id_{$index}";
        $fechaParam = ":fecha_{$index}";
        
        $cases[] = "WHEN {$idParam} THEN {$fechaParam}";
        $ids[] = $idParam;
        $bindings[$idParam] = $correccion->idSolicitudFlete;
        $bindings[$fechaParam] = $correccion->fechaLlegadaDebeDecir;
    }

    $query = "UPDATE tb_control_flete 
              SET dtm_fecha_llegada = CASE id_nu_solicitud_flete " 
            . implode(' ', $cases) 
            . " END WHERE id_nu_solicitud_flete IN (" . implode(',', $ids) . ")";

    $this->repository->ejecutarConParametros($query, $bindings);
}
```

### Summary OutAdapterRole

UseCase (Application)
    ↓ calls OutPort interface
OutAdapter (Infrastructure)
    ✓ Receives domain objects
    ✓ Maps to database structures
    ✓ Builds complex queries
    ↓ delegates to
Repository (Infrastructure)
    ✓ Executes SQL
    ✓ Returns raw data
    ↑ returns
OutAdapter
    ✓ Maps data → Domain entities
    ↑ returns
UseCase