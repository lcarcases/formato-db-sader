
┌─────────────────────────────────────────────────────────────────────────────┐
│                           INFRASTRUCTURE                                     │
│  (Laravel, MySQL, AWS, Redis, HTTP, CLI, Livewire, Blade)                   │
│                                                                              │
│  ✅ PUEDE importar: Application, Domain                                      │
│  ✅ PUEDE usar: Laravel, Eloquent, Facades, Request, AWS SDK                │
│                                                                              │
│         │                                                                    │
│         ▼ depende de                                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                            APPLICATION                                       │
│  (UseCases, DTOs, Ports, Application Services)                              │
│                                                                              │
│  ✅ PUEDE importar: Domain, Ports (interfaces propias)                       │
│  ✅ PUEDE usar: PHP puro, interfaces                                         │
│                                                                              │
│         │                                                                    │
│         ▼ depende de                                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                              DOMAIN                                          │
│  (Entities, Value Objects, Domain Services, Specifications,                 │
│   Aggregates, Enums, Exceptions, Events)                                    │
│                                                                              │
│  ✅ PUEDE importar: NADA externo (solo otras clases de Domain)              │
│  ✅ PUEDE usar: PHP puro únicamente                                          │
│                                                                              │
│  ⚠️  EL DOMINIO NO DEPENDE DE NADA EXTERNO                                  │
└─────────────────────────────────────────────────────────────────────────────┘

REGLA FUNDAMENTAL: Las capas internas NUNCA importan de capas externas

En la Capa de DOMINIO está PROHIBIDO:

<?php
// ❌ PROHIBIDO: Importar clases de Application
use App\Core\Programa\Application\Dtos\In\SolicitudInDto;

// ❌ PROHIBIDO: Importar clases de Infrastructure
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\SolicitudMySQLRepository;

// ❌ PROHIBIDO: Usar clases de Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

// ❌ PROHIBIDO: Usar DTOs en entidades o value objects
class SolicitudEntity {
    public function __construct(SolicitudInDto $dto) {} // ❌ INCORRECTO
}

// ❌ PROHIBIDO: Acceder a base de datos directamente
class PersonaEntity {
    public function guardar() {
        DB::table('personas')->insert([...]); // ❌ INCORRECTO
    }
}

En la Capa de APPLICATION está PROHIBIDO:

<?php
// ❌ PROHIBIDO: Importar clases de Infrastructure
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\SolicitudMySQLOutAdapter;
use App\Core\Programa\Infrastructure\Adapters\Out\Persistence\MySQL\Repositories\SolicitudMySQLRepository;

// ❌ PROHIBIDO: Usar clases de Laravel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// ❌ PROHIBIDO: Recibir $request en UseCase
class GenerarSolicitudUseCase {
    public function ejecutar(Request $request) {} // ❌ INCORRECTO
}

// ❌ PROHIBIDO: Acceder a base de datos directamente
class GenerarSolicitudUseCase {
    public function ejecutar(GenerarSolicitudInDto $dto) {
        DB::table('solicitudes')->insert([...]); // ❌ INCORRECTO
    }
}

// ❌ PROHIBIDO: Instanciar implementaciones concretas de Infrastructure
class GenerarSolicitudUseCase {
    public function __construct() {
        $this->repository = new SolicitudMySQLRepository(); // ❌ INCORRECTO
    }
}

En la Capa de INFRASTRUCTURE está PROHIBIDO:

<?php
// ❌ PROHIBIDO: Pasar $request más allá del InAdapter
class GenerarSolicitudInAdapter {
    public function __invoke(Request $request) {
        $this->useCase->ejecutar($request); // ❌ INCORRECTO - pasando $request
    }
}

// ❌ PROHIBIDO: Retornar modelos Eloquent a capas superiores
class PersonaMySQLOutAdapter implements IPersonaOutPort {
    public function buscarPorCurp(CurpVO $curp): PersonaModel { // ❌ INCORRECTO
        return PersonaModel::where('curp', $curp->valor())->first();
    }
}

// ❌ PROHIBIDO: Retornar Collections de Laravel a capas superiores
class SolicitudMySQLOutAdapter implements ISolicitudOutPort {
    public function buscarTodas(): Collection { // ❌ INCORRECTO
        return SolicitudModel::all();
    }
}

// ❌ PROHIBIDO: Exponer detalles de implementación
class SolicitudMySQLRepository {
    public function getQueryBuilder(): Builder { // ❌ INCORRECTO
        return DB::table('solicitudes');
    }
}

En la Capa de DOMINIO está PERMITIDO:

<?php
// ✅ PERMITIDO: Importar otras clases del Dominio
use App\Core\Programa\Domain\Vo\CurpVO;
use App\Core\Programa\Domain\Enums\EstatusSolicitudEnum;
use App\Core\Programa\Domain\Exceptions\PersonaNoActivaException;

// ✅ PERMITIDO: PHP puro con lógica de negocio
class SolicitudEntity {
    public function aprobar(): void {
        if ($this->estatus !== EstatusSolicitudEnum::EN_REVISION) {
            throw new SolicitudNoAprobableException();
        }
        $this->estatus = EstatusSolicitudEnum::APROBADA;
    }
}

// ✅ PERMITIDO: Value Objects con validación
class CurpVO {
    public function __construct(string $curp) {
        if (!$this->esValida($curp)) {
            throw new CurpInvalidaException($curp);
        }
        $this->valor = strtoupper($curp);
    }
}

En la Capa de APPLICATION está PERMITIDO:

<?php
// ✅ PERMITIDO: Importar del Dominio
use App\Core\Programa\Domain\Entities\SolicitudEntity;
use App\Core\Programa\Domain\Vo\CurpVO;
use App\Core\Programa\Domain\Exceptions\PersonaNoEncontradaException;

// ✅ PERMITIDO: Importar Ports (interfaces)
use App\Core\Programa\Application\Ports\Out\ISolicitudOutPort;
use App\Core\Programa\Application\Ports\Out\IPersonaOutPort;

// ✅ PERMITIDO: Importar DTOs
use App\Core\Programa\Application\Dtos\In\GenerarSolicitudInDto;
use App\Core\Programa\Application\Dtos\Out\GenerarSolicitudOutDto;

// ✅ PERMITIDO: Inyectar dependencias via interfaces (OutPorts)
class GenerarSolicitudUseCase implements IGenerarSolicitudInPort {
    public function __construct(
        private ISolicitudOutPort $solicitudOutPort, // ✅ Interface, no implementación
        private IPersonaOutPort $personaOutPort
    ) {}
}

En la Capa de INFRASTRUCTURE está PERMITIDO:

<?php
// ✅ PERMITIDO: Usar Laravel completamente
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\SolicitudModel;

// ✅ PERMITIDO: Importar de Application y Domain
use App\Core\Programa\Application\Ports\In\IGenerarSolicitudInPort;
use App\Core\Programa\Application\Dtos\In\GenerarSolicitudInDto;
use App\Core\Programa\Domain\Entities\SolicitudEntity;

// ✅ PERMITIDO: Extraer datos del $request SOLO en InAdapter
class GenerarSolicitudInAdapter {
    public function __invoke(Request $request): JsonResponse {
        $inDto = new GenerarSolicitudInDto(
            curp: $request->input('curp'),
            clavePrograma: $request->input('clave_programa')
        );
        $outDto = $this->useCase->ejecutar($inDto); // ✅ Pasando DTO, no $request
        return response()->json($outDto->toArray());
    }
}

// ✅ PERMITIDO: Mapear datos de BD a Entidades de Dominio
class PersonaMySQLOutAdapter implements IPersonaOutPort {
    public function buscarPorCurp(CurpVO $curp): ?PersonaEntity { // ✅ Retorna Entity
        $data = $this->repository->findByCurp($curp->valor());
        return $data ? $this->mapToEntity($data) : null;
    }
    
    private function mapToEntity(object $data): PersonaEntity {
        return new PersonaEntity(
            id: $data->id,
            curp: new CurpVO($data->curp),
            nombre: $data->nombre
        );
    }
}

// ✅ PERMITIDO: Convertir Collections a arrays
class SolicitudMySQLOutAdapter implements ISolicitudOutPort {
    public function buscarTodas(): array { // ✅ Retorna array, no Collection
        return $this->repository->findAll()->toArray();
    }
}

📋 Tabla Resumen de Dependencias
Desde / Hacia	Domain	Application	Infrastructure
Domain	         ✅ Sí	   ❌ No	       ❌ No
Application      ✅ Sí	   ✅ Sí	       ❌ No
Infrastructure	 ✅ Sí	   ✅ Sí	       ✅ Sí


📋 Tabla Resumen de Uso de Laravel
Capa	Laravel Permitido	Ejemplo
Domain	    ❌ NUNCA	       Solo PHP puro
Application	❌ NUNCA	       Solo PHP puro + interfaces
Infrastructure	✅ SIEMPRE  Request, Facades, Eloquent, etc.


## 🚨 CRITICAL: Repository vs OutAdapter Separation

**This is a MANDATORY architectural pattern that must ALWAYS be followed:**

### Repository Responsibilities (Data Layer)

**Repositories** = Pure data access layer (NO business logic, NO interfaces)

```php
// ✅ CORRECT: Repository does NOT implement OutPort
final class TipoRequerimientoPostgresSQLRepository
{
    // ✅ Returns RAW data as it comes from database
    public function findAll(): array
    {
        $results = DB::table('tipos_requerimiento')->get();
        
        // ✅ Return raw data (NOT entities!)
        return $results->toArray();
    }
    
    public function findById(int $id): ?object
    {
        return DB::table('tipos_requerimiento')
            ->where('id', $id)
            ->first();
    }
}
```

**Repository Rules:**
```
✅ MUST:
   - Return raw data (objects, arrays) from database
   - Use Laravel Query Builder / Eloquent
   - Be simple data access only
   - Have NO business logic
   - Have NO interface implementation
   
❌ MUST NOT:
   - Implement OutPort interfaces (OutAdapter does this!)
   - Create/return Domain Entities (OutAdapter does this!)
   - Have business logic
   - Know about Domain layer
```

### OutAdapter Responsibilities (Business-Oriented Data Access)

**OutAdapters** = Implements OutPort, coordinates Repository, maps data ↔ Domain

```php
// ✅ CORRECT: OutAdapter implements OutPort interface
final class TipoRequerimientoPostgresSQLOutAdapter implements ITipoRequerimientoOutPort
{
    public function __construct(
        private TipoRequerimientoPostgresSQLRepository $repository
    ) {}

    // ✅ Calls Repository and maps to Domain
    public function obtenerTodos(): array
    {
        // Get raw data from Repository
        $rawData = $this->repository->findAll();
        
        // Map to Domain objects (if needed) or return as-is
        return array_map(
            fn($row) => [
                'id' => (int) $row->id,
                'nombre' => (string) $row->nombre
            ],
            $rawData
        );
    }
}
```

**OutAdapter Rules:**
```
✅ MUST:
   - Implement OutPort interface
   - Use Repository for data access
   - Map raw data ↔ Domain objects
   - Handle business-oriented data operations
   - Be injected in UseCases
   
❌ MUST NOT:
   - Access database directly (use Repository!)
   - Contain business logic (that's Domain/UseCase!)
```

### The Complete Flow

```
UseCase
   ↓ depends on OutPort interface
   ↓ 
OutAdapter (implements OutPort)
   ↓ uses
   ↓
Repository (NO interface)
   ↓ queries
   ↓
Database
```

### Service Provider Binding

```php
// ✅ CORRECT: Bind OutPort to OutAdapter (NOT Repository!)
$this->app->bind(
    ITipoRequerimientoOutPort::class,
    TipoRequerimientoPostgresSQLOutAdapter::class  // OutAdapter!
);

// ❌ WRONG: Never bind OutPort to Repository
$this->app->bind(
    ITipoRequerimientoOutPort::class,
    TipoRequerimientoPostgresSQLRepository::class  // ❌ Repository should NOT implement interface!
);
```

### Why This Separation?

1. **Single Responsibility**: Repository = data access, OutAdapter = business data operations
2. **Dependency Inversion**: UseCase depends on OutPort (interface), not concrete Repository
3. **Flexibility**: Can swap Repository implementation without changing OutAdapter interface
4. **Clean Mapping**: OutAdapter handles all data ↔ domain transformation logic
5. **Testability**: Can mock OutPort in UseCase tests


⚠️ Antipatrones Comunes a Evitar
Antipatrón	  |   Descripción	                                   | Solución
Anemic Domain |	Entidades sin comportamiento, solo getters/setters | Agregar métodos de negocio a las entidades
Layer Leakage |	$request o Eloquent Models pasados a Application/Domain | Usar DTOs y mapear a Entities
Fat Adapter | Lógica de negocio en InAdapter o OutAdapter | Mover lógica al UseCase o Domain Service
Smart DTO |	DTOs con lógica de negocio | DTOs solo transportan datos, lógica va en Domain
Framework Coupling | Usar Facades o Helpers de Laravel en Application/Domain | Abstraer detrás de interfaces (OutPorts)
Direct DB Access |	Consultas SQL en UseCase | Usar OutPorts que encapsulan acceso a datos
Repository Implements Interface | Repository implementa OutPort | Solo OutAdapter implementa OutPort
Repository Creates Entities | Repository crea Domain Entities | Repository retorna datos raw, OutAdapter mapea
UseCase Depends on Repository | UseCase depende directamente de Repository | UseCase depende de OutPort (interface)