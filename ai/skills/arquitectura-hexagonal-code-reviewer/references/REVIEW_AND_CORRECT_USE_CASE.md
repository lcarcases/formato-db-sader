# Use Case Review and Correction Checklist (Mandatory)

Use this checklist to review and correct any use case. The objective is to keep use cases consistent, reusable, and easy for AI agents to validate with clear pass or fail criteria.

## 1. Role of the Use Case

1. The use case must act primarily as an orchestrator.
2. Its main responsibility is coordinating application flow and invoking domain behavior.
3. Most business rules should live in domain classes, not in the use case itself.
4. If the use case contains non-orchestration business logic, verify whether that logic belongs in:
   - an existing domain class, or
   - a new domain class that should be created.
5. Coordination logic is allowed when it is strictly related to application flow.

## 2. Output Contract

1. The use case must not return an Out DTO.
2. The use case must return raw array data.
3. Out DTO creation belongs to the inbound adapter layer, not to the use case.

## 3. Dependency Rules

1. The use case must depend on ports/interfaces, not on concrete infrastructure implementations.
2. The use case must not import framework-specific classes.
3. The use case may depend on domain classes and application contracts only.

## 4. Forbidden Patterns

1. Returning HTTP responses from a use case.
2. Creating Out DTOs inside a use case.
3. Calling infrastructure adapters directly from a use case.
4. Embedding heavy domain logic that should belong to entities, value objects, or domain services.

## 5. Mandatory Review Actions

For each use case under review, the agent must verify:

1. The class behavior is orchestration-first.
2. Business logic placement is correct, or relocation is recommended.
3. The return value is raw array data.
4. Dependencies respect Hexagonal Architecture direction.
5. No framework or infrastructure coupling exists in the use case.

## 6. Acceptance Criteria

A use case passes review only if all conditions are true:

1. Orchestration is the dominant responsibility.
2. Non-orchestration business logic is either justified or moved to domain classes.
3. Output is a raw array and no Out DTO is returned.
4. Dependencies are interface-driven and layer-safe.
5. No infrastructure or framework leakage exists.

