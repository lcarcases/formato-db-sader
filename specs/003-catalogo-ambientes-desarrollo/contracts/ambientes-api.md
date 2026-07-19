# API Contract: Obtener Catálogo de Ambientes

**Feature**: Catálogo de Ambientes de Desarrollo  
**Version**: v1  
**Date**: 2026-06-28  
**Status**: Final

## Endpoint Overview

Endpoint público que retorna el catálogo completo de ambientes de desarrollo disponibles en el sistema. Permite a las aplicaciones cliente seleccionar dinámicamente el ambiente con el cual desean interactuar.

## HTTP Request

### Method & Path

```
GET /api/v1/admin/ambientes-desarrollo
```

### Authentication

**None** - Este endpoint es público y no requiere autenticación.

**Rationale**: La lista de ambientes disponibles no es información sensible y debe ser accesible para que los clientes puedan configurarse antes de autenticarse.

### Headers

**Required**: None

**Optional**:
- `Accept: application/json` (recomendado, pero el endpoint siempre retorna JSON)

### Query Parameters

None - Este endpoint no acepta parámetros de query.

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
  ],
  "message": "Ambientes obtenidos exitosamente",
  "code": "200",
  "success": true
}
```

**Field Definitions**:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `data` | `array` | Yes | Array de objetos de ambiente |
| `data[].id` | `integer` | Yes | Identificador único del ambiente (> 0) |
| `data[].nombre` | `string` | Yes | Nombre descriptivo del ambiente |
| `message` | `string` | Yes | Mensaje descriptivo de la operación |
| `code` | `string` | Yes | Código de status HTTP como string |
| `success` | `boolean` | Yes | Indicador de éxito de la operación |

**Constraints**:
- `data` siempre es un array, nunca null
- Si no hay ambientes configurados, `data` es array vacío `[]`
- `data[].id` es siempre un entero positivo
- `data[].nombre` nunca está vacío

**Empty Response Example** (cuando no hay ambientes configurados):

```json
{
  "data": [],
  "message": "Ambientes obtenidos exitosamente",
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
  "message": "Error al obtener ambientes. Por favor contacte al administrador.",
  "code": "500",
  "success": false
}
```

**When it occurs**:
- Base de datos no disponible o no responde
- Error en query SQL
- Error en mapeo de Eloquent a Value Object
- Cualquier excepción no manejada en el backend

**Client handling**: Mostrar mensaje de error genérico al usuario, retry después de un tiempo, contactar soporte si persiste.

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
curl -X GET "https://api.sader.gob.mx/api/v1/admin/ambientes-desarrollo" \
  -H "Accept: application/json"
```

### JavaScript (Fetch API)

```javascript
fetch('https://api.sader.gob.mx/api/v1/admin/ambientes-desarrollo', {
  method: 'GET',
  headers: {
    'Accept': 'application/json'
  }
})
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Ambientes disponibles:', data.data);
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
    $response = $client->get('/api/v1/admin/ambientes-desarrollo', [
        'headers' => ['Accept' => 'application/json']
    ]);
    
    $body = json_decode($response->getBody(), true);
    
    if ($body['success']) {
        foreach ($body['data'] as $ambiente) {
            echo "ID: {$ambiente['id']}, Nombre: {$ambiente['nombre']}\n";
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
    'https://api.sader.gob.mx/api/v1/admin/ambientes-desarrollo',
    headers={'Accept': 'application/json'}
)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        for ambiente in data['data']:
            print(f"ID: {ambiente['id']}, Nombre: {ambiente['nombre']}")
else:
    print(f"Error: {response.status_code}")
```

## Rate Limiting

**Policy**: No rate limiting aplicado a este endpoint.

**Rationale**: Es un endpoint de consulta simple sin carga computacional significativa, y se espera bajo volumen de peticiones.

**Future considerations**: Si el tráfico aumenta significativamente, considerar rate limiting estándar (e.g., 100 req/min por IP).

## Caching

### Server-Side Caching

- **Strategy**: Los datos vienen de PostgreSQL pero son muy estáticos (3 registros raramente cambian)
- **Current**: Sin caching implementado (query directo a DB en cada request)
- **Future consideration**: Si la carga aumenta, considerar cache en Redis con TTL largo (1 hora+) e invalidación manual al actualizar ambientes
- **Performance**: Query es muy ligero (3 registros, índice en ind_activo), performance adecuada sin cache

### Client-Side Caching

**Recommended caching headers** (to be implemented):

```
Cache-Control: public, max-age=3600
ETag: "33a64df551425fcc55e4d42a148795d9f25f89d4"
```

**Client recommendations**:
- Cache response por al menos 1 hora (3600 segundos)
- Re-fetch solo cuando la aplicación cliente se reinicia o manualmente
- Los ambientes raramente cambian, caching agresivo es seguro

## Versioning

**Current Version**: v1

**Version Strategy**: URL-based versioning (`/api/v1/...`)

**Breaking Changes**: Si la estructura de respuesta cambia incompatiblemente, incrementar a v2 y mantener v1 por período de deprecación.

**Non-breaking Changes** (no requieren nueva versión):
- Agregar nuevos campos opcionales a la respuesta
- Agregar nuevos ambientes a la lista
- Cambiar mensajes de error (siempre que el formato se mantenga)

## Backward Compatibility Guarantees

**Guaranteed stable**:
- URL path `/api/v1/admin/ambientes-desarrollo`
- HTTP method `GET`
- Response structure: `{data, message, code, success}`
- Response `data[]` structure: `{id, nombre}`
- Field types (id: integer, nombre: string)

**May change without version bump**:
- Orden de elementos en `data[]` array
- Contenido específico de `message` field
- Número de ambientes en la lista

## Testing Contract

### Contract Test Checklist

- [ ] GET request retorna status 200
- [ ] Response tiene Content-Type: application/json
- [ ] Response body contiene campos requeridos: data, message, code, success
- [ ] `data` es un array
- [ ] Cada elemento en `data` tiene `id` (integer > 0) y `nombre` (non-empty string)
- [ ] `success` es `true` en respuesta exitosa
- [ ] `code` es "200" (string)
- [ ] POST/PUT/DELETE retornan 405
- [ ] Response cumple con JSON schema definido

### Example PHPUnit Contract Test

```php
public function test_contrato_obtener_ambientes(): void
{
    $response = $this->getJson('/api/v1/admin/ambientes-desarrollo');
    
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
    
    foreach ($data as $ambiente) {
        $this->assertIsInt($ambiente['id']);
        $this->assertGreaterThan(0, $ambiente['id']);
        $this->assertIsString($ambiente['nombre']);
        $this->assertNotEmpty($ambiente['nombre']);
    }
}
```

## Security Considerations

### Threat Model

**Threats mitigated**:
- SQL injection: N/A (no database queries)
- XSS: N/A (no HTML rendering, pure JSON API)
- CSRF: N/A (stateless GET endpoint)

**Potential threats**:
- Information disclosure: **Acceptable** - los nombres de ambientes no son sensibles
- DDoS: **Low risk** - endpoint ligero, considerar rate limiting si aumenta tráfico
- Cache poisoning: **Mitigated** - config caching es server-side, no user-controllable

### Data Sensitivity

**Classification**: Public  
**PII**: None  
**Sensitive Business Data**: None

Los nombres de ambientes (e.g., "Desarrollo", "QA", "Producción") no constituyen información sensible o confidencial.

## Monitoring & Observability

### Metrics to Track

- Request rate (req/s)
- Response time (p50, p95, p99)
- Error rate (5xx responses)
- Cache hit rate (si se implementa HTTP caching)

### Logging

**Log level**: INFO for successful requests, ERROR for 5xx responses

**Log structure** (JSON format):

```json
{
  "timestamp": "2026-06-28T10:30:00Z",
  "level": "INFO",
  "message": "Ambientes obtenidos",
  "context": {
    "endpoint": "/api/v1/admin/ambientes-desarrollo",
    "method": "GET",
    "status": 200,
    "response_time_ms": 15,
    "ambiente_count": 3,
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
| v1.0 | 2026-06-28 | Initial contract definition |

## Related Documentation

- **Specification**: [spec.md](../spec.md)
- **Data Model**: [data-model.md](../data-model.md)
- **Implementation Plan**: [plan.md](../plan.md)
- **Quick Start**: [quickstart.md](../quickstart.md)
