# Requirement: Obtener catálogo de tipos de permiso para base de datos

## Story

Como trabajador de la DGTIC en la SADER,  
quiero poder consultar el catálogo de tipos de permiso disponibles (Consulta, Cambios, Eliminación, Consulta y Cambios),  
para poder seleccionar el tipo de permiso que solicitaré para una base de datos específica durante el llenado del formato de base de datos.

## Objective

Exponer un endpoint REST que retorne el catálogo de tipos de permiso activos, de modo que el proceso de llenado del formato de base de datos pueda presentar al trabajador las opciones válidas para seleccionar.

## Context

Durante el llenado del formato de base de datos en el sistema SADER, el trabajador debe indicar qué tipo de acceso solicitará sobre una base de datos en particular. Para ello, se necesita un catálogo centralizado con los tipos de permiso válidos, consumible vía API. Este catálogo es análogo a los catálogos ya implementados de `TipoPersonal` y `TipoRequerimiento` dentro del bounded context `Admin`.

## Scope

### In scope

- Endpoint `GET /api/v1/admin/tipos-permiso` que retorne los tipos de permiso con `activo = true`, ordenados por `id` ascendente.
- Tabla `tb_cat_tipo_permiso` con los campos `id`, `nombre`, `activo` (y opcionalmente `descripcion`).
- Migración de esquema y migración de datos para sembrar los 4 tipos: Consulta, Cambios, Eliminación, Consulta y Cambios.
- Campo `activo` para filtrar registros inactivos (solo se exponen los activos).
- Endpoint público, sin autenticación.
- Rate limiting: 60 solicitudes por minuto por IP (consistente con el patrón del proyecto).
- Respuesta en formato estándar: `{ success, message, code, data }`.
- Logs estructurados en JSON (request_id, action, result, user_ip, duration_ms).
- Tests unitarios del caso de uso, de integración del repositorio y de contrato del endpoint.

### Out of scope

- CRUD para administrar los tipos de permiso (alta, edición, eliminación vía API).
- Selección múltiple de tipos de permiso simultáneos (la selección es única).
- Autenticación o autorización sobre el endpoint.
- Lógica de negocio que restrinja qué tipos de permiso están disponibles según la base de datos.
- Persistencia de la selección del trabajador (no es responsabilidad de este endpoint).
- Frontend o renderizado del selector.

## Closed decisions

- **Forma de la solución**: Nuevo endpoint dedicado `GET /api/v1/admin/tipos-permiso` — no se extiende ningún endpoint existente.
- **Persistencia**: Catálogo fijo sembrado mediante dos migraciones: `create_tb_cat_tipo_permiso_table` (esquema) y `seed_tb_cat_tipo_permiso_table` (datos). Patrón idéntico al de `TipoPersonal`.
- **Selección**: Un único tipo de permiso por solicitud de base de datos (sin selección múltiple).
- **Nombre de la tabla**: `tb_cat_tipo_permiso` — sigue la convención `tb_cat_tipo_*` del proyecto.
- **Filtrado por estado**: Solo se retornan registros con `activo = true`.
- **Autenticación**: Endpoint público, sin token requerido.
- **Bounded context**: `Admin` — mismo contexto que `TipoPersonal` y `TipoRequerimiento`.
- **Patrón de implementación**: Arquitectura hexagonal siguiendo el patrón `TipoPersonal` (InAdapter, OutPort, UseCase, Repository, Respuesta class, `app()->make()`).
- **Criterio de éxito**: El endpoint responde correctamente con los 4 tipos activos en formato `{success, message, code, data}` y supera las pruebas unitarias, de integración y de contrato.

## Expected behavior

### Flujo normal

- El cliente realiza `GET /api/v1/admin/tipos-permiso`.
- El endpoint retorna HTTP 200 con los tipos de permiso que tienen `activo = true`, ordenados por `id` ascendente.
- La respuesta incluye exactamente los campos `id` y `nombre` por cada tipo.

### Caso borde: catálogo vacío

- Si todos los registros tienen `activo = false`, el endpoint retorna HTTP 200 con `data: []` y un mensaje apropiado.

### Fallo de infraestructura

- Si ocurre un error de conexión a base de datos u otro error inesperado, el endpoint retorna HTTP 500 con `success: false`, `data: null`, y un mensaje de error descriptivo.
- El error queda registrado en los logs estructurados con nivel ERROR.

### Rate limiting

- Si se superan 60 solicitudes por minuto desde la misma IP, el endpoint retorna HTTP 429.

## Expected output

Respuesta HTTP 200 — catálogo con tipos activos:

```json
{
  "success": true,
  "message": "Tipos de permiso obtenidos exitosamente",
  "code": 200,
  "data": [
    { "id": 1, "nombre": "Consulta" },
    { "id": 2, "nombre": "Cambios" },
    { "id": 3, "nombre": "Eliminación" },
    { "id": 4, "nombre": "Consulta y Cambios" }
  ]
}
```

Respuesta HTTP 200 — sin tipos activos:

```json
{
  "success": true,
  "message": "No hay tipos de permiso activos",
  "code": 200,
  "data": []
}
```

Respuesta HTTP 500 — error de infraestructura:

```json
{
  "success": false,
  "message": "Hubo un error al momento de obtener los permisos",
  "code": 500,
  "data": null
}
```

## Success criteria

- `GET /api/v1/admin/tipos-permiso` retorna HTTP 200 con los 4 tipos de permiso activos en el formato `{success, message, code, data}`.
- Cada elemento del array contiene exactamente `id` (entero) y `nombre` (cadena).
- Con todos los registros inactivos, el endpoint retorna HTTP 200 con `data: []`.
- Con más de 60 solicitudes por minuto, el endpoint retorna HTTP 429.
- Error de base de datos resulta en HTTP 500 con `data: null`.
- El test unitario del caso de uso pasa con repositorio mockeado.
- El test de integración del repositorio valida la consulta real contra la base de datos.
- El test de contrato del endpoint valida estructura, códigos de respuesta y casos borde.
- PHPStan nivel 9 sin errores.
- PSR-12 (Pint) sin violaciones.
