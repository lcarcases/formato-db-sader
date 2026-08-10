# Feature Specification: Catálogo de Bases de Datos

**Feature Branch**: `[004-catalogo-bases-datos]`
**Created**: 2026-08-07
**Status**: Draft
**Input**: User description: "caso de uso que permita obtener el catalogo de base de datos para que el usuario del sistema pueda seleccionar la base de datos de la cual quiere obtener permisos e interactuar" (ver `userStory/enriched/2026-08-06-catalogo-de-bases-de-datos-user-story.md`)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Obtener Catálogo de Bases de Datos (Priority: P1)

Como trabajador de la DGTIC en la SADER, quiero poder consultar la lista de bases de datos disponibles (PPB, SURI, XAMAN, OTROS) para poder seleccionar aquella sobre la cual quiero solicitar acceso al llenar el formato de BD.

**Why this priority**: Es el paso fundamental para que el proceso de llenado del formato de BD pueda ofrecer una forma estandarizada de elegir la base de datos objetivo; sin este catálogo no hay lista fiable de opciones válidas.

**Independent Test**: Se puede probar de forma independiente desplegando el endpoint y verificando que devuelve el listado de bases de datos activas predefinido, sin depender de ninguna otra funcionalidad del formulario.

**Acceptance Scenarios**:

1. **Given** un catálogo con las bases de datos "PPB", "SURI", "XAMAN" y "OTROS" activas, **When** un cliente realiza una petición GET al endpoint `/api/v1/admin/bases-datos`, **Then** el sistema debe responder con código 200 y un cuerpo JSON que contenga las cuatro bases de datos, cada una con su `id` y `nombre`.
2. **Given** que no hay bases de datos activas, **When** un cliente realiza una petición GET al endpoint `/api/v1/admin/bases-datos`, **Then** el sistema debe responder con código 200 y un cuerpo JSON con una lista vacía (`data: []`).

---

### Edge Cases

- **¿Qué sucede si la base de datos PostgreSQL no está disponible?** El sistema debe registrar el error y responder con código 500 y un mensaje genérico, sin exponer detalles internos.
- **¿Cómo maneja el sistema bases de datos marcadas como inactivas?** Deben excluirse del listado devuelto por el endpoint.
- **¿Qué ocurre si el usuario selecciona "OTROS"?** Fuera del alcance de esta historia: el catálogo entrega "OTROS" como un valor fijo más (id + nombre); la captura de un nombre de base de datos no listada queda para una historia futura de integración con el formulario.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE exponer un endpoint RESTful (`GET /api/v1/admin/bases-datos`) que retorne el catálogo de bases de datos activas.
- **FR-002**: Cada base de datos en el catálogo DEBE tener al menos un `id` (identificador único) y un `nombre` descriptivo.
- **FR-003**: El endpoint no DEBE requerir autenticación para ser consultado.
- **FR-004**: El sistema DEBE excluir del listado las bases de datos marcadas como inactivas.
- **FR-005**: El catálogo sembrado inicialmente DEBE incluir los valores "PPB", "SURI", "XAMAN" y "OTROS", todos en mayúsculas y activos.

### Key Entities *(include if feature involves data)*

- **BaseDatos**: Representa un sistema de base de datos sobre el cual un trabajador puede solicitar acceso. Es un Objeto de Valor (Value Object), inmutable, cuya identidad está definida por sus atributos.
  - `id`: int
  - `nombre`: string (e.g., "PPB", "SURI", "XAMAN", "OTROS")

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El tiempo de respuesta del endpoint del catálogo de bases de datos debe ser inferior a 200ms bajo una carga de 50 peticiones por segundo.
- **SC-002**: La estructura de la respuesta JSON debe ser consistente y validada contra un esquema predefinido, permitiendo a los clientes consumirla sin errores de parseo.
- **SC-003**: El 100% de las bases de datos activas del catálogo ("PPB", "SURI", "XAMAN", "OTROS") están disponibles para consulta sin intervención manual ni soporte técnico.

## Assumptions

- Las bases de datos disponibles son un conjunto finito y conocido de antemano (PPB, SURI, XAMAN, OTROS).
- El listado de bases de datos no cambia con frecuencia.
- La información de los nombres de bases de datos es pública y no contiene datos sensibles.
- El manejo de la opción "OTROS" (captura de un nombre de base de datos no listada) queda fuera de este alcance y se resolverá en una historia futura de integración con el formulario de llenado.
- No se requiere en esta historia la creación de casos de uso de administración (crear, actualizar, eliminar) del catálogo.
