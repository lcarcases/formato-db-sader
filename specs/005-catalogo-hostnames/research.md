# Research: Catálogo de Hostnames

**Feature**: Catálogo de Hostnames
**Date**: 2026-08-22
**Status**: Completed

## Purpose

This document consolidates research findings for technical decisions required to implement the
"Catálogo de Hostnames" feature following hexagonal architecture and DDD principles. Given the
near-identical shape of the already-shipped "Catálogo de Bases de Datos" feature
(`specs/004-catalogo-bases-datos`), every decision below reuses that precedent's rationale
directly, adapted to hostnames/IPs. All decisions were closed during the enrichment stage (see
`userStory/enriched/2026-08-22-catalogo-de-hostnames-user-story.md` → "Closed decisions") — there
are no open unknowns for this planning phase.

## Research Areas

### 1. Esquema de Base de Datos PostgreSQL

**Decision**: Tabla `tb_cat_hostname` con estructura minimal, idéntica en forma a
`tb_cat_base_datos`.

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

**Rationale**:
- `id_nu_hostname`: Serial/autoincrement para identificador único (convención `id_nu_` para PKs
  numéricas).
- `sn_nombre`: VARCHAR(100) suficiente para nombres de servidor (e.g., `pgrprdbdsmz02`, 13
  caracteres) y direcciones IPv4 en notación decimal (e.g., `10.54.49.100`) con margen amplio
  (convención `sn_` para strings).
- `ind_activo`: SMALLINT (0/1) en lugar de soft deletes de Laravel, mismo criterio que
  bases-datos/ambientes.
- Constraint CHECK asegura que `ind_activo` solo puede ser 0 o 1.
- UNIQUE en `sn_nombre` previene duplicados (p. ej. dos filas con el mismo hostname o IP).
- Timestamps para auditoría.
- Índice en `ind_activo` para queries frecuentes.
- **Sin columna de tipo (hostname vs IP)**: por decisión cerrada del spec, ambos valores conviven
  como cadenas planas equivalentes en `sn_nombre`, sin columna adicional ni agrupación.

**Seed Data**:
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

**Alternatives considered**:
- Columna de tipo (`ind_tipo` o `sn_tipo`: "hostname" / "ip"): Rechazado — decisión cerrada del
  spec (FR-006), no hay distinción estructural en esta historia.
- Validación de formato (regex de hostname RFC 1123 / regex de IPv4): Rechazado — el spec excluye
  explícitamente validación de formato; los valores se aceptan como cadenas planas, igual que
  `BaseDatosVO`.
- Normalizar a mayúsculas (como se hizo con `tb_cat_base_datos`): Rechazado explícitamente en el
  enriquecimiento — son identificadores técnicos reales (hostnames en minúsculas, IPs en notación
  decimal) cuya forma ya es consistente y no debe alterarse.
- Usar `deleted_at` (soft deletes de Laravel): Rechazado, el proyecto requiere `ind_activo` como
  convención (mismo criterio que 004/003).
- Nombres de columnas sin prefijo: Rechazado, se debe seguir convención de tablas existentes
  (`id_nu_`, `sn_`).

### 2. Migrations Strategy

**Decision**: 2 migrations separadas (schema + seed), mismo patrón que 004 — migración de *seed*
explícita, no un `Seeder` de clase (`database/seeders/`).

**Files**:
1. `2026_08_22_000001_create_tb_cat_hostname_table.php` - Schema
2. `2026_08_22_000002_seed_tb_cat_hostname_table.php` - Seed data

**Rationale**:
- Separar schema de datos permite rollback independiente.
- Seed migration facilita tener datos iniciales en todos los ambientes de despliegue sin paso
  manual adicional — mismo mecanismo verificado en
  `database/migrations/2026_08_07_000002_seed_tb_cat_base_datos_table.php`.
- Naming convention con fecha garantiza orden de ejecución.

**Alternatives considered**:
- Single migration con schema + seed: Rechazado por falta de granularidad en rollback.
- Seeder class (`database/seeders/`): Rechazado porque los 11 valores iniciales deben estar
  siempre presentes en cualquier entorno donde se corran migrations, sin paso manual adicional
  (decisión cerrada, ver "Closed decisions" en el user story enriquecido).

### 3. Repository Pattern con Eloquent

**Decision**: Interface en Application, Repository en Infrastructure, OutAdapter delega al
Repository — mismo patrón verificado en código para `BaseDatosOutPort` /
`BaseDatosRepository` / `BaseDatosOutAdapter`.

**Rationale**:
- Port (interface) en `Application/Ports/Out/HostnameOutPort.php`.
- Repository en `Infrastructure/Adapters/Out/PostgresSQL/Repositories/HostnameRepository.php`.
- Adapter en `Infrastructure/Adapters/Out/PostgresSQL/HostnameOutAdapter.php`.
- Eloquent Model en `Infrastructure/Adapters/Out/PostgresSQL/Models/HostnameModel.php`.
- Binding en `AdminServiceProvider::register()` conecta `HostnameOutPort` con
  `HostnameOutAdapter`, agregado al mismo bloque donde ya viven los bindings de `BaseDatosOutPort`,
  `AmbienteDesarrolloOutPort`, etc. (verificado en
  `app/Core/Admin/Infrastructure/Providers/AdminServiceProvider.php`).
- UseCase depende solo de la interface (dependency inversion).
- OutAdapter delega al Repository para separación de concerns.

**Alternatives considered**:
- Query Builder directo en el use case: Rechazado por no seguir hexagonal architecture.
- Repository en Domain: Rechazado, las interfaces de repositorio van en `Application/Ports/Out`.

### 4. Value Objects Inmutables en PHP 8.4

**Decision**: Usar `readonly` properties (PHP 8.1+) con validación en constructor — mismo patrón
que `BaseDatosVO`, sin validación adicional de formato hostname/IP.

**Rationale**:
- PHP 8.4 mantiene y mejora el soporte de `readonly` properties.
- Constructor valida invariantes del dominio: `id > 0`, `nombre` no vacío tras `trim()` —
  exactamente las mismas dos reglas que `BaseDatosVO`, sin regex de formato de hostname ni de IP
  (decisión cerrada explícita: "no se agrega validación de formato hostname/IP (regex)").
- Named constructor `fromArray()` para creación desde arrays.
- `toArray()` para serialización JSON.

**Alternatives considered**:
- Regex de validación de formato (RFC 1123 para hostnames, notación decimal con puntos para
  IPv4): Rechazado explícitamente — mismo nivel de validación minimalista ya establecido en los
  catálogos existentes; no aporta valor de negocio en esta historia (no hay verificación de
  DNS/conectividad).
- Private properties con getters: Rechazado por ser más verboso sin beneficios adicionales en
  PHP 8.4.
- Array asociativo: Rechazado por carecer de type safety y validación de invariantes.

### 5. Testing de Endpoints Públicos

**Decision**: Unit test del use case con mock + Feature/contract test del endpoint sin
autenticación — mismo patrón y **mismas ubicaciones reales** verificadas en el árbol del repo para
`ObtenerBasesDatosUseCaseTest.php` y `ObtenerBasesDatosApiTest.php` (no las rutas descritas
originalmente, con ligeras imprecisiones, en `specs/004-catalogo-bases-datos/plan.md`).

**Test coverage strategy**:
1. **Unit**: `ObtenerHostnamesUseCaseTest.php` — Use case con `HostnameOutPort` mock.
   Ubicación real verificada: `tests/Unit/Core/Admin/Application/UseCases/`.
2. **Integration**: `HostnameRepositoryIntegrationTest.php` — Repository con base de datos real.
   Ubicación real verificada: `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/Repositories/`.
3. **Integration**: `HostnameOutAdapterIntegrationTest.php` — OutAdapter con Repository mock.
   Ubicación real verificada: `tests/Integration/Core/Admin/Infrastructure/Adapters/Out/PostgresSQL/`.
4. **Feature/Contract**: `ObtenerHostnamesApiTest.php` — Endpoint completo E2E, incluyendo caso de
   catálogo vacío (11→0) y caso de error 500. Ubicación real verificada:
   `tests/Feature/Core/Admin/Api/`.

**Alternatives considered**:
- Solo feature tests: Rechazado por no aislar capas y dificultar debugging.
- Ubicar tests en `tests/Feature/Api/` o `tests/Integration/Infrastructure/...` (rutas "planas"
  usadas por el precedente 003/Ambientes antes de que se introdujera el prefijo `Core/Admin/`):
  Rechazado — el precedente 004 (más reciente) ya migró a rutas namespaced por bounded context
  (`tests/.../Core/Admin/...`), verificado directamente en el filesystem del repo; esta feature
  sigue esa convención más reciente.

### 6. JSON API Response Format

**Decision**: El `ObtenerHostnamesInAdapter` construye la respuesta JSON directamente con
`response()->json([...])`, replicando línea por línea el patrón verificado en
`ObtenerBasesDatosInAdapter` — NO se instancia ninguna de las dos clases `Respuesta` compartidas
(`App\Core\Shared\Infraestructure\Respuesta` ni `App\Core\Shared\Infrastructure\Respuesta`).

```json
{
  "data": [...],
  "message": "...",
  "code": "200",
  "success": true
}
```

**Rationale**:
- Consistencia con el catálogo más recientemente implementado y estructuralmente más cercano
  (`bases-datos`).
- Decisión cerrada explícita en el user story enriquecido: replicar el patrón inline de
  `ObtenerBasesDatosInAdapter` en lugar de usar las clases `Respuesta` compartidas.

**Alternatives considered**:
- Usar `App\Core\Shared\Infraestructure\Respuesta` (envelope fijo `{success, message, data}`,
  status 200/500 únicamente): Rechazado — decisión cerrada, no es el patrón usado por el
  precedente directo (`bases-datos`).
- Usar `App\Core\Shared\Infrastructure\Respuesta` (envelope enriquecido con mapeo de excepciones):
  Rechazado por el mismo motivo — este catálogo replica el InAdapter inline, no las clases
  `Respuesta`.
- JSON:API spec: Rechazado por no alinearse con estándar ya establecido en el proyecto.

## Summary of Decisions

| Area | Decision | Rationale |
|------|----------|--------|
| **Database** | PostgreSQL tabla `tb_cat_hostname` | Source of truth, 11 registros estáticos |
| **Migrations** | 2 files (schema + seed) | Rollback granular, datos siempre presentes |
| **ind_activo** | SMALLINT 0/1 con CHECK constraint | Convención del proyecto vs soft deletes |
| **hostname vs IP** | Sin columna de tipo, sin agrupación | Fuera de alcance por decisión cerrada del spec |
| **Normalización** | Sin normalización (a diferencia de `base_datos`) | Identificadores técnicos reales, forma ya consistente |
| **Repository** | Interface en Application, Eloquent en Infrastructure | Hexagonal architecture, DIP |
| **Value Object** | `readonly` class con validación minimalista (`HostnameVO`) | Inmutabilidad garantizada, PHP 8.4, sin regex de formato |
| **Testing** | 3 niveles (Unit/Integration/Feature), rutas namespaced por `Core/Admin` | Cobertura completa, capas aisladas, coherente con 004 real |
| **Response Format** | Construcción inline en InAdapter (`response()->json`) | Igual que `ObtenerBasesDatosInAdapter`, no clases `Respuesta` compartidas |

## Open Questions

No hay preguntas abiertas. Todas las decisiones técnicas están resueltas y listas para
implementación (heredadas del spec ya clarificado y del patrón 004 ya validado en producción).

## Next Steps

Proceder a **Phase 1: Design Artifacts** para crear:
1. `data-model.md` — Modelo de datos del Value Object `HostnameVO`
2. `contracts/hostnames-api.md` — Contrato detallado del endpoint
3. `quickstart.md` — Guía rápida de desarrollo y testing
