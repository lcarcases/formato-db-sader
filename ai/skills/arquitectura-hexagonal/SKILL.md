## Goal
Implementar la arquitectura hexagonal y DDD por defecto:

- Transformar las descripciones de casos de uso siguiendo la Arquitectura Hexagonal y DDD.

- Establecer límites arquitectónicos estrictos entre las capas de Dominio, Aplicación e Infraestructura.

- Garantizar que la lógica de negocio resida en las capas de Dominio y Aplicación, sin dependencias de frameworks.

- Estandarizar la generación de Casos de Uso, DTO, Puertos, Adaptadores y Modelos de Dominio.

- Promover la consistencia, la reutilización y la mantenibilidad en todo el código.

- Guiar a los desarrolladores hacia las mejores prácticas alineadas con el clean architecture y domain-centric design.

## Specialized Agent Available

**For automated use case implementation, use the dedicated agent:**

🤖 **@hexagonal-usecase** - Specialized Copilot agent that ALWAYS uses this skill to implement complete use cases with all required artifacts (Domain, Application, Infrastructure layers + tests).

[**Agent Usage Guide**](AGENT_USAGE_GUIDE.md) - Quick start, examples, and best practices

Invoke with: `@hexagonal-usecase implement [your use case description]`

## When to use this skill

- Cuando necesites implementar un nuevo caso de uso.

- Cuando necesites crear entidades de dominio.

- Cuando necesites agregar adaptadores de entrada (InAdapters) para APIs, Web o CLI que reciban información del cliente.

- Cuando necesites crear adaptadores de salida (OutAdapters) para interactuar con bases de datos, servicios externos, APIs, AWS, o sistemas de archivos.

- Cuando necesites implementar DTOs de entrada (InDto) o salida (OutDto) para mover datos entre capas.

- Cuando necesites definir puertos (interfaces) de entrada o salida para establecer contratos entre capas.

- Cuando necesites crear repositories para ejecutar consultas contra bases de datos.

- Cuando necesites implementar specifications para encapsular reglas booleanas de negocio.

- Cuando necesites crear aggregates para agrupar múltiples entidades y value objects como una unidad cohesiva.

- Cuando necesites refactorizar código existente para que cumpla con los principios de Arquitectura Hexagonal y DDD.

- Cuando necesites separar lógica de negocio del framework (Laravel) para hacerla más reutilizable y testeable.

- Cuando necesites manejar excepciones de dominio específicas del negocio.

- Cuando necesites implementar eventos de dominio para comunicar cambios entre diferentes partes del sistema.

## ⚠️ CRITICAL PATTERNS - InAdapters (Must Follow)

When creating InAdapters (entry points like API controllers), ALWAYS follow these NON-NEGOTIABLE patterns:

### 1. Naming Convention
- ✅ **CORRECT**: `{VerbSpanish}{NounSpanish}InAdapter`
  - Examples: `ObtenerTiposRequerimientosInAdapter`, `CrearSolicitudInAdapter`
- ❌ **NEVER USE**: 
  - English verbs: `GetTipoRequerimientoInAdapter` ❌
  - Controller suffix: `ObtenerTiposRequerimientosController` ❌
  - Mixed: `GetTipoRequerimientoController` ❌

### 2. Constructor Pattern
```php
// ✅ CORRECT PATTERN
private ObtenerTiposRequerimientosUseCase $obtenerTiposRequerimientosUseCase;

public function __construct()
{
    try {
        $this->obtenerTiposRequerimientosUseCase = app()->make(ObtenerTiposRequerimientosUseCase::class);
    } catch (\Exception $ex) {
        throw $ex;
    }
}

// ❌ NEVER DO THIS
public function __construct(
    private readonly IGetTipoRequerimientoUseCase $getTipoRequerimientoUseCase
) {}
```

### 3. Respuesta Class Usage
```php
// ✅ CORRECT IMPORT (note: Infraestructure with 'a')
use App\Core\Shared\Infraestructure\Respuesta;

// ✅ CORRECT METHOD PATTERN
public function obtener()
{
    try {
        // 1. Create Respuesta instance FIRST
        $respuesta = new Respuesta();
        
        // 2. Execute use case
        $outDto = $this->obtenerTiposRequerimientosUseCase->obtener();
        
        // 3. Set up response
        $respuesta->setSuccess(true);
        $respuesta->setMessage("Message in Spanish");
        $respuesta->setData($outDto);
        
        // 4. Return standardized response
        return $respuesta->successResponse();
        
    } catch (\Exception $ex) {
        // 5. Handle errors properly
        $respuesta->setSuccess(false);
        $respuesta->setData([]);
        $respuesta->setMessage("Error message in Spanish");
        return $respuesta->errorResponse($ex);
    }
}
```

### Spanish Verb Reference for InAdapters
| Spanish | English | Example InAdapter |
|---------|---------|-------------------|
| Obtener | Get/Fetch | `ObtenerTiposRequerimientosInAdapter` |
| Crear | Create | `CrearSolicitudInAdapter` |
| Actualizar | Update | `ActualizarBeneficiarioInAdapter` |
| Eliminar | Delete | `EliminarDocumentoInAdapter` |
| Listar | List | `ListarSolicitudesInAdapter` |
| Generar | Generate | `GenerarReporteInAdapter` |

**See full details in**: [INFRASTRUCTURE_INADAPTER_EXAMPLES.md](/references/INFRASTRUCTURE_INADAPTER_EXAMPLES.md)

**⚠️ MANDATORY CHECKLIST**: [INADAPTER_MANDATORY_CHECKLIST.md](/references/INADAPTER_MANDATORY_CHECKLIST.md) - Complete verification checklist for InAdapters

## 🚨 CRITICAL PATTERNS - Route Organization (Must Follow)

When creating API InAdapters, routes MUST be organized properly:

### ❌ WRONG - Routes in Laravel's Default Files
```php
// filepath: routes/web.php or routes/api.php
Route::get('/api/admin/tipos-requerimientos', ...);  // ❌ NEVER DO THIS
```

### ✅ CORRECT - Module-Specific Route Files

**1. Create route file:** `app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php`

**2. Use versioned prefix:** `api/v1/{module}` (ALWAYS include /v1 for versioning)

**3. Follow naming pattern:** `api.{module}.{resource}.{action}`

```php
// filepath: app/Core/Admin/Infrastructure/Routes/AdminApiRoutes.php
Route::prefix('api/v1/admin')->group(function () {
    Route::get('/tipos-requerimientos', ObtenerTiposRequerimientosInAdapter::class)
        ->name('api.admin.tipos-requerimientos.index');
});
```

**4. Register in ServiceProvider:**
```php
public function boot(): void {
    $this->loadRoutesFrom(__DIR__ . '/../Routes/AdminApiRoutes.php');
}
```

**See complete guide:** [ROUTING_CONVENTIONS.md](/references/ROUTING_CONVENTIONS.md)

## 🚨 CRITICAL PATTERNS - DTO Naming (Must Follow)

**ALL DTOs must be prefixed with the use case verb. NEVER use generic names!**

### ❌ WRONG - Generic Names Without Verb Prefix
```php
// These are FORBIDDEN naming patterns:
TipoRequerimientoItemDto        // ❌ Missing verb + wrong suffix!
TipoRequerimientoDataDto        // ❌ Missing verb + wrong suffix!
SolicitudInfoDto                // ❌ Missing verb + wrong suffix!
BeneficiarioDto                 // ❌ Missing verb!
TipoRequerimientoOutDto         // ❌ Missing verb prefix!
```

### ✅ CORRECT - Use Case Verb + Concept + OutDto
```php
// ✅ CORRECT PATTERN: {VerbSpanish}{ConceptSpanish}OutDto
ObtenerTipoRequerimientoOutDto        // Single item
ObtenerTiposRequerimientosOutDto      // Collection
CrearSolicitudOutDto
ListarBeneficiariosOutDto
ActualizarDatosPersonaOutDto
```

### Naming Rules (MANDATORY):
1. **✅ ALWAYS** prefix with Spanish verb: `Obtener`, `Crear`, `Listar`, `Actualizar`, `Eliminar`
2. **✅ ALWAYS** suffix with `OutDto` for output or `InDto` for input
3. **✅ Use singular** for single items: `ObtenerTipoRequerimientoOutDto`
4. **✅ Use plural** for collections: `ObtenerTiposRequerimientosOutDto`
5. **❌ NEVER** use generic suffixes: `ItemDto`, `DataDto`, `InfoDto` are FORBIDDEN
6. **❌ NEVER** omit the verb prefix
7. **❌ NEVER** use English verbs: Use `Obtener` not `Get`, `Crear` not `Create`

**WHY THIS MATTERS:**
- ✅ **Traceability**: `ObtenerTipoRequerimientoOutDto` clearly belongs to `ObtenerTipoRequerimientoUseCase`
- ❌ **Ambiguity**: `TipoRequerimientoItemDto` is unclear - which use case owns it?

**See complete details:** [NAMING_CONVENTIONS.md](/references/NAMING_CONVENTIONS.md) | [APPLICATION_OUTDTO_EXAMPLES.md](/references/APPLICATION_OUTDTO_EXAMPLES.md)

## Resumen de la Arquitectura Hexagonal y DDD

[ARCHITECTURE.md](/references/ARCHITECTURE.md)

## Folder Structure

[FOLDER_STRUCTURE.md](/references/FOLDER_STRUCTURE.md)

## Templates

Code templates are available for all hexagonal architecture components. Use these as starting structures when implementing new classes:

[templates/README.md](/templates/README.md)

## 📥 Input (Entrada)

### Información Requerida

| Campo | Obligatorio | Descripción | Ejemplo |
|-------|-------------|-------------|---------|
| **Descripción del caso de uso** | ✅ Sí | Descripción en lenguaje natural de lo que se desea implementar | "Verificar si un beneficiario es elegible para un programa" |
| **Datos de entrada** | ✅ Sí | Datos de entrada que recibe el caso de uso | `idBeneficiario`, `idPrograma`|
| **Módulo/Contexto** | ✅ Sí | Nombre del módulo o bounded context donde se implementará | `Programa`, `Beneficiario`, `Solicitud` |
| **Actor** | ⚪ Opcional | Quién ejecuta el caso de uso | Usuario, Sistema, API Externa, Cron Job |
| **Reglas de negocio** | ⚪ Opcional | Restricciones o invariantes que deben cumplirse | "El beneficiario debe tener más de 18 años" |
| **Contexto de dominio** | ⚪ Opcional | Información adicional sobre el dominio | Entidades existentes, relaciones, vocabulario |
| **Tipo de entrada** | ⚪ Opcional | Canal por donde se recibe la petición | API REST, Web (Livewire), CLI, Queue |
| **Sistemas externos** | ⚪ Opcional | Servicios con los que se debe interactuar | MySQL, AWS S3, API externa, Redis |
| **Respuesta esperada** | ✅ Sí | Respuesta del caso de uso | `indEsBeneficiario`|

### Formatos de Entrada Aceptados

[ACCEPTED_INPUT_FORMAT.md](/references/ACCEPTED_INPUT_FORMAT.md)

---

## 📤 Output (Salida)

### Artefactos Generados por Capa

La skill DEBE generar los siguientes artefactos organizados por capa:

[LAYER_GENERATED_ARTIFACTS.md](/references/LAYER_GENERATED_ARTIFACTS.md)


### Ejemplo Completo de Input/Output

[INPUT_OUTPUT_EXAMPLE.md](/references/INPUT_OUTPUT_EXAMPLE.md)

---

### 📋 Checklist de Generación

Antes de finalizar, verificar que se generaron todos los artefactos necesarios:

[CHECKLIST.md](/references/CHECKLIST.md)


## 🚨 CRITICAL: Entity vs DTO Decision Guide

**BEFORE creating any Entity, read this guide:**

[ENTITY_VS_DTO_DECISION_GUIDE.md](/references/ENTITY_VS_DTO_DECISION_GUIDE.md)

**Anemic entities are FORBIDDEN!** An entity with only getters and no business logic is actually a DTO in disguise.

### Quick Check: Is this really an Entity?
- ❌ **If it only has getters** → Use DTO, NOT Entity
- ❌ **If `toArray()` is its only "logic"** → Use DTO, NOT Entity  
- ❌ **If it's catalog/lookup data** → Use DTO or Enum, NOT Entity
- ✅ **If it has business behavior** → Entity is correct
- ✅ **If it protects invariants** → Entity is correct
- ✅ **If it manages state/lifecycle** → Entity is correct

## Best Practices

[BEST_PRACTICES.md](/references/BEST_PRACTICES.md)

## Naming Conventions

[NAMING_CONVENTIONS.md](/references/BEST_PRACTICES.md)

## Reasoning Process (WORKFLOW) (MANDATORY)

### Paso 1: Analizar caso de uso

[ANALIZE_USE_CASE.md](/references/ANALIZE_USE_CASE.md)

### Paso 2: Identificar conceptos de dominio

**Árbol de decisión:**

[IDENTIFY_DOMAIN_CONCEPTS.md](/references/IDENTIFY_DOMAIN_CONCEPTS.md)


### Step 3: Define Domain Model

**3.1 Create Entities (with behavior, NOT anemic)**

[DOMAIN_ENTITIES_EXAMPLES.md](/references/DOMAIN_ENTITIES_EXAMPLES.md)

**3.2 Create Value Objects (immutable + validation in constructor)**

[DOMAIN_VO_EXAMPLES.md](/references/DOMAIN_VO_EXAMPLES.md)

**3.3 Create Aggregates (group of entities/VOs as unit)**

[DOMAIN_AGGREGATES_EXAMPLE.md](/references/DOMAIN_AGGREGATES_EXAMPLE.md)

**3.4 Create Enums (constrained values)**

[DOMAIN_ENUM_EXAMPLES.md](/references/DOMAIN_ENUM_EXAMPLES.md)

**3.5 Create Domain Exceptions (business errors)**

[DOMAIN_EXCEPTIONS_EXAMPLES.md](/references/DOMAIN_EXCEPTIONS_EXAMPLES.md)

**3.6 Create Specifications (boolean rules)**

[DOMAIN_SPECIFICATIONS_EXAMPLES.md](/references/DOMAIN_SPECIFICATIONS_EXAMPLES.md)

**3.7 Create Domain Services (cross-entity logic)**

[DOMAIN_SERVICES_EXAMPLES.md](/references/DOMAIN_SERVICES_EXAMPLES.md)

**3.8 Create Domain Events (things that happened)**

[DOMAIN_EVENTS_EXAMPLES.md](/references/DOMAIN_EVENTS_EXAMPLES.md)

### Step 4: Define Application Contracts (Ports)

**4.1 Implement Decorator Pattern using InPort (When to use InPort and Decorator pattern)**

[APPLICATION_INPORTS_EXAMPLES.md](/references/APPLICATION_INPORTS_EXAMPLES.md)

**4.2 Create OutPorts (what the application NEEDS from external world)**

[APPLICATION_OUTPORTS_EXAMPLES.md](/references/APPLICATION_OUTPORTS_EXAMPLES.md)


### Step 5: Implement Use Case

[APPLICATION_USECASE_EXAMPLES.md](/references/APPLICATION_USECASE_EXAMPLES.md)

### Step 6: Generate DTOs

**6.1 InDto (Input from client)**

[APPLICATION_INDTO_EXAMPLES.md](/references/APPLICATION_INDTO_EXAMPLES.md)

**6.2 OutDto (Output formatted for client)**

[APPLICATION_OUTDTO_EXAMPLES.md](/references/APPLICATION_OUTDTO_EXAMPLES.md)

### Step 7: Generate Infrastructure

**7.1 InAdapter (Entry point - HTTP/CLI/Web → InDto → UseCase)**

⚠️ **MANDATORY**: Before implementing any InAdapter, consult:
- [INADAPTER_MANDATORY_CHECKLIST.md](/references/INADAPTER_MANDATORY_CHECKLIST.md) - Complete verification checklist with 6 critical patterns
- [INFRASTRUCTURE_INADAPTER_EXAMPLES.md](/references/INFRASTRUCTURE_INADAPTER_EXAMPLES.md) - Examples and patterns

**Non-negotiable InAdapter patterns:**
1. Spanish verb naming (Obtener, Crear, etc.) - NEVER use English verbs or "Controller" suffix
2. app()->make() in constructor - NEVER use dependency injection parameters
3. Respuesta class usage - ALWAYS create instance, set properties, return successResponse()/errorResponse()
4. Try-catch wrapping - ALWAYS wrap __invoke() and all methods
5. Import from Infraestructure (with 'a') - NEVER use "Infrastructure"
6. Private property declaration - ALWAYS declare properties before constructor

**7.2 OutAdapter (Implements OutPorts - interacts with external systems)**

[INFRASTRUCTURE_OUTADAPTER_EXAMPLES.md](/references/INFRASTRUCTURE_OUTADAPTER_EXAMPLES.md)

**7.3 Repository (DB queries - Laravel Eloquent/Query Builder allowed)**

[INFRASTRUCTURE_REPOSITORY_EXAMPLES.md](/references/INFRASTRUCTURE_REPOSITORY_EXAMPLES.md)

### Step 8: Generate Unit Tests

[UNIT_TESTS_EXAMPLES.md](/references/UNIT_TESTS_EXAMPLES.md)


### Step 9: Verify Layer Boundaries (Quality Gate)

[LAYER_BOUNDARIES_VERIFICATION.md](/references/LAYER_BOUNDARIES_VERIFICATION.md)

### Step 10: Register Dependencies (Service Container)

[SERVICE_CONTAINER_REGISTRATION.md](/references/SERVICE_CONTAINER_REGISTRATION.md)

## Architectural Rules (STRICT)

[ARCHITECTURAL_RULES.md](/references/ARCHITECTURAL_RULES.md)