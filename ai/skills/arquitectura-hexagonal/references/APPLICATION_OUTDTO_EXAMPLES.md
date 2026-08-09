## 6.2 OutDto (Output formatted for client)

**Template:** Use [templates/out-dto.php](../templates/out-dto.php) as a starting structure.

## 🚨 CRITICAL OutDTO Patterns

### 🔴 MANDATORY NAMING CONVENTION:

**ALL OutDTOs MUST follow this pattern: `{VerbSpanish}{ConceptSpanish}OutDto`**

```php
// ✅ CORRECT - Use case verb + concept + OutDto
ObtenerTipoRequerimientoOutDto        // Single item
ObtenerTiposRequerimientosOutDto      // Collection
CrearSolicitudOutDto
ListarBeneficiariosOutDto

// ❌ FORBIDDEN - Generic suffixes without verb prefix
TipoRequerimientoItemDto              // ❌ WRONG: No verb + ItemDto!
TipoRequerimientoDataDto              // ❌ WRONG: No verb + DataDto!
SolicitudInfoDto                      // ❌ WRONG: No verb + InfoDto!
BeneficiarioDto                       // ❌ WRONG: No verb!

// ❌ FORBIDDEN - English verbs
GetTipoRequerimientoOutDto            // ❌ WRONG: Use Obtener, not Get!
CreateSolicitudOutDto                 // ❌ WRONG: Use Crear, not Create!
```

**WHY THIS MATTERS:**
- ✅ Clear traceability: DTO name matches the use case
- ✅ Prevents confusion: `ObtenerTipoRequerimientoOutDto` clearly belongs to `ObtenerTipoRequerimientoUseCase`
- ❌ `TipoRequerimientoItemDto` is ambiguous - which use case does it belong to?

### ✅ What OutDTOs MUST DO:
1. **Use semantic property names** (e.g., `$tiposRequerimientos`, not `$data`)
2. **Use Spanish verbs** in class names (`Obtener`, not `Get`)
3. **Use plural forms** for collections (`ObtenerTiposRequerimientosOutDto` for multiple)
4. **Use nested DTOs** for collections (e.g., array of `ObtenerTipoRequerimientoOutDto`) — never a domain VO/Entity
5. **Be simple data carriers** (no business logic)
6. **ALWAYS prefix with use case verb** (`Obtener`, `Crear`, `Listar`, etc.)
7. **Be instantiated ONLY in the InAdapter, after calling the use case** — never inside the UseCase, Domain, or Infrastructure (OutAdapter/Repository) layers

### ❌ What OutDTOs MUST NOT HAVE:
1. **NO `$success` attribute** (InAdapter handles success/failure)
2. **NO `$message` attribute** (InAdapter creates messages)
3. **NO generic `$data` property** (use semantic names!)
4. **NO business logic** (validation, calculations, etc.)
5. **NO generic suffixes** (`ItemDto`, `DataDto`, `InfoDto` are FORBIDDEN)
6. **NO missing verb prefix** (`TipoRequerimientoOutDto` → should be `ObtenerTipoRequerimientoOutDto`)
7. **NO domain classes as property types** (`list<BaseDatosVO>` is FORBIDDEN — wrap each item in a nested OutDTO with primitive properties instead)
8. **NO instantiation outside the InAdapter** (UseCases MUST return raw domain data — e.g. `list<BaseDatosVO>` — and let the InAdapter build the OutDTO; see Mistake 5 below)

---

## ✅ Correct OutDTO Pattern (Single Item)

```php
// filepath: app/Core/Admin/Application/Dtos/Out/ObtenerTipoRequerimientoOutDto.php
<?php

namespace App\Core\Admin\Application\Dtos\Out;

use App\Core\Shared\Application\Dto\IDto;

// ✅ Singular name for single item
final readonly class ObtenerTipoRequerimientoOutDto implements IDto
{
    public function __construct(
        // ✅ Semantic property names (not $data!)
        public int $id,
        public string $nombre
    ) {}
    
    public function toJson(): string
    {
        return json_encode([
            'id' => $this->id,
            'nombre' => $this->nombre
        ]);
    }
}
```

---

## ✅ Correct OutDTO Pattern (Collection with Nested DTOs)

### Pattern for Collections: Singular DTO + Plural DTO

```php
// filepath: app/Core/Admin/Application/Dtos/Out/ObtenerTipoRequerimientoOutDto.php
<?php

namespace App\Core\Admin\Application\Dtos\Out;

use App\Core\Shared\Application\Dto\IDto;

// ✅ Singular DTO (represents ONE item)
final readonly class ObtenerTipoRequerimientoOutDto implements IDto
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {}
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre
        ];
    }
}
```

```php
// filepath: app/Core/Admin/Application/Dtos/Out/ObtenerTiposRequerimientosOutDto.php
<?php

namespace App\Core\Admin\Application\Dtos\Out;

use App\Core\Shared\Application\Dto\IDto;

// ✅ Plural DTO (represents collection)
final class ObtenerTiposRequerimientosOutDto implements IDto
{
    // ✅ Semantic property name (not $data!)
    // ✅ Array of singular DTOs
    /** @var ObtenerTipoRequerimientoOutDto[] */
    public array $tiposRequerimientos;

    public function __construct(array $data)
    {
        $this->tiposRequerimientos = [];
        
        // ✅ Build array of singular DTOs
        foreach ($data as $item) {
            $this->tiposRequerimientos[] = new ObtenerTipoRequerimientoOutDto(
                id: $item['id'] ?? $item->id,
                nombre: $item['nombre'] ?? $item->nombre
            );
        }
    }

    public function toJson(): string
    {
        try {
            // Convert DTOs to arrays
            $tiposArray = array_map(
                fn(ObtenerTipoRequerimientoOutDto $dto) => $dto->toArray(),
                $this->tiposRequerimientos
            );
            
            return json_encode(['tipos_requerimientos' => $tiposArray]);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
    
    public function toArray(): array
    {
        return array_map(
            fn(ObtenerTipoRequerimientoOutDto $dto) => $dto->toArray(),
            $this->tiposRequerimientos
        );
    }
}
```

---

## Real-World Collection Example

```php
// filepath: app/Core/Fletes/DiceDebeDecir/Application/Dtos/Out/EvidenciaFleteOutDto.php
<?php

namespace App\Core\Fletes\DiceDebeDecir\Application\Dtos\Out;

use App\Core\Shared\Application\Dto\IDto;

// ✅ Singular DTO
final class EvidenciaFleteOutDto implements IDto
{
    public int $idEvidencia;
    public string $tipoEvidencia;
    public string $urlDocumento;

    public function __construct($data)
    {
        $this->idEvidencia = $data->idEvidencia ?? $data['idEvidencia'];
        $this->tipoEvidencia = $data->tipoEvidencia ?? $data['tipoEvidencia'];
        $this->urlDocumento = $data->urlDocumento ?? $data['urlDocumento'];
    }

    public function toArray(): array
    {
        return [
            'id_evidencia' => $this->idEvidencia,
            'tipo_evidencia' => $this->tipoEvidencia,
            'url_documento' => $this->urlDocumento
        ];
    }
}
```

```php
// filepath: app/Core/Fletes/DiceDebeDecir/Application/Dtos/Out/ObtenerEvidenciasFleteOutDto.php
<?php

namespace App\Core\Fletes\DiceDebeDecir\Application\Dtos\Out;

use App\Core\Shared\Application\Dto\IDto;

// ✅ Plural DTO with nested singular DTOs
final class ObtenerEvidenciasFleteOutDto implements IDto
{
    public int $idFlete;
    
    // ✅ Semantic property name + type hint
    /** @var EvidenciaFleteOutDto[] */
    public array $evidencias;

    public function __construct($data)
    {
        $this->idFlete = $data->idFlete ?? $data['idFlete'];
        $this->evidencias = [];
        
        // ✅ Convert each item to singular DTO
        foreach ($data->evidencias ?? $data['evidencias'] ?? [] as $evidencia) {
            $this->evidencias[] = new EvidenciaFleteOutDto($evidencia);
        }
    }

    public function toJson(): string
    {
        try {
            $json = json_encode(get_object_vars($this));
            return $json;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}
```

---

## ❌ Common Mistakes in OutDTOs

### Mistake 1: Generic `$data` property

```php
// ❌ WRONG: Generic property name
class ObtenerTiposRequerimientosOutDto
{
    public array $data;  // ❌ Not semantic!
}

// ✅ CORRECT: Semantic property name
class ObtenerTiposRequerimientosOutDto
{
    public array $tiposRequerimientos;  // ✅ Clear and semantic!
}
```

### Mistake 2: Including $success and $message

```php
// ❌ WRONG: DTO with success/message
class ObtenerTipoRequerimientoOutDto
{
    public function __construct(
        public array $data,
        public bool $success,      // ❌ Not DTO responsibility!
        public string $message     // ❌ Not DTO responsibility!
    ) {}
}

// ✅ CORRECT: DTO only carries data
class ObtenerTipoRequerimientoOutDto
{
    public function __construct(
        public int $id,
        public string $nombre
    ) {}
}
```

### Mistake 3: Not using nested DTOs for collections

```php
// ❌ WRONG: Raw arrays instead of nested DTOs
class ObtenerTiposRequerimientosOutDto
{
    public array $tiposRequerimientos;  // ❌ Array of raw arrays

    public function __construct(array $data)
    {
        $this->tiposRequerimientos = $data;  // ❌ No transformation!
    }
}
```

### Mistake 4: Contains domain classes

```php
// ❌ WRONG: OutDto property typed as list<DomainValueObject> — a domain
// class (BaseDatosVO) leaks into the Application/InAdapter contract
final readonly class ObtenerBasesDatosOutDto
{
    /**
     * @param  list<BaseDatosVO>  $basesDatos  Array of base de datos value objects
     */
    public function __construct(
        public array $basesDatos,
    ) {}
}
```

```php
// ✅ CORRECT: Array of nested DTOs (primitives only), never domain VOs/Entities
final readonly class ObtenerBaseDatosOutDto
{
    public function __construct(
        public int $id,
        public string $nombre,
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'nombre' => $this->nombre];
    }
}

final readonly class ObtenerBasesDatosOutDto
{
    /** @param list<ObtenerBaseDatosOutDto> $basesDatos */
    public function __construct(
        public array $basesDatos,
    ) {}

    public function toArray(): array
    {
        return array_map(
            fn (ObtenerBaseDatosOutDto $baseDatos): array => $baseDatos->toArray(),
            $this->basesDatos
        );
    }
}

// ✅ CORRECT: Array of DTOs
class ObtenerTiposRequerimientosOutDto
{
    /** @var ObtenerTipoRequerimientoOutDto[] */
    public array $tiposRequerimientos;  // ✅ Array of DTOs

    public function __construct(array $data)
    {
        $this->tiposRequerimientos = [];
        foreach ($data as $item) {
            // ✅ Each item is a DTO
            $this->tiposRequerimientos[] = new ObtenerTipoRequerimientoOutDto(
                id: $item['id'],
                nombre: $item['nombre']
            );
        }
    }
}
```

### Mistake 5: Instantiating the OutDTO outside the InAdapter

```php
// ❌ WRONG: UseCase constructs and returns the OutDto itself
final class ObtenerBasesDatosUseCase
{
    public function __construct(
        private BaseDatosOutPort $outPort,
    ) {}

    public function execute(): ObtenerBasesDatosOutDto
    {
        $basesDatos = $this->outPort->obtenerBasesDatos();

        // ❌ OutDTOs are an InAdapter/API-response concern — the UseCase
        // must not know they exist
        return new ObtenerBasesDatosOutDto($basesDatos);
    }
}

// ❌ WRONG: OutAdapter or Repository instantiates an OutDto
final class BaseDatosOutAdapter implements BaseDatosOutPort
{
    public function obtenerBasesDatos(): array
    {
        $rawData = $this->baseDatosRepository->obtenerBasesDatos();

        // ❌ Infrastructure must not know about Application-layer OutDTOs
        return new ObtenerBasesDatosOutDto(/* ... */);
    }
}
```

```php
// ✅ CORRECT: UseCase returns raw domain data; OutDto is built ONLY in the InAdapter
// ✅ Property named after the OutPort interface, NOT generic "$outPort"
final readonly class ObtenerBasesDatosUseCase
{
    public function __construct(
        private BaseDatosOutPort $baseDatosOutPort,
    ) {}

    /** @return list<BaseDatosVO> */
    public function execute(): array
    {
        return $this->baseDatosOutPort->obtenerBasesDatos();
    }
}

final readonly class ObtenerBasesDatosInAdapter
{
    public function __construct(
        private ObtenerBasesDatosUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $basesDatos = $this->useCase->execute(); // list<BaseDatosVO>

        // ✅ The OutDto (and its nested item DTOs) is instantiated here,
        // in the InAdapter, after the use case has already returned
        $dto = new ObtenerBasesDatosOutDto(
            array_map(
                fn (BaseDatosVO $baseDatos): ObtenerBaseDatosOutDto => new ObtenerBaseDatosOutDto(
                    id: $baseDatos->id,
                    nombre: $baseDatos->nombre,
                ),
                $basesDatos
            )
        );

        return response()->json([
            'data' => $dto->toArray(),
            'message' => 'Bases de datos obtenidas exitosamente',
            'code' => '200',
            'success' => true,
        ]);
    }
}
```

**Why this matters:**
- The UseCase's return type stays a pure domain/application type (`list<BaseDatosVO>`, an array, etc.) — it has no idea an "OutDTO" or an HTTP response even exists.
- OutAdapters and Repositories are Infrastructure — they must not import or construct Application-layer DTOs either.
- Only the InAdapter sits at the boundary to the outside world, so only the InAdapter is allowed to shape a UseCase's raw result into the OutDTO that becomes the API response.

---

## When to Use OutDTOs

### ✅ USE OutDTOs when:
- **InAdapter needs to format data** for specific client (API, Web, CLI)
- **Transforming UseCase output** to match API contract
- **Multiple use cases return similar data** (reuse DTO)
- **Client expects specific structure** (GraphQL, REST standard)

### ❌ DON'T use OutDTOs when:
- **UseCase can return raw array** (simpler and more flexible)  
- **InAdapter can work with raw data** (no transformation needed)
- **Over-engineering simple responses** (KISS principle)

---

## OutDTO vs UseCase Return

**Preferred Pattern: UseCases return RAW arrays**

```php
// ✅ BEST: UseCase returns raw array
final class ObtenerTiposRequerimientosUseCase
{
    public function obtener(): array
    {
        // ✅ Return raw data
        return $this->tipoRequerimientoOutPort->obtenerTodos();
    }
}

// ✅ InAdapter creates DTO when needed
final class ObtenerTiposRequerimientosInAdapter
{
    public function __invoke()
    {
        try {
            $respuesta = new Respuesta();
            
            // Get raw data from UseCase
            $rawData = $this->useCase->obtener();
            
            // ✅ Create DTO here if specific format needed
            $outDto = new ObtenerTiposRequerimientosOutDto($rawData);
            
            $respuesta->setSuccess(true);
            $respuesta->setMessage("Tipos obtenidos correctamente");
            $respuesta->setData($outDto);
            
            return $respuesta->successResponse();
        } catch (\Exception $ex) {
            // Handle error
        }
    }
}
```

---

Purpose: Transform and format data for the client

**DTO Rules:**
```
✅ DTOs are simple data carriers (no business logic)
✅ Use readonly properties (immutable)
✅ Constructor receives all required data
✅ Can have transformation methods (toArray, toJson)
✅ Live in Application layer
✅ Use semantic property names
✅ Use nested DTOs for collections
✅ OutDTOs are instantiated ONLY in the InAdapter, after calling the use case

❌ DTOs MUST NOT contain:
   - Business logic
   - Validation rules (validation happens in VOs/Entities)
   - Dependencies on Infrastructure
   - Laravel Request objects
   - $success or $message attributes (InAdapter handles these)
   - Domain Value Objects/Entities as property types (use nested OutDTOs with primitive properties)

❌ OutDTOs MUST NOT be instantiated in:
   - UseCases (must return raw domain data instead, e.g. list<SomeVO>)
   - OutAdapters or Repositories (Infrastructure must not know OutDTOs exist)
```

**Naming Convention:**
| Type | Format | Location |
|------|--------|----------|
| InDto | `{UseCaseName}InDto` | `app/Core/{Module}/Application/Dtos/In/` |
| OutDto (single) | `{UseCaseName}OutDto` | `app/Core/{Module}/Application/Dtos/Out/` |
| OutDto (plural) | `{UseCaseName}sOutDto` (plural) | `app/Core/{Module}/Application/Dtos/Out/` |
