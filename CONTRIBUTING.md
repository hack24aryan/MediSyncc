# Contributing to MediSyncc

Thank you for your interest in contributing to MediSyncc! This document outlines the conventions and workflow used by the core team.

---

## Table of Contents

1. [Branch Naming](#branch-naming)
2. [Commit Style](#commit-style)
3. [Pull Requests](#pull-requests)
4. [Code Formatting](#code-formatting)
5. [Reporting Issues](#reporting-issues)

---

## Branch Naming

Use the following prefixes when creating branches:

| Prefix       | Purpose                              | Example                        |
|--------------|--------------------------------------|--------------------------------|
| `feature/`   | New features                         | `feature/nominee-alerts`       |
| `fix/`       | Bug fixes                            | `fix/login-redirect`           |
| `hotfix/`    | Critical production fixes            | `hotfix/session-crash`         |
| `chore/`     | Dependency updates, config changes   | `chore/update-gitignore`       |
| `docs/`      | Documentation updates only           | `docs/readme-update`           |
| `refactor/`  | Code cleanup without behaviour change| `refactor/db-connection`       |

> Always branch off `main` and keep branches short-lived.

---

## Commit Style

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <short description>
```

### Types

| Type       | When to use                                   |
|------------|-----------------------------------------------|
| `feat`     | A new feature                                 |
| `fix`      | A bug fix                                     |
| `docs`     | Documentation changes only                    |
| `style`    | Formatting, missing semicolons, etc.          |
| `refactor` | Code change that is neither fix nor feature   |
| `chore`    | Build tasks, dependency updates               |
| `test`     | Adding or updating tests                      |

### Examples

```bash
feat(dashboard): add weekly adherence chart
fix(auth): redirect unauthenticated users to auth.php
docs(readme): add quick start installation section
chore(env): add .env.example template
```

> Keep the subject line under 72 characters. Use the body to explain *why*, not *what*.

---

## Pull Requests

1. **Base branch** — Always open PRs against `main`.
2. **Title** — Use the same format as commit messages.
3. **Description** — Include:
   - What the PR does.
   - Why the change is needed.
   - Steps to test it.
4. **Size** — Keep PRs focused and small. One logical change per PR.
5. **Review** — At least one approval from the other contributor is required before merging.
6. **Squash commits** before merging to keep history clean.

---

## Code Formatting

### PHP

- Follow **PSR-12** coding standards.
- Use 4-space indentation (no tabs).
- Always use `require_once` instead of `require` or `include` for configuration files.
- Sanitize all user input using `htmlspecialchars()`, prepared statements, or `real_escape_string()`.
- Never hardcode database credentials — use `.env` via `load_env.php`.

### HTML / CSS

- Use semantic HTML5 elements (`<nav>`, `<main>`, `<footer>`, `<section>`, etc.).
- Use CSS custom properties (`var(--accent)`) for all theme colours — no inline hex values.
- Keep CSS scoped to page or component where possible.

### JavaScript

- Use `const` and `let` — never `var`.
- Prefer `async/await` over raw Promise chains.
- Always handle `catch` blocks in `fetch()` calls.

---

## Reporting Issues

If you find a bug or want to suggest a feature:

1. [Open a GitHub Issue](../../issues/new).
2. Use a clear, descriptive title.
3. Include steps to reproduce (for bugs) or a use-case description (for features).
4. Tag with the appropriate label (`bug`, `enhancement`, `documentation`, etc.).

---

*Questions? Reach out to the authors listed in [AUTHORS.md](./AUTHORS.md).*
