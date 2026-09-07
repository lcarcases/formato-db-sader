# Feature Specification: Catálogo de Esquemas por Hostname

**Feature Branch**: `006-catalogo-esquemas-hostname`
**Created**: 2026-08-30
**Status**: Draft
**Input**: User description: "caso de uso que permite, dado un hostname, seleccionar los esquemas de ese hostname a los cuales un trabajador de la DGTIC en la SADER quiere conectarse y solicitar acceso (incluyendo una opción sintética "Todos" para solicitar acceso a la totalidad de los esquemas de ese hostname), para especificar con precisión el alcance de acceso a base de datos requerido al llenar el formato de BD. Se debe crear un catálogo independiente `Esquema` (tb_cat_esquema) más una relación muchos-a-muchos (tb_r_hostname_esquema) con Hostname, y exponer dos endpoints de solo lectura: GET /api/v1/admin/hostnames/{idHostname}/esquemas (esquemas asociados a un hostname + "Todos") y GET /api/v1/admin/esquemas (catálogo completo de esquemas activos), siguiendo el patrón de arquitectura hexagonal ya establecido por "Catálogo de Hostnames" (specs/005-catalogo-hostnames) y "Catálogo de Bases de Datos" (specs/004-catalogo-bases-datos)" (ver `userStory/enriched/2026-08-30-seleccionar-esquemas-por-hostname-user-story.md`)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Obtener Esquemas de un Hostname (Priority: P1)

Como trabajador de la DGTIC en la SADER, dado un hostname al que quiero solicitar acceso, quiero
poder consultar los esquemas de base de datos asociados a ese hostname (incluyendo una opción
"Todos" para solicitar acceso a la totalidad de sus esquemas), para poder especificar con precisión
el alcance de acceso que necesito al llenar el formato de BD.

**Why this priority**: Es el flujo principal de la historia: sin esta consulta el trabajador no
puede acotar su solicitud de acceso a esquemas específicos de un servidor concreto, y el formato de
BD carecería de una forma estandarizada de capturar ese alcance.

**Independent Test**: Se puede probar de forma independiente desplegando el endpoint anidado
`GET /api/v1/admin/hostnames/{idHostname}/esquemas` y verificando, para un hostname sembrado con
asociaciones conocidas (p. ej. id 2, 4 o 7), que la respuesta contiene la opción "Todos" seguida de
los esquemas reales asociados, sin depender de ninguna otra funcionalidad del formulario.

**Acceptance Scenarios**:

1. **Given** un hostname existente con esquemas asociados (p. ej. `id_nu_hostname = 2`), **When**
   un cliente realiza una petición GET a `/api/v1/admin/hostnames/2/esquemas`, **Then** el sistema
   responde 200 con un arreglo que inicia con `{ "id": 0, "nombre": "Todos" }` seguido de los
   esquemas realmente asociados a ese hostname, ordenados por `id_nu_esquema` ascendente.
2. **Given** un hostname existente sin esquemas asociados, **When** un cliente realiza una
   petición GET a `/api/v1/admin/hostnames/{idHostname}/esquemas`, **Then** el sistema responde 200
   con `data` conteniendo únicamente `{ "id": 0, "nombre": "Todos" }` (nunca un arreglo vacío).
3. **Given** un `idHostname` que no existe en el catálogo de hostnames, **When** un cliente
   realiza la misma petición, **Then** el sistema responde 404 con `success: false`.

---

### User Story 2 - Obtener Catálogo Completo de Esquemas (Priority: P2)

Como trabajador de la DGTIC en la SADER (o como administrador del catálogo), quiero poder consultar
el catálogo completo de esquemas activos disponibles, para tener visibilidad del universo total de
esquemas gestionados por el sistema, de forma consistente con el resto de catálogos administrativos
ya expuestos (tipos de permiso, tipos de personal, ambientes de desarrollo, bases de datos,
hostnames).

**Why this priority**: Es un endpoint de soporte/consistencia respecto al resto de catálogos del
contexto `Admin`; no es indispensable para el flujo principal (que ya obtiene los esquemas por
hostname), pero se requiere para mantener paridad de capacidades administrativas.

**Independent Test**: Se puede probar de forma independiente desplegando el endpoint
`GET /api/v1/admin/esquemas` y verificando que devuelve los 16 esquemas activos sembrados
inicialmente, sin depender del endpoint anidado por hostname.

**Acceptance Scenarios**:

1. **Given** un catálogo con los 16 esquemas iniciales activos, **When** un cliente realiza una
   petición GET a `/api/v1/admin/esquemas`, **Then** el sistema responde 200 con un cuerpo JSON que
   contiene los 16 esquemas, cada uno con su `id` y `nombre`, sin la entrada sintética "Todos".
2. **Given** que no hay esquemas activos, **When** un cliente realiza la misma petición, **Then**
   el sistema responde 200 con `data: []` y `success: true`.

---

### Edge Cases

- **¿Qué sucede si la base de datos PostgreSQL no está disponible?** El sistema debe registrar el
  error y responder con código 500 y un mensaje genérico, sin exponer detalles internos, en ambos
  endpoints.
- **¿Cómo distingue el sistema "hostname inexistente" (404) de "hostname existente sin esquemas
  asociados" (200 con solo "Todos")?** Un `idHostname` que no corresponde a ninguna fila en el
  catálogo de hostnames responde 404; un `idHostname` válido sin asociaciones registradas responde
  200 con únicamente la opción "Todos".
- **¿La opción "Todos" se almacena como un esquema real?** No. Es una entrada sintética que nunca se
  persiste en el catálogo de esquemas; se antepone siempre a la respuesta del endpoint anidado por
  hostname, representada como `{ "id": 0, "nombre": "Todos" }`.
- **¿Un mismo esquema puede pertenecer a más de un hostname?** Sí; la relación es muchos-a-muchos
  (p. ej. los 16 esquemas sembrados están simultáneamente asociados a tres hostnames distintos).
- **¿Cómo maneja el sistema esquemas o asociaciones marcados como inactivos?** Deben excluirse de
  ambos listados (solo se consideran esquemas y asociaciones con `ind_activo = 1`).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE exponer un endpoint RESTful anidado
  (`GET /api/v1/admin/hostnames/{idHostname}/esquemas`) que retorne los esquemas activos asociados
  a un hostname específico, identificado por su identificador numérico.
- **FR-002**: El sistema DEBE anteponer siempre a la respuesta del endpoint anidado una entrada
  sintética `{ "id": 0, "nombre": "Todos" }`, que representa la opción de solicitar acceso a la
  totalidad de los esquemas de ese hostname, sin importar cuántos esquemas reales estén asociados.
- **FR-003**: El sistema DEBE responder con código 404 y `success: false` cuando el `idHostname`
  solicitado no exista en el catálogo de hostnames.
- **FR-004**: El sistema DEBE responder con código 200 y `data` conteniendo únicamente la entrada
  "Todos" cuando el hostname exista pero no tenga esquemas asociados (nunca un arreglo vacío en
  este caso).
- **FR-005**: El sistema DEBE exponer un endpoint RESTful adicional (`GET /api/v1/admin/esquemas`)
  que retorne el catálogo completo de esquemas activos, sin la entrada sintética "Todos".
- **FR-006**: Cada esquema en ambos catálogos DEBE tener al menos un `id` (identificador único) y
  un `nombre` descriptivo.
- **FR-007**: Un mismo esquema DEBE poder estar asociado a más de un hostname, y un mismo hostname
  DEBE poder tener más de un esquema asociado (relación muchos-a-muchos explícita).
- **FR-008**: El sistema DEBE excluir de ambos listados los esquemas y/o asociaciones marcados
  como inactivos.
- **FR-009**: El catálogo de esquemas sembrado inicialmente DEBE incluir, en este orden, los 16
  valores: `ap_activemq_pd`, `ap_apoyos_pd`, `ap_biometricos_pd`, `ap_gestion_doc`, `ap_interfaz`,
  `ap_inventario_pd`, `ap_movil_pd`, `ap_proagro_pd`, `ap_reportes_suri`, `ap_supervision_pd`,
  `ap_suri_pd`, `ap_svc`, `ap_tramites_pd`, `ap_viaticos`, `tr_seguridad_pd`, `tr_suri_pd`, todos
  activos.
- **FR-010**: Las asociaciones sembradas inicialmente DEBEN vincular los 16 esquemas anteriores a
  cada uno de los tres hostnames `sridesbds09` (id 2), `sriqabds08` (id 7) y `sriprdbdsmz02` (id 4)
  ya sembrados, resultando en 48 asociaciones activas; ningún otro hostname sembrado recibe
  asociaciones en esta historia.
- **FR-011**: Ninguno de los dos endpoints DEBE requerir autenticación para ser consultado,
  consistente con el resto de catálogos del contexto `Admin`.
- **FR-012**: El sistema NO DEBE exponer en esta historia casos de uso de creación, actualización o
  eliminación de esquemas ni de sus asociaciones con hostnames (catálogos de solo lectura,
  poblados exclusivamente vía datos sembrados).
- **FR-013**: El sistema NO DEBE exponer en esta historia un endpoint inverso que liste los
  hostnames asociados a un esquema dado.
- **FR-014**: El sistema NO DEBE persistir en esta historia la selección real que un trabajador
  haga de esquema(s) u opción "Todos" en el contexto de una solicitud o formato de BD.

### Key Entities *(include if feature involves data)*

- **Esquema**: Representa un esquema de base de datos al cual un trabajador puede solicitar acceso
  dentro de un hostname determinado. Es un Objeto de Valor (Value Object), inmutable, cuya
  identidad está definida por sus atributos.
  - `id`: int (> 0)
  - `nombre`: string no vacío (e.g., "ap_activemq_pd")
- **Asociación Hostname-Esquema**: Representa la relación muchos-a-muchos entre un `Hostname` y un
  `Esquema`; determina qué esquemas están disponibles para solicitar acceso en un hostname
  determinado. No se expone como entidad independiente en la API; solo determina el contenido del
  listado "esquemas por hostname".
- **Opción "Todos"**: Entrada sintética, no persistida, que representa la solicitud de acceso a la
  totalidad de los esquemas de un hostname dado. Se identifica siempre con `id = 0` y
  `nombre = "Todos"`, y aparece únicamente en la respuesta del endpoint anidado por hostname,
  siempre como primer elemento.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El tiempo de respuesta de ambos endpoints del catálogo de esquemas debe ser inferior
  a 200ms bajo una carga de 50 peticiones por segundo.
- **SC-002**: La estructura de la respuesta JSON de ambos endpoints debe ser consistente y
  validada contra un esquema predefinido, permitiendo a los clientes consumirla sin errores de
  parseo.
- **SC-003**: El 100% de los 16 esquemas y las 48 asociaciones hostname-esquema sembradas
  inicialmente están disponibles para consulta sin intervención manual ni soporte técnico
  inmediatamente después de ejecutar las migraciones.
- **SC-004**: El 100% de las solicitudes al endpoint anidado con un hostname inexistente reciben
  una respuesta 404 clara, evitando que un identificador inválido se confunda con un hostname
  válido sin esquemas.

## Assumptions

- Los esquemas disponibles son un conjunto finito y conocido de antemano (los 16 valores listados
  en los requisitos funcionales), asociados únicamente a los tres hostnames de ejemplo indicados.
- El listado de esquemas y sus asociaciones no cambia con frecuencia; cualquier alta/baja futura se
  gestionará fuera del alcance de esta historia (no hay CRUD).
- El identificador de hostname usado en la ruta del endpoint anidado es el identificador numérico
  interno (`id_nu_hostname`), no el nombre/IP en texto, asumiendo que el cliente ya obtuvo ese
  identificador mediante el catálogo de hostnames existente.
- No se requiere en esta historia la creación de casos de uso de administración (crear, actualizar,
  eliminar) de esquemas ni de sus asociaciones.
- La integración de este catálogo en el formulario de solicitud de acceso a BD (persistencia de la
  selección del usuario) queda fuera de este alcance y se resolverá en una historia futura.
- No se requiere validación de existencia/conectividad real de los esquemas contra la base de datos
  física del hostname correspondiente.
