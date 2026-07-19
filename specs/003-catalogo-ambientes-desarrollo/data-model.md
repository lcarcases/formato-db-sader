# Data Model: Catálogo de Ambientes de Desarrollo

**Feature**: Catálogo de Ambientes de Desarrollo  
**Date**: 2026-06-28  
**Status**: Final

## Overview

Este documento define el modelo de datos para el catálogo de ambientes de desarrollo. El modelo incluye persistencia en PostgreSQL, repository pattern para aislamiento de infraestructura, y value objects para representación de dominio.

## Database Schema (PostgreSQL)

### Tabla: tb_cat_ambiente_desarrollo

**Purpose**: Almacenar el catálogo de ambientes de desarrollo disponibles en el sistema.

**Schema**:

```sql
CREATE TABLE tb_cat_ambiente_desarrollo (
    id_nu_ambiente_desarrollo SERIAL PRIMARY KEY,
    sn_nombre VARCHAR(100) NOT NULL UNIQUE,
    ind_activo SMALLINT NOT NULL DEFAULT 1
        CHECK (ind_activo IN (0, 1)),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tb_cat_ambiente_desarrollo_activo 
    ON tb_cat_ambiente_desarrollo(ind_activo);
```

**Columns**:

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id_nu_ambiente_desarrollo` | SERIAL | PRIMARY KEY | Identificador único autogenerado |
| `sn_nombre` | VARCHAR(100) | NOT NULL, UNIQUE | Nombre del ambiente |
| `ind_activo` | SMALLINT | NOT NULL, CHECK (0 o 1), DEFAULT 1 | Indicador de registro activo |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de creación |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de última actualización |

**Indexes**:
- PRIMARY KEY on `id_nu_ambiente_desarrollo`
- UNIQUE on `sn_nombre`
- INDEX on `ind_activo` (queries frecuentes)

**Data Conventions**:
- `ind_activo = 1`: Registro activo (visible en el catálogo)
- `ind_activo = 0`: Registro inactivo (oculto del catálogo)
- **No soft deletes**: Se usa `ind_activo` en lugar del `deleted_at` de Laravel

**Seed Data** (initial data):

```sql
INSERT INTO tb_cat_ambiente_desarrollo (sn_nombre, ind_activo) VALUES
    ('Desarrollo', 1),
    ('QA', 1),
    ('Producción', 1);
```

**Column Naming Convention**:
- `id_nu_` prefix: Numeric primary keys
- `sn_` prefix: String/text columns
- `ind_` prefix: Indicator/flag columns (0/1)
- Esta convención sigue el patrón de otras tablas del proyecto

**Why PostgreSQL?**
- Single source of truth requerido por constitución
- Integridad referencial
- Datos pueden ser gestionados por admin UI en el futuro
- No Redis caching (datos son estáticos)

## Domain Model

### Value Object: AmbienteVO

**Type**: Value Object (Immutable)  
**Location**: `app/Core/Admin/Domain/ValueObjects/AmbienteVO.php`  
**Language**: PHP 8.4 with readonly properties

**Purpose**: Representa un ambiente de desarrollo/despliegue disponible en el sistema. Es un Value Object porque su identidad está definida completamente por sus atributos y es inmutable.

#### Attributes

| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| `id` | `int` | > 0, required | Identificador único del ambiente |
| `nombre` | `string` | non-empty, required | Nombre descriptivo del ambiente (e.g., "Desarrollo", "QA") |

#### Invariants

1. **ID positivo**: El `id` DEBE ser un entero positivo mayor a 0
2. **Nombre no vacío**: El `nombre` DEBE contener al menos un carácter no-whitespace
3. **Inmutabilidad**: Una vez creado, un `Ambiente` NO PUEDE ser modificado

#### Behavior Methods

| Method | Signature | Purpose |
|--------|-----------|---------|
| Constructor | `__construct(int $id, string $nombre)` | Crea un nuevo Ambiente validando invariantes |
| Named Constructor | `static fromArray(array $data): self` | Crea Ambiente desde array (útil para mapeo desde config) |
| Serialization | `toArray(): array` | Convierte a array asociativo para JSON response |

#### Example Usage

```php
use App\Core\Admin\Domain\ValueObjects\AmbienteVO;

// Creación directa
$ambiente = new AmbienteVO(id: 1, nombre: 'Desarrollo');

// Creación desde array
$ambiente = AmbienteVO::fromArray(['id' => 1, 'nombre' => 'Desarrollo']);

// Serialización para API response
$array = $ambiente->toArray();
// ['id' => 1, 'nombre' => 'Desarrollo']
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

### Port Out (Interface): AmbienteDesarrolloOutPort

**Type**: Repository Interface (Port Out)  
**Location**: `app/Core/Admin/Application/Ports/Out/AmbienteDesarrolloOutPort.php`

**Purpose**: Define el contrato para obtener ambientes desde el almacenamiento (database).

**Methods**:

```php
interface AmbienteDesarrolloOutPort
{
    /**
     * Obtiene todos los ambientes de desarrollo activos (ind_activo = 1)
     * ordenados por ID.
     *
     * @return list<AmbienteVO>
     */
    public function obtenerAmbientesDesarrollo(): array;
}
```

**Why Interface in Application?**
- Dependency Inversion Principle (DIP)
- Use case depende de abstracción, no de implementación concreta
- Permite testing con mocks
- Infraestructura (Eloquent) es detalle de implementación

### ObtenerAmbientesUseCase

**Type**: Application Service (Use Case)  
**Location**: `app/Core/Admin/Application/UseCases/ObtenerAmbientesUseCase.php`

**Dependencies**: 
- `AmbienteDesarrolloOutPort` (injected via constructor)

**Responsibility**: Orquestar la obtención de ambientes activos desde el OutPort y retornar datos raw para máxima reutilización

**Flow**:
1. Invoca `outPort->obtenerAmbientesDesarrollo()`
2. Recibe array de `AmbienteVO`
3. Retorna array directamente (raw data)

**Implementation**:

```php
class ObtenerAmbientesUseCase
{
    public function __construct(
        private readonly AmbienteDesarrolloOutPort $outPort
    ) {}
    
    /**
     * @return array<AmbienteVO>
     */
    public function execute(): array
    {
        return $this->outPort->obtenerAmbientesDesarrollo();
    }
}
```

**Why return raw data?**
- Maximiza reutilización del use case (puede ser usado por diferentes adapters)
- El InAdapter (REST, CLI, etc.) es responsable de transformar a DTO según sus necesidades
- Mantiene el use case simple y enfocado en lógica de negocio
```

### ObtenerAmbientesOutDto (DTO)

**Type**: Data Transfer Object (Immutable)  
**Location**: `app/Core/Admin/Application/DTOs/Out/ObtenerAmbientesOutDto.php`

**Attributes**:
- `ambientes`: `array<AmbienteVO>` - Lista de ambientes obtenidos

**Usage**: Creado por el InAdapter (REST controller) a partir del resultado del UseCase

**Why DTO?**
- Explicit contract entre InAdapter y respuesta API
- Type-safe
- Permite diferentes representaciones según el adapter (REST JSON, CLI, etc.)

**Note**: El UseCase NO crea este DTO. Es responsabilidad del InAdapter transformar el array<AmbienteVO> a DTO.

## Infrastructure Layer: Persistence

### Eloquent Model: AmbienteDesarrolloModel

**Type**: Eloquent Model (Laravel ORM)  
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/AmbienteDesarrolloModel.php`

**Purpose**: Mapear tabla `tb_cat_ambiente_desarrollo` a objeto PHP.

**Configuration**:

```php
use Illuminate\Database\Eloquent\Model;

class AmbienteDesarrolloModel extends Model
{
    protected $table = 'tb_cat_ambiente_desarrollo';
    
    protected $primaryKey = 'id_nu_ambiente_desarrollo';
    
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
- Solo AmbienteDesarrolloRepository lo usa

### Repository: AmbienteDesarrolloRepository

**Type**: Repository (Data access layer)  
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/AmbienteDesarrolloRepository.php`

**Purpose**: Encapsular lógica de acceso a datos usando Eloquent Model

**Implementation**:

```php
use App\Core\Admin\Domain\ValueObjects\AmbienteVO;

class AmbienteDesarrolloRepository
{
    /**
     * Obtiene ambientes de desarrollo activos desde la base de datos
     *
     * @return array<AmbienteVO>
     */
    public function obtenerAmbientesDesarrollo(): array
    {
        return AmbienteDesarrolloModel::where('ind_activo', 1)
            ->orderBy('id_nu_ambiente_desarrollo')
            ->get(['id_nu_ambiente_desarrollo', 'sn_nombre'])
            ->map(fn(AmbienteDesarrolloModel $model) => new AmbienteVO(
                id: $model->id_nu_ambiente_desarrollo,
                nombre: $model->sn_nombre
            ))
            ->all(); // Convert Collection to array
    }
}
```

**Key Points**:
- Query solo registros con `ind_activo = 1`
- Ordenar por `id_nu_ambiente_desarrollo` para resultado consistente
- Seleccionar solo columnas necesarias
- Mapear Eloquent Model → Domain Value Object
- Retornar array (list) de `AmbienteVO`

**Why separate Repository class?**
- Separación de concerns: OutAdapter implementa port, Repository accede a datos
- Facilita testing: Repository puede ser mockeado en tests del OutAdapter
- Reutilización: Repository puede ser usado por múltiples adapters si es necesario

### OutAdapter Implementation: AmbienteDesarrolloOutAdapter

**Type**: Repository OutAdapter (Out)  
**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/AmbienteDesarrolloOutAdapter.php`

**Implements**: `AmbienteDesarrolloOutPort` interface

**Dependencies**: `AmbienteDesarrolloRepository` (injected via constructor)

**Purpose**: Implementar el OutPort delegando al Repository.

**Implementation**:

```php
use App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort;
use App\Core\Admin\Domain\ValueObjects\AmbienteVO;

class AmbienteDesarrolloOutAdapter implements AmbienteDesarrolloOutPort
{
    public function __construct(
        private readonly AmbienteDesarrolloRepository $repository
    ) {}
    
    public function obtenerAmbientesDesarrollo(): array
    {
        return $this->repository->obtenerAmbientesDesarrollo();
    }
}

**Why in Infrastructure?**
- Implementación depende de Eloquent (Laravel framework)
- Hexagonal architecture: Adapters en Infrastructure
- Testeable con integration tests usando database real

### Service Provider Binding

**Location**: `app/Providers/AppServiceProvider.php`

**Purpose**: Conectar interface con implementación concreta.

```php
public function register(): void
{
    $this->app->bind(
        \App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort::class,
        \App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\AmbienteDesarrolloOutAdapter::class
    );
}
```

**Why binding?**
- Dependency Injection Container resuelve dependencias
- Use case recibe OutPort interface, Laravel inyecta OutAdapter implementation
- Cambiar implementación es trivial (solo actualizar binding)

## Testing Strategy

### Unit Tests

**Test**: `ObtenerAmbientesUseCaseTest.php`  
**Location**: `tests/Unit/Core/Admin/Application/UseCases/`

**Purpose**: Test del use case en aislamiento con OutPort mock

**Scenarios**:
1. Test que verifica que el use case invoca `outPort->obtenerAmbientesDesarrollo()`
2. Test que verifica que retorna array<AmbienteVO> con ambientes del OutPort
3. Test que verifica manejo de lista vacía desde OutPort

**Mocking**:
```php
$outPortMock = $this->createMock(AmbienteDesarrolloOutPort::class);
$outPortMock->expects($this->once())
    ->method('obtenerAmbientesDesarrollo')
    ->willReturn([
        new AmbienteVO(1, 'Desarrollo'),
        new AmbienteVO(2, 'QA'),
    ]);

$useCase = new ObtenerAmbientesUseCase($outPortMock);
$result = $useCase->execute();

$this->assertIsArray($result);
$this->assertCount(2, $result);
$this->assertEquals('Desarrollo', $result[0]->nombre);
```

### Integration Tests

**Test 1**: `AmbienteDesarrolloRepositoryTest.php`  
**Location**: `tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/Repositories/`

**Purpose**: Test del Repository con base de datos real

**Setup**: 
- Usa `RefreshDatabase` trait para migraciones
- Seed data en cada test con factories o inserts directos

**Scenarios**:
1. Test que `obtenerAmbientesDesarrollo()` retorna solo registros con `ind_activo = 1`
2. Test que registros con `ind_activo = 0` son excluidos
3. Test que resultados están ordenados por `id_nu_ambiente_desarrollo` ASC
4. Test que retorna array de `AmbienteVO` con valores correctos
5. Test que retorna array vacío si no hay registros activos

**Example**:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class AmbienteDesarrolloRepositoryTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_obtiene_solo_ambientes_activos(): void
    {
        // Arrange
        DB::table('tb_cat_ambiente_desarrollo')->insert([
            ['id_nu_ambiente_desarrollo' => 1, 'sn_nombre' => 'Desarrollo', 'ind_activo' => 1],
            ['id_nu_ambiente_desarrollo' => 2, 'sn_nombre' => 'QA', 'ind_activo' => 0], // Inactivo
            ['id_nu_ambiente_desarrollo' => 3, 'sn_nombre' => 'Producción', 'ind_activo' => 1],
        ]);
        
        $repository = new AmbienteDesarrolloRepository();
        
        // Act
        $result = $repository->obtenerAmbientesDesarrollo();
        
        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('Desarrollo', $result[0]->nombre);
        $this->assertEquals('Producción', $result[1]->nombre);
    }
}
```

**Test 2**: `AmbienteDesarrolloOutAdapterTest.php`  
**Location**: `tests/Integration/Infrastructure/Adapters/Out/PostgresSQL/`

**Purpose**: Test del OutAdapter en aislamiento con Repository mock

**Scenarios**:
1. Test que `obtenerAmbientesDesarrollo()` invoca `repository->obtenerAmbientesDesarrollo()`
2. Test que retorna el resultado del repository sin transformación

**Example**:
```php
class AmbienteDesarrolloOutAdapterTest extends TestCase
{
    public function test_obtener_ambientes_desarrollo_delega_al_repository(): void
    {
        // Arrange
        $expectedAmbientes = [
            new AmbienteVO(1, 'Desarrollo'),
            new AmbienteVO(2, 'QA'),
        ];
        
        $repositoryMock = $this->createMock(AmbienteDesarrolloRepository::class);
        $repositoryMock->expects($this->once())
            ->method('obtenerAmbientesDesarrollo')
            ->willReturn($expectedAmbientes);
        
        $outAdapter = new AmbienteDesarrolloOutAdapter($repositoryMock);
        
        // Act
        $result = $outAdapter->obtenerAmbientesDesarrollo();
        
        // Assert
        $this->assertSame($expectedAmbientes, $result);
    }
}
```

### Feature/Contract Tests

**Test**: `ObtenerAmbientesApiTest.php`  
**Location**: `tests/Feature/Api/`

**Purpose**: Test E2E del endpoint REST con base de datos

**Setup**: 
- Usa `RefreshDatabase` trait
- Seed data para el test

**Scenarios**:
1. GET /api/v1/admin/ambientes-desarrollo → 200 con estructura JSON correcta
2. Verifica schema de respuesta (data, success, message, code)
3. Verifica que data contiene solo ambientes con `ind_activo = 1`
4. Verifica que ambientes están ordenados por id
5. Test con base de datos vacía retorna array vacío en data

## Domain-Driven Design Compliance

### Ubiquitous Language

| Término | Español | Usado en | Justificación |
|---------|---------|----------|---------------|
| Ambiente | "Environment" | Code, API, DB, Tests, Docs | Término institucional SADER |
| Obtener | "Fetch/Get" | Use case names | Verbo de dominio claro |

### Bounded Context

**Context**: Admin  
**Responsibility**: Catálogos y configuraciones del sistema  
**Related Contexts**: Ninguno (este bounded context es independiente por ahora)

### Aggregate Patterns

**N/A**: No hay aggregates en esta feature. `Ambiente` es un Value Object sin ciclo de vida ni relaciones.

## Non-Functional Considerations

### Performance

- **Target**: < 200ms response time @ 50 req/s
- **Strategy**: Datos hardcoded en memoria, sin I/O de red o DB
- **Bottlenecks**: Ninguno esperado (lectura de array en memoria)

### Scalability

- **Current**: 3-10 ambientes típicos
- **Future**: Si crece > 50 ambientes, considerar paginación o filtrado

### Security

- **Public endpoint**: No requiere autenticación (por requisito funcional)
- **Data sensitivity**: Nombres de ambientes son públicos, no hay datos sensibles
- **Input validation**: No hay input del usuario en este endpoint

## Future Considerations

1. **Ambientes dinámicos**: Si ambientes se crean/eliminan dinámicamente:
   - Migrar a tabla PostgreSQL
   - Agregar casos de uso para CRUD de ambientes
   - Implementar caching en Redis

2. **Ambientes configurables por usuario**: Si en el futuro se requiere que diferentes usuarios vean diferentes ambientes:
   - Mover a base de datos con relación User-Ambiente
   - Agregar filtrado por permisos de usuario
   - Mantener Value Object, cambiar arquitectura

3. **Metadata adicional**: Si se requiere más info por ambiente (URL, descripción, icono):
   - Extender Value Object con atributos opcionales
   - Mantener backward compatibility en API

## Diagram: Domain Model

```
┌───────────────────────────────────────┐
│     Value Object: Ambiente            │
│  ─────────────────────────────────── │
│  + id: int                            │
│  + nombre: string                     │
│  ─────────────────────────────────── │
│  + __construct(int, string)           │
│  + fromArray(array): Ambiente         │
│  + toArray(): array                   │
└───────────────────────────────────────┘
           ▲
           │ returns array of
           │
┌──────────┴────────────────────────────┐
│  <<Interface>>                        │
│  AmbienteDesarrolloRepository                   │
│  ────────────────────────────────────│
│  + obtenerTodos(): array<Ambiente>    │
└───────────────────────────────────────┘
           ▲
           │ implements
           │
┌──────────┴────────────────────────────┐
│  ConfigAmbienteDesarrolloRepository             │
│  ────────────────────────────────────│
│  + obtenerTodos(): array<Ambiente>    │
│                                       │
│  [reads from config/environments.php] │
└───────────────────────────────────────┘
```

## Glossary

- **Ambiente**: Un entorno de despliegue del sistema (e.g., Desarrollo, QA, Producción)
- **Value Object**: Objeto de dominio inmutable cuya identidad está definida por sus atributos
- **DTO**: Data Transfer Object - objeto para transferencia de datos entre capas
