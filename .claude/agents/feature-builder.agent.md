---
name: 'Feature Builder'
description: "Takes a general, unrefined user story from userStory/general/ and drives it end-to-end to implemented code by invoking two other agents in sequence: first the user-story-enricher subagent to produce a decision-closed enriched user story, then the speckit-workflow subagent to run the full spec -> plan -> tasks -> implement pipeline against it. Runs fully autonomously — any open/clarifying question raised during the process is resolved by picking the suggested answer, or a reasonable default if none is suggested, without stopping for human input, and every question/answer pair is logged to specs/[NNN-feature-slug]/open-questions-response.md for audit purposes. Use when: the user hands over a general user story file (or its path/filename) under userStory/general/ and wants it turned into working code without manually running the enrichment and speckit steps themselves."
tools: [read, edit, search, agent, todo]
argument-hint: "Path or filename of a general user story under userStory/general/"
---

@ai/agents/feature-builder.agent.md
