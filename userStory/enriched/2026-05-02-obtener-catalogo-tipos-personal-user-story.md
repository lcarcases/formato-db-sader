# Requirement: Obtener Catálogo de Tipos de Personal

## Story

Como trabajador de la DGTIC en SADER,  
Quiero consultar el catálogo de tipos de personal disponibles (Base, Enlace, Confianza, Externo),  
Para poder seleccionar mi tipo de personal durante el proceso de llenado del formato de solicitud de permisos de acceso a bases de datos.

## Objective

Proporcionar un endpoint REST API que exponga el catálogo de tipos de personal activos, permitiendo que los formularios de solicitud de permisos presenten una lista de opciones válidas al usuario.

## Context

El sistema SADER Database Access Permissions API gestiona solicitudes de permisos de acceso a bases de datos. Durante el proceso de solicitud, los trabajadores de la DGTIC necesitan declarar su tipo de personal, lo cual es relevante para la clasificación y procesamiento de las solicitudes de acceso.

Actualmente, el sistema ya cuenta con un patrón establecido para catálogos administrativos (como `tipos-requerimientos` en el módulo Admin), el cual debe replicarse para mantener consistencia arquitectónica.

## Scope

### In scope

- Crear endpoint REST API `GET /api/v1/admin/tipos-personal` que retorne el catálogo de tipos de personal activos
- Implementar siguiendo arquitectura hexagonal (Domain/Application/Infrastructure)
- Crear tabla PostgreSQL `tb_cat_tipo_personal` con campos: `id`, `nombre`, `descripcion`, `activo`, `created_at`, `updated_at`
- Insertar datos iniciales: Base, Enlace, Confianza, Externo (todos activos)
- Implementar use case, DTOs, ports, adapters siguiendo patrón existente de TipoRequerimiento
- Retornar solo tipos activos (`activo = true`)
- Formato de respuesta: array de objetos con `{id, nombre}`
- Tests unitarios (domain, application), integración (repository), contrato (API)
- Actualizar documentación OpenAPI 3.x
- Endpoint público (sin autenticación requerida)

### Out of scope

- Validación contra sistemas externos (RH, directorio activo)
- Endpoints CRUD para administrar tipos de personal (crear, actualizar, eliminar)
- Integración con el formulario de solicitud de permisos (eso es otra historia)
- Lógica de negocio que valide el tipo seleccionado por el usuario
- Frontend o componentes UI

## Closed decisions

- **Ubicación arquitectónica**: Bounded context `Admin` (junto a TipoRequerimiento)
- **Almacenamiento**: PostgreSQL, tabla `tb_cat_tipo_personal`
- **Naming conventions**: 
  - Tabla: `tb_cat_tipo_personal`
  - Endpoint: `/api/v1/admin/tipos-personal`
  - Entity: `TipoPersonal`
  - Use case: `ObtenerTiposPersonalUseCase`
- **Estructura de respuesta**: Solo `{id, nombre}` de tipos activos (simplificada vs TipoRequerimiento)
- **Autenticación**: No requerida (endpoint público)
- **Versionamiento API**: v1 (consistente con endpoints existentes)
- **Registros iniciales**: 4 tipos fijos (Base, Enlace, Confianza, Externo)

## Expected behavior

### Comportamiento normal

- **Request**: `GET /api/v1/admin/tipos-personal`
- **Response 200 OK**:
```json
[
  {"id": 1, "nombre": "Base"},
  {"id": 2, "nombre": "Enlace"},
  {"id": 3, "nombre": "Confianza"},
  {"id": 4, "nombre": "Externo"}
]
```

### Comportamiento en casos borde

- **Catálogo vacío o sin tipos activos**:
  - Response 200 OK: `[]` (array vacío)
  - El frontend maneja el mensaje apropiado al usuario

- **Base de datos no disponible**:
  - Response 500 Internal Server Error
  - Estructura JSON estándar del proyecto:
  ```json
  {
    "error": "DatabaseConnectionException",
    "message": "Error al conectar con la base de datos",
    "code": "DB_CONNECTION_FAILED"
  }
  ```
  - Log estructurado con contexto (request_id, action, error_detail)

### Comportamiento ante fallos

- **PostgreSQL caída**: Retornar 500, registrar error en logs con nivel ERROR
- **Timeout de consulta**: Retornar 500 después del timeout configurado
- **Request malformado**: N/A (endpoint GET sin parámetros)

## Expected output

**Estructura de respuesta exitosa**:
```json
[
  {
    "id": integer,
    "nombre": string
  }
]
```

**Características**:
- Content-Type: `application/json`
- Status code: 200 OK (éxito) o 500 (error de servidor)
- Solo tipos activos (`activo = true`)
- Ordenados por `id` ascendente
- Array vacío `[]` si no hay tipos activos (no es error)

## Success criteria

**Funcionales**:
- ✅ Endpoint `/api/v1/admin/tipos-personal` responde correctamente
- ✅ Retorna los 4 tipos de personal con estructura `{id, nombre}`
- ✅ Solo retorna tipos con `activo = true`
- ✅ Migración crea tabla `tb_cat_tipo_personal` con estructura correcta
- ✅ Seeder inserta los 4 tipos iniciales activos

**Técnicos**:
- ✅ Arquitectura hexagonal respetada:
  - Domain: Entity `TipoPersonal` (puro PHP, sin Laravel)
  - Application: UseCase, DTO, Ports (In/Out)
  - Infrastructure: Controller, Repository, Eloquent model
- ✅ Tests unitarios para `ObtenerTiposPersonalUseCase` (sin Laravel bootstrap)
- ✅ Tests de integración para repository (con TestContainers o similar)
- ✅ Tests de contrato API verifican estructura de respuesta
- ✅ PHPStan nivel 9 pasa sin errores
- ✅ Laravel Pint (PSR-12) aplicado
- ✅ Documentación OpenAPI actualizada con el nuevo endpoint
- ✅ Dependency direction respetada: Infrastructure → Application → Domain
- ✅ Zero Laravel dependencies en Domain layer
- ✅ Logs estructurados con contexto (request_id, action, etc.)
- ✅ Manejo de errores según exception hierarchy del proyecto

**Validación**:
- ✅ Ejecutar `curl -X GET http://localhost/api/v1/admin/tipos-personal` retorna los 4 tipos
- ✅ Payload de respuesta coincide con definición OpenAPI
- ✅ Tests de CI/CD pasan (unit, integration, contract)
