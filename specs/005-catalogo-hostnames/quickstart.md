# Quick Start Guide: Catálogo de Hostnames

**Feature**: Catálogo de Hostnames
**Date**: 2026-08-22
**Audience**: Desarrolladores implementando o probando esta funcionalidad

## Resumen en 30 Segundos

Endpoint público `GET /api/v1/admin/hostnames` que retorna la lista de hostnames/direcciones IP
disponibles (11 valores: 7 hostnames de servidor + 4 IPs) desde PostgreSQL (`tb_cat_hostname`).
Implementado con Arquitectura Hexagonal, sin autenticación, con Repository Pattern — mismo patrón
que `bases-datos`.

## Prerequisitos

- PHP 8.4+
- Laravel 13.x
- Composer instalado
- Git checkout en branch `005-catalogo-hostnames`

## Estructura de Archivos (a crear)

```
app/Core/Admin/
├── Domain/ValueObjects/
│   └── HostnameVO.php                              # Value Object inmutable
├── Application/
│   ├── DTOs/Out/
│   │   ├── ObtenerHostnameOutDto.php               # Output DTO (item)
│   │   └── ObtenerHostnamesOutDto.php              # Output DTO (colección, usado por InAdapter)
│   ├── Ports/Out/
│   │   └── HostnameOutPort.php                     # Port Out (interface)
│   └── UseCases/
│       └── ObtenerHostnamesUseCase.php             # Use case (retorna array<HostnameVO>)
└── Infrastructure/Adapters/
    ├── In/Api/
    │   └── ObtenerHostnamesInAdapter.php           # InAdapter REST
    └── Out/PostgresSQL/
        ├── Models/
        │   └── HostnameModel.php                   # Eloquent model
        ├── Repositories/
        │   └── HostnameRepository.php              # Repository (usa Model)
        └── HostnameOutAdapter.php                  # OutAdapter (usa Repository)

database/migrations/
├── 2026_08_22_000001_create_tb_cat_hostname_table.php   # Schema
└── 2026_08_22_000002_seed_tb_cat_hostname_table.php     # Seed data

tests/
├── Unit/Core/Admin/Application/UseCases/
│   └── ObtenerHostnamesUseCaseTest.php             # Use case unit test
├── Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/
│   ├── Repositories/
│   │   └── HostnameRepositoryIntegrationTest.php   # Repository integration test
│   └── HostnameOutAdapterIntegrationTest.php        # OutAdapter integration test
└── Feature/Core/Admin/Api/
    └── ObtenerHostnamesApiTest.php                 # API contract test
```

**Archivos existentes a modificar** (no crear nuevos):
- `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php` — agregar binding
  `HostnameOutPort` → `HostnameOutAdapter`
- `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` — agregar ruta `GET /hostnames`

## Paso 1: Crear y Ejecutar Migrations

Crear migrations para la tabla y seed data:

```bash
php artisan make:migration create_tb_cat_hostname_table
php artisan make:migration seed_tb_cat_hostname_table
```

Editarlas según el esquema definido en `data-model.md` y ejecutar:

```bash
php artisan migrate
```

**Verificar**:

```bash
php artisan db:table tb_cat_hostname
```

Deberías ver 11 registros, en este orden: `pgrdesbds09`, `sridesbds09`, `pgrprdbdsmz02`,
`sriprdbdsmz02`, `divprdbds01`, `pgrqabds08`, `sriqabds08`, `10.1.35.50`, `10.1.21.95`,
`10.1.20.25`, `10.54.49.100`.

## Paso 2: Implementar con Agente Hexagonal

**IMPORTANTE**: La constitución del proyecto **REQUIERE** usar el agente especializado:

```bash
@hexagonal-architecture-specialist implement caso de uso para obtener catálogo de hostnames
```

O alternativamente, si prefieres implementar manualmente, sigue estrictamente el diseño de
`data-model.md`:
- **Domain**: Value Object `HostnameVO` (readonly, PHP puro, sin Laravel)
- **Application**: Port Out (interface), Use case (retorna array<HostnameVO>)
- **Infrastructure**:
  - InAdapter (crea OutDto, responde JSON inline con `response()->json`)
  - OutAdapter (implementa OutPort)
  - Repository (acceso a datos con Eloquent)
  - Model (Eloquent)

Ver `.github/skills/arquitectura-hexagonal/SKILL.md` para detalles del patrón.

## Paso 3: Bind Repository en Service Provider

En `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`, agregar al método
`register()` existente:

```php
use App\Core\Admin\Application\Ports\Out\HostnameOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\HostnameOutAdapter;

public function register(): void
{
    // ... bindings existentes (BaseDatosOutPort, AmbienteDesarrolloOutPort, ...) ...

    $this->app->bind(
        HostnameOutPort::class,
        HostnameOutAdapter::class
    );
}
```

## Paso 4: Registrar Ruta

En `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php`, agregar dentro del grupo
`api/v1/admin` existente, junto a la ruta de `bases-datos`:

```php
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerHostnamesInAdapter;

// Hostnames
Route::get('/hostnames', ObtenerHostnamesInAdapter::class)
    ->name('api.admin.hostnames.index');
```

## Paso 5: Testing

### 5.1 Unit Test (Use Case con Mock)

```bash
php artisan test --filter=ObtenerHostnamesUseCaseTest
```

**Expectativa**: El use case retorna `array<HostnameVO>` correctamente con OutPort mock.

### 5.2 Integration Test (Repository con DB)

```bash
php artisan test --filter=HostnameRepositoryIntegrationTest
```

**Expectativa**: El Repository lee correctamente desde `tb_cat_hostname`, filtrando solo activos.

### 5.3 Integration Test (OutAdapter con Mock)

```bash
php artisan test --filter=HostnameOutAdapterIntegrationTest
```

**Expectativa**: El OutAdapter delega correctamente al Repository.

### 5.4 Feature Test (API Contract)

```bash
php artisan test --filter=ObtenerHostnamesApiTest
```

**Expectativa**: Endpoint retorna 200 con estructura JSON correcta, incluyendo los 11 valores
sembrados, el caso de catálogo vacío y el caso de error 500.

### 5.5 Todos los Tests

```bash
php artisan test
```

## Paso 6: Probar Manualmente

### Iniciar servidor

```bash
php artisan serve
```

### Hacer petición

```bash
curl -X GET "http://localhost:8000/api/v1/admin/hostnames" \
  -H "Accept: application/json" | jq
```

**Respuesta esperada**:

```json
{
  "data": [
    { "id": 1, "nombre": "pgrdesbds09" },
    { "id": 2, "nombre": "sridesbds09" },
    { "id": 3, "nombre": "pgrprdbdsmz02" },
    { "id": 4, "nombre": "sriprdbdsmz02" },
    { "id": 5, "nombre": "divprdbds01" },
    { "id": 6, "nombre": "pgrqabds08" },
    { "id": 7, "nombre": "sriqabds08" },
    { "id": 8, "nombre": "10.1.35.50" },
    { "id": 9, "nombre": "10.1.21.95" },
    { "id": 10, "nombre": "10.1.20.25" },
    { "id": 11, "nombre": "10.54.49.100" }
  ],
  "message": "Hostnames obtenidos exitosamente",
  "code": "200",
  "success": true
}
```

## Comandos Útiles

### Verificar rutas registradas

```bash
php artisan route:list --path=hostnames
```

### Ejecutar análisis estático (PHPStan)

```bash
./vendor/bin/phpstan analyse app/Core/Admin
```

### Ejecutar formateador de código (Pint)

```bash
./vendor/bin/pint app/Core/Admin
```

## Diagrama de Flujo

```
┌─────────────────┐
│   HTTP Client   │
└────────┬────────┘
         │ GET /api/v1/admin/hostnames
         ▼
┌───────────────────────────────┐
│ ObtenerHostnamesInAdapter     │  ◄── Infrastructure/Adapters/In/Api
│ (HTTP Inbound Adapter)        │
└──────────┬────────────────────┘
           │ invokes
           ▼
┌───────────────────────────────┐
│ ObtenerHostnamesUseCase       │  ◄── Application/UseCases
│ (Use Case)                    │
└──────────┬────────────────────┘
           │ depends on
           ▼
┌───────────────────────────────┐
│ HostnameOutPort               │  ◄── Application/Ports/Out (interface)
│ (Port Out)                    │
└──────────┬────────────────────┘
           │ implemented by
           ▼
┌───────────────────────────────┐
│ HostnameOutAdapter            │  ◄── Infrastructure/Out/PostgresSQL
│ (Outbound Adapter)            │
└──────────┬────────────────────┘
           │ delegates to
           ▼
┌───────────────────────────────┐
│ HostnameRepository             │  ◄── Infrastructure/Out/PostgresSQL/Repositories
│ (uses HostnameModel)          │
└──────────┬────────────────────┘
           │ reads from
           ▼
┌───────────────────────────────┐
│ tb_cat_hostname               │  ◄── PostgreSQL database
│ (id_nu, sn_nombre, ind_activo)│
└──────────┬────────────────────┘
           │ maps to
           ▼
┌───────────────────────────────┐
│ HostnameVO (Value Object)     │  ◄── Domain/ValueObjects
│ {id, nombre}                  │
└──────────┬────────────────────┘
           │ returns as array
           ▼
┌───────────────────────────────┐
│ ObtenerHostnamesOutDto        │  ◄── Application/DTOs/Out
│ (Output DTO, wraps items)     │
└───────────────────────────────┘
```

## Validación de Cumplimiento Constitucional

### Checklist Arquitectura Hexagonal

- [ ] **Domain** no tiene dependencias de Laravel (`use Illuminate\...`)
- [ ] **Ports** (interfaces) definidos en Application layer
- [ ] **Adapters** implementan ports y viven en Infrastructure
- [ ] **Use case** orquesta pero no contiene lógica de negocio
- [ ] **Value Object** es inmutable (`readonly`)
- [ ] **Repository** abstracción definida en Application, implementada en Infrastructure
- [ ] **DTOs** claramente definidos para input/output de use cases

### Checklist DDD

- [ ] **Ubiquitous Language**: "HostnameVO" usado consistentemente
- [ ] **Value Object**: `HostnameVO` no tiene identidad propia, es inmutable
- [ ] **Repository Pattern**: Abstracción en Application, implementación en Infrastructure
- [ ] **Bounded Context**: Admin context claramente identificado

### Checklist Testing

- [ ] Unit test para use case (con mock de repository)
- [ ] Integration test para repository adapter
- [ ] Integration test para OutAdapter (con mock de repository)
- [ ] Feature/contract test para endpoint REST (incluye 11 valores sembrados, catálogo vacío y
      error 500)
- [ ] Tests ejecutables sin levantar servidor completo
- [ ] PHPStan sin errores nuevos

### Checklist Database

- [ ] Migration para schema de tabla `tb_cat_hostname`
- [ ] Migration para seed data (11 hostnames/IPs, en el orden especificado)
- [ ] Campo `id_nu_hostname` como PK (convención `id_nu_`)
- [ ] Campo `sn_nombre` para nombres (convención `sn_`), sin normalización de mayúsculas
- [ ] Campo `ind_activo` con CHECK constraint (0 o 1)
- [ ] Índice en `ind_activo` para queries
- [ ] UNIQUE constraint en `sn_nombre`
- [ ] Repository filtra solo registros con `ind_activo = 1`
- [ ] Sin columna adicional para distinguir hostname de IP

## Troubleshooting

### Error: "Table 'tb_cat_hostname' doesn't exist"

**Solución**: Ejecutar migrations: `php artisan migrate`

### Error: "Class HostnameRepository not found"

**Solución**: Verificar que el binding está registrado en `AdminServiceProvider::register()`.

### Endpoint retorna 404

**Solución**:
1. Verificar que la ruta está registrada: `php artisan route:list --path=hostnames`
2. Confirmar que el InAdapter existe en la ruta correcta
3. Limpiar cache de rutas: `php artisan route:clear`

### Tests fallan: "Call to undefined method"

**Solución**:
1. Verificar namespaces correctos en imports
2. Ejecutar `composer dump-autoload`
3. Verificar que las clases existen en las rutas esperadas

## Recursos Adicionales

- **Spec completo**: [spec.md](spec.md)
- **Plan de implementación**: [plan.md](plan.md)
- **Modelo de datos**: [data-model.md](data-model.md)
- **Contrato API**: [contracts/hostnames-api.md](contracts/hostnames-api.md)
- **User story enriquecido**: `../../userStory/enriched/2026-08-22-catalogo-de-hostnames-user-story.md`
- **Constitución del proyecto**: `../../.specify/memory/constitution.md`
- **Skill Arquitectura Hexagonal**: `.github/skills/arquitectura-hexagonal/SKILL.md`
- **Precedente directo**: `specs/004-catalogo-bases-datos/` (feature ya implementada con el mismo
  patrón exacto, catálogo estructuralmente más cercano)

## Próximos Pasos

1. **Generar tareas**: `/speckit-tasks`
2. **Implementar**: Usar agente `@hexagonal-architecture-specialist` (mandatorio per constitución)
3. **Probar**: Ejecutar los 3 niveles de tests (Unit, Integration, Feature)
4. **Validar**: Confirmar cumplimiento con checklist constitucional
5. **Code Review**: Solicitar revisión enfocada en arquitectura hexagonal
6. **Merge**: Una vez aprobado, merge a rama principal

## Contacto

Para preguntas sobre arquitectura hexagonal o DDD, consultar la constitución del proyecto o la
skill `arquitectura-hexagonal`.
