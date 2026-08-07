## Folder Structure

### Module Organization

Each bounded context (module) follows the same structure:

```
Core
├── Padron (Módulo)
├── Programa (Módulo)
│   ├── Application
│   │   ├── Dtos
│   │   │   ├── In
│   │   │   │   └── VerificarBeneficiarioProgramaInDto.php
│   │   │   └── Out
│   │   │       └── VerificarBeneficiarioProgramaOutDto.php
│   │   ├── Ports
│   │   │   ├── In
│   │   │   └── Out
│   │   │       ├── IBeneficioOutPort.php
│   │   │       ├── IPersonaOutPort.php
│   │   │       └── IProgramaOutPort.php
│   │   ├── Services
│   │   └── UseCases
│   │       └── VerificarBeneficiarioProgramaUseCase.php
│   ├── Domain
│   │   ├── Aggregators
│   │   ├── Entities
│   │   │   ├── BeneficioEntity.php
│   │   │   ├── PersonaEntity.php
│   │   │   └── ProgramaEntity.php
│   │   ├── Enums
│   │   │   ├── ClaveEntidadFederativaEnum.php
│   │   │   ├── ClaveEntidadFederativa.php
│   │   │   ├── ClaveProgramaEnum.php
│   │   │   └── SexoEnum.php
│   │   ├── Events
│   │   ├── Exceptions
│   │   │   ├── AnioInvalidoException.php
│   │   │   ├── CurpInvalidaException.php
│   │   │   ├── PersonaNoEncontradaException.php
│   │   │   └── ProgramaNoEncontradoException.php
│   │   ├── Interfaces
│   │   │   ├── IBeneficio.php
│   │   │   └── IIdentificable.php
│   │   ├── Specifications
│   │   │   ├── AnioValidoSpecification.php
│   │   │   ├── ExisteBeneficioSpecification.php
│   │   │   ├── ExistePersonaSpecification.php
│   │   │   └── ExisteProgramaSpecification.php
│   │   └── Vo
│   │       └── CurpVO.php
│   └── Infrastructure
│       ├── Adapters
│       │   ├── In
│       │   │   ├── Api
│       │   │   │   └── VerificarBeneficiarioProgramaInAdapter.php
│       │   │   ├── Cli
│       │   │   └── Web
│       │   └── Out
│       │       ├── Aws
│       │       ├── Cache
│       │       │   └── Redis
│       │       ├── Files
│       │       └── Persistence
│       │           ├── Models
│       │           └── MySQL
│       │               ├── BeneficioMySQLOutAdapter.php
│       │               ├── PersonaMySQLOutAdapter.php
│       │               ├── ProgramaMySQLOutAdapter.php
│       │               └── Repositories
│       │                   ├── BeneficioMySQLRepository.php
│       │                   ├── PersonaMySQLRepository.php
│       │                   └── ProgramaMySQLRepository.php
│       ├── Tests
│       │   └── Units
│       │       └── VerificarBeneficiarioProgramaTest.php
│       └── Views
│           ├── Components
│           └── Livewire
├── RepresentacionRegional (Módulo)
├── Seguridad (Módulo)
├── Shared (Módulo)
│   ├── Application
│   │   └── Dto
│   │       └── IDto.php
│   ├── Domain
│   │   ├── Interfaces
│   │   │   └── IIdentificable.php
│   │   └── Specifications
│   │       ├── FechaCumpleFormatoYYMMDDSpecification.php
│   │       └── ISpecification.php
│   └── Infrastructure
│       └── Respuesta.php
└── Tramite (Módulo)
