# Research: Catálogo de Bases de Datos

**Feature**: Catálogo de Bases de Datos
**Date**: 2026-08-07
**Status**: Completed

## Purpose

This document consolidates research findings for technical decisions required to implement the "Catálogo de Bases de Datos" feature following hexagonal architecture and DDD principles. Given the near-identical shape of the already-shipped "Catálogo de Ambientes de Desarrollo" feature (`specs/003-catalogo-ambientes-desarrollo`), most decisions here reuse that precedent's rationale directly.

## Research Areas

### 1. Esquema de Base de Datos PostgreSQL

**Decision**: Tabla `tb_cat_base_datos` con estructura minimal, idéntica en forma a `tb_cat_ambiente_desarrollo`.

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

**Rationale**:
- `id_nu_base_datos`: Serial/autoincrement para identificador único (convención `id_nu_` para PKs numéricas).
- `sn_nombre`: VARCHAR(100) suficiente para nombres/códigos de bases de datos (convención `sn_` para strings).
- `ind_activo`: SMALLINT (0/1) en lugar de soft deletes de Laravel, mismo criterio que ambientes.
- Constraint CHECK asegura que `ind_activo` solo puede ser 0 o 1.
- UNIQUE en `sn_nombre` previene duplicados (p. ej. dos filas "PPB").
- Timestamps para auditoría.
- Índice en `ind_activo` para queries frecuentes.
- **No columna adicional para "OTROS"**: por alcance cerrado del spec, "OTROS" es una fila más del catálogo (`id` + `nombre`), sin campo de texto libre. No se agrega ninguna columna para capturar un nombre de base de datos no listada.

**Seed Data**:
```sql
INSERT INTO tb_cat_base_datos (sn_nombre, ind_activo) VALUES
    ('PPB', 1),
    ('SURI', 1),
    ('XAMAN', 1),
    ('OTROS', 1);
```

**Alternatives considered**:
- Columna de texto libre para "OTROS" (`sn_detalle_otro`, nullable): Rechazado — fuera de alcance según `spec.md`, se dejará para una historia futura de integración con el formulario.
- Usar `deleted_at` (soft deletes de Laravel): Rechazado, el proyecto requiere `ind_activo` como convención (mismo criterio que 003).
- Nombres de columnas sin prefijo: Rechazado, se debe seguir convención de tablas existentes (`id_nu_`, `sn_`).

### 2. Migrations Strategy

**Decision**: 2 migrations separadas (schema + seed), mismo patrón que 003.

**Files**:
1. `2026_08_07_000001_create_tb_cat_base_datos_table.php` - Schema
2. `2026_08_07_000002_seed_tb_cat_base_datos_table.php` - Seed data

**Rationale**:
- Separar schema de datos permite rollback independiente.
- Seed migration facilita tener datos iniciales en todos los ambientes de despliegue.
- Naming convention con fecha garantiza orden de ejecución.

**Alternatives considered**:
- Single migration con schema + seed: Rechazado por falta de granularidad en rollback.
- Seeder class (`database/seeders/`): Rechazado porque datos iniciales deben estar siempre presentes en cualquier entorno donde se corran migrations, sin paso manual adicional.

### 3. Repository Pattern con Eloquent

**Decision**: Interface en Application, Repository en Infrastructure, OutAdapter delega al Repository — mismo patrón que `AmbienteDesarrolloOutPort` / `AmbienteDesarrolloRepository` / `AmbienteDesarrolloOutAdapter`.

**Rationale**:
- Port (interface) en `Application/Ports/Out/BaseDatosOutPort.php`.
- Repository en `Infrastructure/Adapters/Out/PostgresSQL/Repositories/BaseDatosRepository.php`.
- Adapter en `Infrastructure/Adapters/Out/PostgresSQL/BaseDatosOutAdapter.php`.
- Eloquent Model en `Infrastructure/Adapters/Out/PostgresSQL/Models/BaseDatosModel.php`.
- Binding en `AdminServiceProvider::register()` conecta `BaseDatosOutPort` con `BaseDatosOutAdapter` (nota: el binding de Ambientes vive realmente en `AdminServiceProvider`, no en `AppServiceProvider` como decía por error el `plan.md` de 003 — se usa la ubicación real verificada en código).
- UseCase depende solo de la interface (dependency inversion).
- OutAdapter delega al Repository para separación de concerns.

**Alternatives considered**:
- Query Builder directo en el use case: Rechazado por no seguir hexagonal architecture.
- Repository en Domain: Rechazado, las interfaces de repositorio van en `Application/Ports/Out`.

### 4. Value Objects Inmutables en PHP 8.4

**Decision**: Usar `readonly` properties (PHP 8.1+) con validación en constructor — mismo patrón que `AmbienteVO`.

**Rationale**:
- PHP 8.4 mantiene y mejora el soporte de `readonly` properties.
- Constructor valida invariantes del dominio (`id > 0`, `nombre` no vacío).
- Named constructor `fromArray()` para creación desde arrays.
- `toArray()` para serialización JSON.

**Alternatives considered**:
- Private properties con getters: Rechazado por ser más verboso sin beneficios adicionales en PHP 8.4.
- Array asociativo: Rechazado por carecer de type safety y validación de invariantes.

### 5. Testing de Endpoints Públicos

**Decision**: Feature tests sin autenticación + Contract tests con schema validation — mismo patrón que `ObtenerAmbientesApiTest`.

**Test coverage strategy**:
1. **Unit**: `ObtenerBasesDatosUseCaseTest.php` - Use case con `BaseDatosOutPort` mock.
2. **Integration**: `BaseDatosRepositoryTest.php` - Repository con base de datos real.
3. **Integration**: `BaseDatosOutAdapterTest.php` - OutAdapter con Repository mock.
4. **Feature/Contract**: `ObtenerBasesDatosApiTest.php` - Endpoint completo E2E, incluyendo caso de catálogo vacío y caso de error 500.

**Alternatives considered**:
- Solo feature tests: Rechazado por no aislar capas y dificultar debugging.

### 6. JSON API Response Format

**Decision**: Usar formato estándar definido en constitución del proyecto y ya usado por `ObtenerAmbientesInAdapter`.

```json
{
  "data": [...],
  "message": "...",
  "code": "200",
  "success": true
}
```

**Rationale**:
- Consistencia con otros endpoints del sistema (`ambientes-desarrollo`, `tipos-permiso`, etc.).
- Facilita integración con clientes que ya consumen otros endpoints.

**Alternatives considered**:
- JSON:API spec: Rechazado por no alinearse con estándar ya establecido en el proyecto.

## Summary of Decisions

| Area | Decision | Rationale |
|------|----------|--------|
| **Database** | PostgreSQL tabla `tb_cat_base_datos` | Source of truth, 4 registros estáticos |
| **Migrations** | 2 files (schema + seed) | Rollback granular, datos siempre presentes |
| **ind_activo** | SMALLINT 0/1 con CHECK constraint | Convención del proyecto vs soft deletes |
| **"OTROS" handling** | Fila de catálogo fija, sin columna de texto libre | Fuera de alcance por decisión cerrada del spec |
| **Repository** | Interface en Application, Eloquent en Infrastructure | Hexagonal architecture, DIP |
| **Value Object** | `readonly` class con validación (`BaseDatosVO`) | Inmutabilidad garantizada, PHP 8.4 |
| **Testing** | 3 niveles (Unit/Integration/Feature) | Cobertura completa, capas aisladas |
| **Response Format** | Formato constitución | Consistencia con sistema existente |

## Open Questions

No hay preguntas abiertas. Todas las decisiones técnicas están resueltas y listas para implementación (heredadas del spec ya clarificado y del patrón 003 ya validado en producción).

## Next Steps

Proceder a **Phase 1: Design Artifacts** para crear:
1. `data-model.md` - Modelo de datos del Value Object `BaseDatosVO`
2. `contracts/bases-datos-api.md` - Contrato detallado del endpoint
3. `quickstart.md` - Guía rápida de desarrollo y testing
