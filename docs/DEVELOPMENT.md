# Development Workflow

This document outlines the Git workflow for contributing to this WordPress plugin repository. The workflow follows a structured branching model to ensure a smooth development process.

## Branching Strategy

- **`develop`** → Main branch for ongoing development and integration.
- **Feature branches** → `feature/issue-name` (e.g., `feature/add-plugin`)
- **Bug fix branches** → `fix/issue-name` (e.g., `fix/phpcs-errors`)
- **Chore branches** → `chore/issue-name` (e.g., `chore/pr-template`)
- **Refactoring branches** → `refactor/issue-name` (e.g., `refactor/button-styles`)

### Naming Convention
The `issue-name` in branch names should be replaced with a descriptive issue name related to the changes. Always check that an issue exists for the proposed change before raising a PR. If none exists, first create an issue.

---

## 1️⃣ Development Process

1. Checkout `develop` and update it:
   ```sh
   git checkout develop
   git pull origin develop
   ```
2. Create a new feature or bug fix branch:
   ```sh
   git checkout -b feature/issue-name  # For features
   git checkout -b fix/issue-name      # For bug fixes
   ```
3. Implement your changes and commit:
   ```sh
   git add .
   git commit -m "[type] Short description of change"
   ```
4. Push your branch and create a Pull Request (PR) to `develop`:
   ```sh
   git push origin feature/issue-name
   ```
5. Always link the PR to the GitHub issue it resolves ([Learn more](https://docs.github.com/en/issues/tracking-your-work-with-issues/using-issues/linking-a-pull-request-to-an-issue)).
6. Once reviewed and approved, merge into `develop`.

---

## 2️⃣ Release Process

1. Ensure `develop` is stable and up to date:
   ```sh
   git checkout develop
   git pull origin develop
   ```
2. Create a release branch:
   ```sh
   git checkout -b release/x.y.z
   ```
3. Perform final testing and make necessary fixes.
4. Merge the release branch into `develop` to keep it in sync:
   ```sh
   git checkout develop
   git merge release/x.y.z
   git push origin develop
   ```
5. Create and push a GitHub tag:
   ```sh
   git tag -a vX.Y.Z -m "Release version X.Y.Z"
   git push origin vX.Y.Z
   ```
6. Delete the release branch:
   ```sh
   git branch -d release/x.y.z
   git push origin --delete release/x.y.z
   ```

---

## ⚡ Guidelines

- **Always create a feature or bug fix branch** instead of committing directly to `develop`.
- **Keep PRs small and focused on the GitHub issue it resolves**, making them easier to review.
- **Write meaningful commit messages** describing what changed and why.
- **Sync `develop` regularly** to stay updated with the latest changes.
- **Delete merged branches** to keep the repository clean.


