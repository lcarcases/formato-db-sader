# Requirement: Selección de Esquemas por Hostname

## Story

Como un trabajador de la DGTIC en la SADER,
quiero poder seleccionar, dado un hostname, los esquemas de ese hostname a los cuales quiero
conectarme y solicitar acceso (incluyendo una opción "Todos" para solicitar acceso a la totalidad
de los esquemas de ese hostname),
para que pueda especificar con precisión el alcance de acceso a base de datos que necesito al
llenar el formato de BD.

## Objective

Crear un nuevo catálogo `Esquema` (independiente de `Hostname`, dado que un esquema puede estar
asociado a más de un hostname y viceversa) junto con la relación muchos-a-muchos entre ambos, y
exponer dos endpoints de solo lectura: uno para listar los esquemas asociados a un hostname
específico (incluyendo la opción sintética "Todos") y otro para listar el catálogo completo de
esquemas activos — siguiendo el mismo patrón de arquitectura hexagonal ya establecido por
"Catálogo de Hostnames" (`specs/005-catalogo-hostnames`) y "Catálogo de Bases de Datos"
(`specs/004-catalogo-bases-datos`).

## Context

Actualmente el catálogo de Hostnames (`tb_cat_hostname`, ver
`userStory/enriched/2026-08-22-catalogo-de-hostnames-user-story.md`) permite seleccionar el
servidor al que se solicita acceso, pero no existe forma de acotar esa solicitud a esquemas
("schemas") específicos de ese servidor. Un mismo esquema puede convivir en varios hostnames (p.
ej. los 16 esquemas de ejemplo — `ap_activemq_pd`, `ap_apoyos_pd`, `ap_biometricos_pd`,
`ap_gestion_doc`, `ap_interfaz`, `ap_inventario_pd`, `ap_movil_pd`, `ap_proagro_pd`,
`ap_reportes_suri`, `ap_supervision_pd`, `ap_suri_pd`, `ap_svc`, `ap_tramites_pd`, `ap_viaticos`,
`tr_seguridad_pd`, `tr_suri_pd` — están asociados simultáneamente a los hostnames `sridesbds09`,
`sriqabds08` y `sriprdbdsmz02`, ya sembrados como IDs 2, 7 y 4 respectivamente), por lo que se
requiere una relación muchos-a-muchos explícita en lugar de duplicar el catálogo de esquemas por
cada hostname. Esta historia no existía previamente en el codebase: no hay tabla de esquemas ni
tabla de relación hostname↔esquema.

## Scope

### In scope

- Crear una tabla `tb_cat_esquema` (catálogo independiente de esquemas), mismas columnas y
  convenciones que `tb_cat_hostname`/`tb_cat_base_datos`: `id_nu_esquema` (PK autoincremental),
  `sn_nombre` varchar(100) único, `ind_activo` smallint (default 1, check `IN (0,1)`, indexado),
  timestamps, comentario de tabla.
- Crear una tabla de relación `tb_rel_hostname_esquema` para expresar la relación
  muchos-a-muchos: `id_nu_hostname_esquema` (PK autoincremental), `id_nu_hostname` (FK →
  `tb_cat_hostname.id_nu_hostname`), `id_nu_esquema` (FK → `tb_cat_esquema.id_nu_esquema`),
  `ind_activo` smallint (default 1, check `IN (0,1)`), timestamps, índice único compuesto sobre
  `(id_nu_hostname, id_nu_esquema)` y un índice adicional sobre `id_nu_hostname` (ruta de consulta
  principal del endpoint anidado).
- Migración de *seed* que inserte los 16 esquemas listados en la historia original, en ese orden,
  todos `ind_activo = 1`, en `tb_cat_esquema`.
- Migración de *seed* que inserte las 48 asociaciones (16 esquemas × 3 hostnames) en
  `tb_rel_hostname_esquema`, referenciando los hostnames ya sembrados `sridesbds09` (id 2),
  `sriqabds08` (id 7) y `sriprdbdsmz02` (id 4). Ningún otro hostname sembrado recibe asociaciones.
- Caso de uso "Obtener Esquemas de un Hostname" (`ObtenerEsquemasPorHostnameUseCase`): dado un
  `id_nu_hostname`, retorna la lista de esquemas realmente asociados (vía `tb_rel_hostname_esquema`)
  más la opción sintética "Todos" prepuesta al inicio de la respuesta.
- Endpoint `GET /api/v1/admin/hostnames/{idHostname}/esquemas` (ruta
  `api.admin.hostnames.esquemas.index`), donde `{idHostname}` es el `id_nu_hostname` numérico
  (no el nombre/IP en texto).
- Caso de uso "Obtener Esquemas" (`ObtenerEsquemasUseCase`): retorna el catálogo completo de
  esquemas activos (sin la entrada sintética "Todos").
- Endpoint `GET /api/v1/admin/esquemas` (ruta `api.admin.esquemas.index`), análogo al resto de
  catálogos del contexto `Admin` (`/tipos-permiso`, `/tipos-personal`, `/ambientes-desarrollo`,
  `/bases-datos`, `/hostnames`).
- Pruebas unitarias para ambos casos de uso y pruebas de integración/feature para ambos endpoints,
  incluyendo el caso de catálogo/relación vacíos, hostname inexistente (404) y error 500.

### Out of scope

- CRUD completo (crear, actualizar, eliminar) de esquemas o de asociaciones hostname↔esquema —
  ambas tablas se pueblan exclusivamente vía migraciones de *seed*.
- Persistencia de la selección real del usuario (qué esquema(s)/"Todos" eligió) en una solicitud o
  formato de BD — esta historia expone únicamente el catálogo/lookup de solo lectura, igual que
  `004-catalogo-bases-datos` y `005-catalogo-hostnames`.
- Cualquier endpoint que liste, a la inversa, los hostnames asociados a un esquema dado (no
  solicitado por la historia original).
- Validación de existencia/conectividad real de los esquemas contra la base de datos física del
  hostname correspondiente.
- Asociar esquemas a los 8 hostnames sembrados restantes (`pgrdesbds09`, `pgrprdbdsmz02`,
  `divprdbds01`, `pgrqabds08`, y las 4 IPs) — no hay datos proporcionados para ellos.

## Closed decisions

- `Esquema` es un catálogo independiente (`tb_cat_esquema`), no una columna de `tb_cat_hostname`,
  porque un esquema puede pertenecer a más de un hostname.
- La relación muchos-a-muchos se modela con una tabla dedicada `tb_rel_hostname_esquema` (infijo
  `rel`, análogo al infijo `cat` usado en catálogos, para marcar explícitamente una tabla de pura
  relación); no existe precedente previo de tabla pivote en este repo, por lo que este nombre y
  forma quedan fijados aquí como el patrón a seguir.
- La opción "Todos" **no se persiste** como fila de `tb_cat_esquema`; es una entrada sintética que
  el `InAdapter`/`OutDto` de "esquemas por hostname" antepone siempre a la respuesta, representada
  como `{ "id": 0, "nombre": "Todos" }`. Nunca pasa por `EsquemaVO` (cuyo invariante `id > 0` se
  mantiene idéntico al de `HostnameVO`/`BaseDatosVO`, sin excepciones).
- El endpoint principal es `GET /api/v1/admin/hostnames/{idHostname}/esquemas`, anidado bajo el
  recurso `hostnames` ya existente, usando el **ID numérico** del hostname (no su nombre/IP en
  texto) como parámetro de ruta.
- También se expone `GET /api/v1/admin/esquemas` (catálogo completo, sin "Todos"), por consistencia
  con el resto de catálogos del contexto `Admin`.
- Si `{idHostname}` no existe en `tb_cat_hostname`, el endpoint anidado responde **404** con
  `success: false`. Si el hostname existe pero no tiene asociaciones en `tb_rel_hostname_esquema`,
  responde **200** con `data` conteniendo únicamente la entrada sintética "Todos" (nunca un arreglo
  vacío).
- Ambos nuevos `InAdapter` (`ObtenerEsquemasInAdapter`, `ObtenerEsquemasPorHostnameInAdapter`) usan
  `App\Core\Shared\Infraestructure\Respuesta` (ortografía en español) con `successResponse()` /
  `errorResponse()`, formato `{success, message, data}` — replicando el patrón real y actualmente
  implementado en `ObtenerHostnamesInAdapter` (el catálogo hermano/precedente inmediato de esta
  historia), no el patrón inline `response()->json()` de `ObtenerBasesDatosInAdapter`.
- Nomenclatura de clases: `EsquemaVO`, `EsquemaOutPort` (métodos `obtenerEsquemas(): array` y
  `obtenerEsquemasPorHostname(int $idHostname): ?array`, donde `null` significa "hostname no
  encontrado" y `[]` significa "hostname válido, sin esquemas asociados"), `EsquemaOutAdapter`,
  `EsquemaModel` (`tb_cat_esquema`), `EsquemaRepository` (usa internamente un `HostnameEsquemaModel`
  para `tb_rel_hostname_esquema` y valida existencia del hostname contra `HostnameModel`, ambos ya
  existentes en Infrastructure del mismo contexto `Admin` — no se modifica el `HostnameOutPort`/
  `HostnameOutAdapter` ya mergeados en `005-catalogo-hostnames`), `ObtenerEsquemasUseCase`,
  `ObtenerEsquemasPorHostnameUseCase`, `ObtenerEsquemaOutDto` (item), `ObtenerEsquemasOutDto`
  (colección, catálogo completo), `ObtenerEsquemasPorHostnameOutDto` (colección, endpoint anidado,
  responsable de anteponer la entrada sintética "Todos"), `HostnameNotFoundException`
  (`app/Core/Admin/Domain/Exceptions/`, mismo patrón que `TipoPermisoNotFoundException`/
  `TipoPersonalNotFoundException`).
- Validación de `EsquemaVO`: idéntica a `HostnameVO`/`BaseDatosVO` (`id > 0`, `nombre` no vacío tras
  `trim()`), sin regex de formato.
- Solo se listan esquemas/asociaciones activos (`ind_activo = 1`); no hay parámetros de filtro
  adicionales en ningún endpoint.
- CRUD de esquemas y de asociaciones queda fuera de alcance (solo lectura, poblado por seed).
- No se persiste la selección del usuario; esta historia solo expone catálogo/lookup.

## Expected behavior

- `GET /api/v1/admin/hostnames/{idHostname}/esquemas` con un `idHostname` existente y con
  asociaciones (p. ej. `2`, `4` o `7`) devuelve 200 con un arreglo que inicia con
  `{ "id": 0, "nombre": "Todos" }` seguido de los esquemas realmente asociados a ese hostname,
  ordenados por `id_nu_esquema` ascendente.
- `GET /api/v1/admin/hostnames/{idHostname}/esquemas` con un `idHostname` existente pero sin
  asociaciones (p. ej. cualquiera de los otros 8 hostnames sembrados) devuelve 200 con
  `data: [{ "id": 0, "nombre": "Todos" }]` únicamente.
- `GET /api/v1/admin/hostnames/{idHostname}/esquemas` con un `idHostname` que no existe en
  `tb_cat_hostname` devuelve 404 con `success: false`.
- `GET /api/v1/admin/esquemas` devuelve 200 con los 16 esquemas activos (sin "Todos"); si el
  catálogo estuviera vacío, devuelve `data: []` con `success: true`.
- Ante un error inesperado (fallo de base de datos, excepción no manejada) en cualquiera de los dos
  endpoints, responden 500 con `success: false` y un mensaje genérico, registrando el error en el
  log — mismo comportamiento que `ObtenerHostnamesInAdapter`.

## Expected output

`GET /api/v1/admin/hostnames/2/esquemas` (sridesbds09):

```json
{
  "success": true,
  "message": "Se obtuvieron los esquemas del hostname correctamente.",
  "data": [
    { "id": 0, "nombre": "Todos" },
    { "id": 1, "nombre": "ap_activemq_pd" },
    { "id": 2, "nombre": "ap_apoyos_pd" },
    { "id": 3, "nombre": "ap_biometricos_pd" },
    { "id": 4, "nombre": "ap_gestion_doc" },
    { "id": 5, "nombre": "ap_interfaz" },
    { "id": 6, "nombre": "ap_inventario_pd" },
    { "id": 7, "nombre": "ap_movil_pd" },
    { "id": 8, "nombre": "ap_proagro_pd" },
    { "id": 9, "nombre": "ap_reportes_suri" },
    { "id": 10, "nombre": "ap_supervision_pd" },
    { "id": 11, "nombre": "ap_suri_pd" },
    { "id": 12, "nombre": "ap_svc" },
    { "id": 13, "nombre": "ap_tramites_pd" },
    { "id": 14, "nombre": "ap_viaticos" },
    { "id": 15, "nombre": "tr_seguridad_pd" },
    { "id": 16, "nombre": "tr_suri_pd" }
  ]
}
```

`GET /api/v1/admin/hostnames/1/esquemas` (pgrdesbds09, sin asociaciones):

```json
{
  "success": true,
  "message": "Se obtuvieron los esquemas del hostname correctamente.",
  "data": [
    { "id": 0, "nombre": "Todos" }
  ]
}
```

`GET /api/v1/admin/hostnames/999/esquemas` (no existe):

```json
{
  "success": false,
  "message": "El hostname solicitado no existe.",
  "data": []
}
```

`GET /api/v1/admin/esquemas`:

```json
{
  "success": true,
  "message": "Se obtuvieron los esquemas correctamente.",
  "data": [
    { "id": 1, "nombre": "ap_activemq_pd" },
    { "id": 2, "nombre": "ap_apoyos_pd" },
    { "id": 3, "nombre": "ap_biometricos_pd" },
    { "id": 4, "nombre": "ap_gestion_doc" },
    { "id": 5, "nombre": "ap_interfaz" },
    { "id": 6, "nombre": "ap_inventario_pd" },
    { "id": 7, "nombre": "ap_movil_pd" },
    { "id": 8, "nombre": "ap_proagro_pd" },
    { "id": 9, "nombre": "ap_reportes_suri" },
    { "id": 10, "nombre": "ap_supervision_pd" },
    { "id": 11, "nombre": "ap_suri_pd" },
    { "id": 12, "nombre": "ap_svc" },
    { "id": 13, "nombre": "ap_tramites_pd" },
    { "id": 14, "nombre": "ap_viaticos" },
    { "id": 15, "nombre": "tr_seguridad_pd" },
    { "id": 16, "nombre": "tr_suri_pd" }
  ]
}
```

## Success criteria

- Tras ejecutar las migraciones, `tb_cat_esquema` contiene los 16 esquemas iniciales y
  `tb_rel_hostname_esquema` contiene las 48 asociaciones (16 × 3) descritas arriba.
- `GET /api/v1/admin/hostnames/{idHostname}/esquemas` devuelve el formato definido para los tres
  casos: hostname con asociaciones, hostname sin asociaciones, hostname inexistente (404).
- `GET /api/v1/admin/esquemas` devuelve el catálogo completo de 16 esquemas activos en el formato
  definido.
- Existen pruebas unitarias que validan `ObtenerEsquemasUseCase` y
  `ObtenerEsquemasPorHostnameUseCase` (incluyendo el caso "hostname no encontrado").
- Existen pruebas de integración/feature que validan ambos endpoints, incluyendo catálogo vacío,
  relación vacía, hostname inexistente (404) y error 500.
- El Dominio (`EsquemaVO`, `HostnameNotFoundException`) no importa ninguna clase de
  `Illuminate\*`, y la Aplicación (`ObtenerEsquemasUseCase`/`ObtenerEsquemasPorHostnameUseCase`)
  depende únicamente de `EsquemaOutPort`, siguiendo `.specify/memory/constitution.md` v1.1.0.
