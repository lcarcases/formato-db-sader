# Feature Specification: Catálogo de Hostnames

**Feature Branch**: `[005-catalogo-hostnames]`
**Created**: 2026-08-22
**Status**: Draft
**Input**: User description: "caso de uso que permite a un trabajador de la DGTIC en la SADER obtener el catálogo de hostnames/direcciones IP disponibles mediante una API de solo lectura (GET /api/v1/admin/hostnames), para poder especificar a qué servidor necesita acceso al llenar el formato de solicitud de acceso a base de datos, siguiendo el mismo patrón ya implementado para "Catálogo de Bases de Datos" (specs/004-catalogo-bases-datos)" (ver `userStory/enriched/2026-08-22-catalogo-de-hostnames-user-story.md`)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Obtener Catálogo de Hostnames (Priority: P1)

Como trabajador de la DGTIC en la SADER, quiero poder consultar la lista de hostnames y direcciones IP disponibles para poder seleccionar aquel al cual quiero conectarme al solicitar acceso, al llenar el formato de BD.

**Why this priority**: Es el paso fundamental para que el proceso de llenado del formato de BD pueda ofrecer una forma estandarizada de elegir el servidor objetivo; sin este catálogo no hay lista fiable de opciones válidas y se corre el riesgo de captura libre ambigua o errónea.

**Independent Test**: Se puede probar de forma independiente desplegando el endpoint y verificando que devuelve el listado de los 11 hostnames/IPs activos sembrados inicialmente, sin depender de ninguna otra funcionalidad del formulario.

**Acceptance Scenarios**:

1. **Given** un catálogo con los 11 hostnames/IPs iniciales activos, **When** un cliente realiza una petición GET al endpoint `/api/v1/admin/hostnames`, **Then** el sistema debe responder con código 200 y un cuerpo JSON que contenga los 11 hostnames/IPs, cada uno con su `id` y `nombre`, en el mismo orden en que fueron sembrados.
2. **Given** que no hay hostnames activos, **When** un cliente realiza una petición GET al endpoint `/api/v1/admin/hostnames`, **Then** el sistema debe responder con código 200 y un cuerpo JSON con una lista vacía (`data: []`) y `success: true`.

---

### Edge Cases

- **¿Qué sucede si la base de datos PostgreSQL no está disponible?** El sistema debe registrar el error y responder con código 500 y un mensaje genérico, sin exponer detalles internos.
- **¿Cómo maneja el sistema hostnames marcados como inactivos?** Deben excluirse del listado devuelto por el endpoint (solo se listan los que tienen `ind_activo = 1`).
- **¿Cómo distingue el sistema entre un hostname de servidor y una dirección IP?** No existe distinción estructural alguna: ambos se almacenan y devuelven como cadenas planas equivalentes en el mismo campo `nombre`, sin columna de tipo ni agrupación diferenciada.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE exponer un endpoint RESTful (`GET /api/v1/admin/hostnames`) que retorne el catálogo de hostnames/IPs activos.
- **FR-002**: Cada hostname/IP en el catálogo DEBE tener al menos un `id` (identificador único) y un `nombre` descriptivo (nombre de servidor o dirección IP).
- **FR-003**: El endpoint no DEBE requerir autenticación para ser consultado.
- **FR-004**: El sistema DEBE excluir del listado los hostnames/IPs marcados como inactivos.
- **FR-005**: El catálogo sembrado inicialmente DEBE incluir, en este orden, los 11 valores: `pgrdesbds09`, `sridesbds09`, `pgrprdbdsmz02`, `sriprdbdsmz02`, `divprdbds01`, `pgrqabds08`, `sriqabds08`, `10.1.35.50`, `10.1.21.95`, `10.1.20.25`, `10.54.49.100`, todos activos y almacenados exactamente como fueron provistos (sin normalización de mayúsculas/minúsculas).
- **FR-006**: El sistema NO DEBE aplicar ninguna distinción estructural (columna de tipo, validación de formato, agrupación) entre hostnames de servidor y direcciones IP; ambos conviven como cadenas planas igualmente válidas.
- **FR-007**: El sistema NO DEBE exponer en esta historia casos de uso de creación, actualización o eliminación de hostnames (catálogo de solo lectura).

### Key Entities *(include if feature involves data)*

- **Hostname**: Representa un servidor (por nombre) o una dirección IP al cual un trabajador puede solicitar acceso. Es un Objeto de Valor (Value Object), inmutable, cuya identidad está definida por sus atributos.
  - `id`: int
  - `nombre`: string (e.g., "pgrdesbds09", "10.1.35.50")

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El tiempo de respuesta del endpoint del catálogo de hostnames debe ser inferior a 200ms bajo una carga de 50 peticiones por segundo.
- **SC-002**: La estructura de la respuesta JSON debe ser consistente y validada contra un esquema predefinido, permitiendo a los clientes consumirla sin errores de parseo.
- **SC-003**: El 100% de los 11 hostnames/IPs activos del catálogo están disponibles para consulta sin intervención manual ni soporte técnico inmediatamente después de ejecutar las migraciones.

## Assumptions

- Los hostnames/IPs disponibles son un conjunto finito y conocido de antemano (los 11 valores listados en los requisitos funcionales).
- El listado de hostnames no cambia con frecuencia; cualquier alta/baja futura se gestionará fuera del alcance de esta historia (no hay CRUD).
- La información de nombres de servidor y direcciones IP internas es de uso interno de la DGTIC, consistente con el resto de catálogos administrativos ya expuestos sin autenticación adicional en este API.
- No se requiere en esta historia la creación de casos de uso de administración (crear, actualizar, eliminar) del catálogo.
- La integración de este catálogo en el formulario de solicitud de acceso a BD (persistencia de la selección del usuario) queda fuera de este alcance y se resolverá en una historia futura.
- No se requiere validación de resolución DNS ni de conectividad real hacia los hosts/IPs listados.
