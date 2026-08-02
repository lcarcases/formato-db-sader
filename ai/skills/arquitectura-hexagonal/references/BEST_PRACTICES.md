### Core Principles

- **Aplicar principios SOLID** en todas las capas
  - Single Responsibility: Cada clase tiene una única razón para cambiar
  - Open/Closed: Abierto para extensión, cerrado para modificación
  - Liskov Substitution: Las interfaces deben ser intercambiables
  - Interface Segregation: Interfaces específicas mejor que una general
  - Dependency Inversion: Depender de abstracciones, no de concreciones

- **Reglas de las capas** (capa interna no invoca código de capa externa)

- **Evitar antipatrones** (anemic domain, layer leakage, fat adapters)

- **Mantener la lógica de negocio libre de framework**

---

## 🚨 CRITICAL: Avoid Anemic Entities

**ANEMIC ENTITIES ARE THE #1 DDD ANTI-PATTERN!**

### What is an Anemic Entity?

An entity that:
- ❌ Has ONLY getter/setter methods
- ❌ Has ONLY `toArray()` or `toJson()` (NOT business logic!)
- ❌ Has NO business behavior
- ❌ Has NO business rules
- ❌ Is just a "data holder"

**This is NOT an Entity — it's a DTO!**

### Example of Anemic Entity (FORBIDDEN):

```php
// ❌ FORBIDDEN: Anemic Entity
final readonly class TipoRequerimientoEntity
{
    public function __construct(
        private int $id,
        private string $nombre
    ) {}

    // ❌ Only getters
    public function getId(): int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }

    // ❌ toArray is NOT business logic!
    public function toArray(): array {
        return ['id' => $this->id, 'nombre' => $this->nombre];
    }
}

// ✅ CORRECT: Use DTO instead!
final readonly class TipoRequerimientoOutDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre
    ) {}
    
    public function toArray(): array {
        return ['id' => $this->id, 'nombre' => $this->nombre];
    }
}
```

### How to Identify Anemic Entities

Ask yourself:
1. **Does this entity have business behavior?** (methods beyond getters)
2. **Does it protect invariants?** (validation in constructor)
3. **Does it manage state/lifecycle?** (activate, deactivate, etc.)
4. **Does it contain business logic?** (calculations, rules, decisions)

**If NO to all → It's anemic! Use DTO instead.**

### Read the Complete Guide

**📖 [ENTITY_VS_DTO_DECISION_GUIDE.md](ENTITY_VS_DTO_DECISION_GUIDE.md)**

---

### Domain Layer Best Practices

- **Ubiquitous Language**: Usar el lenguaje del negocio en nombres de clases, métodos y variables
  ```php
  // ✅ GOOD - Ubiquitous Language
  class SolicitudEntity {
      public function aprobar(): void {}
  }
  
  // ❌ BAD - Technical language
  class RequestEntity {
      public function setStatus(int $status): void {}
  }
  ```

- **Rich Domain Models**: Las entidades deben tener comportamiento, no solo getters/setters
  ```php
  // ✅ GOOD - Rich Domain
  class SolicitudEntity {
      public function rechazar(string $motivo): void {
          $this->validarPuedeSerRechazada();
          $this->estatus = EstatusSolicitudEnum::RECHAZADA;
          $this->motivoRechazo = $motivo;
          $this->fechaRechazo = new DateTimeImmutable();
      }
  }
  
  // ❌ BAD - Anemic Domain
  class SolicitudEntity {
      public function setEstatus(string $estatus): void {
          $this->estatus = $estatus;
      }
  }
  ```

- **Immutable Value Objects**: Los VOs nunca deben cambiar después de su creación
  ```php
  // ✅ GOOD - Immutable
  class MontoVO {
      private function __construct(
          private readonly float $valor,
          private readonly string $moneda
      ) {}
      
      public function incrementar(float $cantidad): self {
          return new self($this->valor + $cantidad, $this->moneda);
      }
  }
  
  // ❌ BAD - Mutable
  class MontoVO {
      public function setValor(float $valor): void {
          $this->valor = $valor;
      }
  }
  ```

- **Self-Validating Entities/VOs**: Validar en el constructor para garantizar objetos siempre válidos
  ```php
  // ✅ GOOD - Self-validating
  class EmailVO {
      public function __construct(private readonly string $valor) {
          if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
              throw new EmailInvalidoException($valor);
          }
      }
  }
  ```

- **Domain Events**: Emitir eventos para comunicar cambios de estado importantes
  ```php
  class SolicitudEntity {
      private array $domainEvents = [];
      
      public function aprobar(): void {
          // ... business logic
          $this->domainEvents[] = new SolicitudAprobadaEvent($this->id);
      }
      
      public function pullDomainEvents(): array {
          $events = $this->domainEvents;
          $this->domainEvents = [];
          return $events;
      }
  }
  ```

### Application Layer Best Practices

- **Use Case per Action**: Un caso de uso debe hacer una sola cosa
  ```php
  // ✅ GOOD - Single responsibility
  class GenerarSolicitudUseCase {}
  class AprobarSolicitudUseCase {}
  class RechazarSolicitudUseCase {}
  
  // ❌ BAD - Multiple responsibilities
  class GestionarSolicitudUseCase {
      public function generar() {}
      public function aprobar() {}
      public function rechazar() {}
  }
  ```

- **DTO Immutability**: Los DTOs deben ser inmutables (readonly properties)
  ```php
  // ✅ GOOD
  class GenerarSolicitudInDto {
      public function __construct(
          public readonly string $curp,
          public readonly string $programa
      ) {}
  }
  ```

- **Fail Fast**: Validar datos lo antes posible
  ```php
  public function ejecutar(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto
  {
      // Validate and transform to VOs immediately
      $curp = new CurpVO($dto->curp); // Fails if invalid
      
      // Continue with business logic...
  }
  ```

- **No Business Logic in DTOs**: Los DTOs solo transportan datos
  ```php
  // ✅ GOOD
  class SolicitudOutDto {
      public function toArray(): array { return [...]; }
  }
  
  // ❌ BAD
  class SolicitudOutDto {
      public function calcularTotal(): float { return ...; }
  }
  ```

### Infrastructure Layer Best Practices

- **Dependency Injection**: Siempre inyectar dependencias, nunca instanciar directamente
  ```php
  // ✅ GOOD
  public function __construct(
      private ISolicitudOutPort $solicitudOutPort
  ) {}
  
  // ❌ BAD
  public function __construct() {
      $this->solicitudOutPort = new SolicitudMySQLOutAdapter();
  }
  ```

- **Adapter Pattern**: Los adaptadores solo traducen entre capas, sin lógica de negocio
  ```php
  // ✅ GOOD - Simple translation
  class SolicitudMySQLOutAdapter implements ISolicitudOutPort {
      public function persistir(SolicitudEntity $solicitud): int {
          return $this->repository->insertar([
              'folio' => $solicitud->getFolio()->valor(),
              'estatus' => $solicitud->getEstatus()->value
          ]);
      }
  }
  
  // ❌ BAD - Business logic in adapter
  class SolicitudMySQLOutAdapter implements ISolicitudOutPort {
      public function persistir(SolicitudEntity $solicitud): int {
          if ($solicitud->getEstatus() === 'aprobada') {
              // Send notification... ❌ This is business logic!
          }
          return $this->repository->insertar([...]);
      }
  }
  ```

- **Repository Responsibility**: Los repositories solo acceden a datos, no contienen lógica de negocio
  ```php
  // ✅ GOOD
  class SolicitudMySQLRepository {
      public function findByEstatus(string $estatus): array {
          return DB::table('solicitudes')
              ->where('estatus', $estatus)
              ->get()
              ->toArray();
      }
  }
  ```

- **Map External Data to Domain**: Siempre convertir datos externos a objetos de dominio
  ```php
  // ✅ GOOD
  private function mapToEntity(object $data): PersonaEntity {
      return new PersonaEntity(
          id: $data->id,
          curp: new CurpVO($data->curp),
          nombre: $data->nombre
      );
  }
  
  // ❌ BAD - Returning raw data
  public function buscarPorCurp(CurpVO $curp): object {
      return DB::table('personas')->where('curp', $curp->valor())->first();
  }
  ```

### Testing Best Practices

- **Test Behavior, Not Implementation**: Probar qué hace, no cómo lo hace
  ```php
  // ✅ GOOD
  public function debe_generar_folio_unico_cuando_se_crea_solicitud(): void
  
  // ❌ BAD
  public function debe_llamar_metodo_generarFolio_del_servicio(): void
  ```

- **Given-When-Then Structure**: Estructura clara en todos los tests
  ```php
  public function test_example(): void
  {
      // GIVEN (arrange)
      $solicitud = new SolicitudEntity(...);
      
      // WHEN (act)
      $resultado = $solicitud->aprobar();
      
      // THEN (assert)
      $this->assertEquals(EstatusSolicitudEnum::APROBADA, $solicitud->getEstatus());
  }
  ```

- **Mock External Dependencies**: Mockear OutPorts, no el dominio
  ```php
  // ✅ GOOD - Mock infrastructure
  $personaOutPort = $this->createMock(IPersonaOutPort::class);
  
  // ❌ BAD - Mock domain
  $persona = $this->createMock(PersonaEntity::class);
  ```

- **Test Edge Cases**: Probar límites y casos excepcionales
  ```php
  public function debe_lanzar_excepcion_cuando_monto_es_negativo(): void
  public function debe_lanzar_excepcion_cuando_curp_es_invalida(): void
  public function debe_manejar_lista_vacia_de_solicitudes(): void
  ```

### Error Handling Best Practices

- **Domain Exceptions**: Crear excepciones específicas del negocio
  ```php
  // ✅ GOOD - Domain exception
  class SolicitudNoAprobableException extends DomainException {
      public function __construct(string $estatus) {
          parent::__construct("No se puede aprobar una solicitud en estatus: {$estatus}");
      }
  }
  
  // ❌ BAD - Generic exception
  throw new Exception("Error");
  ```

- **Catch at Adapter Level**: Manejar excepciones en los adaptadores de entrada
  ```php
  public function __invoke(Request $request): JsonResponse
  {
      try {
          $outDto = $this->useCase->ejecutar($inDto);
          return Respuesta::exito($outDto->toArray());
      } catch (CurpInvalidaException $e) {
          return Respuesta::error($e->getMessage(), 400);
      } catch (DomainException $e) {
          return Respuesta::error($e->getMessage(), 422);
      } catch (\Exception $e) {
          Log::error($e);
          return Respuesta::error('Error interno', 500);
      }
  }
  ```

### When to use Shared Module (Core/Shared)

[SHARED_MODULE.md](/references/SHARED_MODULE.md)

### Performance Best Practices

- **Lazy Loading**: Cargar datos solo cuando sean necesarios
  ```php
  public function buscarConDocumentos(int $id): ?SolicitudEntity
  {
      $solicitud = $this->buscarPorId($id);
      if ($solicitud && $this->needsDocuments()) {
          $solicitud->cargarDocumentos($this->documentoRepository->findBySolicitud($id));
      }
      return $solicitud;
  }
  ```

- **Batch Operations**: Procesar en lotes cuando sea posible
  ```php
  public function persistirMultiples(array $solicitudes): array
  {
      $ids = [];
      DB::transaction(function() use ($solicitudes, &$ids) {
          foreach ($solicitudes as $solicitud) {
              $ids[] = $this->persistir($solicitud);
          }
      });
      return $ids;
  }
  ```

### Code Organization Best Practices

- **One Class Per File**: Cada clase en su propio archivo
- **Namespace Matches Folder Structure**: Los namespaces deben reflejar la estructura de carpetas
- **Consistent Naming**: Seguir convenciones de nomenclatura estrictas
- **Group Related Concepts**: Agrupar conceptos relacionados en el mismo módulo

### Documentation Best Practices

- **Document Business Rules**: Documentar reglas de negocio complejas
  ```php
  /**
   * Genera un folio único siguiendo el formato:
   * AA-PROGRAMA-YYYY-NNNNNN-LNNN-EE
   * 
   * Donde:
   * - AA: Siglas del estado
   * - PROGRAMA: Clave del programa
   * - YYYY: Año
   * - NNNNNN: Consecutivo
   * - LNNN: Localidad
   * - EE: Entidad
   */
  public static function generar(ProgramaEntity $programa, string $estado): self
  ```

- **PHPDoc for Complex Methods**: Documentar métodos no obvios
  ```php
  /**
   * Verifica si el beneficiario es elegible para el programa.
   * 
   * @param BeneficiarioEntity $beneficiario
   * @param ProgramaEntity $programa
   * @return bool True si cumple todas las reglas de elegibilidad
   * @throws BeneficiarioNoElegibleException Si no cumple requisitos
   */
  ```

### Refactoring Best Practices

- **Small Commits**: Commits pequeños y atómicos
- **Test Before Refactor**: Tener tests antes de refactorizar
- **Refactor Continuously**: Mejorar código continuamente, no en grandes cambios
- **Extract Methods**: Métodos pequeños y con nombres descriptivos
  ```php
  // ✅ GOOD
  public function ejecutar(GenerarSolicitudInDto $dto): GenerarSolicitudOutDto
  {
      $persona = $this->validarYObtenerPersona($dto->curp);
      $programa = $this->validarYObtenerPrograma($dto->programa);
      $solicitud = $this->crearSolicitud($persona, $programa);
      
      return $this->persistirYRetornarRespuesta($solicitud);
  }
  ```

### Security Best Practices

- **Input Validation**: Validar todas las entradas en Value Objects
- **Output Encoding**: Escapar outputs en adaptadores de salida
- **No Sensitive Data in Logs**: No loguear información sensible
  ```php
  // ✅ GOOD
  Log::info('Solicitud generada', ['folio' => $folio]);
  
  // ❌ BAD
  Log::info('Solicitud generada', ['curp' => $curp, 'rfc' => $rfc]);
  ```

### Maintainability Best Practices

- **Boy Scout Rule**: Dejar el código mejor de como lo encontraste
- **DRY (Don't Repeat Yourself)**: Evitar duplicación de código
- **KISS (Keep It Simple, Stupid)**: Preferir soluciones simples
- **YAGNI (You Aren't Gonna Need It)**: No agregar funcionalidad que no se necesita ahora

### Code Review Checklist

Before submitting code, verify:
- ✅ Layer boundaries respected
- ✅ No framework coupling in Domain/Application
- ✅ All dependencies injected via interfaces
- ✅ Tests written and passing
- ✅ Naming conventions followed
- ✅ No code duplication
- ✅ Domain exceptions used for business errors
- ✅ DTOs are immutable
- ✅ Value Objects validate in constructor
- ✅ Entities have behavior (not anemic)