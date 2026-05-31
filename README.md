# SADER Database Access Permissions API

Internal REST API system for managing database access permissions within SADER (Secretaría de
Agricultura y Desarrollo Rural), a Mexican government institution.

## Project Overview

**Purpose**: Manage database access permissions, user roles, and audit access control within
SADER's infrastructure.

**Architecture**: Hexagonal Architecture (Ports & Adapters) with Domain-Driven Design (DDD)
principles.

**Scope**: Backend REST API only—no frontend, SSR, Blade templates, Livewire, or SPA.

## Technical Stack

- **PHP**: 8.4+
- **Framework**: Laravel 13.x (infrastructure layer only)
- **Database**: PostgreSQL 16.x (system of record)
- **Cache**: Redis 7.4.x (cache only, not source of truth)
- **Quality**: PHPStan Level 9, PSR-12 (Laravel Pint)

## Architecture Principles

This project strictly follows:

1. **Hexagonal Architecture**: Domain core isolated from infrastructure
2. **Domain-Driven Design**: Aggregates, entities, value objects, ubiquitous language
3. **Domain Isolation**: Zero Laravel dependencies in domain layer
4. **Test-First**: Mandatory unit, integration, and contract tests
5. **SOLID Principles**: Clean code, explicit contracts, immutability
6. **API-First**: RESTful JSON endpoints with OpenAPI documentation

**Read the full constitution**: `.specify/memory/constitution.md`

## Project Structure

```
app/Core/{BoundedContext}/
├── Domain/               # Pure PHP domain logic (framework-agnostic)
│   ├── Entities/        # Domain entities with behavior
│   ├── ValueObjects/    # Immutable value objects
│   ├── Events/          # Domain events
│   └── Exceptions/      # Domain-specific exceptions
├── Application/          # Use cases and application services
│   ├── UseCases/        # Application service implementations
│   ├── DTOs/            # Input/Output data transfer objects
│   └── Ports/
│       ├── In/          # Inbound ports (use case interfaces)
│       └── Out/         # Outbound ports (repository interfaces)
└── Infrastructure/       # Laravel-specific adapters
    └── Adapters/
        ├── In/
        │   └── Api/     # HTTP controllers, requests, resources
        └── Out/
            └── Persistence/  # Eloquent models, repository implementations
```

**Dependency Direction** (ENFORCED):
```
Infrastructure → Application → Domain
```

Domain NEVER depends on Laravel. Infrastructure adapters implement application ports.

## Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.4+ (local development)
- Composer 2.x
- PostgreSQL 16.x (via Docker)
- Redis 7.4.x (via Docker)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd formato-db-sader
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Configure database and Redis connections in `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=sader_permissions
   DB_USERNAME=your_user
   DB_PASSWORD=your_password

   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

4. **Start Docker services**
   ```bash
   docker-compose up -d
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```

### Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Code Quality

```bash
# Static analysis (PHPStan level 9)
./vendor/bin/phpstan analyse

# Code formatting (PSR-12)
./vendor/bin/pint

# Check formatting without changes
./vendor/bin/pint --test
```

## Development Workflow

### Implementing New Features

**IMPORTANT**: All use case implementations MUST use the `Hexagonal Architecture Specialist`
agent to ensure proper architectural compliance.

1. **Create feature specification**
   ```
   /speckit.specify [feature description]
   ```

2. **Create implementation plan**
   ```
   /speckit.plan
   ```

3. **Generate tasks**
   ```
   /speckit.tasks
   ```

4. **Implement use case with hexagonal architecture**
   ```
   @Hexagonal Architecture Specialist implement [use case description]
   ```

### Layer Responsibilities

- **Domain Layer**: Business rules, entities, value objects, domain services. Pure PHP only.
- **Application Layer**: Use cases (orchestration), DTOs, port interfaces. Framework-agnostic.
- **Infrastructure Layer**: Controllers, Eloquent models, repositories, external services.
  Laravel-specific code lives here.

### Testing Requirements

- **Unit Tests**: All domain entities, value objects, and application use cases
- **Integration Tests**: Repository implementations, external service adapters
- **Contract Tests**: API endpoint request/response contracts

**Minimum Coverage**: 80% for domain/application layers, 60% overall.

## API Documentation

API documentation is maintained using OpenAPI 3.x specification.

**Convention**: All endpoints MUST be documented in the OpenAPI spec before implementation.

Example endpoints:
```
GET    /api/v1/tipos-requerimiento      # List requirement types
POST   /api/v1/tipos-requerimiento      # Create requirement type
GET    /api/v1/tipos-requerimiento/{id} # Get requirement type
PUT    /api/v1/tipos-requerimiento/{id} # Update requirement type
DELETE /api/v1/tipos-requerimiento/{id} # Delete requirement type
```

## Database Conventions

- **Tables**: `tb_{context}_{entity}` (e.g., `tb_cat_tipo_requerimiento`)
- **Columns**: snake_case, use Spanish domain terms where appropriate
- **Foreign Keys**: `fk_{table}_{referenced_table}_{column}`
- **Indexes**: `idx_{table}_{columns}`
- **Audit Fields**: All tables include `created_at`, `updated_at`, `created_by_user_id`,
  `updated_by_user_id`

## Security

- **Authentication**: Laravel Sanctum (API tokens)
- **Authorization**: Role-Based Access Control (RBAC)
- **Audit Logging**: All permission grants/revocations logged with user, timestamp, IP
- **Secrets**: All credentials via environment variables (never in code)
- **Input Validation**: Form Requests at HTTP layer, invariants in domain

## Contributing

### Pull Request Requirements

- [ ] Hexagonal Architecture principles respected
- [ ] Tests included and passing (unit, integration, contract)
- [ ] PHPStan level 9 passing
- [ ] Laravel Pint formatting applied
- [ ] API OpenAPI spec updated (if API changes)
- [ ] Domain language consistency maintained
- [ ] No Laravel dependencies in domain layer
- [ ] Conventional Commits message format

### Code Review Checklist

Reviewers MUST verify:
- Domain isolation (no framework leakage)
- Dependency direction correctness
- Repository abstractions used (no Eloquent in use cases)
- Test coverage adequate  - Error handling follows exception hierarchy
- Logging includes appropriate context

See `.specify/memory/constitution.md` for complete governance rules.

## Project Governance

Development is governed by the **Project Constitution** at `.specify/memory/constitution.md`
(v1.0.0). All contributors MUST comply with constitutional principles and standards.

The constitution defines:
- Architectural mandates
- Design principles
- Code quality standards
- Testing requirements
- Security requirements
- Forbidden practices
- Definition of Done

## License

[Specify License]

## Contact

For questions or issues, contact [SADER IT Team Contact Information]

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
