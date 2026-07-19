# Quick Start Guide: Catálogo de Ambientes de Desarrollo

**Feature**: Catálogo de Ambientes de Desarrollo  
**Date**: 2026-06-28  
**Audience**: Desarrolladores implementando o probando esta funcionalidad

## Resumen en 30 Segundos

Endpoint público `GET /api/v1/admin/ambientes-desarrollo` que retorna la lista de ambientes desde PostgreSQL (`tb_cat_ambiente_desarrollo`). Implementado con Arquitectura Hexagonal, sin autenticación, con Repository Pattern.

## Prerequisitos

- PHP 8.4+
- Laravel 13.x
- Composer instalado
- Git checkout en branch `feature/003-catalogo-ambientes-desarrollo`

## Estructura de Archivos (a crear)

```
app/Core/Admin/
├── Domain/ValueObjects/
│   └── AmbienteVO.php                                  # Value Object inmutable
├── Application/
│   ├── DTOs/Out/
│   │   └── ObtenerAmbientesOutDto.php                  # Output DTO (usado por InAdapter)
│   ├── Ports/Out/
│   │   └── AmbienteDesarrolloOutPort.php               # Port Out (interface)
│   └── UseCases/
│       └── ObtenerAmbientesUseCase.php                 # Use case (retorna array<AmbienteVO>)
└── Infrastructure/Adapters/
    ├── In/Api/
    │   └── ObtenerAmbientesInAdapter.php               # InAdapter REST (crea OutDto)
    └── Out/PostgresSQL/
        ├── Models/
        │   └── AmbienteDesarrolloModel.php             # Eloquent model
        ├── Repositories/
        │   └── AmbienteDesarrolloRepository.php        # Repository (usa Model)
        └── AmbienteDesarrolloOutAdapter.php            # OutAdapter (usa Repository)

database/migrations/
├── 2026_06_28_000001_create_tb_cat_ambiente_desarrollo_table.php   # Schema
└── 2026_06_28_000002_seed_tb_cat_ambiente_desarrollo_table.php     # Seed data

routes/
└── api.php                                         # Route registration

tests/
├── Unit/Core/Admin/Application/UseCases/
│   └── ObtenerAmbientesUseCaseTest.php             # Use case unit test
├── Integration/Infrastructure/Adapters/Out/PostgresSQL/
│   ├── Repositories/
│   │   └── AmbienteDesarrolloRepositoryTest.php    # Repository integration test
│   └── AmbienteDesarrolloOutAdapterTest.php        # OutAdapter integration test
└── Feature/Api/
    └── ObtenerAmbientesApiTest.php                   # API contract test
```

## Paso 1: Crear y Ejecutar Migrations

Crear migrations para la tabla y seed data:

```bash
php artisan make:migration create_tb_cat_ambiente_desarrollo_table
php artisan make:migration seed_tb_cat_ambiente_desarrollo_table
```

Editarlas según el esquema definido en `data-model.md` y ejecutar:

```bash
php artisan migrate
```

**Verificar**:

```bash
php artisan db:table tb_cat_ambiente_desarrollo
```

Deberías ver 3 registros: Desarrollo, QA, Producción.

## Paso 2: Implementar con Agente Hexagonal

**IMPORTANTE**: La constitución del proyecto **REQUIERE** usar el agente especializado:

```bash
@hexagonal-architecture-specialist implement caso de uso para obtener catálogo de ambientes de desarrollo
```

O alternativamente, si prefieres implementar manualmente, sigue estrictamente:
- **Domain**: Value Object `AmbienteVO` (readonly, PHP puro, sin Laravel)
- **Application**: Port Out (interface), Use case (retorna array<AmbienteVO>)
- **Infrastructure**: 
  - InAdapter (Controller, crea OutDto)
  - OutAdapter (implementa OutPort)
  - Repository (acceso a datos con Eloquent)
  - Model (Eloquent)

Ver `.github/skills/arquitectura-hexagonal/SKILL.md` para detalles del patrón.

## Paso 3: Bind Repository en Service Provider

En `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`:

```php
use App\Core\Admin\Application\Ports\Out\AmbienteDesarrolloOutPort;
use App\Core\Admin\Infrastructure\Adapters\Out\PostgresSQL\AmbienteDesarrolloOutAdapter;

public function register(): void
{
    $this->app->bind(
        AmbienteDesarrolloOutPort::class,
        AmbienteDesarrolloOutAdapter::class
    );
}
```

## Paso 4: Registrar Ruta

En `routes/api.php`, agregar:

```php
use App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerAmbientesInAdapter;

Route::prefix('v1')->group(function () {
    Route::get('/ambientes-desarrollo', ObtenerAmbientesInAdapter::class)
        ->name('api.v1.ambientes-desarrollo.index');
});
```

## Paso 5: Testing

### 5.1 Unit Test (Use Case con Mock)

```bash
php artisan test --filter=ObtenerAmbientesUseCaseTest
```

**Expectativa**: El use case retorna array<AmbienteVO> correctamente con OutPort mock.

### 5.2 Integration Test (Repository con DB)

```bash
php artisan test --filter=AmbienteDesarrolloRepositoryTest
```

**Expectativa**: El Repository lee correctamente desde `tb_cat_ambiente_desarrollo`.

### 5.3 Integration Test (OutAdapter con Mock)

```bash
php artisan test --filter=AmbienteDesarrolloOutAdapterTest
```

**Expectativa**: El OutAdapter delega correctamente al Repository.

### 5.3 Feature Test (API Contract)

```bash
php artisan test --filter=ObtenerAmbientesApiTest
```

**Expectativa**: Endpoint retorna 200 con estructura JSON correcta.

### 5.4 Todos los Tests

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
curl -X GET "http://localhost:8000/api/v1/admin/ambientes-desarrollo" \
  -H "Accept: application/json" | jq
```

**Respuesta esperada**:

```json
{
  "data": [
    {"id": 1, "nombre": "Desarrollo"},
    {"id": 2, "nombre": "QA"},
    {"id": 3, "nombre": "Producción"}
  ],
  "message": "Ambientes obtenidos exitosamente",
  "code": "200",
  "success": true
}
```

## Comandos Útiles

### Verificar rutas registradas

```bash
php artisan route:list --path=ambientes
```

### Ejecutar análisis estático (PHPStan)

```bash
./vendor/bin/phpstan analyse app/Core/Admin
```

**Expectativa**: Level 9, cero errores.

### Ejecutar formateador de código (Pint)

```bash
./vendor/bin/pint app/Core/Admin
```

## Diagrama de Flujo

```
┌─────────────────┐
│   HTTP Client   │
└────────┬────────┘
         │ GET /api/v1/admin/ambientes-desarrollo
         ▼
┌───────────────────────────────┐
│ ObtenerAmbientesInAdapter     │  ◄── Infrastructure/Adapters/In/Api
│ (HTTP Inbound Adapter)        │
└──────────┬────────────────────┘
           │ invokes
           ▼
┌───────────────────────────────┐
│ ObtenerAmbientesUseCase       │  ◄── Application/UseCases
│ (Use Case)                    │
└──────────┬────────────────────┘
           │ depends on
           ▼
┌───────────────────────────────┐
│ AmbienteDesarrolloRepository  │  ◄── Application/Ports/Out (interface)
│ (Port Out)                    │
└──────────┬────────────────────┘
           │ implemented by
           ▼
┌───────────────────────────────┐
│ EloquentAmbiente...Repository │  ◄── Infrastructure/Out/PostgresSQL/Repositories
│ (Outbound Adapter)            │
└──────────┬────────────────────┘
           │ queries via
           ▼
┌───────────────────────────────┐
│ AmbienteDesarrolloModel       │  ◄── Infrastructure/Out/PostgresSQL/Models
│ (Eloquent ORM)                │
└──────────┬────────────────────┘
           │ reads from
           ▼
┌───────────────────────────────┐
│ tb_cat_ambiente_desarrollo    │  ◄── PostgreSQL database
│ (id_nu, sn_nombre, ind_activo)│
└──────────┬────────────────────┘
           │ maps to
           ▼
┌───────────────────────────────┐
│ AmbienteVO (Value Object)     │  ◄── Domain/ValueObjects
│ {id, nombre}                  │
└──────────┬────────────────────┘
           │ returns as array
           ▼
┌───────────────────────────────┐
│ ObtenerAmbientesOutDto        │  ◄── Application/DTOs/Out
│ (Output DTO)                  │
└───────────────────────────────┘
```
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

- [ ] **Ubiquitous Language**: "AmbienteVO" usado consistentemente
- [ ] **Value Object**: AmbienteVO no tiene identidad propia, es inmutable
- [ ] **Repository Pattern**: Abstracción en Application, implementación en Infrastructure
- [ ] **Bounded Context**: Admin context claramente identificado

### Checklist Testing

- [ ] Unit test para use case (con mock de repository)
- [ ] Integration test para repository adapter
- [ ] Feature/contract test para endpoint REST
- [ ] Tests ejecutables sin levantar servidor completo
- [ ] PHPStan Level 9 sin errores

### Checklist Database

- [ ] Migration para schema de tabla `tb_cat_ambiente_desarrollo`
- [ ] Migration para seed data (3 ambientes iniciales)
- [ ] Campo `id_nu_ambiente_desarrollo` como PK (convención id_nu_)
- [ ] Campo `sn_nombre` para nombres (convención sn_)
- [ ] Campo `ind_activo` con CHECK constraint (0 o 1)
- [ ] Índice en `ind_activo` para queries
- [ ] UNIQUE constraint en `sn_nombre`
- [ ] Repository filtra solo registros con `ind_activo = 1`

## Troubleshooting

### Error: "Table 'tb_cat_ambiente_desarrollo' doesn't exist"

**Solución**: Ejecutar migrations: `php artisan migrate`

### Error: "Class AmbienteDesarrolloRepository not found"

**Solución**: Verificar que el binding está registrado en `AdminServiceProvider::register()`.

### Endpoint retorna 404

**Solución**:
1. Verificar que la ruta está registrada: `php artisan route:list`
2. Confirmar que el controller existe en la ruta correcta
3. Limpiar cache de rutas: `php artisan route:clear`

### Tests fallan: "Call to undefined method"

**Solución**:
1. Verificar namespaces correctos en imports
2. Ejecutar `composer dump-autoload`
3. Verificar que las clases existen en las rutas esperadas

### PHPStan reporta "Class not found"

**Solución**:
1. Ejecutar `composer dump-autoload`
2. Verificar que `phpstan.neon` incluye los directorios correctos

## Recursos Adicionales

- **Spec completo**: [spec.md](spec.md)
- **Plan de implementación**: [plan.md](plan.md)
- **Modelo de datos**: [data-model.md](data-model.md)
- **Contrato API**: [contracts/ambientes-api.md](contracts/ambientes-api.md)
- **Constitución del proyecto**: `../../.specify/memory/constitution.md`
- **Skill Arquitectura Hexagonal**: `.github/skills/arquitectura-hexagonal/SKILL.md`

## Próximos Pasos

1. **Implementar**: Usar agente `@hexagonal-architecture-specialist`
2. **Probar**: Ejecutar los 3 niveles de tests (Unit, Integration, Feature)
3. **Validar**: Confirmar cumplimiento con checklist constitucional
4. **Documentar**: Actualizar README si es necesario
5. **Code Review**: Solicitar revisión enfocada en arquitectura hexagonal
6. **Merge**: Una vez aprobado, merge a rama principal

## Contacto

Para preguntas sobre arquitectura hexagonal o DDD, consultar la constitución del proyecto o la skill `arquitectura-hexagonal`.
