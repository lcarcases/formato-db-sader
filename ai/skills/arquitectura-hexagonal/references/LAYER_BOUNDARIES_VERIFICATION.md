## Step 9: Verify Layer Boundaries (Quality Gate)

Before completing implementation, verify:

**Domain Layer Checklist:**
```
✅ NO imports from Application layer
✅ NO imports from Infrastructure layer
✅ NO Laravel classes (Request, Facades, Models)
✅ Only pure PHP code
🚨 Entities have behavior (not anemic) - CRITICAL CHECK!
🚨 NO entities with only getters and toArray() - USE DTO INSTEAD!
🚨 Catalog/lookup data uses DTOs or Enums, NOT Entities!
✅ Value Objects validate in constructor
✅ Value Objects are immutable
```

**🚨 Anemic Entity Detection:**

Ask for EACH Entity:
1. ❓ Does it have business behavior beyond getters? 
   - If NO → ❌ ANEMIC - Use DTO!
   
2. ❓ Does it protect business invariants?
   - If NO → ❌ ANEMIC - Use DTO!
   
3. ❓ Does it manage state/lifecycle?
   - If NO → ❌ ANEMIC - Use DTO!

**Common Anemic Entities to Avoid:**
- ❌ `TipoRequerimientoEntity` → Use `TipoRequerimientoOutDto`
- ❌ `EstadoCivilEntity` → Use `EstadoCivilEnum`
- ❌ `CategoriaEntity` (if just ID + name) → Use `CategoriaOutDto`
- ❌ `TipoDocumentoEntity` → Use `TipoDocumentoEnum` or DTO

**Valid Rich Entities:**
- ✅ `SolicitudEntity` → Has `aprobar()`, `rechazar()`, state management
- ✅ `BeneficiarioEntity` → Has `activar()`, `desactivar()`, business rules
- ✅ `CuentaEntity` → Has `depositar()`, `retirar()`, balance management

**Application Layer Checklist:**
```
✅ NO imports from Infrastructure layer
✅ Imports ONLY Domain + Ports (interfaces)
✅ NO Laravel classes
✅ NO $request objects
✅ UseCase implements InPort interface
✅ UseCase receives InDto, returns OutDto
✅ Dependencies injected via OutPort interfaces
```

**Infrastructure Layer Checklist:**
```
✅ InAdapter is ONLY place that uses $request
✅ OutAdapter implements OutPort interface
✅ Repository uses Laravel (Eloquent/Query Builder)
✅ Maps DB data → Domain objects (not returning Eloquent models)
✅ Handles exceptions and returns proper responses
```

**Dependency Direction Verification:**
```
┌─────────────────────────────────────────────────────────┐
│                    INFRASTRUCTURE                        │
│  (Laravel, MySQL, AWS, HTTP)                            │
│         │                                               │
│         ▼ depends on                                    │
├─────────────────────────────────────────────────────────┤
│                    APPLICATION                          │
│  (UseCases, DTOs, Ports)                               │
│         │                                               │
│         ▼ depends on                                    │
├─────────────────────────────────────────────────────────┤
│                      DOMAIN                             │
│  (Entities, VOs, Services, Specifications)             │
│                                                         │
│  ⚠️ DOMAIN DEPENDS ON NOTHING EXTERNAL                 │
└─────────────────────────────────────────────────────────┘

RULE: Inner layers NEVER import from outer layers
```
