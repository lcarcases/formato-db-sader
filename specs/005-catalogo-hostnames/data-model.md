# Data Model: Catálogo de Hostnames

**Feature**: Catálogo de Hostnames
**Date**: 2026-08-22
**Status**: Final

## Overview

Este documento define el modelo de datos para el catálogo de hostnames/direcciones IP disponibles
(11 valores: 7 hostnames de servidor + 4 direcciones IP). El modelo incluye persistencia en
PostgreSQL, repository pattern para aislamiento de infraestructura, y value objects para
representación de dominio — replicando exactamente el modelo ya validado en
`specs/004-catalogo-bases-datos/data-model.md`.

## Database Schema (PostgreSQL)

### Tabla: tb_cat_hostname

**Purpose**: Almacenar el catálogo de hostnames/direcciones IP disponibles para solicitud de
acceso.

**Schema**:

```sql
CREATE TABLE tb_cat_hostname (
    id_nu_hostname SERIAL PRIMARY KEY,
    sn_nombre VARCHAR(100) NOT NULL UNIQUE,
    ind_activo SMALLINT NOT NULL DEFAULT 1
        CHECK (ind_activo IN (0, 1)),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tb_cat_hostname_activo
    ON tb_cat_hostname(ind_activo);
```

**Columns**:

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id_nu_hostname` | SERIAL | PRIMARY KEY | Identificador único autogenerado |
| `sn_nombre` | VARCHAR(100) | NOT NULL, UNIQUE | Hostname de servidor o dirección IP |
| `ind_activo` | SMALLINT | NOT NULL, CHECK (0 o 1), DEFAULT 1 | Indicador de registro activo |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de creación |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de última actualización |

**Indexes**:
- PRIMARY KEY on `id_nu_hostname`
- UNIQUE on `sn_nombre`
- INDEX on `ind_activo` (queries frecuentes), nombre `idx_tb_cat_hostname_activo`

**Data Conventions**:
- `ind_activo = 1`: Registro activo (visible en el catálogo)
- `ind_activo = 0`: Registro inactivo (oculto del catálogo)
- **No soft deletes**: Se usa `ind_activo` en lugar del `deleted_at` de Laravel
- **Sin columna de tipo (hostname vs IP)**: por decisión cerrada del spec (FR-006), ambos valores
  conviven como cadenas planas equivalentes en `sn_nombre`, sin columna adicional, sin validación
  de formato específico, sin agrupación en la respuesta
- **Sin normalización de mayúsculas**: a diferencia de `tb_cat_base_datos` (donde los códigos
  cortos se normalizaron a mayúsculas), los valores se almacenan exactamente como fueron
  provistos — son identificadores técnicos reales cuya forma ya es consistente (hostnames en
  minúsculas, IPs en notación decimal con puntos)

**Table comment**: `'Catálogo de hostnames/direcciones IP disponibles para solicitud de acceso'`

**Seed Data** (initial data, en este orden exacto):

```sql
INSERT INTO tb_cat_hostname (sn_nombre, ind_activo) VALUES
    ('pgrdesbds09', 1),
    ('sridesbds09', 1),
    ('pgrprdbdsmz02', 1),
    ('sriprdbdsmz02', 1),
    ('divprdbds01', 1),
    ('pgrqabds08', 1),
    ('sriqabds08', 1),
    ('10.1.35.50', 1),
    ('10.1.21.95', 1),
    ('10.1.20.25', 1),
    ('10.54.49.100', 1);
```

**Column Naming Convention**:
- `id_nu_` prefix: Numeric primary keys
- `sn_` prefix: String/text columns
- `ind_` prefix: Indicator/flag columns (0/1)
- Esta convención sigue el patrón de otras tablas del proyecto (`tb_cat_base_datos`,
  `tb_cat_ambiente_desarrollo`, `tb_cat_tipo_permiso`, etc.)

**Why PostgreSQL?**
- Single source of truth requerido por constitución
- Integridad referencial
- Datos pueden ser gestionados por admin UI en el futuro
- No Redis caching (datos son estáticos)

## Domain Model

### Value Object: HostnameVO

**Type**: Value Object (Immutable)
**Location**: `app/Core/Admin/Domain/ValueObjects/HostnameVO.php`
**Language**: PHP 8.4 with readonly properties

**Purpose**: Representa un hostname de servidor o una dirección IP al cual un trabajador puede
solicitar acceso. Es un Value Object porque su identidad está definida completamente por sus
atributos y es inmutable. No existe distinción de tipo entre "hostname" e "IP" — ambos son
representados idénticamente por esta clase.

#### Attributes

| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `id` | `int` | > 0, required | Identificador único del hostname |
| `nombre` | `string` | non-empty, required | Hostname de servidor o dirección IP (e.g., "pgrdesbds09", "10.1.35.50") |

#### Invariants

1. **ID positivo**: El `id` DEBE ser un entero positivo mayor a 0
2. **Nombre no vacío**: El `nombre` DEBE contener al menos un carácter no-whitespace tras `trim()`
3. **Inmutabilidad**: Una vez creado, un `HostnameVO` NO PUEDE ser modificado
4. **Sin validación de formato**: NO se valida que `nombre` cumpla un formato de hostname (RFC
   1123) ni de dirección IP (notación decimal con puntos) — decisión cerrada del spec, mismo nivel
   minimalista que `BaseDatosVO`

#### Behavior Methods

| Method | Signature | Purpose |
|--------|-----------|---------|
| Constructor | `__construct(int $id, string $nombre)` | Crea un nuevo `HostnameVO` validando invariantes |
| Named Constructor | `static fromArray(array $data): self` | Crea `HostnameVO` desde array |
| Serialization | `toArray(): array` | Convierte a array asociativo para JSON response |

#### Example Usage

```php
use App\Core\Admin\Domain\ValueObjects\HostnameVO;

// Creación directa
$hostname = new HostnameVO(id: 1, nombre: 'pgrdesbds09');
$ip = new HostnameVO(id: 8, nombre: '10.1.35.50');

// Creación desde array
$hostname = HostnameVO::fromArray(['id' => 1, 'nombre' => 'pgrdesbds09']);

// Serialización para API response
$array = $hostname->toArray();
// ['id' => 1, 'nombre' => 'pgrdesbds09']
```

#### Validation Rules

**Constructor validations** (domain invariants) — idénticas a `BaseDatosVO`:
- `id` DEBE ser `int` y > 0, de lo contrario throw `\InvalidArgumentException`
- `nombre` DEBE ser `string` y non-empty (trim), de lo contrario throw `\InvalidArgumentException`
- **No hay validación adicional de regex** de hostname o de IP

**Why Value Object?**
- No tiene identidad propia más allá de sus atributos
- Es inmutable
- Se compara por valor, no por referencia
- Representa un concepto del dominio sin ciclo de vida

## Application Layer: Use Case & Repository

### Port Out (Interface): HostnameOutPort

**Type**: Repository Interface (Port Out)
**Location**: `app/Core/Admin/Application/Ports/Out/HostnameOutPort.php`

**Purpose**: Define el contrato para obtener hostnames desde el almacenamiento (database).

**Methods**:

```php
interface HostnameOutPort
{
    /**
     * Obtiene todos los hostnames activos (ind_activo = 1)
     * ordenados por ID.
     *
     * @return list<HostnameVO>
     */
    public function obtenerHostnames(): array;
}
```

**Why Interface in Application?**
- Dependency Inversion Principle (DIP)
- Use case depende de abstracción, no de implementación concreta
- Permite testing con mocks
- Infraestructura (Eloquent) es detalle de implementación

### ObtenerHostnamesUseCase

**Type**: Application Service (Use Case)
**Location**: `app/Core/Admin/Application/UseCases/ObtenerHostnamesUseCase.php`

**Dependencies**:
- `HostnameOutPort` (injected via constructor)

**Responsibility**: Orquestar la obtención de hostnames activos desde el OutPort y retornar datos
raw para máxima reutilización

**Flow**:
1. Invoca `outPort->obtenerHostnames()`
2. Recibe array de `HostnameVO`
3. Retorna array directamente (raw data)

**Implementation**:

```php
class ObtenerHostnamesUseCase
{
    public function __construct(
        private readonly HostnameOutPort $outPort
    ) {}

    /**
     * @return array<HostnameVO>
     */
    public function execute(): array
    {
        return $this->outPort->obtenerHostnames();
    }
}
```

**Why return raw data?**
- Maximiza reutilización del use case (puede ser usado por diferentes adapters)
- El InAdapter (REST, CLI, etc.) es responsable de transformar a DTO según sus necesidades
- Mantiene el use case simple y enfocado en lógica de negocio

### ObtenerHostnameOutDto (item DTO) y ObtenerHostnamesOutDto (collection DTO)

**Type**: Data Transfer Objects (Immutable)
**Location**:
- `app/Core/Admin/Application/DTOs/Out/ObtenerHostnameOutDto.php` (item — `{id, nombre}`)
- `app/Core/Admin/Application/DTOs/Out/ObtenerHostnamesOutDto.php` (colección de items)

**Attributes**:
- `ObtenerHostnameOutDto`: `id: int`, `nombre: string`
- `ObtenerHostnamesOutDto`: `hostnames`: `array<ObtenerHostnameOutDto>` — Lista de hostnames
  obtenidos

**Usage**: Creados por el InAdapter (REST controller) a partir del resultado del UseCase — mapeo
de `array<HostnameVO>` a `ObtenerHostnamesOutDto(array<ObtenerHostnameOutDto>)`, replicando
exactamente el patrón verificado en código para `ObtenerBaseDatosOutDto` /
`ObtenerBasesDatosOutDto`.

**Why two DTOs (item + collection)?**
- Explicit contract entre InAdapter y respuesta API, a nivel de item y de colección
- Type-safe
- Permite diferentes representaciones según el adapter (REST JSON, CLI, etc.)
- Nombres prefijados con el verbo del use case (`Obtener...`) por convención de `CLAUDE.md`

**Note**: El UseCase NO crea estos DTOs. Es responsabilidad del InAdapter transformar el
`array<HostnameVO>` a DTOs.

## Infrastructure Layer: Persistence

### Eloquent Model: HostnameModel

**Type**: Eloquent Model (Laravel ORM)
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameModel.php`

**Purpose**: Mapear tabla `tb_cat_hostname` a objeto PHP.

**Configuration**:

```php
use Illuminate\Database\Eloquent\Model;

class HostnameModel extends Model
{
    protected $table = 'tb_cat_hostname';

    protected $primaryKey = 'id_nu_hostname';

    protected $fillable = ['sn_nombre', 'ind_activo'];

    public $timestamps = true; // created_at, updated_at

    protected $casts = [
        'ind_activo' => 'integer',
    ];
}
```

**Scopes** (optional):

```php
public function scopeActivos($query)
{
    return $query->where('ind_activo', 1);
}
```

**Why in Infrastructure?**
- Eloquent es un detalle de implementación (Framework-specific)
- No debe ser visible al Domain ni Application
- Solo `HostnameRepository` lo usa

### Repository: HostnameRepository

**Type**: Repository (Data access layer)
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepository.php`

**Purpose**: Encapsular lógica de acceso a datos usando Eloquent Model

**Implementation**:

```php
use App\Core\Admin\Domain\ValueObjects\HostnameVO;

class HostnameRepository
{
    /**
     * Obtiene hostnames activos desde la base de datos
     *
     * @return array<HostnameVO>
     */
    public function obtenerHostnames(): array
    {
        return HostnameModel::where('ind_activo', 1)
            ->orderBy('id_nu_hostname')
            ->get(['id_nu_hostname', 'sn_nombre'])
            ->map(fn(HostnameModel $model) => new HostnameVO(
                id: $model->id_nu_hostname,
                nombre: $model->sn_nombre
            ))
            ->all(); // Convert Collection to array
    }
}
```

**Key Points**:
- Query solo registros con `ind_activo = 1`
- Ordenar por `id_nu_hostname` para resultado consistente
- Seleccionar solo columnas necesarias
- Mapear Eloquent Model → Domain Value Object
- Retornar array (list) de `HostnameVO`

**Why separate Repository class?**
- Separación de concerns: OutAdapter implementa port, Repository accede a datos
- Facilita testing: Repository puede ser mockeado en tests del OutAdapter
- Reutilización: Repository puede ser usado por múltiples adapters si es necesario

### OutAdapter Implementation: HostnameOutAdapter

**Type**: Repository OutAdapter (Out)
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapter.php`

**Implements**: `HostnameOutPort` interface

**Dependencies**: `HostnameRepository` (injected via constructor)

**Purpose**: Implementar el OutPort delegando al Repository.

**Implementation**:

```php
use App\Core\Admin\Application\Ports\Out\HostnameOutPort;
use App\Core\Admin\Domain\ValueObjects\HostnameVO;

class HostnameOutAdapter implements HostnameOutPort
{
    public function __construct(
        private readonly HostnameRepository $repository
    ) {}

    public function obtenerHostnames(): array
    {
        return $this->repository->obtenerHostnames();
    }
}
```

**Why in Infrastructure?**
- Implementación depende de Eloquent (Laravel framework)
- Hexagonal architecture: Adapters en Infrastructure
- Testeable con integration tests usando database real

### InAdapter: ObtenerHostnamesInAdapter

**Type**: Inbound Adapter (REST Controller, invokable)
**Location**: `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerHostnamesInAdapter.php`

**Dependencies**: `ObtenerHostnamesUseCase`, resuelto vía `app()->make(ObtenerHostnamesUseCase::class)`
en el constructor (no constructor-promoted DI), per convención de `CLAUDE.md`.

**Responsibility**:
- Invocar el use case
- Mapear `array<HostnameVO>` a `ObtenerHostnamesOutDto`/`ObtenerHostnameOutDto`
- Construir la respuesta JSON inline con `response()->json([...])` — formato
  `{data, message, code, success}` — replicando exactamente `ObtenerBasesDatosInAdapter`; **no**
  usa ninguna de las dos clases `Respuesta` compartidas
- Capturar cualquier excepción, loguearla (`logger()->error(...)`), y devolver 500 con mensaje
  genérico

**Success message**: `'Hostnames obtenidos exitosamente'`
**Error message**: `'Error al obtener hostnames. Por favor contacte al administrador.'`

### Service Provider Binding

**Location**: `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`

**Purpose**: Conectar interface con implementación concreta. Se agrega al bloque `register()`
existente, junto a los bindings de `BaseDatosOutPort`, `AmbienteDesarrolloOutPort`,
`ITipoRequerimientoOutPort`, etc. (ubicación verificada directamente en el código actual).

```php
use App\Core\Admin\Application\Ports\Out\HostnameOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\HostnameOutAdapter;

public function register(): void
{
    // ... bindings existentes ...

    // Hostname bindings
    $this->app->bind(
        HostnameOutPort::class,
        HostnameOutAdapter::class
    );
}
```

**Why binding?**
- Dependency Injection Container resuelve dependencias
- Use case recibe OutPort interface, Laravel inyecta OutAdapter implementation
- Cambiar implementación es trivial (solo actualizar binding)

## Testing Strategy

### Unit Tests

**Test**: `ObtenerHostnamesUseCaseTest.php`
**Location**: `tests/Unit/Core/Admin/Application/UseCases/` (ubicación real verificada, misma que
`ObtenerBasesDatosUseCaseTest.php`)

**Purpose**: Test del use case en aislamiento con OutPort mock

**Scenarios**:
1. Test que verifica que el use case invoca `outPort->obtenerHostnames()`
2. Test que verifica que retorna `array<HostnameVO>` con hostnames del OutPort
3. Test que verifica manejo de lista vacía desde OutPort

**Mocking**:
```php
$outPortMock = $this->createMock(HostnameOutPort::class);
$outPortMock->expects($this->once())
    ->method('obtenerHostnames')
    ->willReturn([
        new HostnameVO(1, 'pgrdesbds09'),
        new HostnameVO(2, 'sridesbds09'),
    ]);

$useCase = new ObtenerHostnamesUseCase($outPortMock);
$result = $useCase->execute();

$this->assertIsArray($result);
$this->assertCount(2, $result);
$this->assertEquals('pgrdesbds09', $result[0]->nombre);
```

### Integration Tests

**Test 1**: `HostnameRepositoryIntegrationTest.php`
**Location**: `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/`
(ubicación real verificada, misma que `BaseDatosRepositoryIntegrationTest.php`)

**Purpose**: Test del Repository con base de datos real

**Setup**:
- Usa `RefreshDatabase` trait para migraciones
- Seed data en cada test con inserts directos

**Scenarios**:
1. Test que `obtenerHostnames()` retorna solo registros con `ind_activo = 1`
2. Test que registros con `ind_activo = 0` son excluidos
3. Test que resultados están ordenados por `id_nu_hostname` ASC
4. Test que retorna array de `HostnameVO` con valores correctos (hostnames e IPs indistintamente)
5. Test que retorna array vacío si no hay registros activos

**Test 2**: `HostnameOutAdapterIntegrationTest.php`
**Location**: `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/` (ubicación
real verificada, misma que `BaseDatosOutAdapterIntegrationTest.php`)

**Purpose**: Test del OutAdapter en aislamiento con Repository mock

**Scenarios**:
1. Test que `obtenerHostnames()` invoca `repository->obtenerHostnames()`
2. Test que retorna el resultado del repository sin transformación

### Feature/Contract Tests

**Test**: `ObtenerHostnamesApiTest.php`
**Location**: `tests/Feature/Core/Admin/Api/` (ubicación real verificada, misma que
`ObtenerBasesDatosApiTest.php`)

**Purpose**: Test E2E del endpoint REST con base de datos

**Setup**:
- Usa `RefreshDatabase` trait
- Seed data para el test

**Scenarios**:
1. GET /api/v1/admin/hostnames → 200 con estructura JSON correcta
2. Verifica schema de respuesta (data, message, code, success)
3. Verifica que data contiene los 11 hostnames/IPs sembrados, en orden, con `ind_activo = 1`
4. Verifica que hostnames están ordenados por id
5. Test con catálogo vacío retorna array vacío en data (`data: []`, `success: true`)
6. Test de error 500 cuando la base de datos no está disponible (mensaje genérico, sin detalles
   internos)

## Domain-Driven Design Compliance

### Ubiquitous Language

| Término | Español | Usado en | Justificación |
|---------|---------|----------|---------------|
| Hostname | "Hostname/IP" | Code, API, DB, Tests, Docs | Término técnico consistente, sin traducción |
| Obtener | "Fetch/Get" | Use case names | Verbo de dominio claro |

### Bounded Context

**Context**: Admin
**Responsibility**: Catálogos y configuraciones del sistema
**Related Contexts**: Ninguno directo; comparte bounded context con Bases de Datos, Ambientes de
Desarrollo y Tipos (Permiso, Personal, Requerimiento)

### Aggregate Patterns

**N/A**: No hay aggregates en esta feature. `Hostname` es un Value Object sin ciclo de vida ni
relaciones.

## Non-Functional Considerations

### Performance

- **Target**: < 200ms response time @ 50 req/s (SC-001)
- **Strategy**: Query simple sobre tabla pequeña con índice en `ind_activo`
- **Bottlenecks**: Ninguno esperado (11 registros, índice adecuado)

### Scalability

- **Current**: 11 hostnames/IPs (7 nombres de servidor + 4 direcciones IP)
- **Future**: Si crece significativamente, considerar paginación o filtrado

### Security

- **Public endpoint**: No requiere autenticación (por requisito funcional FR-003)
- **Data sensitivity**: Nombres de servidor y direcciones IP son de uso interno de la DGTIC,
  consistente con el resto de catálogos administrativos ya expuestos sin autenticación adicional
- **Input validation**: No hay input del usuario en este endpoint

## Out of Scope (reflejado del spec, no se implementa en esta historia)

1. **CRUD completo**: Crear, actualizar o eliminar hostnames — catálogo de solo lectura en esta
   historia.
2. **Integración con el formulario de solicitud**: Persistir la selección del usuario en una
   solicitud/formato de BD queda fuera de este alcance.
3. **Distinción estructural hostname vs IP**: Sin columna de tipo, sin validación de formato
   específico, sin agrupación en la respuesta.
4. **Validación de DNS/conectividad**: No se valida resolución DNS ni conectividad real hacia los
   hosts/IPs listados.

## Diagram: Domain Model

```
┌───────────────────────────────────────┐
│     Value Object: HostnameVO          │
│  ─────────────────────────────────── │
│  + id: int                            │
│  + nombre: string                     │
│  ─────────────────────────────────── │
│  + __construct(int, string)           │
│  + fromArray(array): HostnameVO       │
│  + toArray(): array                   │
└───────────────────────────────────────┘
           ▲
           │ returns array of
           │
┌──────────┴────────────────────────────┐
│  <<Interface>>                        │
│  HostnameOutPort                      │
│  ────────────────────────────────────│
│  + obtenerHostnames(): array          │
└───────────────────────────────────────┘
           ▲
           │ implements
           │
┌──────────┴────────────────────────────┐
│  HostnameOutAdapter                   │
│  ────────────────────────────────────│
│  + obtenerHostnames(): array          │
│                                       │
│  [delegates to HostnameRepository]    │
└───────────────────────────────────────┘
```

## Glossary

- **Hostname**: Un nombre de servidor (e.g., "pgrdesbds09") o una dirección IP (e.g.,
  "10.1.35.50") a la cual un trabajador de la DGTIC puede solicitar acceso — ambas
  representaciones son equivalentes en este catálogo
- **Value Object**: Objeto de dominio inmutable cuya identidad está definida por sus atributos
- **DTO**: Data Transfer Object - objeto para transferencia de datos entre capas
