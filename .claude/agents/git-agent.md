---
name: git-agent
description: >
  Canonical, IDE-agnostic specification for the Git Agent: a specialist that safely
  stages, commits, and pushes local Git changes, syncs the local branch with its
  remote via fetch/pull, and switches between existing branches via checkout.
  This is the single source of truth for the agent's mission, allowed/forbidden
  operations, workflow, and safety rules. IDE-specific adapters (Claude Code,
  GitHub Copilot, Codex, OpenCode, ...) should read and follow this file at
  runtime rather than duplicating its rules.
scope: local-git-only
---

@ai/agents/git-agent.md
