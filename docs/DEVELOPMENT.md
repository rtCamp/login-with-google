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
3. Installing dependencies:
   ```sh
   composer install
   cd ./assets
   npm install
   ```
4. Implement your changes and commit:
   ```sh
   git add .
   git commit -m "[type] Short description of change"
   ```
5. Push your branch and create a Pull Request (PR) to `develop`:
   ```sh
   git push origin feature/issue-name
   ```
6. Always link the PR to the GitHub issue it resolves ([Learn more](https://docs.github.com/en/issues/tracking-your-work-with-issues/using-issues/linking-a-pull-request-to-an-issue)).
7. Once reviewed and approved, merge into `develop`.

---

## 2️⃣ Release Process

1. Ensure `develop` is stable and up to date:
   ```sh
   git checkout develop
   git pull origin develop
   ```
2. Create a release branch from develop:
   ```sh
   git checkout -b release/x.y.z
   ```
3. Update the plugin version in the main plugin file (`plugin-name.php`):
   ```php
   /*
   Plugin Name: Plugin Name
   Version: x.y.z
   */
   ```
   Update the `readme.txt` file with the new version number.
   ```md
   == Changelog ==

   = x.y.z =
   * Feature: Description of new feature.
   * Fix: Description of bug fix.
   ```
   Commit the changes:
   ```sh
   git add .
   git commit -m "Release version x.y.z"
   git push origin release/x.y.z
   ```
4. Perform final testing and make necessary fixes.
5. Raise PR against `master` branch for release.
6. Once approved, merge into `master`.
7. Checkout to `master` and pull the changes:
   ```sh
   git checkout master
   git pull origin master
   ```
8. Create and push a GitHub tag:
   ```sh
   git tag -a vX.Y.Z -m "Release version X.Y.Z"
   git push origin vX.Y.Z
   ```

### Dry Run
Before releasing the plugin, it's a good practice to perform a dry run to generate the release plugin zip file. This helps in identifying any issues that might occur during the actual release process.

1. Checkout to the working branch:
   ```sh
   git checkout {to working branch}
   ```

2. Create a Tag with `dry` prefix
	   ```sh
   git tag -a dry-X.Y.Z -m "Dry run release version X.Y.Z"
   git push origin dry-X.Y.Z
   ```
3. This will create a tag with `dry` prefix and you can download the zip file from the action runner. 

---

## ⚡ Guidelines

- **Always create a feature or bug fix branch** instead of committing directly to `develop`.
- **Keep PRs small and focused on the GitHub issue it resolves**, making them easier to review.
- **Write meaningful commit messages** describing what changed and why.
- **Sync `develop` regularly** to stay updated with the latest changes.
- **Delete merged branches** to keep the repository clean.


