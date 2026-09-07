# API Contract: Catálogo de Esquemas por Hostname

**Feature**: 006-catalogo-esquemas-hostname
**Base URL**: `/api/v1/admin`
**Auth**: None (endpoints públicos, consistentes con el resto de catálogos `Admin`)
**Rate limiting**: `throttle:60,1` (60 requests/min por IP) en ambos endpoints

---

## `GET /api/v1/admin/esquemas`

**Route name**: `api.admin.esquemas.index`
**Handler**: `App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerEsquemasInAdapter`

Retorna el catálogo completo de esquemas activos, sin la entrada sintética "Todos".

### Success — 200 OK

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

### Empty catalog — 200 OK

```json
{ "success": true, "message": "Se obtuvieron los esquemas correctamente.", "data": [] }
```

### Server error — 500 Internal Server Error

```json
{ "success": false, "message": "Error mientras se intentaba obtener los esquemas.", "data": [] }
```

---

## `GET /api/v1/admin/hostnames/{idHostname}/esquemas`

**Route name**: `api.admin.hostnames.esquemas.index`
**Handler**: `App\Core\Admin\Infrastructure\Adapters\In\Api\ObtenerEsquemasPorHostnameInAdapter`
**Path parameter**: `idHostname` (integer) — `id_nu_hostname` numérico, **no** el nombre/IP en texto.

Retorna la opción sintética "Todos" (siempre primer elemento) seguida de los esquemas activos
realmente asociados a ese hostname (vía `tb_r_hostname_esquema`), ordenados por `id_nu_esquema`
ascendente.

### Success — hostname con asociaciones — 200 OK

Ejemplo: `GET /api/v1/admin/hostnames/2/esquemas` (sridesbds09)

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

Same shape (200, "Todos" + 16 esquemas) for `idHostname` = 4 (sriprdbdsmz02) and 7 (sriqabds08).

### Success — hostname existente sin asociaciones — 200 OK

Ejemplo: `GET /api/v1/admin/hostnames/1/esquemas` (pgrdesbds09, sin asociaciones sembradas)

```json
{
  "success": true,
  "message": "Se obtuvieron los esquemas del hostname correctamente.",
  "data": [
    { "id": 0, "nombre": "Todos" }
  ]
}
```

### Hostname inexistente — 404 Not Found

Ejemplo: `GET /api/v1/admin/hostnames/999/esquemas`

```json
{
  "success": false,
  "message": "El hostname solicitado no existe.",
  "data": []
}
```

### Server error — 500 Internal Server Error

```json
{ "success": false, "message": "Error mientras se intentaba obtener los esquemas del hostname.", "data": [] }
```

---

## Response Envelope

Both endpoints use `App\Core\Shared\Infraestructure\Respuesta` (Spanish spelling):

```json
{ "success": boolean, "message": string, "data": array }
```

No `code` field (this is the Spanish-spelling `Respuesta`, distinct from the English-spelling
`Respuesta` used by `ObtenerTiposPersonalInAdapter`/`ObtenerTiposRequerimientosInAdapter` — see
`CLAUDE.md` "Response envelope" section). The 404 case for the nested endpoint is built directly
with `response()->json(..., 404)` matching this same `{success, message, data}` shape, since the
shared `Respuesta::errorResponse()` always returns 500.
