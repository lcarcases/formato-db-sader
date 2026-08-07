---
name: github
description: Especialista en la plataforma GitHub para ejecutar operaciones de GitHub a través del MCP de GitHub. Se utiliza cuando se desea gestionar Issues, Branches, Pull Requests, Releases, Projects, Discussions, Workflows y metadatos del repositorio. Nunca realiza operaciones locales de Git.
---

## GOAL

## Goal

The goal of this skill is to enable AI agents to interact with GitHub through the GitHub MCP Server in a safe, consistent, and execution-oriented manner. It provides standardized guidance for executing GitHub platform operations—such as managing repositories, issues, pull requests, branches, releases, projects, discussions, labels, milestones, GitHub Actions, workflow runs, and repository content—while promoting GitHub best practices and minimizing user intervention.

This skill ensures that agents:

- Execute GitHub operations using the available GitHub MCP tools whenever possible.
- Prefer execution over explanation.
- Validate prerequisites before performing operations.
- Generate high-quality GitHub artifacts (issues, pull requests, releases, discussions, etc.).
- Prevent unsafe or destructive actions unless explicitly confirmed by the user.
- Ask only for information that is required to complete an operation.
- Do not execute local Git operations (such as `git add`, `git commit`, `git push`, `git merge`, and `git rebase`) to a dedicated Git agent.

## When to Use This Skill

When someone needs to interact with GitHub resources through the GitHub MCP Server.

This skill should be invoked for any operation involving the GitHub platform rather than the local Git repository.

### Repository Management

Use this skill when you need to:

- Create, update, or delete repositories.
- Retrieve repository information or metadata.
- Configure repository settings.
- Manage collaborators or teams.
- Analyze repository health or activity.

### Issue Management

Use this skill when you need to:

- Create, update, close, or reopen issues.
- Search for existing issues.
- Add comments to issues.
- Apply or modify labels.
- Assign users.
- Associate issues with milestones or projects.
- Detect duplicate issues.
- Generate high-quality issue descriptions.

### Pull Request Management

Use this skill when you need to:

- Create Pull Requests.
- Update Pull Requests.
- Merge or close Pull Requests.
- Request reviews.
- Review Pull Requests.
- Add review comments.
- Link Pull Requests to issues.
- Analyze Pull Request status.

### Branch Management (Remote)

Use this skill when you need to:

- Create remote branches.
- List remote branches.
- Inspect branch information.
- Manage branch-related GitHub resources.

> Local branch creation, switching, merging, rebasing, and deletion are out of the scope of this skill.

### Release Management

Use this skill when you need to:

- Create releases.
- Generate release notes.
- Publish releases.
- Manage release metadata.
- Inspect existing releases.
- Manage repository tags through GitHub.

### Labels, Milestones, and Projects

Use this skill when you need to:

- Create or manage labels.
- Create or manage milestones.
- Organize GitHub Projects.
- Add issues or Pull Requests to projects.
- Track project progress.

### GitHub Discussions

Use this skill when you need to:

- Create discussions.
- Search discussions.
- Manage discussion categories.
- Convert discussions into actionable issues.

### GitHub Actions

Use this skill when you need to:

- Inspect workflows.
- Trigger workflow runs.
- Monitor workflow execution.
- Analyze failed workflows.
- Retrieve workflow run information.

### Repository Content

Use this skill when you need to:

- Read repository files.
- Create files.
- Update files.
- Delete files.
- Search repository code.

### Repository Analysis

Use this skill when you need to:

- Analyze repository structure.
- Review repository activity.
- Inspect contributors.
- Evaluate labels and milestones.
- Identify stale issues or Pull Requests.
- Recommend repository improvements.

---

## Do NOT Use This Skill

Do **not** use this skill for local Git operations.

The following operations belong to a dedicated Git skill or Git agent:

- `git init`
- `git clone`
- `git status`
- `git add`
- `git commit`
- `git push`
- `git pull`
- `git fetch`
- `git checkout`
- `git switch`
- `git merge`
- `git rebase`
- `git cherry-pick`
- `git reset`
- `git restore`
- `git stash`
- `git clean`
- `git tag`
- `git reflog`
- `git bisect`
- `git blame`

---

## Guiding Principle

If the requested operation affects a **GitHub resource**, use this skill.

If the requested operation affects the **local Git repository**, delegate it to the Git skill.

## How interact with GitHub MCP

To successfully interact and carry out your tasks with the GitHub MCP, follow these guidelines:

Prompt format: 

Using the GitHub MCP tool, [task to be executed] in the repository 'usuario_dueño_repositorio/tu_repositorio'. > [expected input parameters]

---
Create an issue:

Using the GitHub MCP tool, create a new issue in the repository 'usuario_dueño_repositorio/tu_repositorio'. > Title: 'Nombre_del_Issue'  
Body: 'Descripción_del_issue'

Example:

Using the GitHub MCP tool, create a new issue in the repository 'lcarcases/formato-db-sader'. > Title: 'Prueba Lisandro'  
Body: 'Este es un issue creado automáticamente vía MCP desde VS Code.'

---

Create a branch from an issue:

Using the GitHub MCP tool, create a new branch in repository 'usuario_dueño_repositorio/tu_repositorio'.  
Branch name: nombre_rama 
Base branch: rama_base  
Context: Descripcion.

Example:

Using the GitHub MCP tool, create a new branch in repository 'lcarcases/formato-db-sader'.  
Branch name: feature/issue-2-prueba-lisandro  
Base branch: main  
Context: This branch is intended to work on issue #2.

---