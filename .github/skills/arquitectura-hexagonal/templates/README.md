# Plantillas de Código - Arquitectura Hexagonal

Este directorio contiene plantillas para cada tipo de clase en la arquitectura hexagonal.

## � CRITICAL: Before Using Entity Template

**READ FIRST:** [../references/ENTITY_VS_DTO_DECISION_GUIDE.md](../references/ENTITY_VS_DTO_DECISION_GUIDE.md)

**Anemic entities are FORBIDDEN!** Before using `entity.php` template, verify:

- ✅ Does it have business behavior? (not just getters)
- ✅ Does it protect invariants?
- ✅ Does it manage state/lifecycle?

**If NO to all → Use DTO template, NOT Entity template!**

---

## �📁 Templates Disponibles

### Infrastructure Layer

| Archivo | Componente | Variables |
|---------|------------|-----------|
| `in-adapter.php` | Adaptador de Entrada | `{{UseCase}}`, `{{InDto}}`, `{{InPort}}` |
| `out-adapter.php` | Adaptador de Salida | `{{Entity}}`, `{{OutPort}}`, `{{Criterio}}` |
| `repository.php` | Repositorio | `{{Entity}}`, `{{table}}`, `{{campo}}`, `{{Criterio}}` |
| `unit-test.php` | Prueba Unitaria | `{{UseCase}}`, `{{InDto}}`, `{{OutPort}}`, `{{Entity}}`, `{{descripcion}}` |

### Application Layer

| Archivo | Componente | Variables |
|---------|------------|-----------|
| `in-dto.php` | DTO de Entrada | `{{InDto}}` |
| `out-dto.php` | DTO de Salida | `{{OutDto}}`, `{{Entity}}` |
| `in-port.php` | Puerto de Entrada (Interface) | `{{InPort}}`, `{{InDto}}`, `{{OutDto}}`, `{{DomainException}}` |
| `out-port.php` | Puerto de Salida (Interface) | `{{OutPort}}`, `{{Entity}}`, `{{Criterio}}` |
| `use-case.php` | Caso de Uso | `{{UseCase}}`, `{{InPort}}`, `{{OutPort}}`, `{{InDto}}`, `{{OutDto}}`, `{{Entity}}`, `{{DomainException}}`, `{{Criterio}}` |
| `app-service.php` | Servicio de Aplicación | `{{AppService}}`, `{{OutPort}}`, `{{Entity}}` |

### Domain Layer

| Archivo | Componente | Variables |
|---------|------------|-----------|
| `entity.php` | Entidad | `{{Entity}}`, `{{ValueObject}}`, `{{Enum}}`, `{{DomainException}}` |
| `value-object.php` | Value Object | `{{ValueObject}}`, `{{DomainException}}` |
| `enum.php` | Enumeración | `{{Enum}}` |
| `domain-event.php` | Evento de Dominio | `{{DomainEvent}}` |
| `domain-exception.php` | Excepción de Dominio | `{{DomainException}}` |
| `domain-service.php` | Servicio de Dominio | `{{DomainService}}`, `{{Entity1}}`, `{{Entity2}}`, `{{ValueObject}}`, `{{DomainException}}` |
| `aggregate.php` | Agregado | `{{Aggregate}}`, `{{RootEntity}}`, `{{ChildEntity}}`, `{{ValueObject}}`, `{{Enum}}` |
| `specification.php` | Especificación | `{{Specification}}`, `{{Entity}}` |

## 🔧 Uso de las Plantillas

### Variables Comunes

- `{{UseCase}}` - Nombre del caso de uso (ej: `GenerarSolicitud`)
- `{{Entity}}` - Nombre de la entidad (ej: `Solicitud`, `Persona`)
- `{{InDto}}` - Nombre del DTO de entrada (ej: `GenerarSolicitudInDTO`)
- `{{OutDto}}` - Nombre del DTO de salida (ej: `SolicitudOutDTO`)
- `{{InPort}}` - Nombre del puerto de entrada (ej: `IGenerarSolicitudInPort`)
- `{{OutPort}}` - Nombre del puerto de salida (ej: `IGenerarSolicitudOutPort`)
- `{{Criterio}}` - Criterio de búsqueda (ej: `Curp`, `Id`, `Email`)
- `{{DomainException}}` - Excepción de dominio (ej: `CURPInvalidaException`)
- `{{ValueObject}}` - Value Object (ej: `DireccionVO`, `CURPVO`)
- `{{Enum}}` - Enumeración (ej: `EstatusSolicitudEnum`)

### Ejemplo de Sustitución

**Template**: `use-case.php`

```php
class {{UseCase}} implements {{InPort}}
{
    private {{OutPort}} $outPort;
    
    public function execute({{InDto}} $dto): {{OutDto}}
```

**Resultado**:

```php
class GenerarSolicitud implements IGenerarSolicitudInPort
{
    private IGenerarSolicitudOutPort $outPort;
    
    public function execute(GenerarSolicitudInDTO $dto): SolicitudOutDTO
```

## 📋 Nomenclaturas

### Infrastructure
- InAdapter: `{UseCase}InAdapter.php`
- OutAdapter: `MySQL{Entity}OutAdapter.php`
- Repository: `{Entity}MySQLRepository.php`
- Test: `{UseCase}Test.php`

### Application
- InDTO: `{UseCase}InDTO.php`
- OutDTO: `{Entity}OutDTO.php`
- InPort: `I{UseCase}InPort.php`
- OutPort: `I{UseCase}OutPort.php`
- UseCase: `{UseCase}.php`
- AppService: `{Nombre}AppService.php`

### Domain
- Entity: `{Entity}.php`
- ValueObject: `{Nombre}VO.php`
- Enum: `{Nombre}Enum.php`
- DomainEvent: `{Nombre}Event.php` (en pasado)
- DomainException: `{Nombre}Exception.php`
- DomainService: `{Nombre}DomainService.php`
- Aggregate: `{Nombre}Aggregate.php`
- Specification: `{Nombre}Specification.php`
