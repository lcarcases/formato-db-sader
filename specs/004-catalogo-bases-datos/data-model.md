# Data Model: Catálogo de Bases de Datos

**Feature**: Catálogo de Bases de Datos
**Date**: 2026-08-07
**Status**: Final

## Overview

Este documento define el modelo de datos para el catálogo de bases de datos disponibles (PPB, SURI, XAMAN, OTROS). El modelo incluye persistencia en PostgreSQL, repository pattern para aislamiento de infraestructura, y value objects para representación de dominio — replicando exactamente el modelo ya validado en `specs/003-catalogo-ambientes-desarrollo/data-model.md`.

## Database Schema (PostgreSQL)

### Tabla: tb_cat_base_datos

**Purpose**: Almacenar el catálogo de bases de datos disponibles para solicitud de acceso.

**Schema**:

```sql
CREATE TABLE tb_cat_base_datos (
    id_nu_base_datos SERIAL PRIMARY KEY,
    sn_nombre VARCHAR(100) NOT NULL UNIQUE,
    ind_activo SMALLINT NOT NULL DEFAULT 1
        CHECK (ind_activo IN (0, 1)),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tb_cat_base_datos_activo
    ON tb_cat_base_datos(ind_activo);
```

**Columns**:

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id_nu_base_datos` | SERIAL | PRIMARY KEY | Identificador único autogenerado |
| `sn_nombre` | VARCHAR(100) | NOT NULL, UNIQUE | Nombre/código de la base de datos |
| `ind_activo` | SMALLINT | NOT NULL, CHECK (0 o 1), DEFAULT 1 | Indicador de registro activo |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de creación |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de última actualización |

**Indexes**:
- PRIMARY KEY on `id_nu_base_datos`
- UNIQUE on `sn_nombre`
- INDEX on `ind_activo` (queries frecuentes)

**Data Conventions**:
- `ind_activo = 1`: Registro activo (visible en el catálogo)
- `ind_activo = 0`: Registro inactivo (oculto del catálogo)
- **No soft deletes**: Se usa `ind_activo` en lugar del `deleted_at` de Laravel
- **Sin columna de texto libre para "OTROS"**: "OTROS" es una fila más de catálogo; la captura de un nombre de base de datos no listada queda fuera de alcance (ver `spec.md` → Assumptions)

**Seed Data** (initial data):

```sql
INSERT INTO tb_cat_base_datos (sn_nombre, ind_activo) VALUES
    ('PPB', 1),
    ('SURI', 1),
    ('XAMAN', 1),
    ('OTROS', 1);
```

**Column Naming Convention**:
- `id_nu_` prefix: Numeric primary keys
- `sn_` prefix: String/text columns
- `ind_` prefix: Indicator/flag columns (0/1)
- Esta convención sigue el patrón de otras tablas del proyecto (`tb_cat_ambiente_desarrollo`, `tb_cat_tipo_permiso`, etc.)

**Why PostgreSQL?**
- Single source of truth requerido por constitución
- Integridad referencial
- Datos pueden ser gestionados por admin UI en el futuro
- No Redis caching (datos son estáticos)

## Domain Model

### Value Object: BaseDatosVO

**Type**: Value Object (Immutable)
**Location**: `app/Core/Admin/Domain/ValueObjects/BaseDatosVO.php`
**Language**: PHP 8.4 with readonly properties

**Purpose**: Representa una base de datos disponible sobre la cual un trabajador puede solicitar acceso. Es un Value Object porque su identidad está definida completamente por sus atributos y es inmutable.

#### Attributes

| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `id` | `int` | > 0, required | Identificador único de la base de datos |
| `nombre` | `string` | non-empty, required | Nombre/código de la base de datos (e.g., "PPB", "SURI", "XAMAN", "OTROS") |

#### Invariants

1. **ID positivo**: El `id` DEBE ser un entero positivo mayor a 0
2. **Nombre no vacío**: El `nombre` DEBE contener al menos un carácter no-whitespace
3. **Inmutabilidad**: Una vez creado, un `BaseDatosVO` NO PUEDE ser modificado

#### Behavior Methods

| Method | Signature | Purpose |
|--------|-----------|---------|
| Constructor | `__construct(int $id, string $nombre)` | Crea un nuevo `BaseDatosVO` validando invariantes |
| Named Constructor | `static fromArray(array $data): self` | Crea `BaseDatosVO` desde array |
| Serialization | `toArray(): array` | Convierte a array asociativo para JSON response |

#### Example Usage

```php
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;

// Creación directa
$baseDatos = new BaseDatosVO(id: 1, nombre: 'PPB');

// Creación desde array
$baseDatos = BaseDatosVO::fromArray(['id' => 1, 'nombre' => 'PPB']);

// Serialización para API response
$array = $baseDatos->toArray();
// ['id' => 1, 'nombre' => 'PPB']
```

#### Validation Rules

**Constructor validations** (domain invariants):
- `id` DEBE ser `int` y > 0, de lo contrario throw `\InvalidArgumentException`
- `nombre` DEBE ser `string` y non-empty (trim), de lo contrario throw `\InvalidArgumentException`

**Why Value Object?**
- No tiene identidad propia más allá de sus atributos
- Es inmutable
- Se compara por valor, no por referencia
- Representa un concepto del dominio sin ciclo de vida

## Application Layer: Use Case & Repository

### Port Out (Interface): BaseDatosOutPort

**Type**: Repository Interface (Port Out)
**Location**: `app/Core/Admin/Application/Ports/Out/BaseDatosOutPort.php`

**Purpose**: Define el contrato para obtener bases de datos desde el almacenamiento (database).

**Methods**:

```php
interface BaseDatosOutPort
{
    /**
     * Obtiene todas las bases de datos activas (ind_activo = 1)
     * ordenadas por ID.
     *
     * @return list<BaseDatosVO>
     */
    public function obtenerBasesDatos(): array;
}
```

**Why Interface in Application?**
- Dependency Inversion Principle (DIP)
- Use case depende de abstracción, no de implementación concreta
- Permite testing con mocks
- Infraestructura (Eloquent) es detalle de implementación

### ObtenerBasesDatosUseCase

**Type**: Application Service (Use Case)
**Location**: `app/Core/Admin/Application/UseCases/ObtenerBasesDatosUseCase.php`

**Dependencies**:
- `BaseDatosOutPort` (injected via constructor)

**Responsibility**: Orquestar la obtención de bases de datos activas desde el OutPort y retornar datos raw para máxima reutilización

**Flow**:
1. Invoca `outPort->obtenerBasesDatos()`
2. Recibe array de `BaseDatosVO`
3. Retorna array directamente (raw data)

**Implementation**:

```php
class ObtenerBasesDatosUseCase
{
    public function __construct(
        private readonly BaseDatosOutPort $outPort
    ) {}

    /**
     * @return array<BaseDatosVO>
     */
    public function execute(): array
    {
        return $this->outPort->obtenerBasesDatos();
    }
}
```

**Why return raw data?**
- Maximiza reutilización del use case (puede ser usado por diferentes adapters)
- El InAdapter (REST, CLI, etc.) es responsable de transformar a DTO según sus necesidades
- Mantiene el use case simple y enfocado en lógica de negocio

### ObtenerBasesDatosOutDto (DTO)

**Type**: Data Transfer Object (Immutable)
**Location**: `app/Core/Admin/Application/DTOs/Out/ObtenerBasesDatosOutDto.php`

**Attributes**:
- `basesDatos`: `array<BaseDatosVO>` - Lista de bases de datos obtenidas

**Usage**: Creado por el InAdapter (REST controller) a partir del resultado del UseCase

**Why DTO?**
- Explicit contract entre InAdapter y respuesta API
- Type-safe
- Permite diferentes representaciones según el adapter (REST JSON, CLI, etc.)
- Nombre prefijado con el verbo del use case (`Obtener...`) por convención de `CLAUDE.md`

**Note**: El UseCase NO crea este DTO. Es responsabilidad del InAdapter transformar el array<BaseDatosVO> a DTO.

## Infrastructure Layer: Persistence

### Eloquent Model: BaseDatosModel

**Type**: Eloquent Model (Laravel ORM)
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/BaseDatosModel.php`

**Purpose**: Mapear tabla `tb_cat_base_datos` a objeto PHP.

**Configuration**:

```php
use Illuminate\Database\Eloquent\Model;

class BaseDatosModel extends Model
{
    protected $table = 'tb_cat_base_datos';

    protected $primaryKey = 'id_nu_base_datos';

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
- Solo `BaseDatosRepository` lo usa

### Repository: BaseDatosRepository

**Type**: Repository (Data access layer)
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepository.php`

**Purpose**: Encapsular lógica de acceso a datos usando Eloquent Model

**Implementation**:

```php
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;

class BaseDatosRepository
{
    /**
     * Obtiene bases de datos activas desde la base de datos
     *
     * @return array<BaseDatosVO>
     */
    public function obtenerBasesDatos(): array
    {
        return BaseDatosModel::where('ind_activo', 1)
            ->orderBy('id_nu_base_datos')
            ->get(['id_nu_base_datos', 'sn_nombre'])
            ->map(fn(BaseDatosModel $model) => new BaseDatosVO(
                id: $model->id_nu_base_datos,
                nombre: $model->sn_nombre
            ))
            ->all(); // Convert Collection to array
    }
}
```

**Key Points**:
- Query solo registros con `ind_activo = 1`
- Ordenar por `id_nu_base_datos` para resultado consistente
- Seleccionar solo columnas necesarias
- Mapear Eloquent Model → Domain Value Object
- Retornar array (list) de `BaseDatosVO`

**Why separate Repository class?**
- Separación de concerns: OutAdapter implementa port, Repository accede a datos
- Facilita testing: Repository puede ser mockeado en tests del OutAdapter
- Reutilización: Repository puede ser usado por múltiples adapters si es necesario

### OutAdapter Implementation: BaseDatosOutAdapter

**Type**: Repository OutAdapter (Out)
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapter.php`

**Implements**: `BaseDatosOutPort` interface

**Dependencies**: `BaseDatosRepository` (injected via constructor)

**Purpose**: Implementar el OutPort delegando al Repository.

**Implementation**:

```php
use App\Core\Admin\Application\Ports\Out\BaseDatosOutPort;
use App\Core\Admin\Domain\ValueObjects\BaseDatosVO;

class BaseDatosOutAdapter implements BaseDatosOutPort
{
    public function __construct(
        private readonly BaseDatosRepository $repository
    ) {}

    public function obtenerBasesDatos(): array
    {
        return $this->repository->obtenerBasesDatos();
    }
}
```

**Why in Infrastructure?**
- Implementación depende de Eloquent (Laravel framework)
- Hexagonal architecture: Adapters en Infrastructure
- Testeable con integration tests usando database real

### Service Provider Binding

**Location**: `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`

**Purpose**: Conectar interface con implementación concreta. Se agrega al bloque `register()` existente, junto a los bindings de `AmbienteDesarrolloOutPort`, `ITipoRequerimientoOutPort`, etc.

```php
use App\Core\Admin\Application\Ports\Out\BaseDatosOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\BaseDatosOutAdapter;

public function register(): void
{
    // ... bindings existentes ...

    // BaseDatos bindings
    $this->app->bind(
        BaseDatosOutPort::class,
        BaseDatosOutAdapter::class
    );
}
```

**Why binding?**
- Dependency Injection Container resuelve dependencias
- Use case recibe OutPort interface, Laravel inyecta OutAdapter implementation
- Cambiar implementación es trivial (solo actualizar binding)

**Note**: A diferencia de lo documentado (por error) en el `plan.md` de la feature 003, el binding real de `AmbienteDesarrolloOutPort` vive en `AdminServiceProvider`, no en `AppServiceProvider`. Este plan usa la ubicación verificada en código.

## Testing Strategy

### Unit Tests

**Test**: `ObtenerBasesDatosUseCaseTest.php`
**Location**: `tests/Unit/Core/Admin/Application/UseCases/`

**Purpose**: Test del use case en aislamiento con OutPort mock

**Scenarios**:
1. Test que verifica que el use case invoca `outPort->obtenerBasesDatos()`
2. Test que verifica que retorna array<BaseDatosVO> con bases de datos del OutPort
3. Test que verifica manejo de lista vacía desde OutPort

**Mocking**:
```php
$outPortMock = $this->createMock(BaseDatosOutPort::class);
$outPortMock->expects($this->once())
    ->method('obtenerBasesDatos')
    ->willReturn([
        new BaseDatosVO(1, 'PPB'),
        new BaseDatosVO(2, 'SURI'),
    ]);

$useCase = new ObtenerBasesDatosUseCase($outPortMock);
$result = $useCase->execute();

$this->assertIsArray($result);
$this->assertCount(2, $result);
$this->assertEquals('PPB', $result[0]->nombre);
```

### Integration Tests

**Test 1**: `BaseDatosRepositoryTest.php`
**Location**: `tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories/`

**Purpose**: Test del Repository con base de datos real

**Setup**:
- Usa `RefreshDatabase` trait para migraciones
- Seed data en cada test con inserts directos

**Scenarios**:
1. Test que `obtenerBasesDatos()` retorna solo registros con `ind_activo = 1`
2. Test que registros con `ind_activo = 0` son excluidos
3. Test que resultados están ordenados por `id_nu_base_datos` ASC
4. Test que retorna array de `BaseDatosVO` con valores correctos
5. Test que retorna array vacío si no hay registros activos

**Test 2**: `BaseDatosOutAdapterTest.php`
**Location**: `tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/`

**Purpose**: Test del OutAdapter en aislamiento con Repository mock

**Scenarios**:
1. Test que `obtenerBasesDatos()` invoca `repository->obtenerBasesDatos()`
2. Test que retorna el resultado del repository sin transformación

### Feature/Contract Tests

**Test**: `ObtenerBasesDatosApiTest.php`
**Location**: `tests/Feature/Api/`

**Purpose**: Test E2E del endpoint REST con base de datos

**Setup**:
- Usa `RefreshDatabase` trait
- Seed data para el test

**Scenarios**:
1. GET /api/v1/admin/bases-datos → 200 con estructura JSON correcta
2. Verifica schema de respuesta (data, message, code, success)
3. Verifica que data contiene solo bases de datos con `ind_activo = 1`
4. Verifica que bases de datos están ordenadas por id
5. Test con catálogo vacío retorna array vacío en data
6. Test de error 500 cuando la base de datos no está disponible (mensaje genérico, sin detalles internos)

## Domain-Driven Design Compliance

### Ubiquitous Language

| Término | Español | Usado en | Justificación |
|---------|---------|----------|---------------|
| BaseDatos | "Database" | Code, API, DB, Tests, Docs | Término institucional SADER (formato de BD) |
| Obtener | "Fetch/Get" | Use case names | Verbo de dominio claro |

### Bounded Context

**Context**: Admin
**Responsibility**: Catálogos y configuraciones del sistema
**Related Contexts**: Ninguno directo; comparte bounded context con Ambientes de Desarrollo y Tipos (Permiso, Personal, Requerimiento)

### Aggregate Patterns

**N/A**: No hay aggregates en esta feature. `BaseDatos` es un Value Object sin ciclo de vida ni relaciones.

## Non-Functional Considerations

### Performance

- **Target**: < 200ms response time @ 50 req/s (SC-001)
- **Strategy**: Query simple sobre tabla pequeña con índice en `ind_activo`
- **Bottlenecks**: Ninguno esperado (4 registros, índice adecuado)

### Scalability

- **Current**: 4 bases de datos (PPB, SURI, XAMAN, OTROS)
- **Future**: Si crece significativamente, considerar paginación o filtrado

### Security

- **Public endpoint**: No requiere autenticación (por requisito funcional FR-003)
- **Data sensitivity**: Nombres de bases de datos son públicos, no hay datos sensibles
- **Input validation**: No hay input del usuario en este endpoint

## Future Considerations

1. **Captura de texto libre para "OTROS"**: Si en el futuro se requiere que el usuario especifique el nombre real de una base de datos no listada al elegir "OTROS", esto se resolverá en una historia de integración con el formulario de llenado — probablemente agregando un campo en la entidad de la solicitud/formulario, no en este catálogo.
2. **CRUD completo**: Si se requiere administrar el catálogo dinámicamente, agregar casos de uso Crear/Actualizar/Eliminar y considerar caching en Redis.
3. **Metadata adicional**: Si se requiere más info por base de datos (descripción, ambiente asociado, etc.), extender el Value Object con atributos opcionales manteniendo backward compatibility en la API.

## Diagram: Domain Model

```
┌───────────────────────────────────────┐
│     Value Object: BaseDatosVO         │
│  ─────────────────────────────────── │
│  + id: int                            │
│  + nombre: string                     │
│  ─────────────────────────────────── │
│  + __construct(int, string)           │
│  + fromArray(array): BaseDatosVO      │
│  + toArray(): array                   │
└───────────────────────────────────────┘
           ▲
           │ returns array of
           │
┌──────────┴────────────────────────────┐
│  <<Interface>>                        │
│  BaseDatosOutPort                     │
│  ────────────────────────────────────│
│  + obtenerBasesDatos(): array         │
└───────────────────────────────────────┘
           ▲
           │ implements
           │
┌──────────┴────────────────────────────┐
│  BaseDatosOutAdapter                  │
│  ────────────────────────────────────│
│  + obtenerBasesDatos(): array         │
│                                       │
│  [delegates to BaseDatosRepository]   │
└───────────────────────────────────────┘
```

## Glossary

- **BaseDatos**: Un sistema de base de datos sobre el cual un trabajador de la DGTIC puede solicitar acceso (e.g., PPB, SURI, XAMAN, OTROS)
- **Value Object**: Objeto de dominio inmutable cuya identidad está definida por sus atributos
- **DTO**: Data Transfer Object - objeto para transferencia de datos entre capas
