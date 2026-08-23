# API Contract: Obtener Catálogo de Hostnames

**Feature**: Catálogo de Hostnames
**Version**: v1
**Date**: 2026-08-22
**Status**: Final

## Endpoint Overview

Endpoint público que retorna el catálogo completo de hostnames/direcciones IP disponibles en el
sistema. Permite a los trabajadores de la DGTIC seleccionar dinámicamente el servidor sobre el
cual solicitan acceso al llenar el formato de BD.

## HTTP Request

### Method & Path

```
GET /api/v1/admin/hostnames
```

### Authentication

**None** - Este endpoint es público y no requiere autenticación.

**Rationale**: La lista de hostnames/IPs disponibles no es información sensible frente a
consumidores internos y debe ser accesible para que los clientes puedan configurarse antes de
autenticarse (mismo criterio que `/api/v1/admin/bases-datos` y `/api/v1/admin/ambientes-desarrollo`).

### Headers

**Required**: None

**Optional**:
- `Accept: application/json` (recomendado, pero el endpoint siempre retorna JSON)

### Query Parameters

None - Este endpoint no acepta parámetros de query (no hay filtros adicionales; solo se listan
hostnames activos).

### Request Body

None - GET request sin body.

## HTTP Response

### Success Response (200 OK)

**Status Code**: `200 OK`

**Content-Type**: `application/json`

**Body Structure**:

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

**Field Definitions**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `data` | `array` | Yes | Array de objetos de hostname/IP |
| `data[].id` | `integer` | Yes | Identificador único del hostname (> 0) |
| `data[].nombre` | `string` | Yes | Hostname de servidor o dirección IP, tal como fue sembrado (sin normalización) |
| `message` | `string` | Yes | Mensaje descriptivo de la operación |
| `code` | `string` | Yes | Código de status HTTP como string |
| `success` | `boolean` | Yes | Indicador de éxito de la operación |

**Constraints**:
- `data` siempre es un array, nunca null
- Si no hay hostnames activos, `data` es array vacío `[]`
- `data[].id` es siempre un entero positivo
- `data[].nombre` nunca está vacío
- No existe campo de tipo ni agrupación entre hostnames de servidor y direcciones IP — ambos
  aparecen indistintamente como cadenas planas en `data[].nombre`, en el mismo orden de siembra
  (por `id_nu_hostname` ascendente)

**Empty Response Example** (cuando no hay hostnames activos):

```json
{
  "data": [],
  "message": "Hostnames obtenidos exitosamente",
  "code": "200",
  "success": true
}
```

### Error Responses

#### 500 Internal Server Error

**Scenario**: Error inesperado en el servidor (error de base de datos, excepción no manejada)

**Status Code**: `500 Internal Server Error`

**Body**:

```json
{
  "data": null,
  "message": "Error al obtener hostnames. Por favor contacte al administrador.",
  "code": "500",
  "success": false
}
```

**When it occurs**:
- Base de datos no disponible o no responde
- Error en query SQL
- Error en mapeo de Eloquent a Value Object
- Cualquier excepción no manejada en el backend

**Client handling**: Mostrar mensaje de error genérico al usuario, retry después de un tiempo,
contactar soporte si persiste.

#### 405 Method Not Allowed

**Scenario**: Cliente intenta usar método HTTP diferente a GET (e.g., POST, PUT, DELETE)

**Status Code**: `405 Method Not Allowed`

**Headers**: `Allow: GET`

**Body**:

```json
{
  "data": null,
  "message": "Método HTTP no permitido. Use GET.",
  "code": "405",
  "success": false
}
```

## Schema Validation

### JSON Schema (Success Response)

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["data", "message", "code", "success"],
  "properties": {
    "data": {
      "type": "array",
      "items": {
        "type": "object",
        "required": ["id", "nombre"],
        "properties": {
          "id": {
            "type": "integer",
            "minimum": 1
          },
          "nombre": {
            "type": "string",
            "minLength": 1
          }
        },
        "additionalProperties": false
      }
    },
    "message": {
      "type": "string"
    },
    "code": {
      "type": "string",
      "pattern": "^[0-9]{3}$"
    },
    "success": {
      "type": "boolean",
      "const": true
    }
  },
  "additionalProperties": false
}
```

## Example Requests

### cURL

```bash
curl -X GET "https://api.sader.gob.mx/api/v1/admin/hostnames" \
  -H "Accept: application/json"
```

### JavaScript (Fetch API)

```javascript
fetch('https://api.sader.gob.mx/api/v1/admin/hostnames', {
  method: 'GET',
  headers: {
    'Accept': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Hostnames disponibles:', data.data);
    } else {
      console.error('Error:', data.message);
    }
  })
  .catch(error => console.error('Error de red:', error));
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client(['base_uri' => 'https://api.sader.gob.mx']);

try {
    $response = $client->get('/api/v1/admin/hostnames', [
        'headers' => ['Accept' => 'application/json']
    ]);

    $body = json_decode($response->getBody(), true);

    if ($body['success']) {
        foreach ($body['data'] as $hostname) {
            echo "ID: {$hostname['id']}, Nombre: {$hostname['nombre']}\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Python (requests)

```python
import requests

response = requests.get(
    'https://api.sader.gob.mx/api/v1/admin/hostnames',
    headers={'Accept': 'application/json'}
)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        for hostname in data['data']:
            print(f"ID: {hostname['id']}, Nombre: {hostname['nombre']}")
else:
    print(f"Error: {response.status_code}")
```

## Rate Limiting

**Policy**: No rate limiting aplicado a este endpoint.

**Rationale**: Es un endpoint de consulta simple sin carga computacional significativa, y se
espera bajo volumen de peticiones (mismo criterio que `bases-datos`/`ambientes-desarrollo`).

**Future considerations**: Si el tráfico aumenta significativamente, considerar rate limiting
estándar (e.g., 100 req/min por IP).

## Caching

### Server-Side Caching

- **Strategy**: Los datos vienen de PostgreSQL pero son muy estáticos (11 registros raramente
  cambian)
- **Current**: Sin caching implementado (query directo a DB en cada request)
- **Future consideration**: Si la carga aumenta, considerar cache en Redis con TTL largo (1 hora+)
  e invalidación manual al actualizar hostnames
- **Performance**: Query es muy ligero (11 registros, índice en `ind_activo`), performance
  adecuada sin cache

### Client-Side Caching

**Recommended caching headers** (to be implemented):

```
Cache-Control: public, max-age=3600
```

**Client recommendations**:
- Cache response por al menos 1 hora (3600 segundos)
- Re-fetch solo cuando la aplicación cliente se reinicia o manualmente
- Los hostnames raramente cambian, caching agresivo es seguro

## Versioning

**Current Version**: v1

**Version Strategy**: URL-based versioning (`/api/v1/...`)

**Breaking Changes**: Si la estructura de respuesta cambia incompatiblemente, incrementar a v2 y
mantener v1 por período de deprecación.

**Non-breaking Changes** (no requieren nueva versión):
- Agregar nuevos campos opcionales a la respuesta
- Agregar nuevos hostnames a la lista
- Cambiar mensajes de error (siempre que el formato se mantenga)

## Backward Compatibility Guarantees

**Guaranteed stable**:
- URL path `/api/v1/admin/hostnames`
- HTTP method `GET`
- Response structure: `{data, message, code, success}`
- Response `data[]` structure: `{id, nombre}`
- Field types (id: integer, nombre: string)

**May change without version bump**:
- Orden de elementos en `data[]` array (aunque, con el catálogo actual estático, el orden por
  `id_nu_hostname` es determinista)
- Contenido específico de `message` field
- Número de hostnames en la lista

## Testing Contract

### Contract Test Checklist

- [ ] GET request retorna status 200
- [ ] Response tiene Content-Type: application/json
- [ ] Response body contiene campos requeridos: data, message, code, success
- [ ] `data` es un array
- [ ] Cada elemento en `data` tiene `id` (integer > 0) y `nombre` (non-empty string)
- [ ] `success` es `true` en respuesta exitosa
- [ ] `code` es "200" (string)
- [ ] `data` contiene únicamente hostnames con `ind_activo = 1`
- [ ] Con catálogo recién sembrado, `data` contiene exactamente los 11 valores en el orden de
      siembra especificado
- [ ] POST/PUT/DELETE retornan 405
- [ ] Respuesta con catálogo vacío retorna `data: []`, `success: true`
- [ ] Response cumple con JSON schema definido
- [ ] Error simulado (repository lanza excepción) retorna 500 con `success: false` y mensaje
      genérico sin detalles internos

### Example PHPUnit Contract Test

```php
public function test_contrato_obtener_hostnames(): void
{
    $response = $this->getJson('/api/v1/admin/hostnames');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre']
            ],
            'message',
            'code',
            'success',
        ])
        ->assertJson([
            'success' => true,
            'code' => '200',
        ]);

    $data = $response->json('data');
    $this->assertIsArray($data);
    $this->assertCount(11, $data);

    foreach ($data as $hostname) {
        $this->assertIsInt($hostname['id']);
        $this->assertGreaterThan(0, $hostname['id']);
        $this->assertIsString($hostname['nombre']);
        $this->assertNotEmpty($hostname['nombre']);
    }
}

public function test_catalogo_vacio_retorna_data_vacia(): void
{
    \DB::table('tb_cat_hostname')->update(['ind_activo' => 0]);

    $response = $this->getJson('/api/v1/admin/hostnames');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [],
            'success' => true,
        ]);
}
```

## Security Considerations

### Threat Model

**Threats mitigated**:
- SQL injection: N/A (queries parametrizadas vía Eloquent)
- XSS: N/A (no HTML rendering, pure JSON API)
- CSRF: N/A (stateless GET endpoint)

**Potential threats**:
- Information disclosure: **Acceptable** — los hostnames/IPs son información técnica de uso
  interno de la DGTIC, consistente con el resto de catálogos administrativos ya expuestos sin
  autenticación adicional
- DDoS: **Low risk** - endpoint ligero, considerar rate limiting si aumenta tráfico
- Cache poisoning: **N/A** - sin caching server-side implementado actualmente

### Data Sensitivity

**Classification**: Internal (uso interno de la DGTIC)
**PII**: None
**Sensitive Business Data**: None

Los hostnames/IPs internos (e.g., "pgrdesbds09", "10.1.35.50") son información técnica de
infraestructura interna, consistente con el nivel de exposición ya aceptado para el resto de
catálogos administrativos (`bases-datos`, `ambientes-desarrollo`) expuestos sin autenticación en
este API.

## Monitoring & Observability

### Metrics to Track

- Request rate (req/s)
- Response time (p50, p95, p99)
- Error rate (5xx responses)

### Logging

**Log level**: INFO for successful requests, ERROR for 5xx responses

**Log structure** (JSON format):

```json
{
  "timestamp": "2026-08-22T10:30:00Z",
  "level": "INFO",
  "message": "Hostnames obtenidos",
  "context": {
    "endpoint": "/api/v1/admin/hostnames",
    "method": "GET",
    "status": 200,
    "response_time_ms": 15,
    "hostname_count": 11,
    "request_id": "req_abc123"
  }
}
```

### Alerting

**Alert on**:
- Error rate > 1% over 5 minutes
- p95 response time > 500ms
- Endpoint returning empty `data` array (may indicate configuration issue)

## Change Log

| Version | Date | Changes |
|---------|------|---------|
| v1.0 | 2026-08-22 | Initial contract definition |

## Related Documentation

- **Specification**: [spec.md](../spec.md)
- **Data Model**: [data-model.md](../data-model.md)
- **Implementation Plan**: [plan.md](../plan.md)
- **Quick Start**: [quickstart.md](../quickstart.md)
