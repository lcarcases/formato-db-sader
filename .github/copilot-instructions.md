<!-- SPECKIT START -->
For additional context about technologies to be used, project structure,
shell commands, and other important information, read the current plan at:
specs/001-catalogo-tipos-personal/plan.md
<!-- SPECKIT END -->

# SADER Database Access Permissions API - Development Guidelines

## Constitutional Principles

**CRITICAL**: All development MUST comply with the project constitution at
`.specify/memory/constitution.md` (v1.0.0).

### Core Architectural Mandates

1. **Hexagonal Architecture (Ports & Adapters)** - All features MUST follow hexagonal architecture
   with strict domain/application/infrastructure layer separation
2. **Domain-Driven Design** - Apply DDD tactical patterns (aggregates, entities, value objects,
   domain events, repositories)
3. **Domain Isolation** - Domain layer MUST be framework-agnostic (zero Laravel dependencies)
4. **Test-First** - Unit tests for use cases, integration tests for adapters are mandatory
5. **Explicit Contracts** - Use immutable DTOs, explicit input/output models, and ports (interfaces)
6. **Ubiquitous Language** - Domain terminology MUST be consistent across code, database, APIs,
   tests, and docs
7. **SOLID & Clean Code** - PSR-12 compliance, PHPStan level 9, SOLID principles enforced
8. **API-First** - REST-only system (no frontend, SSR, Blade, Livewire)

### Implementation Workflow

**For all use case implementations, MUST use the `Hexagonal Architecture Specialist` agent**:

```
@Hexagonal Architecture Specialist implement [use case description]
```

This ensures proper ports & adapters implementation with DDD patterns.

### Project Structure

```
app/Core/{BoundedContext}/
├── Domain/               # Pure PHP, zero framework dependencies
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Events/
│   └── Exceptions/
├── Application/          # Use cases, DTOs, ports (interfaces)
│   ├── UseCases/
│   ├── DTOs/
│   └── Ports/
│       ├── In/          # Inbound ports (use case interfaces)
│       └── Out/         # Outbound ports (repository interfaces)
└── Infrastructure/       # Laravel-specific adapters
    └── Adapters/
        ├── In/
        │   └── Api/     # Controllers, Form Requests, Resources
        └── Out/
            └── Persistence/  # Eloquent models, repositories
```

### Dependency Direction (ENFORCED)

```
Infrastructure → Application → Domain
```

- Domain depends ONLY on pure PHP
- Application MAY depend on domain
- Infrastructure MAY depend on application and domain
- NEVER reverse these dependencies

### Quick Reference

- **Technical Stack**: Laravel 13, PHP 8.4+, PostgreSQL 16, Redis 7.4
- **Testing**: PHPStan level 9, PSR-12 (Pint), unit/integration/contract tests required
- **Database**: PostgreSQL = source of truth, Redis = cache only
- **API**: REST JSON, versioned (e.g., `/api/v1/...`), OpenAPI documented
- **Logging**: Structured JSON logs with context (user_id, request_id, action)
- **Security**: RBAC, audit logging, no secrets in code

For complete details, see `.specify/memory/constitution.md`
