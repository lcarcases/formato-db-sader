<!--
Sync Impact Report (v1.1.0 - Agent Implementation Mandate)
====================================================
Version: 1.1.0 (Minor Release)
Ratified: 2026-05-01
Last Amended: 2026-05-01

Changes in v1.1.0:
- Added MANDATORY requirement to use hexagonal-architecture-specialist agent for all use case implementations
- Added "Implementation Enforcement" subsection to Hexagonal Architecture principle
- Updated Code Review Checklist to verify agent usage

Principles Defined:
- I. Hexagonal Architecture (Ports & Adapters) - MANDATORY (UPDATED)
- II. Domain-Driven Design (DDD) - MANDATORY
- III. Domain Isolation & Framework Independence - MANDATORY
- IV. Test-First & Quality Assurance - MANDATORY
- V. Explicit Contracts & Immutability - MANDATORY
- VI. Ubiquitous Language Consistency - MANDATORY
- VII. Clean Code & SOLID Principles - MANDATORY
- VIII. API-First Design - MANDATORY

Templates Requiring Updates:
✅ All templates already synchronized

Follow-up TODOs: None
-->

# SADER Database Access Permissions API Constitution

## Purpose & Scope

This constitution governs the **SADER (Secretaría de Agricultura y Desarrollo Rural) Database
Access Permissions API**, an internal REST API system for managing database access permissions
within the Mexican government institution SADER.

**System Boundaries:**
- **IN SCOPE**: REST API endpoints, domain logic, data persistence, access control, audit logging
- **OUT OF SCOPE**: Frontend applications, SSR, Blade templates, Livewire components, SPA
  implementations, end-user interfaces

This is a backend-only system. All interaction occurs via HTTP REST API contracts.

## Core Principles

### I. Hexagonal Architecture (Ports & Adapters) — MANDATORY

The system MUST strictly implement Hexagonal Architecture (also known as Ports and Adapters):

- **Business logic lives in the domain core**: All business rules and domain logic MUST reside in
  the domain layer, completely isolated from infrastructure concerns.
- **Ports define contracts**: The domain defines interfaces (ports) that infrastructure implements.
  The domain never depends on infrastructure; infrastructure depends on domain ports.
- **Adapters implement integration**: All external system interactions (HTTP, database, Redis,
  email, etc.) MUST be implemented as adapters in the infrastructure layer.
- **Dependency inversion**: Dependencies MUST point inward. Domain → Application → Infrastructure.
  Infrastructure adapters MUST depend on application/domain ports, never the reverse.
- **Use case orchestration**: Application services orchestrate domain behavior but MUST NOT
  contain business rules or decision logic.

**Implementation Enforcement** — MANDATORY:

- **All use case implementations MUST use the `hexagonal-architecture-specialist` agent**: Every
  new use case, domain entity, value object, adapter, or port implementation MUST be developed
  using the `@hexagonal-architecture-specialist` agent (or `@Hexagonal Architecture Specialist`).
  This agent is specifically trained to enforce hexagonal architecture patterns, DDD tactical
  patterns, and proper layer separation.
- **Direct implementation prohibited**: Developers MUST NOT implement use cases manually without
  using the specialized agent. This ensures consistent architectural compliance and reduces the
  risk of violating domain isolation or dependency direction rules.
- **Agent invocation**: Use the command `@hexagonal-architecture-specialist implement [use case
  description]` or refer to `.github/skills/arquitectura-hexagonal/SKILL.md` for detailed usage.
- **Code review verification**: All pull requests MUST verify that use cases were implemented
  using the designated agent (check commit messages, authorship metadata, or explicit confirmation
  from developer).

**Rationale**: Hexagonal Architecture ensures testability, maintainability, and technology
independence. It allows the domain to remain pure and enables replacement of infrastructure
components without affecting business logic. Using a specialized agent ensures consistent
application of these principles and prevents common architectural violations.

### II. Domain-Driven Design (DDD) — MANDATORY

The system MUST apply Domain-Driven Design tactical and strategic patterns:

- **Ubiquitous Language**: Domain terminology MUST be consistent across code, database schema,
  API contracts, events, tests, and documentation. Spanish terms MAY be used where they reflect
  SADER institutional language (e.g., "Requerimiento", "Tipo de Requerimiento").
- **Bounded Contexts**: Domain boundaries MUST be explicitly defined and enforced. Each bounded
  context owns its data and exposes its functionality through well-defined interfaces.
- **Aggregates**: Entities MUST be organized into aggregates with clearly defined aggregate
  roots. Aggregate boundaries MUST protect invariants. External access MUST occur only through
  the aggregate root.
- **Value Objects**: Immutable concepts without identity MUST be modeled as value objects, not
  entities (e.g., Email, PermissionLevel, DatabaseName).
- **Domain Events**: Significant domain occurrences MUST be modeled as domain events. Events
  MUST use past-tense naming (e.g., `PermissionGranted`, `AccessRevoked`).
- **Repositories**: Data access abstractions MUST be defined as repository interfaces in the
  domain/application layer and implemented in infrastructure.

**Rationale**: DDD provides a shared mental model, reduces complexity through bounded contexts,
and ensures that the codebase reflects real-world SADER institutional operations and terminology.

### III. Domain Isolation & Framework Independence — MANDATORY

The domain layer MUST remain completely framework-agnostic:

- **Zero Laravel dependencies in domain**: The domain layer MUST NOT reference Laravel classes,
  facades, helpers, or framework-specific constructs (e.g., no `Illuminate\*` imports in
  domain entities, value objects, or domain services).
- **Pure PHP in domain**: Domain code MUST use only PHP language features and domain-specific
  abstractions. External dependencies (if absolutely required) MUST be minimal and
  framework-agnostic (e.g., standard libraries, pure PHP value object libraries).
- **Framework isolation in infrastructure**: Laravel-specific code (Eloquent, facades, HTTP
  requests, console commands, service providers) MUST be confined to the infrastructure layer.
- **Testability without bootstrapping**: Domain tests MUST execute without requiring Laravel
  application bootstrapping or framework services.

**Rationale**: Framework independence ensures longevity, testability, and protects business
logic from framework version upgrades or technology shifts. The domain is the most valuable
and stable part of the system; it MUST NOT be coupled to transient technology choices.

### IV. Test-First & Quality Assurance — MANDATORY

Testing is non-negotiable. The system MUST maintain comprehensive, meaningful test coverage:

- **Unit tests for all use cases**: Every application service (use case) MUST have corresponding
  unit tests that verify behavior in isolation using test doubles.
- **Domain tests without framework**: Domain layer tests MUST NOT require Laravel bootstrapping.
  They MUST execute as fast, pure PHP unit tests.
- **Integration tests for adapters**: Infrastructure adapters ( repositories, external services) MUST have integration tests verifying correct interaction with real infrastructure
  components (databases, APIs, Redis) or appropriate test doubles (TestContainers, in-memory implementations).
- **Contract tests for APIs**: Public REST API endpoints MUST have contract tests verifying
  request/response schemas, status codes, error formats, and behavior against published API
  specifications.
- **Test naming conventions**: Tests MUST follow clear naming:
  - Unit: `{UseCase}Test.php` or `{Entity}Test.php`
  - Integration: `{Adapter}IntegrationTest.php`
  - API: `{Endpoint}ApiTest.php`
- **Test organization**: Tests MUST mirror production structure (e.g.,
  `tests/Unit/Core/Admin/Application/UseCases/`, `tests/Integration/Infrastructure/Persistence/`).
- **Minimum philosophy**: Tests are living documentation. Every test MUST have clear Arrange-Act-
  Assert structure and readable assertions that express intent. Flaky tests MUST be fixed or
  removed immediately.

**Rationale**: Test-first development ensures correctness, provides regression safety, enables
confident refactoring, and serves as executable documentation. Tests are not optional overhead;
they are a core deliverable.

### V. Explicit Contracts & Immutability — MANDATORY

The system MUST favor explicitness, immutability, and well-defined boundaries:

- **Immutable DTOs**: Data Transfer Objects (DTOs) MUST be immutable whenever possible. Use
  readonly properties (PHP 8.1+) or private setters with public getters.
- **Explicit Input/Output models**: Every use case MUST define explicit input and output models
  (DTOs, Commands, Queries, Responses). Use primitives or scalar types only when absolutely
  appropriate; prefer value objects.
- **Type safety**: All method signatures MUST use strict types. Enable `declare(strict_types=1)`
  in all files. Avoid mixed types and minimize use of `array` without shape documentation.
- **No magic**: Avoid magic methods (`__get`, `__set`, `__call`), dynamic properties, and hidden
  framework behavior. Favor explicit method calls and constructor injection.
- **Named constructors**: Use static named constructors for complex object creation (e.g.,
  `User::fromRegistration(...)`, `Permission::grant(...)`).
- **Composition over inheritance**: Prefer composition and interface segregation over deep
  inheritance hierarchies.

**Rationale**: Explicitness reduces cognitive load, eliminates ambiguity, and makes code
easier to reason about. Immutability prevents unintended side effects and enables safe
concurrent operations.

### VI. Ubiquitous Language Consistency — MANDATORY

Language MUST be consistent across all artifacts:

The Ubiquitous Language defined in the domain MUST propagate consistently to:

- **Code**: Class names, method names, variable names
- **Database**: Table names, column names, constraint names
- **APIs**: Endpoint paths, request/response field names
- **Events**: Event names, event payload fields
- **Tests**: Test class names, test method names, assertion messages
- **Documentation**: Technical docs, API specifications, inline comments, README files

**Examples of consistency**:
- Domain entity `TipoRequerimiento` → Database table `tb_cat_tipo_requerimiento` → API resource
  `/api/tipos-requerimiento` → Event `TipoRequerimientoCreated` → Test
  `TipoRequerimientoTest.php`

**Language rules**:
- MUST NOT translate domain terms arbitrarily. If SADER uses "Requerimiento", code MUST use
  `Requerimiento`, not `Request` or `Requirement`.
- SHOULD use Spanish for domain-specific SADER terminology that lacks clear English equivalents.
- SHOULD use English for technical/infrastructure terms (Repository, InAdapter, Service).
- MUST document language choices in domain glossary.

**Rationale**: Linguistic consistency eliminates translation errors, reduces cognitive friction,
and ensures that developers, stakeholders, and institutional users share the same mental model.

### VII. Clean Code & SOLID Principles — MANDATORY

Code MUST adhere to Clean Code practices and SOLID principles:

- **Single Responsibility Principle (SRP)**: Each class MUST have one reason to change. Use
  cases MUST perform one business operation. Controllers MUST handle one endpoint concern.
- **Open/Closed Principle (OCP)**: Code MUST be open for extension (via interfaces, abstract
  classes) but closed for modification. Prefer strategy patterns and dependency injection over
  conditional logic sprawl.
- **Liskov Substitution Principle (LSP)**: Subtypes MUST be substitutable for their base types
  without breaking behavior.
- **Interface Segregation Principle (ISP)**: Interfaces MUST be client-specific and narrow.
  No fat interfaces forcing implementations to stub unused methods.
- **Dependency Inversion Principle (DIP)**: High-level modules MUST NOT depend on low-level
  modules. Both MUST depend on abstractions (interfaces). Abstractions MUST NOT depend on details.
- **Code readability**: Code MUST be self-documenting. Prefer expressive names over comments.
  Functions MUST be short and focused. Avoid deep nesting (max 3 levels).
- **PSR-12 compliance**: Code MUST follow PSR-12 coding standards. Use Laravel Pint for automatic
  formatting.
- **Static analysis**: Code MUST pass PHPStan level 9 analysis with zero errors. Type safety is
  enforced at the highest level.

**Rationale**: SOLID principles create maintainable, extensible, testable code. Clean Code
practices reduce defects and onboarding time.

### VIII. API-First Design — MANDATORY

This system exposes ONLY REST API endpoints. No frontend, SSR, or template rendering will exist:

- **RESTful conventions**: API design MUST follow REST principles (resource-based URLs, HTTP
  verbs, status codes, stateless operations).
- **Versioning**: APIs MUST be versioned (e.g., `/api/v1/...`). Breaking changes MUST increment
  the version number.
- **JSON format**: All API requests and responses MUST use JSON (`Content-Type: application/json`).
- **Error responses**: All errors MUST return consistent JSON error structures including `error`,
  `message`, `code`, and optional `details` fields.
- **HTTP status codes**: Use correct HTTP status codes:
  - 200 OK (success with body), 201 Created (resource created), 204 No Content (success without
    body)
  - 400 Bad Request (validation errors), 401 Unauthorized (authentication missing), 403 Forbidden
    (authorization failed), 404 Not Found, 409 Conflict (business rule violation)
  - 422 Unprocessable Entity (domain validation failure)
  - 500 Internal Server Error (unexpected exceptions)
- **Thin InAdapters**: InAdapters MUST remain thin. Responsibilities limited to: request
  validation, input transformation, use case invocation, response serialization. No business
  logic in controllers.
- **API documentation**: All endpoints MUST be documented using OpenAPI 3.x specification.

**Rationale**: API-first design ensures clear contracts, explicit boundaries, and technology-
agnostic integrations. The system serves as a backend platform for multiple potential consumers.

## Technical Stack & Constraints

### Approved Technologies — MUST USE

The system MUST use the following stack:

- **PHP**: Version 8.4 or higher. Leverage modern PHP features (readonly properties, enums,
  union types, attributes, etc.).
- **Laravel**: Version 13.x. Use only as infrastructure framework. Domain MUST NOT depend on it.
- **PostgreSQL**: Version 16.x. Primary data store and system of record.
- **Redis**: Version 7.4.x. Use ONLY for caching and session storage. NEVER as source of truth.

### Forbidden Technologies — MUST NOT USE

The following are explicitly forbidden:

- **No frontend frameworks**: No Vue, React, Angular, Blade, Livewire, Inertia, or any SSR/SPA
  implementation.
- **No NoSQL primary stores**: No MongoDB, Cassandra, DynamoDB as primary persistence. PostgreSQL
  is the system of record.
- **No shared mutable state**: No global variables, static state, or singletons holding mutable
  data.
- **No ORMs in domain**: No Eloquent models, query builders, or ORM constructs in domain layer.
  Use repository abstractions.

## Application Layering Rules

The system MUST organize code into the following layers, enforced by directory structure and
dependency rules:

### Layer Structure

```
app/Core/{BoundedContext}/
├── Domain/               # Pure domain logic, entities, value objects, domain services
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Exceptions/
├── Application/          # Use cases, application services, DTOs, ports
│   ├── UseCases/
│   ├── DTOs/
│   ├── Ports/
│   │   ├── In/          # Inbound ports (use case interfaces)
│   │   └── Out/         # Outbound ports (repository, external service interfaces)
│   └── Services/
└── Infrastructure/       # Framework-specific adapters
    └── Adapters/
        ├── In/
        │   ├── Api/     # HTTP inAdapters, request validation, response transformers
        │   └── Cli/     # Console commands
        └── Out/
            ├── Persistence/   # Eloquent models, repository implementations
            ├── Cache/         # Redis adapters
            └── External/      # Third-party service clients
```

### Dependency Direction Rules — ENFORCED

**MUST enforce the following dependency direction:**

```
Infrastructure → Application → Domain
```

- **Domain dependencies**: MUST depend only on other domain entities, value objects, or pure PHP.
  ZERO dependencies on application or infrastructure layers.
- **Application dependencies**: MAY depend on domain. MUST NOT depend on infrastructure.
  Application defines ports; infrastructure implements them.
- **Infrastructure dependencies**: MAY depend on both application and domain. Infrastructure
  adapters MUST implement application ports. Laravel-specific code lives here.

**Validation**:
- Use PHPStan with phpstan-strict-rules and phpstan-ddd to enforce layering constraints.
- Any violation detected in code review MUST be rejected.

## Ports and Adapters Conventions

### Inbound Ports (Use Case Interfaces)

- **Location**: `Application/Ports/In/`
- **Naming**: `{UseCase}UseCase.php` or `{Action}Command.php` (e.g.,
  `ObtenerTiposRequerimientosUseCase.php`, `GarantizarPermisosCommand.php`)
- **Signature**: Interface MUST define a single method (e.g., `execute(InputDTO): OutputDTO`)
- **Immutability**: Input DTOs MUST be immutable

### Outbound Ports (Repository & Service Interfaces)

- **Location**: `Application/Ports/Out/`
- **Naming**: `{Entity}Repository.php`, `{Service}Service.php` (e.g.,
  `TipoRequerimientoRepository.php`, `NotificacionService.php`)
- **Abstractions**: Interfaces MUST define domain-level contracts, not database-specific queries
  (e.g., `findById(idUsuario): User` not `executeRawQuery(string): array`)

### Inbound Adapters (InAdapters, CLI)

- **Location**: `Infrastructure/Adapters/In/Api/` or `In/Cli/`
- **Naming**: `{UseCase}InAdapter.php` (e.g., `ObtenerTiposRequerimientosInAdapter.php`)
- **Responsibilities**:
  - Request validation (Laravel Form Requests)
  - Input DTO construction from request data
  - Use case invocation 
  - Response serialization (JSON resource transformers)
- **Thin inAdapters**: No business logic, no direct database access, no domain rules.

### Outbound Adapters (Repositories, External Services)

- **Location**: `Infrastructure/Adapters/Out/Persistence/` or `Out/Cache/` or `Out/External/`
- **Naming**: `Eloquent{Entity}Repository.php`, `Redis{Entity}Cache.php` (e.g.,
  `EloquentTipoRequerimientoRepository.php`)
- **Responsibilities**:
  - Implement outbound port interfaces
  - Translate domain entities to/from infrastructure representations (Eloquent models, API
    responses, cache structures)
  - Handle infrastructure exceptions and translate to domain exceptions

## Validation Strategy

Validation MUST occur at appropriate boundaries with clearly defined responsibilities:

### Input Validation (HTTP Layer)

- **Location**: Laravel Form Requests in `Infrastructure/Adapters/In/Api/{UseCase}InAdapter/`
- **Responsibilities**: HTTP-level validation (required fields, types, formats, max lengths,
  regex patterns)
- **Failure mode**: Return 422 Unprocessable Entity with validation errors

### Domain Invariant Protection (Domain Layer)

- **Location**: Entity constructors, value object constructors, domain services
- **Responsibilities**: Enforce business rules and domain invariants that protect aggregate
  consistency (e.g., "PermissionLevel MUST be one of {READ, WRITE, ADMIN}", "Email MUST be
  syntactically valid")
- **Failure mode**: Throw domain exceptions (e.g., `EmailInvalidoException`,
  `NivelPermisosInvalidoException`)

### Business Rule Enforcement (Application Layer)

- **Location**: Application services (use cases)
- **Responsibilities**: Enforce cross-aggregate business rules, orchestration validations
  (e.g., "User MUST have permission before granting permission to others", "Cannot grant
  permission that does not exist")
- **Failure mode**: Throw application exceptions (e.g., `PermisoDenegadoException`,
  `PermisoNoEncontradoException`)

**Rule**: Validation MUST NOT be duplicated across layers. HTTP validation MUST NOT enforce
domain rules. Domain MUST NOT validate HTTP-specific concerns.

## Error Handling Strategy

Error handling MUST follow a structured, layered approach:

### Exception Hierarchy

```
Domain Exceptions (extend domain base exception)
└── EmailInvalidoException, PermisoNoEncontradoException

Application Exceptions (extend application base exception)
└── OperacionNoAutorizadaException

Infrastructure Exceptions (extend infrastructure base exception)
└── DatabaseConnectionException, ExternalServiceException, CacheException
```

### Exception Handling Rules

- **Domain exceptions**: Represent broken business rules or invariants. SHOULD include clear
  error messages suitable for translation and user display.
- **Application exceptions**: Represent orchestration failures, authorization issues, or
  application-level concerns.
- **Infrastructure exceptions**: Represent technical failures. MUST be caught in infrastructure
  adapters and either retried, logged, or translated to domain/application exceptions.
- **Never swallow exceptions**: All exceptions MUST be logged at appropriate level. Never catch
  and ignore without explicit justification.
- **Global exception handler**: Laravel global exception handler MUST translate exceptions to
  consistent JSON API error responses.

### API Error Response Format

All API errors MUST return JSON in the following structure:

```json
{
  "data": null,
  "message": "El campo email es requerido.",
  "code": "422",
  "success": false
}
```

## Database & Persistence Policies

### PostgreSQL as System of Record

- **Primary truth source**: PostgreSQL MUST be the authoritative data store. All persistent
  state MUST be written to PostgreSQL.
- **Migrations**: All schema changes MUST be versioned in Laravel migrations. No manual schema
  changes in production.
- **Naming conventions**:
  - Tables: `tb_{context}_{entity}` (e.g., `tb_cat_tipo_requerimiento`, `tb_permisos`)
  - Columns: snake_case, Spanish domain terms where appropriate
  - Foreign keys: `fk_{table}_{referenced_table}_{column}`
  - Indexes: `idx_{table}_{columns}`
- **Audit fields**: All domain tables MUST include: `created_at`, `updated_at`,
  `created_by_user_id`, `updated_by_user_id` (where applicable).
- **Soft deletes**: Use soft deletes (`deleted_at`) for data that must be retained for audit
  purposes. Hard deletes only for truly ephemeral data.

### Redis Usage Policies

- **Cache only**: Redis MUST be used ONLY for caching and temporary data (sessions, rate
  limiting, job queues). NEVER as primary data store.
- **Expiration required**: All cache entries MUST have explicit TTL (time-to-live). No
  indefinite cache entries.
- **Cache invalidation**: Cache MUST be invalidated on write operations. Defensive caching—assume
  cache can be lost at any time.
- **Key naming**: Use namespaced keys (e.g., `sader:tiposrequerimiento:{id}`,
  `sader:permissions:user:{userId}`)

### Repository Patterns

- **Abstractions in application**: Repository interfaces MUST be defined in
  `Application/Ports/Out/`.
- **Implementations in infrastructure**: Concrete implementations (using Eloquent) MUST live in
  `Infrastructure/Adapters/Out/Persistence/`.
- **Domain-oriented methods**: Repository methods MUST speak domain language, not SQL
  (e.g., `encontrarPermisosActivos(UserId): array<Permission>` not `rawQuery(...)`).
- **No Eloquent in use cases**: Use cases MUST depend on repository interfaces, not Eloquent
  models.

## Security Requirements

Security is paramount for a government system managing database access permissions:


### Input Sanitization

- **SQL injection prevention**: Use parameterized queries exclusively (Eloquent/Query Builder
  handles this). NEVER concatenate user input into raw SQL.
- **XSS prevention**: While no frontend exists, API responses MUST NOT include unsanitized user
  input that could be exploited by consuming applications.
- **CSRF protection**: Not applicable to stateless REST APIs, but session-based admin endpoints
  (if any) MUST use CSRF tokens.

### Secrets Management

- **No secrets in code**: No API keys, passwords, connection strings, or tokens in source code.
- **Environment variables**: All secrets MUST be provided via environment variables (`.env` file
  in local, environment config in production).
- **Encryption**: Sensitive data at rest (e.g., permission audit logs with PII) MUST be encrypted
  using Laravel's encryption facilities.

### Dependency Security

- **Regular updates**: Dependencies MUST be updated regularly. Run `composer audit` in CI/CD
  pipeline.
- **No known vulnerabilities**: Composer packages with critical CVEs MUST be updated or replaced
  immediately.

## Observability & Logging

Comprehensive observability is required for operational excellence:

### Logging Requirements

- **Structured logging**: Use structured JSON logs with contextual fields (user_id, request_id,
  trace_id, action, resource, result).
- **Log levels**: Follow standard log levels strictly:
  - **DEBUG**: Detailed diagnostic information
  - **INFO**: Significant application events (user action completed, permission granted)
  - **WARNING**: Recoverable issues (retry succeeded, deprecated API used)
  - **ERROR**: Operation failures requiring attention (database unavailable, external service
    timeout)
  - **CRITICAL**: System failures requiring immediate intervention
- **No sensitive data in logs**: NEVER log passwords, tokens, full credit card numbers, or PII
  beyond necessary identifiers.
- **Correlation IDs**: Every request MUST include a unique request_id propagated through all
  logs and external calls.

### Monitoring & Metrics

- **Health checks**: Expose `/health` and `/ready` endpoints for liveness and readiness probes.
- **Performance metrics**: Log response times, database query counts, cache hit rates.
- **Business metrics**: Log domain events (permission granted, access revoked, user role
  changed).

### Alerting

- **Critical alerts**: Failed authentication spikes, database connection failures, unexpected
  exceptions.
- **Threshold alerts**: API response time > 500ms, error rate > 1%, cache miss rate > 70%.

## Documentation Requirements

Documentation is a first-class deliverable:

### Code Documentation

- **PHPDoc blocks**: All public methods, classes, and interfaces MUST have PHPDoc comments
  describing purpose, parameters, return types, and exceptions thrown.
- **Inline comments**: Use inline comments sparingly for "why", not "what". Code should be
  self-explanatory.
- **README per bounded context**: Each bounded context directory (`app/Core/{Context}/`) SHOULD
  include a `README.md` explaining the context, key entities, and main use cases.

### API Documentation

- **OpenAPI 3.x specification**: MUST maintain an OpenAPI (Swagger) specification documenting
  all endpoints, request/response schemas, authentication, and error codes.
- **Example requests/responses**: Documentation MUST include example requests and responses for
  all endpoints.
- **Versioning**: API documentation MUST be versioned alongside API versions.

### Architecture Documentation

- **Architecture Decision Records (ADRs)**: Significant architectural decisions (e.g., "Why
  Hexagonal Architecture?", "Why PostgreSQL over MySQL?") MUST be documented as ADRs in `docs/adr/`.
- **Domain model diagrams**: SHOULD maintain UML diagrams or Mermaid diagrams illustrating key
  aggregates, entities, and relationships.
- **Onboarding guide**: MUST include `docs/onboarding.md` explaining project setup, architecture,
  conventions, and development workflow.

## Pull Request Standards

All code changes MUST go through pull requests:

### PR Requirements

- **Atomic commits**: Commits MUST be atomic (one logical change per commit) with clear,
  descriptive messages following Conventional Commits format (e.g., `feat:`, `fix:`, `refactor:`,
  `test:`, `docs:`).
- **Tests included**: PRs MUST include tests for all new functionality and bug fixes. Test
  coverage MUST NOT decrease.
- **PHPStan passing**: All code MUST pass PHPStan level 9 analysis.
- **Pint formatting**: Code MUST be formatted with Laravel Pint (`./vendor/bin/pint`).
- **No merge commits**: Use rebase workflow. Keep commit history linear.

### Code Review Checklist

Reviewers MUST verify:

- ✅ **Use case implemented with specialized agent**: Verify use case was implemented using
  `@hexagonal-architecture-specialist` agent (check for agent invocation in PR description,
  commit messages, or developer confirmation)
- ✅ Hexagonal Architecture principles respected (domain isolation, dependency direction)
- ✅ Tests included and passing
- ✅ Domain language consistency maintained
- ✅ No Laravel leakage into domain layer
- ✅ Repository abstractions used (no Eloquent in use cases)
- ✅ Error handling follows exception hierarchy
- ✅ API contracts follow REST conventions
- ✅ Logging includes appropriate context
- ✅ No secrets in code
- ✅ Documentation updated (API spec, PHPDocs, ADRs if applicable)

### Approval Gates

- **Required approvals**: 1 approval from a senior developer or tech lead.
- **CI/CD passing**: All CI/CD checks MUST pass (tests, static analysis, linting).
- **No open discussions**: All review comments MUST be resolved or explicitly deferred with
  follow-up issue created.

## Definition of Done

A feature or use case is "Done" when ALL of the following are complete:

- [ ] **Domain model implemented**: Entities, value objects, domain events created and tested
- [ ] **Use case implemented**: Application service implements use case interface
- [ ] **Ports defined**: Input and output ports (interfaces) defined in application layer
- [ ] **Adapters implemented**: Infrastructure adapters (controller, repository) implemented
- [ ] **Unit tests written**: Domain and application layer tests passing
- [ ] **Integration tests written**: Infrastructure adapter tests passing
- [ ] **API contract tests written**: Endpoint contract tests passing
- [ ] **API documented**: OpenAPI specification updated
- [ ] **Code reviewed**: PR reviewed and approved
- [ ] **CI/CD passing**: All checks green
- [ ] **Deployed to staging**: Feature deployed and manually verified in staging environment
- [ ] **No known defects**: No open bugs related to the feature

## Non-Functional Requirements

### Performance

- **API response time**: 95th percentile response time MUST be < 500ms for read operations,
  < 1s for write operations.
- **Database query optimization**: N+1 queries MUST be eliminated. Use eager loading where
  appropriate.
- **Caching**: Frequently accessed, rarely changing data MUST be cached in Redis with appropriate
  TTL.

### Scalability

- **Stateless API**: The API MUST be stateless (except for session-based auth if required).
  Horizontal scaling MUST be possible without shared state.
- **Database connection pooling**: Use connection pooling to manage PostgreSQL connections
  efficiently.

### Reliability

- **Uptime target**: 99.5% uptime in production.
- **Graceful degradation**: If Redis is unavailable, API MUST continue functioning with degraded
  performance (no cache).
- **Retry logic**: Transient failures (network errors, temporary service unavailability) MUST be
  retried with exponential backoff.

### maintainability

- **Test coverage**: Minimum 80% code coverage for domain and application layers. 60% overall.
- **Technical debt**: Technical debt MUST be tracked in issues. Dedicate 20% of sprint capacity
  to addressing technical debt.
- **Refactoring**: Continuous refactoring is encouraged. Broken windows MUST be fixed immediately.

## Forbidden Practices & Anti-Patterns

The following practices are strictly forbidden:

### Architecture Violations

- ❌ **Domain depending on infrastructure**: No `Illuminate\*` imports in domain layer
- ❌ **Business logic in controllers**: Controllers MUST NOT contain domain rules or decisions
- ❌ **Eloquent models in use cases**: Use cases MUST NOT depend on Eloquent models directly
- ❌ **Anemic domain models**: Entities MUST have behavior, not just getters/setters
- ❌ **God objects**: No classes with >500 lines or >20 methods (except rare justified cases)

### Code Quality Violations

- ❌ **Magic numbers**: Use named constants or configuration for all non-obvious numeric values
- ❌ **Primitive obsession**: Use value objects for domain concepts (don't pass strings for
  emails, IDs, etc.)
- ❌ **Deep nesting**: Max 3 levels of nesting. Extract methods for complex conditionals.
- ❌ **Long methods**: Methods MUST NOT exceed 50 lines (except rare justified cases)
- ❌ **Suppressed warnings**: No `@phpstan-ignore` or `@suppress` without explicit justification

### Data & Persistence Violations

- ❌ **Redis as source of truth**: NEVER store authoritative data solely in Redis
- ❌ **Missing transactions**: Multi-step write operations MUST use database transactions
- ❌ **Raw SQL without justification**: Use query builder or Eloquent. Raw SQL only for complex
  queries with explicit justification.
- ❌ **N+1 queries**: Unoptimized queries causing N+1 problems MUST be fixed immediately

### Security Violations

- ❌ **Hardcoded credentials**: No secrets in code, configuration files, or version control
- ❌ **SQL injection vulnerabilities**: No string concatenation in queries

## Governance

### Constitutional Authority

This constitution supersedes all conflicting practices, guidelines, or conventions. When in
doubt, constitution rules prevail.

### Amendment Procedure

Amendments to this constitution require:

1. **Proposal**: Document proposed change with rationale in a GitHub issue
2. **Discussion**: Minimum 1-week discussion period with team
3. **Approval**: Approval from 2/3 of senior developers and tech lead
4. **Documentation**: Update this document with sync impact report
5. **Migration**: Create migration plan for existing code if applicable
6. **Communication**: Announce changes to all contributors

### Versioning Policy

This constitution follows Semantic Versioning:

- **MAJOR version**: Backward-incompatible governance changes, principle removals, or
  redefinitions requiring code refactoring
- **MINOR version**: New principles added, new sections added, materially expanded guidance
- **PATCH version**: Clarifications, typo fixes, non-semantic refinements

### Compliance Review

- **PR reviews MUST verify constitutional compliance**: Every PR reviewer is responsible for
  checking adherence to principles.
- **Quarterly architecture reviews**: Tech lead MUST conduct quarterly reviews to identify
  constitutional violations and technical debt.
- **Exceptions**: Exceptions to constitutional rules MUST be explicitly justified in code
  comments, approved by tech lead, and tracked as technical debt.

### Runtime Guidance

For detailed development guidance, prompts, and agent-specific instructions, refer to:
- `.github/copilot-instructions.md` - General development guidance
- `.github/skills/arquitectura-hexagonal/SKILL.md` - Hexagonal architecture implementation
  guidance
- `.github/prompts/speckit.*.prompt.md` - Workflow-specific prompts

**Version**: 1.1.0 | **Ratified**: 2026-05-01 | **Last Amended**: 2026-05-01
