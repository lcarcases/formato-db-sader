## Naming Conventions

### General Rules

- **Use PascalCase** for class names
- **Use camelCase** for method names and variables
- **Use UPPER_SNAKE_CASE** for constants
- **Use Spanish** for domain concepts (ubiquitous language)
- **Use English** for technical terms (UseCase, Repository, Adapter, etc.)
- **Be explicit and descriptive** - avoid abbreviations unless widely known

### Domain Layer Naming

#### Entities

**Format:** `{ConceptoDeNegocio}Entity`

```php
// ✅ GOOD
SolicitudEntity
PersonaEntity
BeneficiarioEntity
ProgramaEntity
DocumentoAdjuntoEntity

// ❌ BAD
Solicitud           // Missing Entity suffix
SolicitudModel      // Model implies Infrastructure
RequestEntity       // English for domain concept
SolEntity           // Abbreviated
```

**Rules:**
- Suffix: `Entity`
- Singular noun
- Business concept name in Spanish
- NO abbreviations

#### Value Objects

**Format:** `{Concepto}VO`

```php
// ✅ GOOD
CurpVO
RfcVO
FolioVO
DireccionVO
MontoVO
EmailVO
TelefonoVO
FechaNacimientoVO
SuperficieVO
CodigoPostalVO

// ❌ BAD
CurpValueObject     // Too verbose
Curp                // Missing VO suffix
CURPValueObj        // Inconsistent casing
CurpValue           // Ambiguous suffix
```

**Rules:**
- Suffix: `VO`
- Singular noun
- Immutable by design
- Self-validating

#### Aggregates

**Format:** `{ConceptoPrincipal}Aggregate`

```php
// ✅ GOOD
SolicitudBeneficioAggregate
PedidoAggregate
FacturaAggregate
ContratoAggregate

// ❌ BAD
SolicitudAgg                    // Abbreviated
SolicitudBeneficioAggregateRoot // Too verbose
AggregateSolicitud              // Wrong order
```

**Rules:**
- Suffix: `Aggregate`
- Describes the root concept
- Groups related entities/VOs

#### Enumerations

**Format:** `{Concepto}Enum`

```php
// ✅ GOOD
EstatusSolicitudEnum
TipoDocumentoEnum
SexoEnum
EstadoCivilEnum
NivelEstudiosEnum

// ❌ BAD
StatusSolicitudEnum     // Mixed languages
SolicitudStatus         // Missing Enum suffix
EstatusSolicitud        // Missing Enum suffix
EstadosSolicitudEnum    // Plural (should be singular)
```

**Enum Values:**
```php
// ✅ GOOD - UPPER_SNAKE_CASE
enum EstatusSolicitudEnum: string
{
    case PENDIENTE = 'pendiente';
    case EN_REVISION = 'en_revision';
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
}

// ❌ BAD
enum EstatusSolicitudEnum: string
{
    case Pendiente = 'pendiente';      // Wrong case
    case enRevision = 'en_revision';   // Wrong case
}
```

#### Specifications

**Format:** `{Criterio}Specification`

```php
// ✅ GOOD
EdadMinimaSpecification
SuperficieMaximaSpecification
PersonaActivaSpecification
DocumentoValidoSpecification
ElegibilidadBeneficiarioSpecification

// ❌ BAD
EdadMinima                      // Missing Specification suffix
SpecificationEdadMinima         // Wrong order
EdadMinimaSpec                  // Abbreviated
ValidarEdadMinima               // Verb (should be noun)
```

**Rules:**
- Suffix: `Specification`
- Describes the business rule
- Boolean evaluation implied

#### Domain Services

**Format:** `{Concepto}DomainService`

```php
// ✅ GOOD
CalculoFolioDomainService
ElegibilidadBeneficiarioDomainService
ValidacionDocumentosDomainService
CalculoMontoMaximoDomainService

// ❌ BAD
FolioService                    // Missing DomainService suffix
DomainServiceFolio              // Wrong order
FolioCalculator                 // English suffix
ServicioCalculoFolio            // Spanish technical term
```

**Rules:**
- Suffix: `DomainService`
- Describes cross-entity logic
- Noun phrase (what it manages)

#### Domain Events

**Format:** `{AccionPasada}Event`

```php
// ✅ GOOD
SolicitudGeneradaEvent
SolicitudAprobadaEvent
SolicitudRechazadaEvent
DocumentoAdjuntadoEvent
BeneficiarioRegistradoEvent

// ❌ BAD
GenerarSolicitudEvent           // Infinitive (should be past)
SolicitudGenerada               // Missing Event suffix
EventoSolicitudGenerada         // Spanish technical term
SolicitudGeneradaEvento         // Spanish suffix
SolicitudGeneratedEvent         // English business concept
```

**Rules:**
- Suffix: `Event`
- Past tense verb + noun
- Describes something that happened

#### Domain Exceptions

**Format:** `{ConceptoOProblema}Exception`

```php
// ✅ GOOD
CurpInvalidaException
PersonaNoEncontradaException
PersonaNoActivaException
SolicitudNoAprobableException
DocumentoNoValidoException
SuperficieExcedidaException
EdadInsuficienteException
ProgramaNoEncontradoException

// ❌ BAD
InvalidCurpException            // English business concept
ExceptionCurpInvalida           // Wrong order
CurpInvalida                    // Missing Exception suffix
ErrorCurpInvalida               // Error instead of Exception
```

**Rules:**
- Suffix: `Exception`
- Business error description
- Specific and descriptive

### Application Layer Naming

#### Use Cases

**Format:** `{Verbo}{Sustantivo}UseCase`

```php
// ✅ GOOD
GenerarSolicitudUseCase
AprobarSolicitudUseCase
RechazarSolicitudUseCase
ConsultarBeneficiarioUseCase
ActualizarDatosPersonaUseCase
EliminarDocumentoUseCase

// ❌ BAD
SolicitudGenerarUseCase         // Wrong order
CasoDeUsoGenerarSolicitud       // Spanish technical term
GenerateSolicitudUseCase        // English verb
GenerarSolicitud                // Missing UseCase suffix
UseCaseGenerarSolicitud         // Wrong order
```

**Rules:**
- Suffix: `UseCase`
- Format: Verb (infinitive) + Noun
- One action per use case
- Descriptive and explicit

#### InPorts (Input Ports)

**Format:** `I{Verbo}{Sustantivo}InPort`

```php
// ✅ GOOD
IGenerarSolicitudInPort
IAprobarSolicitudInPort
IConsultarBeneficiarioInPort
IActualizarDatosPersonaInPort

// ❌ BAD
GenerarSolicitudInPort          // Missing I prefix
IGenerarSolicitudPort           // Missing "In"
IInPortGenerarSolicitud         // Wrong order
GenerarSolicitudInterface       // Wrong suffix
IGenerarSolicitudInput          // Input instead of InPort
```

**Rules:**
- Prefix: `I` (interface indicator)
- Suffix: `InPort`
- Matches UseCase name
- One method: `ejecutar()`

#### OutPorts (Output Ports)

**Format:** `I{Concepto}OutPort`

```php
// ✅ GOOD
ISolicitudOutPort
IPersonaOutPort
IProgramaOutPort
INotificacionOutPort
IArchivoOutPort
IEmailOutPort
IReporteOutPort

// ❌ BAD
SolicitudOutPort                // Missing I prefix
ISolicitudPort                  // Missing "Out"
IOutPortSolicitud               // Wrong order
ISolicitudRepository            // Repository implies implementation
ISolicitudAdapter               // Adapter implies infrastructure
```

**Rules:**
- Prefix: `I` (interface indicator)
- Suffix: `OutPort`
- Concept/resource name (not action)
- Multiple methods allowed

#### Input DTOs

**Format:** `{Verbo}{Sustantivo}InDto`

```php
// ✅ GOOD
GenerarSolicitudInDto
AprobarSolicitudInDto
ConsultarBeneficiarioInDto
ActualizarDatosPersonaInDto
FiltrarSolicitudesInDto

// ❌ BAD
GenerarSolicitudDto             // Missing "In"
GenerarSolicitudInputDto        // "Input" instead of "In"
InDtoGenerarSolicitud           // Wrong order
GenerarSolicitudRequest         // Request implies infrastructure
DtoInGenerarSolicitud           // Wrong order
```

**Rules:**
- Suffix: `InDto`
- Matches UseCase/InPort name
- Readonly properties
- No business logic

#### Output DTOs

**Format:** `{Verbo}{Sustantivo}OutDto`

**🚨 CRITICAL RULES (MANDATORY):**
- ✅ **ALWAYS** prefix with use case verb in Spanish (Obtener, Crear, Listar, etc.)
- ✅ **ALWAYS** suffix with `OutDto`
- ✅ Use **singular** for single items: `ObtenerTipoRequerimientoOutDto`
- ✅ Use **plural** for collections: `ObtenerTiposRequerimientosOutDto`
- ❌ **NEVER** use generic suffixes like `ItemDto`, `DataDto`, `InfoDto`
- ❌ **NEVER** omit the verb: `TipoRequerimientoOutDto` ❌ → `ObtenerTipoRequerimientoOutDto` ✅
- ❌ **NEVER** use suffixes that aren't `OutDto`: `Response`, `Result`, `Output`, `Data`

```php
// ✅ CORRECTLY NAMED - Use case verb + concept + OutDto
ObtenerTipoRequerimientoOutDto        // Single item
ObtenerTiposRequerimientosOutDto      // Collection
CrearSolicitudOutDto
ConsultarBeneficiarioOutDto
ListarSolicitudesOutDto
ObtenerResumenProgramaOutDto
GenerarReporteOutDto

// ❌ WRONG - Missing verb prefix
TipoRequerimientoItemDto              // WRONG: No verb + ItemDto suffix!
TipoRequerimientoOutDto               // WRONG: Missing verb!
TipoRequerimientoDto                  // WRONG: Missing verb + missing Out!
SolicitudDataDto                      // WRONG: No verb + DataDto suffix!
BeneficiarioInfoDto                   // WRONG: No verb + InfoDto suffix!

// ❌ WRONG - Other issues
GenerarSolicitudDto                   // Missing "Out"
GenerarSolicitudOutputDto             // "Output" instead of "Out"
OutDtoGenerarSolicitud                // Wrong order
GenerarSolicitudResponse              // Response implies infrastructure
GenerarSolicitudResult                // Ambiguous suffix
GetTipoRequerimientoOutDto            // English verb (use Obtener)
```

**Rules:**
- Suffix: `OutDto` (NEVER use `ItemDto`, `DataDto`, `InfoDto`, etc.)
- Prefix: Use case verb in Spanish (ALWAYS required)
- Format: `{VerbSpanish}{ConceptSpanish}OutDto`
- Singular/Plural: Match the data being returned
- Can have `toArray()` or `toJson()` methods
- No business logic

### Infrastructure Layer Naming

#### InAdapters (API)

**Format:** `{VerbSpanish}{NounSpanish}InAdapter`

**⚠️ CRITICAL RULES:**
- ✅ ALWAYS use Spanish verbs: Obtener, Crear, Actualizar, Eliminar, Listar, Generar, etc.
- ✅ ALWAYS end with `InAdapter` suffix
- ❌ NEVER use English verbs: Get, Create, Update, Delete, List, Generate, etc.
- ❌ NEVER use `Controller` suffix - This is NOT a Laravel controller pattern!
- ❌ NEVER use `*InController` - This violates hexagonal architecture principles!

**Spanish Verb Reference:**
| Spanish | English | Example |
|---------|---------|---------|
| Obtener | Get/Fetch/Retrieve | `ObtenerTiposRequerimientosInAdapter` |
| Crear | Create | `CrearSolicitudInAdapter` |
| Actualizar | Update | `ActualizarBeneficiarioInAdapter` |
| Eliminar | Delete | `EliminarDocumentoInAdapter` |
| Listar | List | `ListarSolicitudesInAdapter` |
| Generar | Generate | `GenerarReporteInAdapter` |
| Aprobar | Approve | `AprobarSolicitudInAdapter` |
| Rechazar | Reject | `RechazarSolicitudInAdapter` |
| Validar | Validate | `ValidarDocumentoInAdapter` |
| Verificar | Verify | `VerificarElegibilidadInAdapter` |
| Consultar | Query/Consult | `ConsultarBeneficiarioInAdapter` |
| Registrar | Register | `RegistrarPersonaInAdapter` |
| Enviar | Send | `EnviarNotificacionInAdapter` |
| Procesar | Process | `ProcesarPagoInAdapter` |

```php
// ✅ CORRECTLY NAMED - Spanish verb + InAdapter
ObtenerTiposRequerimientosInAdapter    // Obtener (not Get)
CrearSolicitudInAdapter                // Crear (not Create)
ActualizarBeneficiarioInAdapter        // Actualizar (not Update)
EliminarDocumentoInAdapter             // Eliminar (not Delete)
ListarSolicitudesInAdapter             // Listar (not List)
GenerarReporteInAdapter                // Generar (not Generate)
ConsultarBeneficiarioInAdapter         // Consultar (not Query)

// ❌ WRONG - English verbs or wrong suffix
GetTipoRequerimientoController         // WRONG: English verb + Controller!
GetTiposRequerimientosInAdapter        // WRONG: English verb!
ObtenerTiposRequerimientosController   // WRONG: Controller suffix!
TipoRequerimientoController            // WRONG: Missing verb + wrong suffix!
FetchTiposRequerimientosInAdapter      // WRONG: English verb!
RetrieveTiposInAdapter                 // WRONG: English verb!
CreateSolicitudInAdapter               // WRONG: English verb!
UpdateBeneficiarioInAdapter            // WRONG: English verb!
DeleteDocumentoInAdapter               // WRONG: English verb!

// Location: app/Core/{Module}/Infrastructure/Adapters/In/Api/
```

**Rules:**
- Suffix: `InAdapter` (NEVER `Controller`)
- Verb: ALWAYS in Spanish (infinitive form)
- Noun: Business concept in Spanish
- Location: `app/Core/{Module}/Infrastructure/Adapters/In/Api/`

#### InAdapters (CLI)

**Format:** `{Verbo}{Sustantivo}Command`

```php
// ✅ GOOD - CLI
GenerarSolicitudCommand
ProcesarSolicitudesPendientesCommand
GenerarReporteMensualCommand

// Location: app/Core/{Module}/Infrastructure/Adapters/In/Cli/
```

#### InAdapters (Web/Livewire)

**Format:** `{Verbo}{Sustantivo}Component`

```php
// ✅ GOOD - Livewire
GenerarSolicitudComponent
ListarSolicitudesComponent
FormularioBeneficiarioComponent

// Location: app/Core/{Module}/Infrastructure/Adapters/In/Web/
```

#### OutAdapters

**Format:** `{Concepto}{Tecnologia}OutAdapter`

```php
// ✅ GOOD
SolicitudMySQLOutAdapter
PersonaMySQLOutAdapter
NotificacionEmailOutAdapter
ArchivoS3OutAdapter
CacheFredisOutAdapter
EventoRabbitMQOutAdapter
ReporteExcelOutAdapter

// ❌ BAD
SolicitudOutAdapter             // Missing technology
MySQLSolicitudOutAdapter        // Wrong order
SolicitudAdapter                // Missing "Out" and technology
SolicitudMySQLAdapter           // Missing "Out"
OutAdapterSolicitudMySQL        // Wrong order
```

**Rules:**
- Suffix: `OutAdapter`
- Format: Concept + Technology + OutAdapter
- Implements OutPort interface
- Technology examples: MySQL, S3, Redis, Email, RabbitMQ

#### Repositories

**Format:** `{Concepto}{Tecnologia}Repository`

```php
// ✅ GOOD
SolicitudMySQLRepository
PersonaMySQLRepository
DocumentoS3Repository
CacheRedisRepository
LogElasticsearchRepository

// ❌ BAD
SolicitudRepository             // Missing technology
RepositorySolicitudMySQL        // Wrong order
SolicitudRepo                   // Abbreviated
MySQLSolicitudRepository        // Wrong order
```

**Rules:**
- Suffix: `Repository`
- Format: Concept + Technology + Repository
- Used by OutAdapters
- Contains raw queries

#### OutAdapter's injected Repository property

**Format:** `${nameRepositoryClass}` — camelCase of the injected Repository's class name, never
the generic `$repository`.

```php
// ✅ GOOD
final class SolicitudMySQLOutAdapter implements ISolicitudOutPort
{
    public function __construct(
        private SolicitudMySQLRepository $solicitudMySQLRepository
    ) {}
}

// ❌ BAD
final class SolicitudMySQLOutAdapter implements ISolicitudOutPort
{
    public function __construct(
        private SolicitudMySQLRepository $repository   // Generic name, loses which Repository it is
    ) {}
}
```

#### UseCase's injected OutPort property

**Format:** `${namePortInterface}` — camelCase of the injected OutPort interface's name, never the
generic `$outPort`. Same rationale as the Repository property above: a generic name loses which
port is actually injected once a UseCase depends on more than one.

```php
// ✅ GOOD
final readonly class ObtenerBasesDatosUseCase
{
    public function __construct(
        private BaseDatosOutPort $baseDatosOutPort
    ) {}

    public function execute(): array
    {
        return $this->baseDatosOutPort->obtenerBasesDatos();
    }
}

// ❌ BAD
final readonly class ObtenerBasesDatosUseCase
{
    public function __construct(
        private BaseDatosOutPort $outPort   // Generic name, loses which OutPort it is
    ) {}
}
```

### Method Naming

#### Entity Methods

```php
// ✅ GOOD - Business behavior
public function aprobar(): void
public function rechazar(string $motivo): void
public function estaAprobada(): bool
public function puedeSerModificada(): bool
public function calcularMontoTotal(): MontoVO
public function adjuntarDocumento(DocumentoEntity $doc): void

// ❌ BAD
public function setEstatus(string $estatus): void     // Anemic
public function getEstatus(): string                   // Anemic
public function aprobado(): bool                       // Past tense (use "esta")
public function approve(): void                        // English
```

**Rules:**
- Action verbs in infinitive
- Question verbs return bool
- Prefix `esta` for state queries
- NO generic setters/getters

#### Value Object Methods

```php
// ✅ GOOD
public function valor(): string              // Get the value
public function equals(CurpVO $otra): bool   // Compare
public function formato(): string            // Return formatted value
public static function desde(string $valor): self  // Named constructor

// ❌ BAD
public function getValue(): string           // English
public function getValor(): string           // Generic getter
public function estaVacio(): bool            // VOs are never empty (validated in constructor)
```

#### Use Case Methods

```php
// ✅ GOOD
public function ejecutar(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto

// ❌ BAD
public function handle(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto  // Laravel-specific
public function execute(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto // English
public function run(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto     // Ambiguous
public function generarSolicitud(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto // Redundant
```

**Rules:**
- Always use `ejecutar()`
- Receives InDto
- Returns OutDto

#### Repository Methods

```php
// ✅ GOOD
public function insertar(array $data): int
public function actualizar(int $id, array $data): bool
public function eliminar(int $id): bool
public function findById(int $id): ?object
public function findByCurp(string $curp): ?object
public function findAll(): array
public function existeByCurp(string $curp): bool

// ❌ BAD
public function save(array $data): int           // Ambiguous (insert or update?)
public function delete(int $id): bool            // English
public function getByCurp(string $curp): ?object // get instead of find
public function obtenerPorId(int $id): ?object   // Spanish for technical method
```

**Rules:**
- Use Spanish for business operations: `insertar`, `actualizar`, `eliminar`
- Use English for queries: `findById`, `findByCurp`, `existeBy`
- Prefix `existe` for boolean checks
- Return `?object` for single records, `array` for collections

#### OutPort Methods

```php
// ✅ GOOD
public function persistir(SolicitudEntity $solicitud): int
public function buscarPorId(int $id): ?SolicitudEntity
public function buscarPorCurp(CurpVO $curp): array
public function existe(int $id): bool
public function eliminar(int $id): bool

// ❌ BAD
public function save(SolicitudEntity $solicitud): int        // English
public function find(int $id): ?SolicitudEntity              // Ambiguous
public function getPorId(int $id): ?SolicitudEntity          // get instead of buscar
public function check(int $id): bool                         // Ambiguous
```

### File Naming

**Rules:**
- File name MUST match class name exactly
- One class per file
- File extension: `.php`

```
// ✅ GOOD
SolicitudEntity.php
CurpVO.php
GenerarSolicitudUseCase.php
IGenerarSolicitudInPort.php
GenerarSolicitudInDto.php
SolicitudMySQLOutAdapter.php

// ❌ BAD
solicitud-entity.php            // Wrong case
Solicitud.php                   // Missing suffix
generarsolicitud.php            // Wrong case
SolicitudEntityClass.php        // Extra suffix
```

### Variable Naming

```php
// ✅ GOOD - Descriptive and clear
$solicitud
$beneficiario
$curp
$folio
$personaActiva
$solicitudesAprobadas
$montoMaximo

// ❌ BAD - Abbreviated or ambiguous
$sol          // Too short
$ben          // Abbreviated
$c            // Single letter
$data         // Too generic
$temp         // Temporary variable name
$arr          // Generic array
```

### Constant Naming

```php
// ✅ GOOD - UPPER_SNAKE_CASE
const EDAD_MINIMA = 18;
const SUPERFICIE_MAXIMA_HECTAREAS = 50.0;
const FORMATO_FECHA = 'Y-m-d';
const LONGITUD_CURP = 18;

// ❌ BAD
const EdadMinima = 18;           // Wrong case
const edad_minima = 18;          // Wrong case
const EDADMINIMA = 18;           // No underscores
```

### Test Naming

**Format:** `{debe}_{resultado_esperado}_cuando_{condicion}`

```php
// ✅ GOOD
public function debe_generar_solicitud_cuando_datos_son_validos(): void
public function debe_lanzar_excepcion_cuando_curp_es_invalida(): void
public function debe_retornar_null_cuando_persona_no_existe(): void
public function debe_aprobar_solicitud_cuando_cumple_requisitos(): void

// ❌ BAD
public function test_generar_solicitud(): void              // Not descriptive
public function testGenerarSolicitud(): void                // Not descriptive
public function generarSolicitudConDatosValidos(): void     // Missing "debe"
public function should_generate_request(): void             // English
public function it_generates_request(): void                // Different convention
```

**Rules:**
- Start with `debe_`
- Use underscores to separate words
- Format: `debe_{action}_cuando_{condition}`
- Be explicit about expected behavior

### Quick Reference Table

| Artifact | Format | Example | Location |
|----------|--------|---------|----------|
| **Entity** | `{Concepto}Entity` | `SolicitudEntity` | `Domain/Entities/` |
| **Value Object** | `{Concepto}VO` | `CurpVO` | `Domain/Vo/` |
| **Aggregate** | `{Concepto}Aggregate` | `SolicitudBeneficioAggregate` | `Domain/Aggregates/` |
| **Enum** | `{Concepto}Enum` | `EstatusSolicitudEnum` | `Domain/Enums/` |
| **Specification** | `{Criterio}Specification` | `EdadMinimaSpecification` | `Domain/Specifications/` |
| **Domain Service** | `{Concepto}DomainService` | `ElegibilidadBeneficiarioDomainService` | `Domain/Services/` |
| **Domain Event** | `{AccionPasada}Event` | `SolicitudGeneradaEvent` | `Domain/Events/` |
| **Domain Exception** | `{Problema}Exception` | `CurpInvalidaException` | `Domain/Exceptions/` |
| **Use Case** | `{Verbo}{Sustantivo}UseCase` | `GenerarSolicitudUseCase` | `Application/UseCases/` |
| **InPort** | `I{Verbo}{Sustantivo}InPort` | `IGenerarSolicitudInPort` | `Application/Ports/In/` |
| **OutPort** | `I{Concepto}OutPort` | `ISolicitudOutPort` | `Application/Ports/Out/` |
| **InDto** | `{Verbo}{Sustantivo}InDto` | `GenerarSolicitudInDto` | `Application/Dtos/In/` |
| **OutDto** | `{Verbo}{Sustantivo}OutDto` | `GenerarSolicitudOutDto` | `Application/Dtos/Out/` |
| **InAdapter (API)** | `{Verbo}{Sustantivo}InAdapter` | `GenerarSolicitudInAdapter` | `Infrastructure/Adapters/In/Api/` |
| **InAdapter (CLI)** | `{Verbo}{Sustantivo}Command` | `GenerarSolicitudCommand` | `Infrastructure/Adapters/In/Cli/` |
| **InAdapter (Web)** | `{Verbo}{Sustantivo}Component` | `GenerarSolicitudComponent` | `Infrastructure/Adapters/In/Web/` |
| **OutAdapter** | `{Concepto}{Tech}OutAdapter` | `SolicitudMySQLOutAdapter` | `Infrastructure/Adapters/Out/{Type}/` |
| **Repository** | `{Concepto}{Tech}Repository` | `SolicitudMySQLRepository` | `Infrastructure/.../Repositories/` |

### Consistency Checklist

Before finalizing code, verify:
- ✅ All classes follow naming convention
- ✅ File names match class names
- ✅ Suffixes are correct (Entity, VO, UseCase, etc.)
- ✅ Domain concepts use Spanish
- ✅ Technical terms use English
- ✅ No abbreviations
- ✅ Methods are descriptive