# Research: Catálogo de Ambientes de Desarrollo

**Feature**: Catálogo de Ambientes de Desarrollo  
**Date**: 2026-06-28  
**Status**: Completed

## Purpose

This document consolidates research findings for technical decisions required to implement the "Catálogo de Ambientes de Desarrollo" feature following hexagonal architecture and DDD principles.

## Research Areas

### 1. Esquema de Base de Datos PostgreSQL

**Decision**: Tabla `tb_cat_ambiente_desarrollo` con estructura minimal

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

**Rationale**:
- `id_nu_ambiente_desarrollo`: Serial/autoincrement para identificador único (convención id_nu_ para PKs numéricas)
- `sn_nombre`: VARCHAR(100) suficiente para nombres de ambientes (convención sn_ para strings)
- `ind_activo`: SMALLINT (0/1) en lugar de soft deletes de Laravel
- Constraint CHECK asegura que `ind_activo` solo puede ser 0 o 1
- UNIQUE en `sn_nombre` previene duplicados
- Timestamps para auditoria
- Índice en `ind_activo` para queries frecuentes

**Seed Data**:
```sql
INSERT INTO tb_cat_ambiente_desarrollo (sn_nombre, ind_activo) VALUES
    ('Desarrollo', 1),
    ('QA', 1),
    ('Producción', 1);
```

**Alternatives considered**:
- Usar `deleted_at` (soft deletes de Laravel): Rechazado porque el proyecto requiere `ind_activo` como convención
- JSON column para metadata: Rechazado por YAGNI (no hay metadata adicional requerida)
- Nombres de columnas sin prefijo: Rechazado, se debe seguir convención de tablas existentes (id_nu_, sn_)

### 2. Migrations Strategy

**Decision**: 2 migrations separadas (schema + seed)

**Files**:
1. `2026_06_28_000001_create_tb_cat_ambiente_desarrollo_table.php` - Schema
2. `2026_06_28_000002_seed_tb_cat_ambiente_desarrollo_table.php` - Seed data

**Rationale**:
- Separar schema de datos permite rollback independiente
- Seed migration facilita tener datos iniciales en todos los ambientes
- Naming convention con fecha garantiza orden de ejecución
- Timestamps incrementales (000001, 000002) para control fino

**Implementation approach**:
```php
// Migration 1: Schema
public function up(): void
{
    Schema::create('tb_cat_ambiente_desarrollo', function (Blueprint $table) {
        $table->id('id_nu_ambiente_desarrollo');
        $table->string('sn_nombre', 100)->unique();
        $table->smallInteger('ind_activo')->default(1);
        $table->timestamps();
        
        $table->index('ind_activo');
    });
    
    // PostgreSQL CHECK constraint
    DB::statement(
        'ALTER TABLE tb_cat_ambiente_desarrollo '
        . 'ADD CONSTRAINT chk_ind_activo '
        . 'CHECK (ind_activo IN (0, 1))'
    );
}

// Migration 2: Seed
public function up(): void
{
    DB::table('tb_cat_ambiente_desarrollo')->insert([
        ['sn_nombre' => 'Desarrollo', 'ind_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['sn_nombre' => 'QA', 'ind_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['sn_nombre' => 'Producción', 'ind_activo' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);
}
```

**Alternatives considered**:
- Single migration con schema + seed: Rechazado por falta de granularidad en rollback
- Seeder class: Rechazado porque datos iniciales deben estar siempre presentes

### 3. Repository Pattern con Eloquent

**Decision**: Interface en Application, Repository en Infrastructure, OutAdapter delega al Repository

**Rationale**:
- Port (interface) en `Application/Ports/Out/AmbienteDesarrolloOutPort.php`
- Repository en `Infrastructure/Adapters/Out/PostgresSQL/Repositories/AmbienteDesarrolloRepository.php`
- Adapter en `Infrastructure/Adapters/Out/PostgresSQL/AmbienteDesarrolloOutAdapter.php`
- Eloquent Model en `Infrastructure/Adapters/Out/PostgresSQL/Models/AmbienteDesarrolloModel.php`
- Binding en `AdminServiceProvider` conecta OutPort interface con OutAdapter implementation
- UseCase depende solo de la interface (dependency inversion)
- OutAdapter delega al Repository para separación de concerns

**Implementation approach**:
```php
// Application/Ports/Out/AmbienteDesarrolloOutPort.php (Interface)
interface AmbienteDesarrolloOutPort
{
    /**
     * @return list<AmbienteVO>
     */
    public function obtenerAmbientesDesarrollo(): array;
}

// Infrastructure (Eloquent Model)
class AmbienteDesarrolloModel extends Model
{
    protected $table = 'tb_cat_ambiente_desarrollo';
    protected $primaryKey = 'id_nu_ambiente_desarrollo';
    protected $fillable = ['sn_nombre', 'ind_activo'];
    public $timestamps = true;
}

// Infrastructure (Repository)
class AmbienteDesarrolloRepository
{
    public function obtenerAmbientesDesarrollo(): array
    {
        return AmbienteDesarrolloModel::where('ind_activo', 1)
            ->orderBy('id_nu_ambiente_desarrollo')
            ->get()
            ->map(fn($model) => new AmbienteVO(
                id: $model->id_nu_ambiente_desarrollo,
                nombre: $model->sn_nombre
            ))
            ->all();
    }
}

// Infrastructure (OutAdapter Implementation)
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

// Application (UseCase)
class ObtenerAmbientesUseCase
{
    public function __construct(
        private readonly AmbienteDesarrolloOutPort $outPort
    ) {}
    
    public function execute(): array
    {
        return $this->outPort->obtenerAmbientesDesarrollo();
    }
}

// AdminServiceProvider
public function register(): void
{
    $this->app->bind(
        AmbienteDesarrolloOutPort::class,
        AmbienteDesarrolloOutAdapter::class
    );
}
```

**Alternatives considered**:
- Query Builder directo: Rechazado por no seguir hexagonal architecture
- Repository en Domain: Rechazado, las interfaces de repositorio van en Application/Ports

### 4. Value Objects Inmutables en PHP 8.4

**Decision**: Usar `readonly` properties (PHP 8.1+) con validación en constructor

**Rationale**:
- PHP 8.1+ soporta `readonly` properties que garantizan inmutabilidad
- PHP 8.4 mantiene y mejora este soporte
- Constructor valida invariantes del dominio
- Named constructors para casos de creación específicos
- Compatibilidad con serialización JSON

**Implementation approach**:
```php
namespace App\Core\Admin\Domain\ValueObjects;

final readonly class AmbienteVO
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {
        if ($id <= 0) {
            throw new \InvalidArgumentException('El ID del ambiente debe ser mayor a 0');
        }
        
        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre del ambiente no puede estar vacío');
        }
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            nombre: $data['nombre']
        );
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
```

**Alternatives considered**:
- Private properties con getters: Rechazado por ser más verboso sin beneficios adicionales en PHP 8.4
- Array asociativo: Rechazado por carecer de type safety y validación de invariantes
- Stdclass: Rechazado por ser mutable y no expresar intención de dominio

### 5. Testing de Endpoints Públicos

**Decision**: Feature tests sin autenticación + Contract tests con schema validation

**Rationale**:
- Laravel feature tests pueden hacer peticiones HTTP sin autenticación
- Contract tests validan estructura de respuesta JSON contra schema
- Unit tests para use case en aislamiento
- Integration tests para repository que lee configuración

**Implementation approach**:
```php
// tests/Feature/Api/ObtenerAmbientesApiTest.php
public function test_obtener_ambientes_retorna_lista_exitosamente(): void
{
    $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre']
            ],
            'success',
            'message',
        ])
        ->assertJson([
            'success' => true,
        ]);
}
```

**Test coverage strategy**:
1. **Unit**: `ObtenerAmbientesUseCaseTest.php` - Use case con OutPort mock
2. **Integration**: `AmbienteDesarrolloRepositoryTest.php` - Repository con base de datos real
3. **Integration**: `AmbienteDesarrolloOutAdapterTest.php` - OutAdapter con Repository mock
4. **Feature/Contract**: `ObtenerAmbientesApiTest.php` - Endpoint completo E2E

**Alternatives considered**:
- Solo feature tests: Rechazado por no aislar capas y dificultar debugging

### 6. JSON API Response Format

**Decision**: Usar formato estándar definido en constitución del proyecto

**Rationale**:
- La constitución del proyecto ya define el formato estándar:
  ```json
  {
    "data": [...],
    "message": "...",
    "code": "200",
    "success": true
  }
  ```
- Consistencia con otros endpoints del sistema
- Facilita integración con clientes que ya consumen otros endpoints

**Implementation approach**:
```php
// Infrastructure/Adapters/In/Api/ObtenerAmbientesInAdapter.php
public function __invoke(): JsonResponse
{
    $ambientes = $this->useCase->execute(); // Returns array<AmbienteVO>
    
    $dto = new ObtenerAmbientesOutDto(ambientes: $ambientes);
    
    return response()->json([
        'data' => array_map(
            fn(AmbienteVO $ambiente) => $ambiente->toArray(),
            $dto->ambientes
        ),
        'message' => 'Ambientes obtenidos exitosamente',
        'code' => '200',
        'success' => true,
    ]);
}
```

**Alternatives considered**:
- JSON:API spec: Rechazado por no alinearse con estándar ya establecido en el proyecto
- Formato personalizado: Rechazado por romper consistencia con endpoints existentes

## Summary of Decisions

| Area | Decision | Rationale |
|------|----------|--------|
| **Database** | PostgreSQL tabla `tb_cat_ambiente_desarrollo` | Source of truth, 3 registros estáticos |
| **Migrations** | 2 files (schema + seed) | Rollback granular, datos siempre presentes |
| **ind_activo** | SMALLINT 0/1 con CHECK constraint | Convención del proyecto vs soft deletes |
| **Repository** | Interface en Application, Eloquent en Infrastructure | Hexagonal architecture, DIP |
| **Value Object** | `readonly` class con validación (AmbienteVO) | Inmutabilidad garantizada, PHP 8.4 |
| **Testing** | 3 niveles (Unit/Integration/Feature) | Cobertura completa, capas aisladas |
| **Response Format** | Formato constitución | Consistencia con sistema existente |

## Open Questions

No hay preguntas abiertas. Todas las decisiones técnicas están resueltas y listas para implementación.

## Next Steps

Proceder a **Phase 1: Design Artifacts** para crear:
1. `data-model.md` - Modelo de datos del Value Object AmbienteVO
2. `contracts/ambientes-api.md` - Contrato detallado del endpoint
3. `quickstart.md` - Guía rápida de desarrollo y testing
