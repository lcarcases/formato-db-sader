# Requirement: Catálogo de Hostnames

## Story

Como un trabajador de la DGTIC en la SADER,
quiero poder seleccionar un hostname (o dirección IP) al cual quiero conectarme y solicitar acceso,
para que pueda especificar a qué servidor necesito acceso al llenar el formato de BD.

## Objective

Crear un servicio de catálogo que gestione los hostnames/direcciones IP disponibles y exponga una API de solo lectura para que otras partes del sistema puedan consumirlo, siguiendo el mismo patrón ya implementado para "Catálogo de Ambientes de Desarrollo" y "Catálogo de Bases de Datos".

## Context

Actualmente no existe una forma estandarizada de seleccionar el hostname/servidor al solicitar permisos, lo que puede generar ambigüedad o errores de captura libre. Esta funcionalidad centraliza la gestión de los hostnames disponibles (nombres de servidor y direcciones IP), siguiendo los patrones de diseño y arquitectura hexagonal ya establecidos en el proyecto (ver `ObtenerBasesDatosUseCase` / `ObtenerBasesDatosInAdapter`, el catálogo más reciente y estructuralmente más cercano a este).

## Scope

### In scope

- Crear una tabla `tb_cat_hostname` con las columnas `id_nu_hostname`, `sn_nombre` y `ind_activo`.
- Crear una migración para la tabla `tb_cat_hostname` (misma estructura que `tb_cat_base_datos`: `id` autoincremental, `sn_nombre` varchar(100) único, `ind_activo` smallint con default 1 y check constraint `IN (0,1)`, índice sobre `ind_activo`, timestamps, comentario de tabla).
- Crear una migración de *seed* que inserte los 11 valores iniciales, en este orden, todos como activos (`ind_activo = 1`), almacenados tal como fueron provistos (sin normalizar a mayúsculas):
  1. `pgrdesbds09`
  2. `sridesbds09`
  3. `pgrprdbdsmz02`
  4. `sriprdbdsmz02`
  5. `divprdbds01`
  6. `pgrqabds08`
  7. `sriqabds08`
  8. `10.1.35.50`
  9. `10.1.21.95`
  10. `10.1.20.25`
  11. `10.54.49.100`
- Implementar el caso de uso "Obtener/Listar Hostnames" siguiendo la arquitectura hexagonal (Domain / Application / Infrastructure), en el contexto `Admin`.
- Crear un endpoint de API `GET /api/v1/admin/hostnames` (ruta `api.admin.hostnames.index`) que devuelva la lista de hostnames activos, con el mismo formato de respuesta `{data, message, code, success}` usado por `ObtenerBasesDatosInAdapter`.
- Incluir pruebas unitarias para el caso de uso y pruebas de integración para el endpoint.

### Out of scope

- La implementación de los casos de uso para crear, actualizar o eliminar hostnames (CRUD completo).
- La integración de este catálogo en el formulario de llenado (persistencia de la selección del usuario en una solicitud/formato de BD).
- Cualquier distinción estructural entre "hostname" e "IP" (columna de tipo, validación de formato específico, agrupación en la respuesta). Ambas representaciones se almacenan como cadenas planas en la misma columna `sn_nombre`.
- Validación de resolución DNS o conectividad real hacia los hosts/IPs listados.

## Closed decisions

- La gestión de hostnames se realizará a través de una nueva tabla `tb_cat_hostname`, mismo patrón que `tb_cat_base_datos` y `tb_cat_ambiente_desarrollo`.
- El alcance de esta historia se limita a la creación del catálogo y su API REST de solo lectura (sin CRUD, sin integración a formulario).
- No existe columna ni lógica que distinga "hostname" de "dirección IP"; ambos tipos de valor conviven como cadenas en `sn_nombre`, igual de válidos.
- Los valores se guardan exactamente como fueron proporcionados (hostnames en minúsculas, IPs en notación decimal con puntos), sin normalización de mayúsculas (a diferencia de `tb_cat_base_datos`, donde los códigos cortos sí se normalizaron a mayúsculas) porque son identificadores técnicos reales cuya forma ya es consistente.
- Se creará una migración de *seed* (no un `Seeder` de clase) con los 11 valores iniciales listados arriba, en ese orden, todos activos — mismo mecanismo usado en `2026_08_07_000002_seed_tb_cat_base_datos_table.php`.
- Endpoint: `GET /api/v1/admin/hostnames`, nombre de ruta `api.admin.hostnames.index`, registrado en `AdminApiRoutes.php` junto a los demás catálogos del contexto `Admin`.
- El `InAdapter` construye la respuesta JSON directamente con `response()->json([...])` (formato `{data, message, code, success}`), replicando el patrón inline usado por `ObtenerBasesDatosInAdapter` (el catálogo implementado más recientemente), en lugar de instanciar alguna de las dos clases `Respuesta` compartidas.
- La validación del Value Object (`HostnameVO`) se limita a `id > 0` y `nombre` no vacío tras `trim()`, replicando exactamente `BaseDatosVO`; no se agrega validación de formato de hostname/IP (regex), siguiendo el mismo nivel de validación minimalista ya establecido en los catálogos existentes.
- Nomenclatura de clases (mismo patrón que Bases de Datos): `ObtenerHostnamesUseCase`, `ObtenerHostnamesInAdapter`, `ObtenerHostnamesOutDto` (colección) + `ObtenerHostnameOutDto` (item), `HostnameVO`, `HostnameOutPort`, `HostnameOutAdapter` (PostgresSQL), `HostnameModel`, `HostnameRepository`.
- Solo se listan hostnames activos (`ind_activo = 1`); no hay parámetros de filtro adicionales en el endpoint.

## Expected behavior

- Una petición `GET` al endpoint `/api/v1/admin/hostnames` debe devolver una respuesta exitosa (código 200).
- La respuesta debe contener un arreglo de objetos JSON, donde cada objeto representa un hostname activo (`id`, `nombre`), incluyendo tanto nombres de servidor como direcciones IP indistintamente.
- Si no hay hostnames activos, la respuesta debe ser un arreglo vacío (`data: []`), manteniendo `success: true`.
- Ante un error inesperado (fallo de base de datos, excepción no manejada), el endpoint debe responder 500 con `success: false` y un mensaje genérico, registrando el error en el log (mismo comportamiento que `ObtenerBasesDatosInAdapter`).

## Expected output

```json
{
  "data": [
    { "id": 1, "nombre": "pgrdesbds09" },
    { "id": 2, "nombre": "sridesbds09" },
    { "id": 3, "nombre": "pgrprdbdsmz02" },
    { "id": 4, "nombre": "sriprdbdsmz02" },
    { "id": 5, "nombre": "divprdbds01" },
    { "id": 6, "nombre": "pgrqabds08" },
    { "id": 7, "nombre": "sriqabds08" },
    { "id": 8, "nombre": "10.1.35.50" },
    { "id": 9, "nombre": "10.1.21.95" },
    { "id": 10, "nombre": "10.1.20.25" },
    { "id": 11, "nombre": "10.54.49.100" }
  ],
  "message": "Hostnames obtenidos exitosamente",
  "code": "200",
  "success": true
}
```

## Success criteria

- Se puede hacer una petición `GET` a `/api/v1/admin/hostnames` y la respuesta es un JSON con la lista de hostnames activos en el formato definido.
- Los 11 hostnames/IPs iniciales quedan poblados en la base de datos tras ejecutar las migraciones.
- Existen pruebas unitarias que validan la lógica del caso de uso "Obtener Hostnames".
- Existen pruebas de integración que validan el comportamiento del endpoint `GET /api/v1/admin/hostnames`, incluyendo el caso de catálogo vacío y el caso de error 500.
