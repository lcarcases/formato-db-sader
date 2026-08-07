# 🔍 Identifying Domain Concepts

## ⚠️ CRITICAL WARNING: Entity vs DTO

**BEFORE creating any Entity, read:** [ENTITY_VS_DTO_DECISION_GUIDE.md](ENTITY_VS_DTO_DECISION_GUIDE.md)

**🚨 Anemic Entities are FORBIDDEN!**

If your "Entity" only has:
- ❌ Getter methods
- ❌ `toArray()` or `toJson()` methods
- ❌ No business behavior
- ❌ No business rules

→ **IT'S NOT AN ENTITY!** Use a DTO instead.

**Entity Requirements:**
- ✅ MUST have business behavior (methods that enforce rules)
- ✅ MUST protect invariants
- ✅ MUST manage state/lifecycle

**Common Mistake:**
```php
// ❌ WRONG: This is NOT an Entity!
class TipoRequerimientoEntity {
    public function getId(): int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function toArray(): array { return [...]; } // NOT business logic!
}

// ✅ CORRECT: Use DTO for catalog/lookup data
class TipoRequerimientoOutDto {
    public function __construct(
        public readonly int $id,
        public readonly string $nombre
    ) {}
}
```

---

## Decision Tree: Which Domain Concept?

```
¿Es un concepto/sustantivo?

│
├─► ¿Tiene identidad única (ID)?
│   │
│   └─► SÍ → ¿Tiene comportamiento de negocio (más allá de getters)?
│              │
│              ├─► SÍ → ✅ ENTIDAD (p. ej., Solicitud, Beneficiario)
│              │
│              └─► NO → ⚠️ ¡USA DTO! No es una entidad real
│                        (p. ej., TipoRequerimiento, EstadoCivil)
│
├─► ¿Es inmutable y se identifica mediante valores de atributos?
│
│ └─► SÍ → OBJETO DE VALOR (p. ej., CURP, RFC, Dirección, MontoBeneficioVO)
│
├─► ¿Agrupa varias entidades/VOs como una unidad cohesiva?
│ └─► SÍ → AGREGADO (p. ej., SolicitudBeneficioAgregado)
│
├─► ¿Representa un conjunto limitado de valores válidos?
│
│ └─► SÍ → ENUM (p. ej., EstadoSolicitudEnum, SexoEnum)
│
├─► ¿Describe algo que sucedió (en pasado)?
│
│ └─► SÍ → EVENTO DE DOMINIO (p. ej., SolicitudAprobadaEvento)
│
├─► ¿Es una regla/criterio booleano?
│ └─► SÍ → ESPECIFICACIÓN (p. ej., SuperficieMaximaSpecification)
│
├─► ¿La lógica abarca varias entidades (no cabe en una sola)?
│
│ └─► SÍ → SERVICIO DE DOMINIO (p. ej., ElegibilidadBeneficiarioDomainService)
│
└─► ¿Representa un error de negocio?
│
└─► SÍ → EXCEPCIÓN DE DOMINIO (p. ej., CurpInvalidaException)
```

**Preguntas de identificación:**

| Pregunta | Si es SÍ → |
|----------|----------|
| ¿Tiene un ID único? | Entidad **(SOLO si tiene comportamiento de negocio!)** |
| ¿Se define por sus valores, no por su ID? | Value Object |
| ¿Es un grupo de entidades/objetos de valor relacionados? | Agregado |
| ¿Tiene valores fijos y limitados? | Enumeración |
| ¿Describe un evento pasado? | Evento de dominio |
| ¿Es una regla de negocio verdadera/falsa? | Especificación |
| ¿La lógica abarca varias entidades? | Servicio de dominio |
| ¿Representa un error de negocio? | Excepción de dominio |

---

## 📋 Common Scenarios

### ✅ Should be Entity:
- **Solicitud**: Has state transitions (draft → submitted → approved)
- **Beneficiario**: Has lifecycle (activate/deactivate), business rules
- **Cuenta**: Has balance management, transaction rules
- **Documento**: Has validation rules, versioning, approval workflow

### ❌ Should be DTO (NOT Entity!):
- **TipoRequerimiento**: Just lookup/catalog data (ALTA, BAJA, etc.)
- **EstadoCivil**: Static reference data (Soltero, Casado, Divorciado)
- **Pais/State/City**: Geographic reference data
- **TipoDocumento**: Document type catalog
- **Categoria/Tag**: Simple classification data

### Should be Enum:
- **Fixed catalogs** with known values at compile-time
- **Status/State** types that don't change
- **Type classifications** that are hardcoded