# Quick Start Guide: Catálogo de Bases de Datos

**Feature**: Catálogo de Bases de Datos
**Date**: 2026-08-07
**Audience**: Desarrolladores implementando o probando esta funcionalidad

## Resumen en 30 Segundos

Endpoint público `GET /api/v1/admin/bases-datos` que retorna la lista de bases de datos disponibles (PPB, SURI, XAMAN, OTROS) desde PostgreSQL (`tb_cat_base_datos`). Implementado con Arquitectura Hexagonal, sin autenticación, con Repository Pattern — mismo patrón que `ambientes-desarrollo`.

## Prerequisitos

- PHP 8.4+
- Laravel 13.x
- Composer instalado
- Git checkout en branch `feature/004-catalogo-bases-datos`

## Estructura de Archivos (a crear)

```
app/Core/Admin/
├── Domain/ValueObjects/
│   └── BaseDatosVO.php                             # Value Object inmutable
├── Application/
│   ├── DTOs/Out/
│   │   └── ObtenerBasesDatosOutDto.php             # Output DTO (usado por InAdapter)
│   ├── Ports/Out/
│   │   └── BaseDatosOutPort.php                    # Port Out (interface)
│   └── UseCases/
│       └── ObtenerBasesDatosUseCase.php            # Use case (retorna array<BaseDatosVO>)
└── Infrastructure/Adapters/
    ├── In/Api/
    │   └── ObtenerBasesDatosInAdapter.php          # InAdapter REST
    └── Out/PostgresSQL/
        ├── Models/
        │   └── BaseDatosModel.php                  # Eloquent model
        ├── Repositories/
        │   └── BaseDatosRepository.php             # Repository (usa Model)
        └── BaseDatosOutAdapter.php                 # OutAdapter (usa Repository)

database/migrations/
├── 2026_08_07_000001_create_tb_cat_base_datos_table.php   # Schema
└── 2026_08_07_000002_seed_tb_cat_base_datos_table.php     # Seed data

tests/
├── Unit/Core/Admin/Application/UseCases/
│   └── ObtenerBasesDatosUseCaseTest.php            # Use case unit test
├── Integration/Infrastructure/Adapters/Out/PostgresSQL/
│   ├── Repositories/
│   │   └── BaseDatosRepositoryTest.php             # Repository integration test
│   └── BaseDatosOutAdapterTest.php                 # OutAdapter integration test
└── Feature/Api/
    └── ObtenerBasesDatosApiTest.php                # API contract test
```

**Archivos existentes a modificar** (no crear nuevos):
- `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php` — agregar binding `BaseDatosOutPort` → `BaseDatosOutAdapter`
- `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php` — agregar ruta `GET /bases-datos`

## Paso 1: Crear y Ejecutar Migrations

Crear migrations para la tabla y seed data:

```bash
php artisan make:migration create_tb_cat_base_datos_table
php artisan make:migration seed_tb_cat_base_datos_table
```

Editarlas según el esquema definido en `data-model.md` y ejecutar:

```bash
php artisan migrate
```

**Verificar**:

```bash
php artisan db:table tb_cat_base_datos
```

Deberías ver 4 registros: PPB, SURI, XAMAN, OTROS.

## Paso 2: Implementar con Agente Hexagonal

**IMPORTANTE**: La constitución del proyecto **REQUIERE** usar el agente especializado:

```bash
@hexagonal-architecture-specialist implement caso de uso para obtener catálogo de bases de datos
```

O alternativamente, si prefieres implementar manualmente, sigue estrictamente el diseño de `data-model.md`:
- **Domain**: Value Object `BaseDatosVO` (readonly, PHP puro, sin Laravel)
- **Application**: Port Out (interface), Use case (retorna array<BaseDatosVO>)
- **Infrastructure**:
  - InAdapter (crea OutDto, responde JSON)
  - OutAdapter (implementa OutPort)
  - Repository (acceso a datos con Eloquent)
  - Model (Eloquent)

Ver `.github/skills/arquitectura-hexagonal/SKILL.md` para detalles del patrón.

## Paso 3: Bind Repository en Service Provider

En `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`, agregar al método `register()` existente:

```php
use App\Core\Admin\Application\Ports\Out\BaseDatosOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\BaseDatosOutAdapter;

public function register(): void
{
    // ... bindings existentes (AmbienteDesarrolloOutPort, ITipoRequerimientoOutPort, ...) ...

    $this->app->bind(
        BaseDatosOutPort::class,
        BaseDatosOutAdapter::class
    );
}
```

## Paso 4: Registrar Ruta

En `app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php`, agregar dentro del grupo `api/v1/admin` existente:

```php
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerBasesDatosInAdapter;

// Bases de Datos
Route::get('/bases-datos', ObtenerBasesDatosInAdapter::class)
    ->name('api.admin.bases-datos.index');
```

## Paso 5: Testing

### 5.1 Unit Test (Use Case con Mock)

```bash
php artisan test --filter=ObtenerBasesDatosUseCaseTest
```

**Expectativa**: El use case retorna array<BaseDatosVO> correctamente con OutPort mock.

### 5.2 Integration Test (Repository con DB)

```bash
php artisan test --filter=BaseDatosRepositoryTest
```

**Expectativa**: El Repository lee correctamente desde `tb_cat_base_datos`, filtrando solo activos.

### 5.3 Integration Test (OutAdapter con Mock)

```bash
php artisan test --filter=BaseDatosOutAdapterTest
```

**Expectativa**: El OutAdapter delega correctamente al Repository.

### 5.4 Feature Test (API Contract)

```bash
php artisan test --filter=ObtenerBasesDatosApiTest
```

**Expectativa**: Endpoint retorna 200 con estructura JSON correcta, incluyendo casos de catálogo vacío y error 500.

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
curl -X GET "http://localhost:8000/api/v1/admin/bases-datos" \
  -H "Accept: application/json" | jq
```

**Respuesta esperada**:

```json
{
  "data": [
    {"id": 1, "nombre": "PPB"},
    {"id": 2, "nombre": "SURI"},
    {"id": 3, "nombre": "XAMAN"},
    {"id": 4, "nombre": "OTROS"}
  ],
  "message": "Bases de datos obtenidas exitosamente",
  "code": "200",
  "success": true
}
```

## Comandos Útiles

### Verificar rutas registradas

```bash
php artisan route:list --path=bases-datos
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
         │ GET /api/v1/admin/bases-datos
         ▼
┌───────────────────────────────┐
│ ObtenerBasesDatosInAdapter    │  ◄── Infrastructure/Adapters/In/Api
│ (HTTP Inbound Adapter)        │
└──────────┬────────────────────┘
           │ invokes
           ▼
┌───────────────────────────────┐
│ ObtenerBasesDatosUseCase      │  ◄── Application/UseCases
│ (Use Case)                    │
└──────────┬────────────────────┘
           │ depends on
           ▼
┌───────────────────────────────┐
│ BaseDatosOutPort              │  ◄── Application/Ports/Out (interface)
│ (Port Out)                    │
└──────────┬────────────────────┘
           │ implemented by
           ▼
┌───────────────────────────────┐
│ BaseDatosOutAdapter           │  ◄── Infrastructure/Out/PostgresSQL
│ (Outbound Adapter)            │
└──────────┬────────────────────┘
           │ delegates to
           ▼
┌───────────────────────────────┐
│ BaseDatosRepository           │  ◄── Infrastructure/Out/PostgresSQL/Repositories
│ (uses BaseDatosModel)         │
└──────────┬────────────────────┘
           │ reads from
           ▼
┌───────────────────────────────┐
│ tb_cat_base_datos             │  ◄── PostgreSQL database
│ (id_nu, sn_nombre, ind_activo)│
└──────────┬────────────────────┘
           │ maps to
           ▼
┌───────────────────────────────┐
│ BaseDatosVO (Value Object)    │  ◄── Domain/ValueObjects
│ {id, nombre}                  │
└──────────┬────────────────────┘
           │ returns as array
           ▼
┌───────────────────────────────┐
│ ObtenerBasesDatosOutDto       │  ◄── Application/DTOs/Out
│ (Output DTO)                  │
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

- [ ] **Ubiquitous Language**: "BaseDatosVO" usado consistentemente
- [ ] **Value Object**: `BaseDatosVO` no tiene identidad propia, es inmutable
- [ ] **Repository Pattern**: Abstracción en Application, implementación en Infrastructure
- [ ] **Bounded Context**: Admin context claramente identificado

### Checklist Testing

- [ ] Unit test para use case (con mock de repository)
- [ ] Integration test para repository adapter
- [ ] Feature/contract test para endpoint REST (incluye catálogo vacío y error 500)
- [ ] Tests ejecutables sin levantar servidor completo
- [ ] PHPStan sin errores nuevos

### Checklist Database

- [ ] Migration para schema de tabla `tb_cat_base_datos`
- [ ] Migration para seed data (PPB, SURI, XAMAN, OTROS)
- [ ] Campo `id_nu_base_datos` como PK (convención `id_nu_`)
- [ ] Campo `sn_nombre` para nombres (convención `sn_`)
- [ ] Campo `ind_activo` con CHECK constraint (0 o 1)
- [ ] Índice en `ind_activo` para queries
- [ ] UNIQUE constraint en `sn_nombre`
- [ ] Repository filtra solo registros con `ind_activo = 1`

## Troubleshooting

### Error: "Table 'tb_cat_base_datos' doesn't exist"

**Solución**: Ejecutar migrations: `php artisan migrate`

### Error: "Class BaseDatosRepository not found"

**Solución**: Verificar que el binding está registrado en `AdminServiceProvider::register()`.

### Endpoint retorna 404

**Solución**:
1. Verificar que la ruta está registrada: `php artisan route:list --path=bases-datos`
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
- **Contrato API**: [contracts/bases-datos-api.md](contracts/bases-datos-api.md)
- **Constitución del proyecto**: `../../.specify/memory/constitution.md`
- **Skill Arquitectura Hexagonal**: `.github/skills/arquitectura-hexagonal/SKILL.md`
- **Precedente directo**: `specs/003-catalogo-ambientes-desarrollo/` (feature ya implementada con el mismo patrón exacto)

## Próximos Pasos

1. **Generar tareas**: `/speckit-tasks`
2. **Implementar**: Usar agente `@hexagonal-architecture-specialist` (mandatorio per constitución)
3. **Probar**: Ejecutar los 3 niveles de tests (Unit, Integration, Feature)
4. **Validar**: Confirmar cumplimiento con checklist constitucional
5. **Code Review**: Solicitar revisión enfocada en arquitectura hexagonal
6. **Merge**: Una vez aprobado, merge a rama principal

## Contacto

Para preguntas sobre arquitectura hexagonal o DDD, consultar la constitución del proyecto o la skill `arquitectura-hexagonal`.
