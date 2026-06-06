# Feature Specification: Obtener Catálogo de Tipos de Permiso

**Feature Branch**: `002-catalogo-tipos-permiso`  
**Created**: 2026-05-31  
**Status**: Draft  
**Input**: User description: "caso de uso que permite obtener el catalogo de permisos que puede tener un usuario para interactúar con una BD"

## Clarifications

### Session 2026-05-31

- Q: Database Column Naming Convention - FR-006 specifies database columns as `id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`, `sn_descripcion`, but everywhere else in the spec (entity definition, acceptance criteria, assumptions) uses `id`, `nombre`, `activo`, `descripcion`. Which naming convention should be used for the database table columns? → A: Use prefixed columns: `id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`, `sn_descripcion` (as currently in FR-006)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consultar catálogo de tipos de permiso (Priority: P1)

Como trabajador de la DGTIC en la SADER, quiero poder consultar vía API el catálogo de tipos de permiso disponibles (Consulta, Cambios, Eliminación, Consulta y Cambios), para poder seleccionar el tipo de permiso que solicitaré al llenar el formato de solicitud de acceso a base de datos.

**Why this priority**: Este es el único flujo crítico de la feature - sin el catálogo, los usuarios no pueden completar el formato de solicitud. Debe ser la primera entrega.

**Independent Test**: Se puede probar completamente haciendo una petición GET al endpoint y verificando que retorna los 4 tipos de permiso en el formato esperado. No depende de ninguna otra funcionalidad.

**Acceptance Scenarios**:

1. **Given** el sistema tiene configurados 4 tipos de permiso activos en la base de datos, **When** un trabajador consulta el endpoint `/api/v1/admin/tipos-permiso`, **Then** el sistema retorna HTTP 200 con los 4 tipos en formato JSON con estructura `{success, message, code, data}` donde data contiene `[{id, nombre}]`

2. **Given** el sistema está operativo, **When** se hace la consulta al endpoint, **Then** los tipos de permiso retornados están ordenados por `id` ascendente

3. **Given** existen tipos de permiso con `activo = false`, **When** se consulta el endpoint, **Then** el sistema solo retorna aquellos con `activo = true`

4. **Given** todos los tipos de permiso tienen `activo = false`, **When** se consulta el endpoint, **Then** el sistema retorna HTTP 200 con `data: []` y mensaje "No hay tipos de permiso activos"

### Edge Cases

- ¿Qué sucede cuando la base de datos no responde? → Sistema retorna HTTP 500 con estructura de error estándar y registra el error en logs con nivel ERROR
- ¿Qué sucede cuando se excede el rate limit de 60 solicitudes/minuto? → Sistema retorna HTTP 429 (Too Many Requests)
- ¿Qué sucede si no existen tipos de permiso en la tabla? → Sistema retorna HTTP 200 con `data: []` (array vacío) y mensaje apropiado
- ¿Qué sucede si hay timeout en la consulta? → Sistema retorna HTTP 500 después del timeout configurado

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Sistema DEBE exponer un endpoint REST `GET /api/v1/admin/tipos-permiso` que retorne el catálogo de tipos de permiso activos
- **FR-002**: Sistema DEBE retornar solo los tipos de permiso con campo `ind_activo = true` en base de datos (expuesto como `activo` en el modelo de dominio)
- **FR-003**: Sistema DEBE ordenar los resultados por campo `id_nu_tipo_permiso` en base de datos (expuesto como `id` en el API response) en orden ascendente
- **FR-004**: Sistema DEBE responder en formato JSON con estructura estándar `{success, message, code, data}`
- **FR-005**: Sistema DEBE incluir para cada tipo de permiso exactamente los campos `id` (entero) y `nombre` (cadena)
- **FR-006**: Sistema DEBE crear tabla `tb_cat_tipo_permiso` con campos: `id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`, y opcionalmente `sn_descripcion`, `created_at`, `updated_at`
- **FR-007**: Sistema DEBE sembrar 4 tipos de permiso iniciales: "Consulta", "Cambios", "Eliminación", "Consulta y Cambios" (todos con `ind_activo = true`)
- **FR-008**: Sistema DEBE aplicar rate limiting de 60 solicitudes por minuto por IP
- **FR-009**: Sistema DEBE registrar cada petición en logs estructurados con formato JSON incluyendo: request_id, action, result, user_ip, duration_ms
- **FR-010**: Sistema DEBE responder con HTTP 500 y estructura de error cuando falle la conexión a base de datos
- **FR-011**: Endpoint DEBE ser público (sin requerir autenticación)
- **FR-012**: Sistema DEBE retornar HTTP 200 con `data: []` cuando no existan tipos activos (no es un error)

### Key Entities

- **TipoPermiso**: Representa un tipo de permiso que puede ser asignado a un usuario para interactuar con una base de datos. 
  - Atributos de dominio: `id` (identificador único), `nombre` (nombre del tipo de permiso), `activo` (indica si está disponible para selección)
  - Mapeo a base de datos: `id` → `id_nu_tipo_permiso`, `nombre` → `ln_nombre`, `activo` → `ind_activo`, `descripcion` → `sn_descripcion`
  - Comportamiento: Solo los tipos activos deben ser consultables/seleccionables
  - Invariante: El nombre debe ser único y no vacío
  - Diseño: Entity con identity (id), parte del bounded context Admin
  - Nota: La capa de dominio usa nombres limpios; el adapter de persistencia realiza el mapeo a las columnas prefijadas de la base de datos

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Endpoint `/api/v1/admin/tipos-permiso` responde en menos de 200ms en el percentil 95 de las peticiones
- **SC-002**: Sistema retorna exactamente los 4 tipos de permiso activos en el formato especificado `{success, message, code, data: [{id, nombre}]}`
- **SC-003**: Tests unitarios, de integración y de contrato pasan al 100% (cobertura mínima 80% en capa de aplicación)
- **SC-004**: PHPStan nivel 9 ejecuta sin errores en el código generado
- **SC-005**: PSR-12 (Pint) ejecuta sin violaciones en el código generado
- **SC-006**: Sistema maneja correctamente el caso borde de catálogo vacío (retorna HTTP 200 con data vacío)
- **SC-007**: Sistema aplica rate limiting correctamente (retorna HTTP 429 después de 60 solicitudes/minuto)
- **SC-008**: Migraciones de base de datos ejecutan exitosamente en PostgreSQL sin errores
- **SC-009**: Arquitectura hexagonal es respetada: Domain (sin dependencias de framework), Application (casos de uso, DTOs, ports), Infrastructure (adapters, repositories)
- **SC-010**: Logs estructurados se generan correctamente para cada petición con todos los campos requeridos

## Assumptions

- Se asume que el catálogo de tipos de permiso es relativamente estático y no cambia frecuentemente (no se requiere cache invalidation complejo)
- Se asume que la selección de tipo de permiso es única por solicitud (no se permite selección múltiple)
- Se asume reutilización del patrón arquitectónico existente de `TipoPersonal` y `TipoRequerimiento` dentro del bounded context `Admin`
- Se asume que PostgreSQL es la única fuente de verdad para este catálogo (no hay sincronización con sistemas externos)
- Se asume que el rate limiting se aplica por IP (no por usuario autenticado, dado que el endpoint es público)
- Se asume que los 4 tipos de permiso son suficientes y no se anticipan nuevos tipos en el corto plazo
- Se asume que el frontend/cliente manejará la presentación del selector (esta feature solo expone los datos)
- Se asume que la estructura de respuesta `{success, message, code, data}` es el estándar del proyecto para todos los endpoints
- Se asume infraestructura existente de logging estructurado en el proyecto (no se requiere implementar infraestructura nueva)
- Se asume que no hay lógica de negocio que restrinja qué tipos de permiso están disponibles según el contexto (todos los activos siempre se exponen)
- Se asume que la capa de dominio usa nombres de atributos limpios (`id`, `nombre`, `activo`) mientras que el adapter de persistencia (Eloquent model / repository) mapea estas propiedades a las columnas prefijadas de la base de datos (`id_nu_tipo_permiso`, `ln_nombre`, `ind_activo`)
