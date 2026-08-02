# Hexagonal Architecture Agent - Quick Start Guide

## Overview

The **hexagonal-usecase agent** is a specialized Copilot agent that implements use cases following Hexagonal Architecture and Domain-Driven Design (DDD) principles. It ALWAYS uses the `arquitectura-hexagonal` skill to ensure consistency and quality.

## When to Use This Agent

Use `@hexagonal-usecase` when you need to:

- ✅ Implement a new use case from scratch
- ✅ Create domain entities, value objects, or aggregates
- ✅ Add input adapters (REST API, CLI, Web Adapters)
- ✅ Add output adapters (external APIs, file systems, AWS services)
- ✅ Implement DTOs (Input/Output data transfer objects)
- ✅ Define ports (interfaces for use cases and external dependencies)
- ✅ Create repositories with proper separation of concerns
- ✅ Refactor existing code to follow hexagonal architecture
- ✅ Implement any DDD pattern (specifications, domain services, domain events, aggregates)

## How to Invoke

### Method 1: Via Agent Picker
1. Open Copilot chat
2. Click the agent selector (or type `@`)
3. Select `hexagonal-usecase`
4. Describe your use case

### Method 2: Direct Command
```
@hexagonal-usecase implement [your use case description]
```

## Input Format

Provide as much detail as possible:

**Minimum Required:**
- Use case description (what it does)
- Input data (parameters)
- Expected output
- Module/Bounded Context name

**Optional but Recommended:**
- Actor (who executes it)
- Business rules
- External systems involved
- Entry point type (API, Web, CLI)

## Example Invocations

### Example 1: Simple Use Case
```
@hexagonal-usecase implement a use case to verify if a beneficiary is eligible for a program

Input: beneficiaryId (string), programId (string)
Output: isEligible (boolean), reason (string)
Module: Programa
```

### Example 2: Detailed Use Case
```
@hexagonal-usecase implement a use case to register a new loan application

Input:
- applicantId (string)
- loanAmount (decimal)
- loanType (enum: personal, mortgage, auto)
- requestedTerm (int, months)

Output:
- applicationId (string)
- status (enum: pending, approved, rejected)
- message (string)

Module: Prestamo
Actor: Applicant (via REST API)
Business Rules:
- Applicant must be over 18 years old
- Loan amount must be between $1000 and $500000
- Applicant cannot have more than 3 active loans

External Systems:
- Credit score service (external API)
- Email notification service (AWS SES)
- MySQL database
```

### Example 3: Refactoring Request
```
@hexagonal-usecase refactor the existing LoanController to follow hexagonal architecture

Current file: app/Http/Controllers/LoanController.php
Business logic should move to Domain layer
Database queries should move to Repository
```

## What You'll Get

The agent generates a complete implementation including:

### Domain Layer (`app/Core/{Module}/Domain/`)
- **Entities** - Core business objects with identity
- **Value Objects** - Immutable values (Money, Email, etc.)
- **Domain Services** - Cross-entity business logic
- **Aggregates** - Clusters of entities with consistency boundaries
- **Specifications** - Reusable boolean business rules
- **Domain Events** - State change notifications
- **Domain Exceptions** - Business rule violation exceptions

### Application Layer (`app/Core/{Module}/Application/`)
- **Use Case** - Main orchestration logic
- **Input DTO** - Data coming from outside
- **Output DTO** - Data going to outside
- **Input Port** - Use case interface
- **Output Ports** - Interfaces for external dependencies

### Infrastructure Layer (`app/Core/{Module}/Infrastructure/`)
- **InAdapter** - Entry point (InAdapter, CLI, Queue handler)
- **OutAdapter** - Implements OutPort, interacts with external systems
- **Repository** - Database queries (Laravel Query Builder/Eloquent allowed here)

### Tests (`tests/Unit/Core/{Module}/`)
- **Unit Tests** - PHPUnit tests for use cases and domain logic

## Architecture Guarantees

The agent ensures:

✅ **Dependency Rule Enforced** - Outer layers depend on inner layers only
✅ **No Framework in Core** - Laravel code only in Infrastructure layer
✅ **Business Logic Isolated** - Domain/Application layers are framework-agnostic
✅ **All Interfaces Defined** - Clear contracts via Ports (interfaces)
✅ **Repository Pattern** - Proper data access abstraction
✅ **Naming Conventions** - Consistent, standardized names
✅ **Complete Implementation** - No placeholders or TODOs
✅ **Tested Code** - Unit tests included

## File Structure Generated

```
app/Core/{Module}/
├── Application/
│   ├── Ports/
│   │   ├── In/
│   │   │   └── I{UseCase}UseCase.php          # Input Port (interface)
│   │   └── Out/
│   │       └── I{Entity}OutPort.php           # Output Port (interface)
│   ├── DTOs/
│   │   ├── In/
│   │   │   └── {UseCase}InDto.php             # Input DTO
│   │   └── Out/
│   │       └── {UseCase}OutDto.php            # Output DTO
│   └── UseCases/
│       └── {UseCase}UseCase.php                # Use Case implementation
├── Domain/
│   ├── Entities/
│   │   └── {Entity}Entity.php
│   ├── ValueObjects/
│   │   └── {ValueObject}ValueObject.php
│   ├── Services/
│   │   └── {Service}DomainService.php
│   ├── Specifications/
│   │   └── {Rule}Specification.php
│   ├── Events/
│   │   └── {Event}DomainEvent.php
│   ├── Exceptions/
│   │   └── {Exception}DomainException.php
│   └── Aggregates/
│       └── {Aggregate}Aggregate.php
└── Infrastructure/
    └── Adapters/
        ├── In/
        │   └── {Type}/
        │       └── {UseCase}InAdapter.php     # InAdapter
        └── Out/
            └── {Provider}/
                ├── {Entity}OutAdapter.php      # OutAdapter
                └── Repositories/
                    └── {Entity}Repository.php  # Repository
```

## Best Practices

### Do's ✅
- Provide clear, detailed use case descriptions
- Specify all input/output data types
- Mention business rules upfront
- Indicate which external systems are involved
- Let the agent generate ALL files (don't request partial implementations)

### Don'ts ❌
- Don't ask for "just the controller" - let it generate the full stack
- Don't request Laravel-specific code in Domain layer
- Don't skip testing - tests are generated automatically
- Don't mix business logic with infrastructure concerns

## Troubleshooting

### Agent Not Found
**Solution:** Ensure `.github/agents/hexagonal-usecase.agent.md` exists and VS Code Copilot is reloaded

### Wrong Architecture Generated
**Problem:** Generated code doesn't follow hexagonal architecture
**Solution:** The agent is configured to ALWAYS use the arquitectura-hexagonal skill. If this happens, report it as a bug.

### Missing Files
**Problem:** Not all expected files were generated
**Solution:** Ask the agent to complete the implementation: `@hexagonal-usecase complete the missing artifacts for [use case name]`

### Business Logic in Wrong Layer
**Problem:** Business logic ended up in Infrastructure layer
**Solution:** Ask for refactoring: `@hexagonal-usecase refactor [file] to move business logic to Domain layer`

## Advanced Usage

### Implementing Multiple Related Use Cases
```
@hexagonal-usecase implement the following use cases for the Customer module:

1. CreateCustomer - creates new customer with validation
2. UpdateCustomer - updates existing customer data  
3. GetCustomerDetails - retrieves customer information
4. DeactivateCustomer - soft deletes a customer

Share common domain entities and value objects across all use cases.
```

### Adding to Existing Module
```
@hexagonal-usecase add a new use case to the existing Loan module

Use case: CalculateMonthlyPayment
Input: loanAmount, interestRate, termInMonths
Output: monthlyPayment, totalInterest, amortizationSchedule
Reuse existing LoanEntity and MoneyValueObject
```

## Learning Resources

- **Main Skill Documentation:** [.github/skills/arquitectura-hexagonal/SKILL.md](../skills/arquitectura-hexagonal/SKILL.md)
- **Architecture Overview:** [references/ARCHITECTURE.md](../skills/arquitectura-hexagonal/references/ARCHITECTURE.md)
- **Templates:** [templates/](../skills/arquitectura-hexagonal/templates/)
- **Examples:** [references/*_EXAMPLES.md](../skills/arquitectura-hexagonal/references/)
- **Naming Conventions:** [references/NAMING_CONVENTIONS.md](../skills/arquitectura-hexagonal/references/NAMING_CONVENTIONS.md)

## Support

For issues or questions:
1. Check the ARCHITECTURE.md for architectural patterns
2. Review example files in `/references/` directory
3. Validate against the CHECKLIST.md
4. Ask the agent to explain its decisions: `@hexagonal-usecase explain why you generated [component] this way`
