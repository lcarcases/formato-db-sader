app/Core/{Modulo}/
├── Domain/                          # 🔵 CAPA DE DOMINIO
│   ├── Entities/
│   │   └── {Concepto}Entity.php         # Entidades con comportamiento
│   ├── Vo/
│   │   └── {Concepto}VO.php             # Value Objects inmutables
│   ├── Enums/
│   │   └── {Concepto}Enum.php           # Enumeraciones
│   ├── Exceptions/
│   │   └── {Concepto}Exception.php      # Excepciones de dominio
│   ├── Specifications/
│   │   └── {Regla}Specification.php     # Especificaciones (si aplica)
│   ├── Services/
│   │   └── {Concepto}DomainService.php  # Servicios de dominio (si aplica)
│   ├── Events/
│   │   └── {Concepto}Event.php          # Eventos de dominio (si aplica)
│   └── Aggregates/
│       └── {Concepto}Aggregate.php      # Agregados (si aplica)
│
├── Application/                     # 🟢 CAPA DE APLICACIÓN
│   ├── UseCases/
│   │   └── {CasoUso}UseCase.php         # Implementación del caso de uso
│   ├── Ports/
│   │   ├── In/
│   │   │   └── I{CasoUso}InPort.php     # Puerto de entrada (interface)
│   │   └── Out/
│   │       └── I{Concepto}OutPort.php   # Puertos de salida (interfaces)
|   |── Services/ 
|       └── {Concepto}Service.php        # Servicios de aplicación (si aplica) 
│   └── Dtos/
│       ├── In/
│       │   └── {CasoUso}InDto.php       # DTO de entrada
│       └── Out/
│           └── {CasoUso}OutDto.php      # DTO de salida
│
└── Infrastructure/                  # 🟠 CAPA DE INFRAESTRUCTURA
    ├── Adapters/
    │   ├── In/
    │   │   ├── Api/
    │   │   │   └── {CasoUso}InAdapter.php    # Adaptador API REST
    │   │   ├── Web/
    │   │   │   └── {CasoUso}InAdapter.php    # Adaptador Web/Livewire
    │   │   └── Cli/
    │   │       └── {CasoUso}Command.php      # Comando de consola
    │   └── Out/
    │       ├── Persistence/
    │       │   └── MySQL/
    │       │       ├── {Concepto}MySQLOutAdapter.php
    │       │       └── Repositories/
    │       │           └── {Concepto}MySQLRepository.php
    │       ├── Storage/
    │       │   └── AWS/
    │       │       └── {ServicioAWS}OutAdapter.php (si aplica) 
    │       └── Notification/
    │       │  └── {ProveedorCorreo}OutAdapter.php (si aplica) 
    |       │
    |       └── Archivos/        
    |           └── {Excel}OutAdapter.php (si aplica)
    ├── Providers/
    │   └── {Module}ServiceProvider.php   # 🚨 Registro de dependencias y rutas
    ├── Routes/
    │   └── {Module}ApiRoutes.php         # 🚨 CRITICAL: Rutas API prefix api/v1/{module}
    └── Tests/
        └── Units/
            └── {CasoUso}Test.php         # Prueba unitaria


## 🚨 CRITICAL: Route Organization

### Route File (MANDATORY for API InAdapters)

**Location:** `app/Core/{Module}/Infrastructure/Routes/{Module}ApiRoutes.php`

**Purpose:** Organize all API routes for the module with proper versioning

**Pattern:**
```php
Route::prefix('api/v1/{module}')->group(function () {
    Route::get('/{resource}', {InAdapter}::class)->name('api.{module}.{resource}.index');
});
```

**Example:** See [ROUTING_CONVENTIONS.md](ROUTING_CONVENTIONS.md) for complete guidelines
