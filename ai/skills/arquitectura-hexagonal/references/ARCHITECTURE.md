# Arquitectura Hexagonal y Domain Driven Design

## 📋 Índice Rápido

1. [Principios Fundamentales](#principios-fundamentales)
2. [Reglas de Dependencia](#reglas-de-dependencia)
3. [Capa Infrastructure](#capa-infrastructure)
4. [Capa Application](#capa-application)
5. [Capa Domain](#capa-domain)
6. [Estructura de Carpetas](#estructura-de-carpetas)
7. [Pruebas Unitarias](#pruebas-unitarias)

---

## Principios Fundamentales

Este proyecto implementa **Arquitectura Hexagonal** (Puertos y Adaptadores) + **Domain Driven Design (DDD)**.

### Estructura de 3 Capas

```
┌─────────────────────────────────────────┐
│   INFRASTRUCTURE (Capa Externa)         │  ← Menos reutilizable
│   ┌─────────────────────────────────┐   │
│   │   APPLICATION (Capa Media)      │   │  ← Reutilizable
│   │   ┌─────────────────────────┐   │   │
│   │   │   DOMAIN (Capa Interna) │   │   │  ← Más reutilizable
│   │   └─────────────────────────┘   │   │
│   └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

---

## Reglas de Dependencia

### ✅ PERMITIDO (Dependency Inversion)

| Capa Origen | Puede Invocar |
|-------------|---------------|
| **Infrastructure** | Infrastructure + Application + Domain |
| **Application** | Application + Domain |
| **Domain** | SOLO Domain |

### ❌ PROHIBIDO

- Domain **NUNCA** invoca Application o Infrastructure
- Application **NUNCA** invoca Infrastructure  
- Capas internas **NUNCA** dependen de capas externas

### 📊 Reusabilidad por Capa

| Capa | Nivel de Reusabilidad | Regla de Implementación |
|------|----------------------|------------------------|
| **Domain** | 🟢 Máxima | Escribir MÁXIMA lógica de negocio aquí |
| **Application** | 🟡 Media | Lógica específica de casos de uso |
| **Infrastructure** | 🔴 Mínima | Código acoplado a framework/sistemas externos |

> **REGLA DE ORO**: Mientras más lógica de negocio escribamos en Domain, mejor → Máxima reusabilidad

---

## Capa INFRASTRUCTURE

### 🎯 Propósito

Código acoplado a framework (Laravel) y sistemas externos.

**Nivel de Reusabilidad**: 🔴 Mínimo

### Componentes de Infrastructure

| Componente | Responsabilidad | Nomenclatura | Ejemplo |
|------------|-----------------|--------------|---------|
| **InAdapter** | Punto de entrada (Web/API/CLI) | `{UseCase}InAdapter.php` | `GenerarTramiteInAdapter.php` |
| **OutAdapter** | Interacción con sistemas externos | `{Sistema}{Entidad}OutAdapter.php` | `MySQLPersonaOutAdapter.php` |
| **Repository** | Ejecución de SQL | `{Entidad}MySQLRepository.php` | `PersonaMySQLRepository.php` |
| **Unit Tests** | Validación de casos de uso | `{UseCase}Test.php` | `GenerarSolicitudTest.php` |
| **Views** | Presentación visual | `*.blade.php` | `solicitud/create.blade.php` |

---

### InAdapter (Adaptador de Entrada)

**Función**: Punto de entrada al sistema desde el mundo exterior.

#### Fuentes de Información

- 📄 **Web**: Formularios HTML (POST/GET)
- 🔌 **API**: Endpoints JSON/XML
- 💻 **CLI**: Comandos de consola

#### Responsabilidades

| Responsabilidad | ✅/❌ |
|----------------|-------|
| Capturar parámetros del cliente | ✅ |
| Crear InDTO con datos recibidos | ✅ |
| Invocar caso de uso (InPort) | ✅ |
| Validar formato de entrada | ✅ |
| Contener lógica de negocio | ❌ |
| Interactuar directamente con BD | ❌ |

#### Ejemplo de Flujo

```
Cliente → InAdapter → crea InDTO → invoca UseCase → retorna OutDTO → serializa respuesta
```

---

### OutAdapter (Adaptador de Salida)

**Función**: Traducir operaciones de dominio a sistemas externos.

#### Sistemas Externos Soportados

| Categoría | Tecnologías |
|-----------|-------------|
| 💾 **Bases de Datos** | MySQL, PostgresSQL, MongoDB |
| ☁️ **Cloud Storage** | AWS S3, Azure Blob Storage |
| 📁 **Archivos** | PDFs, Excel, CSV |
| 🔔 **Notificaciones** | Email (SMTP), SMS |
| 📨 **Colas** | Kafka, RabbitMQ, SQS |
| 🌐 **APIs Externas** | REST, SOAP, GraphQL |
| 🗄️ **Cache** | Redis, Memcache |
| ⚡ **Procesamiento** | Apache Spark, Flink |

#### Responsabilidades

| Responsabilidad | ✅/❌ |
|----------------|-------|
| Implementa OutPort (interfaz) | ✅ |
| Construye queries complejas (JOINS, CASE) | ✅ |
| Mapea Entidad ↔ Modelo de BD | ✅ |
| Prepara datos para sistemas externos | ✅ |
| Ejecuta SQL directamente | ❌ (delega a Repository) |
| Contiene reglas de negocio | ❌ |

#### Separación OutAdapter vs Repository

| Aspecto | OutAdapter | Repository |
|---------|------------|------------|
| **Construcción de queries** | ✅ Construye lógica (WHERE dinámicos, JOINS) | ❌ |
| **Ejecución SQL** | ❌ | ✅ Ejecuta queries |
| **Mapping** | ✅ Modelo BD ↔ Entidad | ❌ |
| **Transacciones** | ❌ | ✅ |

---

### Repository

**Función**: Ejecutar SQL contra la base de datos.

#### Responsabilidades

| Responsabilidad | ✅/❌ |
|----------------|-------|
| Ejecutar SELECT, INSERT, UPDATE, DELETE | ✅ |
| Manejar transacciones | ✅ |
| Ejecutar queries preparadas | ✅ |
| Construir lógica de queries complejas | ❌ (OutAdapter lo hace) |
| Contener reglas de negocio | ❌ |
| Mapear a entidades | ❌ (OutAdapter lo hace) |

---

### Unit Tests

**Función**: Validar casos de uso de forma aislada.

#### Características

- ✅ Usa **mocks** para OutPorts
- ✅ **NO** requiere BD real, APIs, colas
- ✅ Valida lógica de negocio pura
- ✅ Enfoque **Given-When-Then** (ver sección)

#### Nomenclatura

`{UseCase}Test.php` → `GenerarSolicitudTest.php`

---

### Views y Componentes

**Función**: Presentación visual.

#### Incluye

- Plantillas Blade (`.blade.php`)
- Componentes Blade
- Componentes Livewire

---

## Capa APPLICATION

### 🎯 Propósito

Lógica de negocio específica a **casos de uso**.

**Nivel de Reusabilidad**: 🟡 Media

### Regla de Pureza

**✅ PERMITIDO**: PHP puro, sin dependencias de framework  
**❌ PROHIBIDO**: Código de Laravel, interacción directa con BD/APIs

### Componentes de Application

| Componente | Responsabilidad | Nomenclatura | Capa |
|------------|-----------------|--------------|------|
| **InDTO** | Transportar datos de entrada | `{UseCase}InDTO.php` | Application |
| **OutDTO** | Transformar datos de salida | `{Entidad}OutDTO.php` | Application |
| **InPort** | Contrato del caso de uso (interfaz) | `I{UseCase}InPort.php` | Application |
| **OutPort** | Contrato de salida (interfaz) | `I{UseCase}OutPort.php` | Application |
| **UseCase** | Implementación de lógica de negocio | `{UseCase}.php` | Application |
| **AppService** | Lógica reutilizable entre casos de uso | `{Nombre}AppService.php` | Application |

---

### InDTO (DTO de Entrada)

**Función**: Transportar información del client a capas internas.

#### Características

- ✅ Instanciado en **InAdapter**
- ✅ Mueve datos entre capas (Application ↔ Domain)
- ✅ **Reemplaza** el objeto `$Request` de Laravel
- ❌ NO contiene lógica de negocio

#### Ejemplo de Uso

```php
// ❌ ANTES (acoplado a Laravel)
public function execute(Request $request) {
    $nombre = $request->input('nombre');
    $curp = $request->input('curp');
}

// ✅ DESPUÉS (desacoplado)
public function execute(RegistrarPersonaInDTO $dto) {
    $nombre = $dto->nombre;
    $curp = $dto->curp;
}
```

---

### OutDTO (DTO de Salida)

**Función**: Transformar información al formato esperado por el cliente.

#### Transformaciones Típicas

| Origen (BD) | Transformación | Destino (Cliente) |
|-------------|----------------|-------------------|
| `ln_nombre`, `ln_primer_apellido` | Concatenación | `"Diego Rodrigues Avila"` |
| `DateTime` | `->format('Y-m-d')` | `"2025-04-05"` (string) |
| `decimal(10,2)` | `number_format()` | `"1,500.00"` |
| `1` (int) | Mapeo enum | `"Activo"` (string) |

#### Diferencia InDTO vs OutDTO

| Aspecto | InDTO | OutDTO |
|---------|-------|--------|
| **Dirección** | Cliente → Sistema | Sistema → Cliente |
| **Propósito** | Recibir información | Exponer información |
| **Transformación** | Validación básica | Formateo para presentación |

---

### InPort (Puerto de Entrada)

**Función**: Contrato de lo que la aplicación **puede hacer**.

#### Características

- ✅ Es una **interfaz**
- ✅ Representa el caso de uso
- ✅ Define **QUÉ** hace (no CÓMO)
- ❌ NO contiene implementación

#### Ejemplo

```php
interface IGenerarSolicitudInPort
{
    public function generarSolicitud(GenerarSolicitudInDTO $dto): GenerarSolicitudOutDTO;
}
```

---

### OutPort (Puerto de Salida)

**Función**: Contrato de lo que la aplicación **necesita** del mundo externo.

#### Características

- ✅ Es una **interfaz**
- ✅ Define dependencias hacia infraestructura (BD, APIs, colas)
- ✅ Implementada por **OutAdapter** (Infrastructure)
- ❌ NO contiene implementación

#### Ejemplo

```php
interface IGenerarSolicitudOutPort
{
    public function persistirSolicitud(Solicitud $solicitud): void;
    public function buscarPorCurp(string $curp): ?Persona;
}
```

---

### UseCase (Caso de Uso)

**Función**: Orquestador de la lógica de negocio para una tarea específica.

#### Responsabilidades

| Responsabilidad | ✅ |
|----------------|-----|
| **Orquestar** interacción entre clases de dominio | ✅ |
| **Invocar** OutPorts para infraestructura | ✅ |
| **Transformar** DTOs ↔ Entidades | ✅ |
| **Ejecutar** secuencias de pasos | ✅ |
| **Disparar** excepciones de negocio | ✅ |
| **Iterar** listas (for, while) | ✅ |
| **Condicionales** (if/else) para flujos | ✅ |

#### Lo que NO hace

| Anti-patrón | ❌ |
|------------|-----|
| Ejecutar SQL directamente | ❌ |
| Usar clases de Laravel (`DB::`, `Cache::`) | ❌ |
| Contener reglas de dominio (van en Entity/DomainService) | ❌ |

#### Metáfora

> **El UseCase es el coreógrafo de la aplicación, NO el bailarín.**  
> Los bailarines son las **Entidades** y **Servicios de Dominio**.

---

### AppService (Servicio de Aplicación)

**Función**: Lógica reutilizable entre múltiples casos de uso.

#### Diferencia UseCase vs AppService

| Aspecto | UseCase | AppService |
|---------|---------|------------|
| **Trigger** | Acción directa del usuario | Invocado por UseCase |
| **Alcance** | Una tarea específica del negocio | Apoyo a múltiples UseCases |
| **Reusabilidad** | Baja (específico) | Alta (compartido) |
| **Ejemplo** | `GenerarSolicitud` | `ValidadorCURP`, `CalculadoraMonto` |

---

## Capa DOMAIN

### 🎯 Propósito

Lógica de negocio específica a **conceptos del dominio** (sustantivos).

**Nivel de Reusabilidad**: 🟢 Máxima

### Diferencia Application vs Domain

| Aspecto | Application | Domain |
|---------|-------------|--------|
| **Alcance** | Lógica de **caso de uso** | Lógica de **concepto** (Trámite, Persona) |
| **Ejemplo** | Cómo se genera una solicitud | Qué es un Folio válido |

### Componentes de Domain

| Componente | Función | Ejemplo |
|------------|---------|---------|
| **Entity** | Concepto con identidad única | `Solicitud`, `Tramite` |
| **Value Object** | Concepto inmutable sin ID | `DireccionVO`, `CURPVO` |
| **Enum** | Conjunto limitado de valores | `EstatusSolicitudEnum` |
| **DomainEvent** | Hecho que ocurrió en el negocio | `SolicitudCreadaEvent` |
| **Exception** | Error de negocio | `PersonaNoActivaException` |
| **DomainService** | Lógica cruzada entre entidades | `ElegibilidadBeneficiarioDomainService` |
| **Aggregate** | Grupo de objetos como unidad | `SolicitudBeneficioAggregate` |
| **Specification** | Regla booleana de negocio | `SuperficieMaximaSpecification` |

---

### Entity (Entidad)

**Función**: Modelar conceptos del negocio con identidad única.

#### Características

- ✅ Tiene **identificador único** (ID)
- ✅ Contiene **lógica de negocio** del concepto
- ✅ Representa **sustantivos** (Solicitud, Trámite, Productor)

#### Ejemplo Conceptual

**Entidad**: `Solicitud`  
**Lógica interna**: Generar folio con formato `25-PRONAFE-FERT-000024-L001-AS`

```
Año (25) + PRONAFE + Consecutivo (000024) + Estado (AS)
```

---

### Value Object (VO)

**Función**: Modelar conceptos inmutables sin identidad.

#### Características

| Característica | Descripción |
|----------------|-------------|
| **Identidad** | La combinación de TODOS sus atributos |
| **Inmutabilidad** | Cambias 1 atributo = objeto diferente |
| **Sin ID** | No tiene identificador único |

#### Ejemplos

- `DireccionVO` (Calle + Número + Colonia + CP + Estado)
- `CURPVO`
- `RFCVO`
- `SuperficieCultivoVO`

#### Regla de Inmutabilidad

```php
// ❌ INCORRECTO
$direccion->calle = "Nueva Calle";

// ✅ CORRECTO
$nuevaDireccion = new DireccionVO(
    calle: "Nueva Calle",
    numero: $direccion->numero,
    colonia: $direccion->colonia,
    cp: $direccion->cp
);
```

---

### Enum (Enumeración)

**Función**: Conjunto limitado y cerrado de valores válidos.

#### Ejemplo

**Concepto**: Estatus de una Solicitud  
**Valores**: `PENDIENTE`, `APROBADA`, `RECHAZADA`, `CANCELADA`

**Clase**: `EstatusSolicitudEnum`

---

### DomainEvent (Evento del Dominio)

**Función**: Describir algo que **ya ocurrió** en el negocio.

#### Características

- ✅ Nombrado en **pasado** (evento ya ocurrió)
- ✅ Contiene **información del evento**
- ✅ Activa procesos secundarios

#### Ejemplos

- `AnioSeleccionadoEvent` → contiene: `$anio`
- `ProgramaSeleccionadoEvent` → contiene: `$programaId`
- `SolicitudAprobadaEvent` → contiene: `$solicitudId`, `$fechaAprobacion`

---

### Exception (Excepción de Dominio)

**Función**: Representar errores de negocio.

#### Ejemplos

- `PersonaNoActivaException` → La persona no está activa (viva)
- `CURPInvalidaException` → CURP no cumple formato
- `SuperficieExcedidaException` → Más de 50 hectáreas
- `BeneficiarioNoElegibleException` → No cumple criterios

---

### DomainService (Servicio de Dominio)

**Función**: Lógica de negocio **cruzada** entre entidades.

#### Cuándo Usarlo

Cuando la lógica de negocio:
- ✅ Involucra **múltiples entidades**
- ✅ NO pertenece naturalmente a una sola entidad
- ✅ Es **ambiguo** dónde ponerla

#### Ejemplo

**Lógica**: Verificar elegibilidad de beneficiario  
**Regla**: Cumple edad + superficie < 50 ha + no duplicado en otros programas

**Involucra**:
- `Beneficiario` (edad)
- `SuperficieVO` (hectáreas)
- `Solicitud` (programa)

**Solución**: `ElegibilidadBeneficiarioDomainService`

#### Principio

Un DomainService se enfoca en **una sola regla** (Single Responsibility Principle - SOLID).

---

### Aggregate (Agregado)

**Función**: Agrupar entidades/VOs como una unidad cohesiva.

#### Características

- ✅ Tiene **raíz de agregado** (root entity con ID)
- ✅ Agrupa objetos relacionados
- ✅ Se trata como **una unidad** en transacciones

#### Ejemplo

**Aggregate**: `SolicitudBeneficioAggregate`

**Componentes agrupados**:
- `Solicitud` (raíz - Entity con ID)
- `DocumentoAdjunto` (Entity - INE, CURP, Comprobante)
- `PeriodoVigenciaVO` (VO - fecha inicio/fin)
- `MontoBeneficioVO` (VO - validaciones de monto)
- `EstatusSolicitudEnum` (Enum)

#### Regla de Consistencia

Todo cambio al aggregate se hace a través de la **raíz**.

---

### Specification (Especificación)

**Función**: Regla booleana que verifica si un objeto cumple criterio.

#### Características

- ✅ Retorna `true` o `false`
- ✅ Encapsula regla de negocio compleja
- ✅ Reutilizable en múltiples lugares

#### Ejemplo

**Regla**: "Un beneficiario es elegible si tiene menos de 50 hectáreas"

**Clase**: `SuperficieMaximaSpecification`

```php
$spec = new SuperficieMaximaSpecification(50);
if ($spec->isSatisfiedBy($beneficiario)) {
    // Elegible
}
```

---

## Estructura de Carpetas

### Vista General

```
app/Core/
├── Infrastructure/
│   ├── Adapters/
│   │   ├── In/
│   │   │   ├── Api/
│   │   │   ├── Web/
│   │   │   └── Cli/
│   │   └── Out/
│   │       ├── Aws/
│   │       ├── Files/
│   │       └── Persistence/
│   │           └── MySQL/
│   │               ├── Models/
│   │               └── Repositories/
│   ├── Tests/Unit/
│   └── Views/
│       ├── Components/
│       └── Livewire/
├── Application/
│   ├── Dtos/
│   │   ├── In/
│   │   └── Out/
│   ├── Ports/
│   │   ├── In/
│   │   └── Out/
│   ├── Services/
│   └── UseCases/
└── Domain/
    ├── Aggregates/
    ├── Entities/
    ├── Enums/
    ├── Events/
    ├── Exceptions/
    ├── Services/
    ├── Specifications/
    └── Vo/
```

### Descripción de Carpetas

#### Infrastructure

| Carpeta | Contenido |
|---------|-----------|
| `Adapters/In/Api` | InAdapters para endpoints API |
| `Adapters/In/Web` | InAdapters para controladores web |
| `Adapters/In/Cli` | InAdapters para comandos de consola |
| `Adapters/Out/Aws` | OutAdapters para AWS (S3, SQS) |
| `Adapters/Out/Files` | OutAdapters para sistema de archivos |
| `Adapters/Out/Persistence/MySQL` | OutAdapters para MySQL |
| `Adapters/Out/Persistence/MySQL/Models` | Modelos de Laravel (Eloquent) |
| `Adapters/Out/Persistence/MySQL/Repositories` | Repositories para ejecutar SQL |
| `Tests/Unit` | Pruebas unitarias de casos de uso |
| `Views/Components` | Componentes Blade |
| `Views/Livewire` | Componentes Livewire |

#### Application

| Carpeta | Contenido |
|---------|-----------|
| `Dtos/In` | DTOs de entrada (del cliente) |
| `Dtos/Out` | DTOs de salida (al cliente) |
| `Ports/In` | Interfaces de casos de uso |
| `Ports/Out` | Interfaces de salida (infraestructura) |
| `Services` | Application Services |
| `UseCases` | Casos de uso (implementaciones) |

#### Domain

| Carpeta | Contenido |
|---------|-----------|
| `Aggregates` | Agregados del dominio |
| `Entities` | Entidades con identidad |
| `Enums` | Enumeraciones |
| `Events` | Eventos del dominio (pasado) |
| `Exceptions` | Excepciones de negocio |
| `Services` | Servicios de dominio |
| `Specifications` | Reglas booleanas |
| `Vo` | Value Objects |

### Nota Importante sobre Core

**`app/Core/`** = Core del **negocio/dominio**  
**NO** = Core de Laravel

Laravel solo vive en **Infrastructure**.

---

## Pruebas Unitarias

### Enfoque Given-When-Then

Las pruebas unitarias usan el patrón **GWT** (Given-When-Then) para mejorar claridad y homogeneidad.

### Estructura GWT

| Fase | Propósito | Ejemplo |
|------|-----------|---------|
| **Given** (Dado) | Estado inicial / precondiciones | "Dado que existe un beneficiario registrado con CURP válida" |
| **When** (Cuando) | Acción a probar (caso de uso) | "Cuando se actualiza su dirección" |
| **Then** (Entonces) | Verificación de resultados | "Entonces la dirección debe reflejar los nuevos datos" |

### Ejemplo de Test

```php
class ActualizarDireccionTest extends TestCase
{
    /** @test */
    public function debe_actualizar_direccion_de_beneficiario()
    {
        // GIVEN (Contexto)
        $beneficiario = new Beneficiario(/* datos */);
        $dto = new ActualizarDireccionInDTO(
            calle: "Nueva Calle",
            numero: "123"
        );
        
        // Mock del OutPort
        $outPort = $this->createMock(IActualizarDireccionOutPort::class);
        $outPort->expects($this->once())
                ->method('persistirBeneficiario');
        
        $useCase = new ActualizarDireccion($outPort);
        
        // WHEN (Acción)
        $useCase->execute($dto);
        
        // THEN (Verificación)
        $this->assertEquals("Nueva Calle", $beneficiario->direccion->calle);
    }
}
```

### Configuración en Given

En la fase **Given** se:
- ✅ Crean objetos necesarios (DTOs, Entidades)
- ✅ Configuran mocks de OutPorts
- ✅ Simulan datos de entrada

### Características de Tests Unitarios

| Característica | ✅ |
|----------------|-----|
| **Aislados** | No dependen de BD real |
| **Rápidos** | Ejecución en milisegundos |
| **Reproducibles** | Siempre mismo resultado |
| **Mocks** | Simulan OutPorts/Repositories |

---

## Resumen de Decisiones Rápidas

### ¿Dónde pongo esta lógica?

| Lógica | Capa | Componente |
|--------|------|------------|
| Generar formato de Folio | Domain | `Folio` (Entity o VO) |
| Validar CURP | Domain | `CURPVO` |
| Verificar elegibilidad (múltiples entidades) | Domain | `ElegibilidadDomainService` |
| Orquestar "Generar Solicitud" | Application | `GenerarSolicitudUseCase` |
| Construir query con JOINS dinámicos | Infrastructure | `MySQLPersonaOutAdapter` |
| Ejecutar SQL | Infrastructure | `PersonaMySQLRepository` |
| Recibir POST de formulario | Infrastructure | `GenerarSolicitudInAdapter` |
| Transformar datos para respuesta | Application | `SolicitudOutDTO` |

### ¿Qué patrón uso?

| Situación | Patrón |
|-----------|--------|
| Concepto con identidad única | Entity |
| Concepto inmutable sin ID | Value Object |
| Conjunto cerrado de valores | Enum |
| Lógica cruzada entre entidades | DomainService |
| Regla booleana | Specification |
| Agrupar entidades relacionadas | Aggregate |
| Algo que ya ocurrió | DomainEvent |
| Error de negocio | DomainException |

---

## Checklist de Cumplimiento

### ✅ Al crear UseCase

- [ ] Código es PHP puro (sin Laravel)
- [ ] Recibe InDTO (no `Request`)
- [ ] Retorna OutDTO
- [ ] NO ejecuta SQL directamente
- [ ] Orquesta entidades de dominio
- [ ] Usa OutPorts para infraestructura

### ✅ Al crear Entity/VO

- [ ] Está en capa Domain
- [ ] Contiene lógica de negocio del concepto
- [ ] NO depende de Laravel
- [ ] NO accede a BD directamente

### ✅ Al crear OutAdapter

- [ ] Implementa OutPort (interfaz)
- [ ] Construye queries complejas
- [ ] Mapea Entidad ↔ Modelo
- [ ] Delega ejecución SQL a Repository

### ✅ Al crear Repository

- [ ] Solo ejecuta SQL
- [ ] NO construye lógica de queries complejas
- [ ] Maneja transacciones
- [ ] NO contiene reglas de negocio
