---
name: 'SpecKit Workflow Executor'
description: "Runs the full Spec-Driven Development pipeline (speckit-specify -> speckit-clarify -> speckit-plan -> speckit-tasks -> speckit-analyze -> speckit-implement) end-to-end for a single feature. Takes as its argument the path to an enriched user story file under userStory/enriched/ (produced by the User Story Enricher agent) and drives each speckit skill in strict order, carrying forward the spec.md/plan.md/tasks.md paths between steps. Use when: the user has an enriched user story ready and wants it turned into a spec, plan, tasks, and implemented code without manually invoking each speckit command."
tools: [read, edit, search, agent, todo]
argument-hint: "Path to an enriched user story file under userStory/enriched/"
---

@ai/agents/speckit-workflow.agent.md
