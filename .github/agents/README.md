# Hexagonal Architecture Agents

This directory contains specialized VS Code Copilot agents for implementing hexagonal architecture and DDD patterns.

## Available Agents

### hexagonal-usecase.agent.md

**Specialized agent for implementing use cases with Hexagonal Architecture & DDD**

**Capabilities:**
- Implements complete use cases from description
- Generates all required artifacts (Domain, Application, Infrastructure layers)
- Always uses the `arquitectura-hexagonal` skill
- Creates unit tests automatically
- Validates architectural constraints

**Tool Access:**
- `read` - Read files and documentation
- `edit` - Create and modify code files
- `search` - Find existing code and patterns
- `execute` - Run tests and validation
- `todo` - Track implementation progress

**Invocation:**
```
@hexagonal-usecase implement a use case to [description]
```

**Key Constraints:**
- ALWAYS uses the arquitectura-hexagonal skill
- NO business logic in Infrastructure layer
- NO framework dependencies in Domain/Application layers
- MUST generate ALL artifacts (no partial implementations)
- MUST follow naming conventions and folder structure

## Agent File Format

All `.agent.md` files follow this structure:

```yaml
---
description: "Agent purpose with trigger keywords"
tools: [tool, aliases]
argument-hint: "What to pass when invoking"
---

Agent instructions and behavior...
```

## Creating New Agents

1. Create `your-agent-name.agent.md` in this directory
2. Define clear description with trigger keywords
3. Specify minimal tool set needed
4. Document approach and constraints
5. Test invocation via agent picker or `@your-agent-name`

## Documentation

- **Main Documentation:** [.github/AGENTS.md](../AGENTS.md)
- **Skill Reference:** [.github/skills/arquitectura-hexagonal/](../skills/arquitectura-hexagonal/)
- **Templates:** [.github/skills/arquitectura-hexagonal/templates/](../skills/arquitectura-hexagonal/templates/)
- **Agent Customization Docs:** [VS Code Copilot Agent Docs](https://code.visualstudio.com/docs/copilot/customization/custom-agents)
