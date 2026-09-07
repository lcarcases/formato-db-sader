# Data Model: Catálogo de Esquemas por Hostname

**Feature**: Catálogo de Esquemas por Hostname
**Date**: 2026-08-30
**Status**: Final

## Overview

Este documento define el modelo de datos para el catálogo `Esquema` (16 valores) y su relación
muchos-a-muchos con `Hostname` (48 asociaciones: 16 esquemas × 3 hostnames). Sigue el modelo ya
validado en `specs/005-catalogo-hostnames/data-model.md`, extendido con la primera tabla pivote del
repositorio.

## Database Schema (PostgreSQL)

### Tabla: tb_cat_esquema

**Purpose**: Almacenar el catálogo independiente de esquemas de base de datos disponibles para
solicitud de acceso.

**Schema**:

```sql
CREATE TABLE tb_cat_esquema (
    id_nu_esquema SERIAL PRIMARY KEY,
    sn_nombre VARCHAR(100) NOT NULL UNIQUE,
    ind_activo SMALLINT NOT NULL DEFAULT 1
        CHECK (ind_activo IN (0, 1)),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tb_cat_esquema_activo
    ON tb_cat_esquema(ind_activo);
```

**Columns**:

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id_nu_esquema` | SERIAL | PRIMARY KEY | Identificador único autogenerado |
| `sn_nombre` | VARCHAR(100) | NOT NULL, UNIQUE | Nombre del esquema de base de datos |
| `ind_activo` | SMALLINT | NOT NULL, CHECK (0 o 1), DEFAULT 1 | Indicador de registro activo |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de creación |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de última actualización |

**Table comment**: `'Catálogo de esquemas de base de datos disponibles para solicitud de acceso'`

**Seed Data** (16 valores, en este orden exacto):

```sql
INSERT INTO tb_cat_esquema (sn_nombre, ind_activo) VALUES
    ('ap_activemq_pd', 1),
    ('ap_apoyos_pd', 1),
    ('ap_biometricos_pd', 1),
    ('ap_gestion_doc', 1),
    ('ap_interfaz', 1),
    ('ap_inventario_pd', 1),
    ('ap_movil_pd', 1),
    ('ap_proagro_pd', 1),
    ('ap_reportes_suri', 1),
    ('ap_supervision_pd', 1),
    ('ap_suri_pd', 1),
    ('ap_svc', 1),
    ('ap_tramites_pd', 1),
    ('ap_viaticos', 1),
    ('tr_seguridad_pd', 1),
    ('tr_suri_pd', 1);
```

### Tabla: tb_r_hostname_esquema

**Purpose**: Expresar la relación muchos-a-muchos entre `tb_cat_hostname` y `tb_cat_esquema` — qué
esquemas están disponibles para solicitar acceso en un hostname determinado. Primera tabla de
"relación pura" (infijo `r`) del repositorio.

**Schema**:

```sql
CREATE TABLE tb_r_hostname_esquema (
    id_nu_hostname_esquema SERIAL PRIMARY KEY,
    id_nu_hostname INTEGER NOT NULL REFERENCES tb_cat_hostname(id_nu_hostname) ON DELETE CASCADE,
    id_nu_esquema INTEGER NOT NULL REFERENCES tb_cat_esquema(id_nu_esquema) ON DELETE CASCADE,
    ind_activo SMALLINT NOT NULL DEFAULT 1
        CHECK (ind_activo IN (0, 1)),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX uq_tb_r_hostname_esquema_hostname_esquema
    ON tb_r_hostname_esquema(id_nu_hostname, id_nu_esquema);

CREATE INDEX idx_tb_r_hostname_esquema_hostname
    ON tb_r_hostname_esquema(id_nu_hostname);
```

**Columns**:

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| `id_nu_hostname_esquema` | SERIAL | PRIMARY KEY | Identificador único autogenerado de la asociación |
| `id_nu_hostname` | INTEGER | NOT NULL, FK → `tb_cat_hostname.id_nu_hostname` | Hostname asociado |
| `id_nu_esquema` | INTEGER | NOT NULL, FK → `tb_cat_esquema.id_nu_esquema` | Esquema asociado |
| `ind_activo` | SMALLINT | NOT NULL, CHECK (0 o 1), DEFAULT 1 | Indicador de asociación activa |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de creación |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT now() | Fecha de última actualización |

**Indexes**:
- PRIMARY KEY on `id_nu_hostname_esquema`
- UNIQUE composite on `(id_nu_hostname, id_nu_esquema)` — previene asociaciones duplicadas, nombre
  `uq_tb_r_hostname_esquema_hostname_esquema`
- INDEX on `id_nu_hostname` — ruta de consulta principal del endpoint anidado, nombre
  `idx_tb_r_hostname_esquema_hostname`

**Table comment**: `'Relación muchos-a-muchos entre hostnames y esquemas de base de datos'`

**Seed Data**: 48 filas = producto cartesiano de los 16 `id_nu_esquema` (1–16, en el orden
sembrado arriba) × los 3 hostnames `sridesbds09` (`id_nu_hostname = 2`), `sriqabds08`
(`id_nu_hostname = 7`), `sriprdbdsmz02` (`id_nu_hostname = 4`), todas con `ind_activo = 1`. Ningún
otro hostname sembrado (`id_nu_hostname` 1, 3, 5, 6, 8–11) recibe asociaciones en esta historia.

**Data Conventions (ambas tablas)**:
- `ind_activo = 1`: Registro/asociación activa (visible en el catálogo/listado)
- `ind_activo = 0`: Registro/asociación inactiva (oculta)
- **No soft deletes**: Se usa `ind_activo` en lugar del `deleted_at` de Laravel
- **Column naming convention**: `id_nu_` (PKs/FKs numéricas), `sn_` (strings), `ind_` (flags) — y
  el infijo `r` en el nombre de tabla para marcar una tabla de pura relación (vs. `cat` para
  catálogos)

**Why PostgreSQL?**
- Single source of truth requerido por constitución
- Integridad referencial explícita vía FKs entre `tb_r_hostname_esquema` y las dos tablas padre
- No Redis caching (datos son estáticos)

## Domain Model

### Value Object: EsquemaVO

**Type**: Value Object (Immutable)
**Location**: `app/Core/Admin/Domain/ValueObjects/EsquemaVO.php`
**Language**: PHP 8.4 with readonly properties

**Purpose**: Representa un esquema de base de datos al cual un trabajador puede solicitar acceso
dentro de un hostname determinado. Idéntico en forma e invariantes a `HostnameVO`/`BaseDatosVO`.
La entrada sintética "Todos" (`id = 0`) **nunca** se instancia como `EsquemaVO` — el invariante
`id > 0` se mantiene sin excepciones.

#### Attributes

| Attribute | Type | Constraints | Description |
|-----------|------|-------------|--------------|
| `id` | `int` | > 0, required | Identificador único del esquema |
| `nombre` | `string` | non-empty (trim), required | Nombre del esquema (e.g., "ap_activemq_pd") |

#### Invariants

1. **ID positivo**: El `id` DEBE ser un entero positivo mayor a 0
2. **Nombre no vacío**: El `nombre` DEBE contener al menos un carácter no-whitespace tras `trim()`
3. **Inmutabilidad**: Una vez creado, un `EsquemaVO` NO PUEDE ser modificado
4. **Sin validación de formato**: NO se valida un formato específico de nombre de esquema —
   decisión cerrada del spec, mismo nivel minimalista que `HostnameVO`/`BaseDatosVO`

#### Behavior Methods

| Method | Signature | Purpose |
|--------|-----------|---------|
| Constructor | `__construct(int $id, string $nombre)` | Crea un nuevo `EsquemaVO` validando invariantes |
| Named Constructor | `static fromArray(array $data): self` | Crea `EsquemaVO` desde array |
| Serialization | `toArray(): array` | Convierte a array asociativo para JSON response |

#### Example Usage

```php
use App\Core\Admin\Domain\ValueObjects\EsquemaVO;

$esquema = new EsquemaVO(id: 1, nombre: 'ap_activemq_pd');
$esquema = EsquemaVO::fromArray(['id' => 1, 'nombre' => 'ap_activemq_pd']);
$array = $esquema->toArray(); // ['id' => 1, 'nombre' => 'ap_activemq_pd']
```

**Why Value Object?** Igual justificación que `HostnameVO`: sin identidad más allá de sus
atributos, inmutable, se compara por valor.

### Domain Exception: HostnameNotFoundException

**Type**: Domain Exception
**Location**: `app/Core/Admin/Domain/Exceptions/HostnameNotFoundException.php`

**Purpose**: Señalar que el `idHostname` solicitado no existe en `tb_cat_hostname`, para que el
InAdapter lo traduzca a HTTP 404. Mirror exacto del patrón de `TipoPersonalNotFoundException`/
`TipoPermisoNotFoundException` (extiende `\Exception` puro, sin dependencias Laravel, código HTTP
404 por defecto).

**Constructor**: `__construct(int $idHostname, int $code = 404, ?\Throwable $previous = null)` —
mensaje generado: `sprintf('Hostname with ID %d not found. Verify the ID exists in tb_cat_hostname table.', $idHostname)`.

**Thrown by**: `ObtenerEsquemasPorHostnameUseCase::execute()`, cuando
`EsquemaOutPort::obtenerEsquemasPorHostname()` retorna `null`.

**Caught by**: `ObtenerEsquemasPorHostnameInAdapter::__invoke()`, que traduce a HTTP 404 con
`success: false`.

## Application Layer: Ports, Use Cases & DTOs

### Port Out (Interface): EsquemaOutPort

**Type**: Repository Interface (Port Out)
**Location**: `app/Core/Admin/Application/Ports/Out/EsquemaOutPort.php`

```php
interface EsquemaOutPort
{
    /**
     * Obtiene todos los esquemas activos (ind_activo = 1), ordenados por ID.
     *
     * @return list<EsquemaVO>
     */
    public function obtenerEsquemas(): array;

    /**
     * Obtiene los esquemas activos asociados a un hostname (vía tb_r_hostname_esquema),
     * ordenados por id_nu_esquema ascendente.
     *
     * @return list<EsquemaVO>|null null si el hostname no existe en tb_cat_hostname;
     *                               [] si existe pero no tiene asociaciones activas.
     */
    public function obtenerEsquemasPorHostname(int $idHostname): ?array;
}
```

**Why `?array` and not a Result/Either type?** Simplicidad explícita: `null` es inequívoco como
"recurso padre no encontrado" en este contrato de Application, evitando introducir un tipo de
retorno adicional solo para esta distinción (ver `research.md` Decision 7).

### ObtenerEsquemasUseCase

**Type**: Application Service (Use Case) — catálogo completo
**Location**: `app/Core/Admin/Application/UseCases/ObtenerEsquemasUseCase.php`

```php
final readonly class ObtenerEsquemasUseCase
{
    public function __construct(
        private EsquemaOutPort $esquemaOutPort,
    ) {}

    /** @return list<EsquemaVO> */
    public function execute(): array
    {
        return $this->esquemaOutPort->obtenerEsquemas();
    }
}
```

### ObtenerEsquemasPorHostnameUseCase

**Type**: Application Service (Use Case) — por hostname, con manejo de "no encontrado"
**Location**: `app/Core/Admin/Application/UseCases/ObtenerEsquemasPorHostnameUseCase.php`

```php
final readonly class ObtenerEsquemasPorHostnameUseCase
{
    public function __construct(
        private EsquemaOutPort $esquemaOutPort,
    ) {}

    /**
     * @return list<EsquemaVO> Lista de esquemas reales asociados (sin "Todos" — se antepone en el OutDto)
     *
     * @throws HostnameNotFoundException si el hostname no existe
     */
    public function execute(int $idHostname): array
    {
        $esquemas = $this->esquemaOutPort->obtenerEsquemasPorHostname($idHostname);

        if ($esquemas === null) {
            throw new HostnameNotFoundException($idHostname);
        }

        return $esquemas;
    }
}
```

### DTOs: ObtenerEsquemaOutDto / ObtenerEsquemasOutDto / ObtenerEsquemasPorHostnameOutDto

**Type**: Data Transfer Objects (Immutable)
**Location**: `app/Core/Admin/Application/DTOs/Out/`

- **`ObtenerEsquemaOutDto`** (item): `id: int`, `nombre: string`, `toArray(): array{id:int, nombre:string}`
  — idéntico a `ObtenerHostnameOutDto`.
- **`ObtenerEsquemasOutDto`** (colección, catálogo completo): `esquemas: list<ObtenerEsquemaOutDto>`,
  `toArray(): list<array{id:int, nombre:string}>` — idéntico a `ObtenerHostnamesOutDto`, sin la
  entrada "Todos".
- **`ObtenerEsquemasPorHostnameOutDto`** (colección, endpoint anidado): `esquemas: list<ObtenerEsquemaOutDto>`
  (solo los reales, sin "Todos"), pero `toArray()` antepone siempre
  `['id' => 0, 'nombre' => 'Todos']` como primer elemento:

  ```php
  final readonly class ObtenerEsquemasPorHostnameOutDto
  {
      /** @param list<ObtenerEsquemaOutDto> $esquemas */
      public function __construct(
          public array $esquemas,
      ) {}

      /** @return list<array{id: int, nombre: string}> */
      public function toArray(): array
      {
          $todos = ['id' => 0, 'nombre' => 'Todos'];

          return [
              $todos,
              ...array_map(fn (ObtenerEsquemaOutDto $e): array => $e->toArray(), $this->esquemas),
          ];
      }
  }
  ```

**Usage**: Creados por el InAdapter correspondiente a partir del resultado del Use Case,
replicando exactamente el patrón verificado en `ObtenerHostnamesInAdapter`.

## Infrastructure Layer: Persistence

### Eloquent Model: EsquemaModel

**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/EsquemaModel.php`

```php
final class EsquemaModel extends Model
{
    protected $table = 'tb_cat_esquema';
    protected $primaryKey = 'id_nu_esquema';
    protected $fillable = ['sn_nombre', 'ind_activo'];
    public $timestamps = true;

    protected function casts(): array
    {
        return ['ind_activo' => 'integer'];
    }
}
```

### Eloquent Model: HostnameEsquemaModel (pivot, interno a EsquemaRepository)

**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameEsquemaModel.php`

```php
final class HostnameEsquemaModel extends Model
{
    protected $table = 'tb_r_hostname_esquema';
    protected $primaryKey = 'id_nu_hostname_esquema';
    protected $fillable = ['id_nu_hostname', 'id_nu_esquema', 'ind_activo'];
    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'id_nu_hostname' => 'integer',
            'id_nu_esquema' => 'integer',
            'ind_activo' => 'integer',
        ];
    }
}
```

**Not exposed as its own Port/OutAdapter** — usado únicamente dentro de `EsquemaRepository`, per
decisión cerrada del spec (Stage 0 pregunta 13): no hay endpoint inverso hostnames-por-esquema en
esta historia, así que no se justifica un port dedicado a la tabla pivote.

### Repository: EsquemaRepository

**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/EsquemaRepository.php`

**Responsibility**: Ejecutar queries con Eloquent y retornar datos crudos (modelos), igual que
`HostnameRepository`. Hace también el chequeo de existencia de hostname contra `HostnameModel`
(mismo bounded context/capa, sin modificar `HostnameOutPort`/`HostnameOutAdapter`).

```php
final class EsquemaRepository
{
    /** @return list<EsquemaModel> */
    public function obtenerEsquemas(): array
    {
        return EsquemaModel::query()
            ->where('ind_activo', 1)
            ->orderBy('id_nu_esquema', 'asc')
            ->get(['id_nu_esquema', 'sn_nombre'])
            ->values()
            ->all();
    }

    /**
     * @return list<EsquemaModel>|null null si el hostname no existe;
     *                                  [] si existe pero no tiene asociaciones activas.
     */
    public function obtenerEsquemasPorHostname(int $idHostname): ?array
    {
        $hostnameExists = HostnameModel::query()
            ->whereKey($idHostname)
            ->exists();

        if (! $hostnameExists) {
            return null;
        }

        return EsquemaModel::query()
            ->select(['tb_cat_esquema.id_nu_esquema', 'tb_cat_esquema.sn_nombre'])
            ->join('tb_r_hostname_esquema', 'tb_r_hostname_esquema.id_nu_esquema', '=', 'tb_cat_esquema.id_nu_esquema')
            ->where('tb_r_hostname_esquema.id_nu_hostname', $idHostname)
            ->where('tb_r_hostname_esquema.ind_activo', 1)
            ->where('tb_cat_esquema.ind_activo', 1)
            ->orderBy('tb_cat_esquema.id_nu_esquema', 'asc')
            ->get()
            ->values()
            ->all();
    }
}
```

**Key Points**:
- `obtenerEsquemas()`: mismo patrón exacto que `HostnameRepository::obtenerHostnames()`.
- `obtenerEsquemasPorHostname()`: usa `HostnameModel::query()->whereKey($idHostname)->exists()`
  para el chequeo de existencia (no instancia `HostnameVO`, no expone `HostnameOutPort`); luego
  hace un `join` explícito contra `tb_r_hostname_esquema` filtrando por `ind_activo = 1` en
  ambas tablas.

### OutAdapter Implementation: EsquemaOutAdapter

**Location**: `app/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/EsquemaOutAdapter.php`

```php
final readonly class EsquemaOutAdapter implements EsquemaOutPort
{
    public function __construct(
        private EsquemaRepository $esquemaRepository,
    ) {}

    public function obtenerEsquemas(): array
    {
        return array_map(
            fn (EsquemaModel $model): EsquemaVO => new EsquemaVO(
                id: $model->id_nu_esquema,
                nombre: $model->sn_nombre,
            ),
            $this->esquemaRepository->obtenerEsquemas()
        );
    }

    public function obtenerEsquemasPorHostname(int $idHostname): ?array
    {
        $rawData = $this->esquemaRepository->obtenerEsquemasPorHostname($idHostname);

        if ($rawData === null) {
            return null;
        }

        return array_map(
            fn (EsquemaModel $model): EsquemaVO => new EsquemaVO(
                id: $model->id_nu_esquema,
                nombre: $model->sn_nombre,
            ),
            $rawData
        );
    }
}
```

### InAdapter: ObtenerEsquemasInAdapter

**Location**: `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerEsquemasInAdapter.php`

Mirror exacto de `ObtenerHostnamesInAdapter`: resuelve `ObtenerEsquemasUseCase` vía
`app()->make(...)` en el constructor, usa `App\Core\Shared\Infraestructure\Respuesta`.

**Success message**: `'Se obtuvieron los esquemas correctamente.'`
**Error message**: `'Error mientras se intentaba obtener los esquemas.'`

### InAdapter: ObtenerEsquemasPorHostnameInAdapter

**Location**: `app/Core/Admin/Infrastructure/Adapters/In/Api/ObtenerEsquemasPorHostnameInAdapter.php`

Resuelve `ObtenerEsquemasPorHostnameUseCase` vía `app()->make(...)`. Recibe `int $idHostname` como
parámetro de ruta (Laravel route-model-binding implícito por tipo `int`). Captura
`HostnameNotFoundException` por separado de `\Exception` genérica para devolver 404 en vez de 500:

```php
public function __invoke(int $idHostname)
{
    $respuesta = new Respuesta;

    try {
        $esquemas = $this->obtenerEsquemasPorHostnameUseCase->execute($idHostname);

        $outDto = new ObtenerEsquemasPorHostnameOutDto(
            array_map(
                fn (EsquemaVO $e): ObtenerEsquemaOutDto => new ObtenerEsquemaOutDto(id: $e->id, nombre: $e->nombre),
                $esquemas
            )
        );

        $respuesta->setSuccess(true);
        $respuesta->setMessage('Se obtuvieron los esquemas del hostname correctamente.');
        $respuesta->setData($outDto->toArray());

        return $respuesta->successResponse();
    } catch (HostnameNotFoundException $ex) {
        $respuesta->setSuccess(false);
        $respuesta->setData([]);
        $respuesta->setMessage('El hostname solicitado no existe.');

        return response()->json([
            'success' => false,
            'message' => 'El hostname solicitado no existe.',
            'data' => [],
        ], 404);
    } catch (\Exception $ex) {
        $respuesta->setSuccess(false);
        $respuesta->setData([]);
        $respuesta->setMessage('Error mientras se intentaba obtener los esquemas del hostname.');

        return $respuesta->errorResponse($ex);
    }
}
```

**Note**: `Respuesta::errorResponse()` (español) siempre responde 500; como el 404 exige un código
distinto, el `catch (HostnameNotFoundException $ex)` construye la respuesta JSON directamente con
`response()->json(..., 404)` manticiendo el mismo shape `{success, message, data}` que produce
`Respuesta`, sin necesidad de modificar la clase compartida `Respuesta` (que es usada por múltiples
InAdapters ya mergeados).

### Service Provider Binding

**Location**: `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`

```php
$this->app->bind(
    EsquemaOutPort::class,
    EsquemaOutAdapter::class
);
```

Se agrega al bloque `register()` existente, junto al binding de `HostnameOutPort`.

## Testing Strategy

### Unit Tests

- `EsquemaVOTest.php` (`tests/Unit/Core/Admin/Domain/ValueObjects/`) — mirror de
  `AmbienteVOTest.php`: constructor válido, `id <= 0` lanza excepción, `nombre` vacío/whitespace
  lanza excepción, `fromArray` (incluye trim y claves faltantes), `toArray`, inmutabilidad
  (`readonly`).
- `ObtenerEsquemasUseCaseTest.php` (`tests/Unit/Core/Admin/Application/UseCases/`) — mirror de
  `ObtenerHostnamesUseCaseTest.php`: invoca el port, retorna array de `EsquemaVO`, maneja lista
  vacía, retorna el array sin modificar.
- `ObtenerEsquemasPorHostnameUseCaseTest.php` — casos: port retorna lista con datos (se retorna tal
  cual, "Todos" NO se agrega aquí — responsabilidad del OutDto), port retorna `[]` (se retorna
  `[]`, no excepción), port retorna `null` (se lanza `HostnameNotFoundException` con el mismo
  `idHostname`).

### Integration Tests

- `EsquemaRepositoryIntegrationTest.php` — mismos escenarios que
  `HostnameRepositoryIntegrationTest.php` para `obtenerEsquemas()` (activos, orden, exclusión de
  inactivos, catálogo vacío), más para `obtenerEsquemasPorHostname()`: hostname con asociaciones
  activas devuelve solo esas (ordenadas), hostname sin asociaciones devuelve `[]`, hostname
  inexistente devuelve `null`, asociaciones/esquemas inactivos se excluyen.
- `EsquemaOutAdapterIntegrationTest.php` — mismos escenarios que
  `HostnameOutAdapterIntegrationTest.php` para el mapeo `EsquemaModel → EsquemaVO`, más el mapeo
  correcto de `null`/`[]` a través de `obtenerEsquemasPorHostname()`.

### Feature/Contract Tests

- `ObtenerEsquemasApiTest.php` — mirror de `ObtenerHostnamesApiTest.php`: `GET /api/v1/admin/esquemas`
  devuelve 200 con los 16 esquemas sembrados en orden; catálogo vacío devuelve `data: []`,
  `success: true`; error 500 con DB no disponible.
- `ObtenerEsquemasPorHostnameApiTest.php`: `GET /api/v1/admin/hostnames/2/esquemas` (y 4, 7) 200
  con "Todos" + 16 esquemas; hostname sembrado sin asociaciones (p. ej. 1) 200 con solo "Todos";
  `idHostname` inexistente (p. ej. 999) 404 con `success: false`; error 500 con DB no disponible.

## Domain-Driven Design Compliance

### Ubiquitous Language

| Término | Español | Usado en | Justificación |
|---------|---------|----------|----------------|
| Esquema | "Esquema" | Code, API, DB, Tests, Docs | Término de dominio del spec, sin traducción |
| Todos | "Todos" | API response (sintético) | Término literal exigido por el spec/enriched story |
| Obtener | "Fetch/Get" | Use case names | Verbo de dominio consistente con el resto del contexto |

### Bounded Context

**Context**: Admin. **Related Contexts**: comparte con `Hostname`, `BaseDatos`, `AmbienteDesarrollo`,
Tipos (`Permiso`, `Personal`, `Requerimiento`).

### Aggregate Patterns

**N/A**: `Esquema` es un Value Object sin ciclo de vida. La relación Hostname-Esquema es un
concepto de infraestructura (tabla pivote), no una entidad de dominio ni un aggregate.

## Non-Functional Considerations

### Performance

- **Target**: < 200ms @ 50 req/s (SC-001).
- **Strategy**: Índices en `ind_activo` (ambas tablas nuevas) y en `id_nu_hostname` (pivot) para la
  ruta de consulta principal del endpoint anidado.

### Security

- **Public endpoints**: sin autenticación (FR-011), mismo criterio que el resto de catálogos
  `Admin`.
- **Input validation**: `idHostname` se castea a `int` por Laravel route binding; sin validación
  adicional de formato — un valor no numérico resulta en 404 por Laravel routing estándar
  (`{idHostname}` sin `where('idHostname', '[0-9]+')` explícito, pero el cast a `int` en la firma
  del método produce `0` para strings no numéricos, que naturalmente no existe como
  `id_nu_hostname` → 404).

## Out of Scope (reflejado del spec, no se implementa en esta historia)

1. CRUD de esquemas y de asociaciones — solo lectura, poblado por seed.
2. Persistencia de la selección del usuario en un formato de BD.
3. Endpoint inverso hostnames-por-esquema.
4. Validación de existencia/conectividad real de los esquemas contra la BD física del hostname.
5. Asociaciones para los 8 hostnames sembrados restantes (sin datos proporcionados).

## Glossary

- **Esquema**: Un esquema de base de datos (e.g., "ap_activemq_pd") al cual un trabajador de la
  DGTIC puede solicitar acceso, dentro de un hostname determinado.
- **Todos**: Entrada sintética (`id = 0`, `nombre = "Todos"`) que representa la solicitud de acceso
  a la totalidad de los esquemas de un hostname; nunca persistida.
- **Value Object**: Objeto de dominio inmutable cuya identidad está definida por sus atributos.
- **DTO**: Data Transfer Object — objeto para transferencia de datos entre capas.
