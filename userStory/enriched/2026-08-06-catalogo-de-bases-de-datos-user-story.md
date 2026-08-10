# Requirement: Catálogo de Bases de Datos

## Story

Como un trabajador de la DGTIC en la SADER,
quiero poder seleccionar una base de datos a la cual quiero conectarme y solicitar acceso (PPB, SURI, XAMAN u OTROS),
para que pueda especificar a qué base de datos necesito acceso al llenar el formato de BD.

## Objective

Crear un servicio de catálogo que gestione las bases de datos disponibles y exponga una API para que otras partes del sistema puedan consumirlo, siguiendo el mismo patrón ya implementado para el "Catálogo de Ambientes de Desarrollo".

## Context

Actualmente no existe una forma estandarizada de seleccionar la base de datos al solicitar permisos, lo que puede generar ambigüedad. Esta funcionalidad centraliza la gestión de bases de datos disponibles (PPB, SURI, XAMAN, OTROS), siguiendo los patrones de diseño y arquitectura hexagonal ya establecidos en el proyecto (ver `ObtenerAmbientesUseCase` / `ObtenerAmbientesInAdapter`).

## Scope

### In scope

- Crear una tabla `tb_cat_base_datos` con las columnas `id_nu_base_datos`, `sn_nombre` y `ind_activo`.
- Crear una migración para la tabla `tb_cat_base_datos`.
- Crear un *seeder* para poblar la tabla con los valores iniciales: `PPB`, `SURI`, `XAMAN`, `OTROS` (todos en mayúsculas), todos como activos.
- Implementar el caso de uso "Listar Bases de Datos" siguiendo la arquitectura hexagonal (Domain / Application / Infrastructure), en el contexto `Admin`.
- Crear un endpoint de API `GET /api/v1/admin/bases-datos` (ruta `api.admin.bases-datos.index`) que devuelva la lista de bases de datos activas, con el mismo formato de respuesta `{data, message, code, success}` usado por `ObtenerAmbientesInAdapter`.
- Incluir pruebas unitarias para el caso de uso y pruebas de integración para el endpoint.

### Out of scope

- La implementación de los casos de uso para crear, actualizar o eliminar bases de datos (CRUD completo).
- La integración de este catálogo en el formulario de llenado (persistencia de la selección del usuario).
- La captura de texto libre cuando se selecciona "OTROS": en este alcance, "OTROS" es un valor fijo más del catálogo (`id` + `nombre`), sin campo adicional para especificar una base de datos no listada.

## Closed decisions

- La gestión de bases de datos se realizará a través de una nueva tabla `tb_cat_base_datos`, mismo patrón que `tb_cat_ambiente_desarrollo`.
- El alcance de esta historia se limita a la creación del catálogo y su API REST de solo lectura (sin CRUD, sin integración a formulario).
- "OTROS" es un valor de catálogo fijo, no habilita texto libre; esa capacidad queda para una historia futura de integración con el formulario.
- Se creará un *seeder* con los valores iniciales `PPB`, `SURI`, `XAMAN`, `OTROS`, todos en mayúsculas y activos, en ese orden.
- Endpoint: `GET /api/v1/admin/bases-datos`, nombre de ruta `api.admin.bases-datos.index`.

## Expected behavior

- Una petición `GET` al endpoint `/api/v1/admin/bases-datos` debe devolver una respuesta exitosa (código 200).
- La respuesta debe contener un arreglo de objetos JSON, donde cada objeto representa una base de datos activa (`id`, `nombre`).
- Si no hay bases de datos activas, la respuesta debe ser un arreglo vacío (`data: []`), manteniendo `success: true`.
- Ante un error inesperado (fallo de base de datos, excepción no manejada), el endpoint debe responder 500 con `success: false` y un mensaje genérico, registrando el error en el log (mismo comportamiento que `ObtenerAmbientesInAdapter`).

## Expected output

```json
{
  "data": [
    { "id": 1, "nombre": "PPB" },
    { "id": 2, "nombre": "SURI" },
    { "id": 3, "nombre": "XAMAN" },
    { "id": 4, "nombre": "OTROS" }
  ],
  "message": "Bases de datos obtenidas exitosamente",
  "code": "200",
  "success": true
}
```

## Success criteria

- Se puede hacer una petición `GET` a `/api/v1/admin/bases-datos` y la respuesta es un JSON con la lista de bases de datos activas en el formato definido.
- Existen pruebas unitarias que validan la lógica del caso de uso "Listar Bases de Datos".
- Existen pruebas de integración que validan el comportamiento del endpoint `GET /api/v1/admin/bases-datos`, incluyendo el caso de catálogo vacío y el caso de error 500.
