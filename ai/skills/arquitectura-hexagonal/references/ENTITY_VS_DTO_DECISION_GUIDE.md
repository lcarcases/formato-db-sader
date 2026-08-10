# 🚨 CRITICAL: Entity vs DTO Decision Guide

## The Anemic Entity Problem

**ANEMIC ENTITIES ARE FORBIDDEN!** An entity with only getters and no business logic is actually just a DTO in disguise and violates DDD principles.

---

## ❌ What is an Anemic Entity?

An anemic entity is a **DDD anti-pattern** that has:
- **Only getter methods** (and maybe setters)
- **No business behavior**
- **No domain logic**
- **No invariant protection**

### Example of Anemic Entity (FORBIDDEN):

```php
// ❌ FORBIDDEN: This is NOT an Entity - it's an anemic data holder!
final readonly class TipoRequerimientoEntity
{
    public function __construct(
        private int $idRequerimiento,
        private string $requerimiento
    ) {}

    public function getIdRequerimiento(): int {
        return $this->idRequerimiento;
    }

    public function getRequerimiento(): string {
        return $this->requerimiento;
    }

    public function toArray(): array {  // ❌ toArray is NOT business logic!
        return [
            'idRequerimiento' => $this->idRequerimiento,
            'requerimiento' => $this->requerimiento,
        ];
    }
}
```

**Why is this wrong?**
1. ❌ Only has getters (no behavior)
2. ❌ `toArray()` is NOT business logic (it's data formatting)
3. ❌ No business rules to enforce
4. ❌ No lifecycle management
5. ❌ No state changes
6. ❌ This should be an **OutDTO**, not an Entity!

---

## ✅ What Makes Something an Entity?

An Entity **MUST HAVE** at least one of these characteristics:

### 1. **Business Behavior** (Methods that enforce business rules)
```php
// ✅ CORRECT: Has business behavior
class SolicitudEntity
{
    private EstatusSolicitudEnum $estatus;
    
    // ✅ Business behavior: enforces rules about state transitions
    public function aprobar(): void
    {
        if ($this->estatus !== EstatusSolicitudEnum::EN_REVISION) {
            throw new SolicitudNoAprobableException(
                "Solo se pueden aprobar solicitudes en revisión"
            );
        }
        $this->estatus = EstatusSolicitudEnum::APROBADA;
        $this->fechaAprobacion = new DateTimeImmutable();
    }
    
    // ✅ Business query: domain logic to determine state
    public function puedeSerModificada(): bool
    {
        return $this->estatus === EstatusSolicitudEnum::BORRADOR;
    }
}
```

### 2. **Invariant Protection** (Constructor validates business rules)
```php
// ✅ CORRECT: Protects business invariants
class SolicitudEntity
{
    public function __construct(
        private int $id,
        private MontoVO $monto,
        private FolioVO $folio
    ) {
        // ✅ Business rule: monto must be positive
        if ($monto->valor() <= 0) {
            throw new MontoInvalidoException("El monto debe ser mayor a cero");
        }
        
        // ✅ Business rule: folio must follow specific format
        if (!$folio->esValido()) {
            throw new FolioInvalidoException("Formato de folio incorrecto");
        }
    }
}
```

### 3. **State Management** (Lifecycle and state transitions)
```php
// ✅ CORRECT: Manages lifecycle and state
class BeneficiarioEntity
{
    private bool $activo;
    private ?DateTimeImmutable $fechaBaja;
    
    // ✅ Business behavior: manages entity lifecycle
    public function darDeBaja(string $motivo): void
    {
        if (!$this->activo) {
            throw new BeneficiarioYaInactivoException();
        }
        
        $this->activo = false;
        $this->fechaBaja = new DateTimeImmutable();
        $this->motivoBaja = $motivo;
    }
    
    public function reactivar(): void
    {
        if ($this->activo) {
            throw new BeneficiarioYaActivoException();
        }
        
        $this->activo = true;
        $this->fechaBaja = null;
        $this->motivoBaja = null;
    }
}
```

### 4. **Complex Domain Logic** (Calculations, aggregations, rules)
```php
// ✅ CORRECT: Contains complex domain logic
class CarritoCompraEntity
{
    private array $items = [];
    
    // ✅ Business calculation
    public function calcularTotal(): MontoVO
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->subtotal();
        }
        
        // ✅ Business rule: apply discount if total exceeds threshold
        if ($total > 1000.0) {
            $total *= 0.9; // 10% discount
        }
        
        return new MontoVO($total, 'MXN');
    }
    
    // ✅ Business behavior: validates business rules
    public function agregarItem(ProductoVO $producto, int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new CantidadInvalidaException();
        }
        
        if (count($this->items) >= 50) {
            throw new CarritoLlenoException("Máximo 50 items por carrito");
        }
        
        $this->items[] = new ItemCarrito($producto, $cantidad);
    }
}
```

---

## 🎯 Decision Tree: Entity or DTO?

```
Is this data from the database?
    │
    ├─► Does it have business behavior (methods beyond getters)?
    │       │
    │       ├─► YES → ✅ ENTITY
    │       │
    │       └─► NO ↓
    │
    ├─► Does it protect business invariants?
    │       │
    │       ├─► YES → ✅ ENTITY
    │       │
    │       └─► NO ↓
    │
    ├─► Does it manage state changes/lifecycle?
    │       │
    │       ├─► YES → ✅ ENTITY
    │       │
    │       └─► NO ↓
    │
    ├─► Does it contain complex domain logic?
    │       │
    │       ├─► YES → ✅ ENTITY
    │       │
    │       └─► NO ↓
    │
    └─► Is it just a data holder (catalog, lookup, reference data)?
            │
            └─► YES → ✅ USE DTO (NOT Entity!)
```

---

## 📋 Common Scenarios

### ✅ Should be Entity:
- **User/Beneficiario**: Has lifecycle (activate/deactivate), business rules
- **Solicitud/Order**: Has state transitions (draft → submitted → approved)
- **Carrito/Cart**: Has calculations, aggregations, capacity limits
- **Cuenta/Account**: Has balance management, transaction rules
- **Documento**: Has validation rules, versioning, approval workflow

### ❌ Should be DTO (NOT Entity!):
- **TipoRequerimiento**: Just lookup/catalog data (ALTA, BAJA, etc.)
- **EstadoCivil**: Static reference data (Soltero, Casado, Divorciado)
- **Pais/State/City**: Geographic reference data
- **TipoDocumento**: Document type catalog
- **Categoria/Tag**: Simple classification data
- **Configuration Settings**: Read-only system config

---

## 🔍 Special Case: Catalog/Reference Data Pattern

For **catalog tables** (tipos, categorías, clasificaciones), use this pattern:

### ❌ WRONG: Creating an Entity
```php
// ❌ DON'T DO THIS - Anemic entity for catalog data
class TipoRequerimientoEntity
{
    public function __construct(
        private int $id,
        private string $nombre
    ) {}
    
    public function getId(): int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
}
```

### ✅ CORRECT: Use OutDTO or Enum

**Option 1: OutDTO** (when data comes from database)
```php
// ✅ CORRECT: Simple DTO for catalog data
class TipoRequerimientoOutDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre
    ) {}
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}

// UseCase returns raw data to make it more reusable
class ObtenerTiposRequerimientosUseCase
{
    public function obtener(): array
    {
        $data = $this->adapter->obtenerTodos();
        
        $info = new \stdClass();
        $info->nombreRequerimiento = $data["nombreRequerimiento"];
        $info->tipoRequerimiento = $data["tipoRequerimiento"];

        return info;
    }
}
```

**Option 2: Enum** (when values are fixed and known at compile-time)
```php
// ✅ BEST: Use Enum for fixed catalog values
enum TipoRequerimientoEnum: string
{
    case ALTA = 'ALTA';
    case BAJA = 'BAJA';
    case MODIFICACION = 'MODIFICACION';
    case VALIDACION_PERMANENCIA = 'VALIDACION DE PERMANENCIA';
    
    public function label(): string
    {
        return match($this) {
            self::ALTA => 'Alta de beneficiario',
            self::BAJA => 'Baja de beneficiario',
            self::MODIFICACION => 'Modificación de datos',
            self::VALIDACION_PERMANENCIA => 'Validación de permanencia',
        };
    }
}
```

---

## ⚠️ What is NOT Business Logic

These methods **DO NOT** qualify as business logic:

| Method Type | Example | Is Business Logic? |
|-------------|---------|-------------------|
| Simple getters | `getIdRequerimiento()` | ❌ NO |
| Simple setters | `setNombre($nombre)` | ❌ NO |
| Data formatting | `toArray()`, `toJson()` | ❌ NO |
| Data conversion | `toDTO()`, `fromArray()` | ❌ NO |
| Simple accessors | `id()`, `nombre()` | ❌ NO |

These methods **DO** qualify as business logic:

| Method Type | Example | Is Business Logic? |
|-------------|---------|-------------------|
| State transitions | `aprobar()`, `rechazar()` | ✅ YES |
| Rule validation | `puedeSerAprobada()` | ✅ YES |
| Calculations | `calcularTotal()`, `aplicarDescuento()` | ✅ YES |
| Business queries | `estaVencida()`, `esElegible()` | ✅ YES |
| Lifecycle management | `activar()`, `darDeBaja()` | ✅ YES |
| Complex validation | `cumpleRequisitos()` | ✅ YES |

---

## 🛠️ How to Fix Anemic Entities

If you've already created an anemic entity, here's how to fix it:

### Step 1: Identify the Problem
Ask yourself:
- Does this entity have ANY business behavior beyond getters?
- Does it protect ANY invariants?
- Does it manage ANY lifecycle or state?

If **NO** to all → It's anemic!

### Step 2: Determine the Correct Approach

**Path A: Convert to DTO**
If it's just catalog/reference data:
```php
// BEFORE: Anemic Entity
class TipoRequerimientoEntity { /* only getters */ }

// AFTER: OutDTO  
class TipoRequerimientoOutDto {
    public function __construct(
        public readonly int $id,
        public readonly string $nombre
    ) {}
}
```

**Path B: Add Real Business Behavior**
If it genuinely should be an entity but lacks behavior, add it:
```php
// BEFORE: Anemic
class SolicitudEntity {
    public function getEstatus(): string { return $this->estatus; }
    public function setEstatus(string $estatus): void { $this->estatus = $estatus; }
}

// AFTER: Rich Domain Model
class SolicitudEntity {
    public function aprobar(): void {
        if (!$this->puedeSerAprobada()) {
            throw new SolicitudNoAprobableException();
        }
        $this->estatus = EstatusSolicitudEnum::APROBADA;
        $this->fechaAprobacion = new DateTimeImmutable();
    }
    
    private function puedeSerAprobada(): bool {
        return $this->estatus === EstatusSolicitudEnum::EN_REVISION
            && $this->documentacion->estaCompleta();
    }
}
```

---

## 📚 Summary Rules

### ✅ DO:
- Create Entities **ONLY** when they have business behavior
- Use DTOs for simple data transfer (catalog data, API responses)
- Use Enums for fixed catalog values
- Add meaningful business methods to Entities
- Protect invariants in Entity constructors
- Manage entity lifecycle with domain methods

### ❌ DON'T:
- Create Entities with only getters/setters
- Consider `toArray()` or `toJson()` as business logic
- Create Entities for catalog/lookup tables
- Create Entities just because data comes from a database table
- Use setters for state changes (use domain methods instead)
- Leave Entities without any behavior

---

## 🎓 Key Takeaway

**If an "Entity" has only getters and no business logic, it's NOT an Entity—it's a DTO!**

Always ask: **"What business behavior does this entity provide?"**

If the answer is **"None, it just holds data"** → Use a DTO, not an Entity.
