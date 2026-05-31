# Feature Specification: Obtener Catálogo de Tipos de Personal

**Feature Branch**: `001-catalogo-tipos-personal`  
**Created**: 2026-05-02  
**Status**: Draft  
**Input**: User description: "Dado que soy un trabajador de la DGTIC en la Sader, durante el proceso de llenado del formato de BD quiero poder seleccionar el tipo de personal al que pertenezco (Base, Enlace, Confianza y Externo)"

## Clarifications

### Session 2026-05-02

- Q: Rate limiting & API protection for public endpoint → A: Standard rate limiting (60 requests per minute per IP address)
- Q: Error response structure for consistent API contracts → A: Custom format: `{data, message, code, success}`
- Q: Log context fields for production observability → A: Standard web context (request_id, action, result, user_ip, timestamp, duration_ms)
- Q: CORS configuration for cross-origin frontend access → A: Allow all origins (*)
- Q: Database seeding strategy for initial catalog data → A: Database migration

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Consultar Catálogo de Tipos de Personal (Priority: P1)

Como trabajador de la DGTIC en SADER, quiero consultar el catálogo de tipos de personal disponibles (Base, Enlace, Confianza, Externo) a través de la API, para poder obtener las opciones válidas que necesito presentar en los formularios de solicitud de permisos de acceso a bases de datos.

**Why this priority**: Esta es la funcionalidad base que permite obtener los datos del catálogo. Sin este endpoint, no es posible presentar las opciones al usuario en ningún formulario. Es el MVP mínimo para habilitar la selección de tipo de personal.

**Independent Test**: Se puede probar completamente llamando al endpoint `GET /api/v1/admin/tipos-personal` y verificando que retorne los 4 tipos de personal en formato JSON con la estructura `{id, nombre}`. No requiere ninguna otra funcionalidad del sistema para funcionar.

**Acceptance Scenarios**:

1. **Given** el catálogo tiene los 4 tipos de personal activos (Base, Enlace, Confianza, Externo), **When** un cliente realiza GET /api/v1/admin/tipos-personal, **Then** la API retorna 200 OK con un array JSON conteniendo los 4 tipos con sus id y nombre
2. **Given** el catálogo está vacío o todos los tipos están inactivos, **When** un cliente realiza GET /api/v1/admin/tipos-personal, **Then** la API retorna 200 OK con un array vacío []
3. **Given** la base de datos PostgreSQL no está disponible, **When** un cliente realiza GET /api/v1/admin/tipos-personal, **Then** la API retorna 500 Internal Server Error con estructura `{success: false, message: "Database connection error", code: 500, data: null}`
4. **Given** un cliente ha excedido el límite de 60 solicitudes por minuto, **When** realiza otra solicitud GET /api/v1/admin/tipos-personal, **Then** la API retorna 429 Too Many Requests con header `Retry-After` indicando segundos hasta reset

---

### Edge Cases

- **¿Qué pasa cuando el catálogo está vacío?**: Retorna array vacío [] con status 200 OK. El frontend maneja el mensaje apropiado.
- **¿Qué pasa cuando la base de datos no responde?**: Retorna 500 Internal Server Error con estructura JSON: `{success: false, message: string, code: integer, data: null}`.
- **¿Qué pasa si hay tipos de personal inactivos?**: Solo se retornan los tipos con `activo = true`. Los inactivos no aparecen en la respuesta.
- **¿Cómo se manejan timeouts de consulta?**: Se retorna 500 después del timeout configurado y se registra en logs con nivel ERROR.
- **¿Qué pasa cuando se excede el rate limit?**: Retorna 429 Too Many Requests con header `Retry-After` indicando segundos hasta que se resetee el límite. Solicitudes subsecuentes dentro del período de bloqueo continúan retornando 429.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST exponer un endpoint REST API `GET /api/v1/admin/tipos-personal` que retorne el catálogo de tipos de personal activos
- **FR-002**: El sistema MUST retornar solo los tipos de personal con estado `activo = true`
- **FR-003**: El sistema MUST retornar los tipos de personal en formato JSON como array de objetos con estructura `{id: integer, nombre: string}` envuelto en respuesta exitosa: `{success: true, message: string, code: 200, data: [{id, nombre}]}`
- **FR-004**: El sistema MUST ordenar los tipos de personal por `id` ascendente
- **FR-005**: El sistema MUST retornar 200 OK cuando la consulta es exitosa, incluso si el catálogo está vacío
- **FR-006**: El sistema MUST retornar 500 Internal Server Error cuando ocurra un error de infraestructura (base de datos no disponible, timeout, etc.) con estructura JSON: `{success: false, message: string, code: integer, data: null}`
- **FR-007**: El sistema MUST persistir los tipos de personal en PostgreSQL en una tabla `tb_cat_tipo_personal` con campos: id, nombre, descripcion, activo, created_at, updated_at
- **FR-008**: El sistema MUST incluir 4 tipos de personal iniciales (Base, Enlace, Confianza, Externo) insertados mediante migration de base de datos, todos configurados con estado activo = true
- **FR-009**: El sistema MUST implementar siguiendo arquitectura hexagonal con separación Domain/Application/Infrastructure
- **FR-010**: El sistema MUST registrar logs estructurados con contexto completo en la capa de Infrastructure (InAdapter) para todas las operaciones: request_id (UUID único), action (nombre de la operación), result (success/error), user_ip (dirección IP del cliente), timestamp (ISO 8601), duration_ms (tiempo de respuesta en milisegundos). La capa Application (Use Cases) MUST permanecer libre de dependencias del framework (no logging).
- **FR-011**: The endpoint MUST be público (sin requerir autenticación)
- **FR-012**: El sistema MUST retornar Content-Type: application/json en todas las respuestas (ver FR-003 para estructura de respuesta)
- **FR-013**: El sistema MUST implementar rate limiting de 60 solicitudes por minuto por dirección IP usando middleware ThrottleRequests, retornando 429 Too Many Requests con header `Retry-After` cuando se exceda el límite
- **FR-014**: El sistema MUST configurar CORS (Cross-Origin Resource Sharing) permitiendo todas los orígenes (Access-Control-Allow-Origin: *) para máxima compatibilidad con clientes frontend

### Key Entities *(include if feature involves data)*

- **TipoPersonal (Domain Entity)**: Representa un tipo de personal válido en SADER (Base, Enlace, Confianza, Externo). 
  - **Atributos**: id (identificador único), nombre (nombre del tipo), descripcion (descripción del tipo), activo (indica si está disponible para selección)
  - **Comportamiento**: El entity debe validar que el nombre no esté vacío y que el estado activo sea booleano
  - **Invariante**: Un TipoPersonal solo puede ser retornado por la API si activo = true
  - **Repository**: Se accede a través de ITipoPersonalOutPort (repository interface) definida en Application layer

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El endpoint `/api/v1/admin/tipos-personal` responde en menos de 500ms en el percentil 95 para consultas exitosas
- **SC-002**: El endpoint retorna los 4 tipos de personal (Base, Enlace, Confianza, Externo) con la estructura JSON correcta `{id, nombre}`
- **SC-003**: 100% de las respuestas siguen el formato JSON definido en la especificación OpenAPI
- **SC-004**: El sistema maneja correctamente casos de error (BD no disponible) retornando 500 con estructura de error consistente
- **SC-005**: Los tests unitarios, de integración y de contrato pasan al 100% sin fallos
- **SC-006**: El código pasa PHPStan nivel 9 sin errores y cumple PSR-12 (verificado con Laravel Pint)
- **SC-007**: La arquitectura hexagonal es respetada: Domain layer sin dependencias de Laravel, Infrastructure implementa ports de Application

## Assumptions

### Scope Assumptions
- Se asume que los 4 tipos de personal (Base, Enlace, Confianza, Externo) son suficientes para las necesidades actuales de SADER y no requieren ser configurables por administradores en esta primera versión
- Se asume que el endpoint es de solo lectura (GET); las operaciones CRUD para administrar tipos de personal están fuera de alcance
- Se asume que el catálogo es relativamente estático y puede cachearse en futuras iteraciones, pero en v1 siempre se consulta la base de datos

### Technical Assumptions
- Se asume que la tabla `tb_cat_tipo_personal` sigue las convenciones de nomenclatura del proyecto (prefijo `tb_cat_` para tablas de catálogo)
- Se asume que PostgreSQL 16 es la base de datos disponible y configurada según `config/database.php`
- Se asume que el patrón arquitectónico existente en `app/Core/Admin/` (TipoRequerimiento) es el modelo a replicar
- Se asume que el proyecto ya tiene configurado el sistema de logs estructurados y manejo de excepciones según la constitution
- Se asume que los datos de catálogo iniciales se insertan mediante migration (no seeder separado) para garantizar consistencia en todos los ambientes

### User Assumptions
- Se asume que los clientes del API (frontends, aplicaciones) manejarán adecuadamente el caso de array vacío cuando no haya tipos activos
- Se asume que no se requiere paginación dado que el catálogo tiene solo 4 elementos fijos
- Se asume que no se requiere filtrado, búsqueda o parámetros adicionales en el endpoint

### Data Assumptions
- Se asume que los 4 tipos iniciales (Base, Enlace, Confianza, Externo) estarán activos (`activo = true`) al momento del seeding
- Se asume que la descripción de cada tipo puede ser null o una cadena vacía inicialmente (no es crítica para la funcionalidad básica)
- Se asume que el campo `activo` es suficiente para controlar la visibilidad sin necesidad de soft-deletes

### Integration Assumptions
- Se asume que el endpoint no requiere integración con sistemas externos (RH, directorio activo) para validar tipos de personal en esta versión
- Se asume que la selección del tipo de personal por parte del usuario se manejará en otro endpoint/formulario (fuera del alcance de esta especificación)
- Se asume que el formulario de solicitud de permisos consumirá este endpoint pero su implementación es una historia separada
