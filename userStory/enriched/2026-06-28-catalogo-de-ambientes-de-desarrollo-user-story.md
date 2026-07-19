# Requirement: Catálogo de Ambientes de Desarrollo

## Story

Como un trabajador de la DGTIC,
quiero poder seleccionar un ambiente de desarrollo (Desarrollo, QA, Producción),
para que pueda especificar a qué ambiente de base de datos necesito acceso al llenar un formato.

## Objective

Crear un servicio de catálogo que gestione los diferentes ambientes de desarrollo y exponga una API para que otras partes del sistema puedan consumirlos.

## Context

Actualmente, no existe una forma estandarizada de seleccionar el ambiente de la base de datos al solicitar permisos, lo que puede generar ambigüedad. Esta funcionalidad centralizará la gestión de ambientes, siguiendo los patrones de diseño y arquitectura existentes en el proyecto.

## Scope

### In scope

- Crear una tabla en la base de datos llamada `tb_cat_ambiente_desarrollo` con las columnas `id_nu_ambiente`, `sn_nombre` y `ind_activo`.
- Crear una migración para la tabla `tb_cat_ambiente`.
- Crear un *seeder* para poblar la tabla con los valores iniciales: "Desarrollo", "QA", y "Producción", todos como activos.
- Implementar el caso de uso "Listar Ambientes de Desarrollo" siguiendo la arquitectura hexagonal.
- Crear un endpoint de API `GET /api/v1/ambientes-desarrollo-desarrollo` que devuelva la lista de ambientes activos.
- Incluir pruebas unitarias para el caso de uso y pruebas de integración para el endpoint.

### Out of scope

- La implementación de los casos de uso para crear, actualizar o eliminar ambientes (CRUD completo).
- La integración de este catálogo en cualquier formulario o interfaz de usuario.

## Closed decisions

- La gestión de ambientes se realizará a través de una nueva tabla `tb_cat_ambiente_desarrollo`.
- El alcance de esta historia se limita a la creación del catálogo y su API REST.
- Se creará un *seeder* para insertar los datos iniciales ("Desarrollo", "QA", "Producción").

## Expected behavior

- Una petición `GET` al endpoint `/api/v1/ambientes-desarrollo` debe devolver una respuesta exitosa (código 200).
- La respuesta debe contener un arreglo de objetos JSON, donde cada objeto representa un ambiente activo.
- Si no hay ambientes activos, la respuesta debe ser un arreglo vacío.

## Expected output

```json
[
    {
        "id": 1,
        "nombre": "Desarrollo"
    },
    {
        "id": 2,
        "nombre": "QA"
    },
    {
        "id": 3,
        "nombre": "Producción"
    }
]
```

## Success criteria

- Se puede hacer una petición `GET` a `/api/v1/ambientes-desarrollo` y la respuesta es un JSON con la lista de ambientes activos.
- Existen pruebas unitarias que validan la lógica del caso de uso "Listar Ambientes Activos".
- Existen pruebas de integración que validan el comportamiento del endpoint `GET /api/v1/ambientes-desarrollo`.
