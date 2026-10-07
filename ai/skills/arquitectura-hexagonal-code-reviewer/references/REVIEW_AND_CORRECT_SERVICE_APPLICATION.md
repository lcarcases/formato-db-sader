# Application Service Review and Correction Checklist (Mandatory)

Use this checklist to review or correct any Application Service. The objective is to keep Application Services consistent, reusable, and easy for AI agents to validate with clear pass/fail criteria.

## 1. Purpose of the Service

1. An Application Service must represent one clear application capability.
2. Its name must describe the capability it provides.
3. It must not represent the primary user-facing business goal unless the goal is intentionally reusable.
4. The service should be reused by one or more Use Cases.

## 2. Single Responsibility Rule

1. An Application Service must do only one thing.
2. It must not mix unrelated responsibilities in the same class.
3. If a class handles multiple capabilities, split it into separate services.

## 3. Coordination Rule

1. The service must coordinate domain objects, application ports, or other collaborators needed to achieve the capability.
2. It may orchestrate multiple steps to accomplish the application goal.
3. It must not contain direct infrastructure implementation details.
4. It must not directly access the database or framework services unless they are behind ports.

## 4. Reuse Rule

1. The service must be potentially useful to more than one Use Case.
2. If the logic is duplicated across multiple Use Cases, extract it into an Application Service.
3. If the capability is one-off and not reused, it likely belongs to a Use Case instead.

## 5. Domain Rule

1. The service must not own core domain rules.
2. Domain validation, policies, and business logic belong to domain entities, value objects, specifications, or domain services.
3. The service coordinates these pieces, but does not become the domain authority.

## 6. Forbidden Patterns

1. A service that mixes multiple unrelated capabilities.
2. A service that contains database access logic directly.
3. A service that owns core business rules instead of domain classes.
4. A service that is only used once and is not reusable.
5. A service that exposes HTTP or transport-specific behavior.

## 7. Mandatory Review Actions

For each Application Service under review, the agent must verify:

1. The class represents one application capability.
2. The class name clearly describes that capability.
3. The service coordinates domain objects and/or ports.
4. The behavior is potentially reusable across more than one Use Case.
5. Domain business rules are not duplicated in the service.
6. The service stays inside the Application layer and avoids infrastructure leakage.

## 8. Acceptance Criteria

An Application Service passes review only if all checks are true:

1. It has a single, well-defined responsibility.
2. It is reusable or clearly designed for reuse.
3. It orchestrates collaborators rather than owning core rules.
4. It depends on ports/contracts instead of concrete infrastructure classes.
5. It does not mix multiple capabilities or business domains.
6. Domain logic remains in the Domain layer.

## 9. Decision Shortcut

Use this quick decision rule:

- If the behavior is a primary user goal -> Use Case
- If the behavior is a reusable capability -> Application Service
- If the behavior is core business logic -> Domain class

