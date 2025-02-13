# Development Workflow

This document outlines the Git workflow for contributing to this WordPress plugin repository. The workflow follows a structured branching model to ensure a smooth development process.

## Branching Strategy

- **`master`** → Always holds stable, production-ready code.
- **`develop`** → Main branch for ongoing development and integration.
- **Feature branches** (`feature/xyz`) → For new features and bug fixes.
- **Release branches** (`release/x.y.z`) → Prepares a new version for release.
- **Hotfix branches** (`hotfix/x.y.z`) → Fixes critical bugs in `master`.

---

## 1️⃣ New Feature Development

1. Checkout `develop` and ensure it's up to date:
   ```sh
   git checkout develop
   git pull origin develop
   ```
2. Create a new feature branch:
   ```sh
   git checkout -b feature/new-feature
   ```
3. Implement changes and commit:
   ```sh
   git add .
   git commit -m "Added new feature: [describe feature]"
   ```
4. Push the branch and create a Pull Request (PR) to `develop`:
   ```sh
   git push origin feature/new-feature
   ```
5. Once approved, merge into `develop`.

---

## 2️⃣ Preparing for a New Release

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
4. Merge the release branch into `master` and tag the release:
   ```sh
   git checkout master
   git merge release/x.y.z
   git tag -a vX.Y.Z -m "Release version X.Y.Z"
   git push origin master --tags
   ```
5. Merge the release branch back into `develop` to keep it in sync:
   ```sh
   git checkout develop
   git merge release/x.y.z
   git push origin develop
   ```
6. Delete the release branch:
   ```sh
   git branch -d release/x.y.z
   git push origin --delete release/x.y.z
   ```

---

## 3️⃣ Fixing Bugs After a Release (Hotfixes)

1. Create a hotfix branch from `master`:
   ```sh
   git checkout master
   git pull origin master
   git checkout -b hotfix/x.y.z+1
   ```
2. Fix the bug, commit, and push:
   ```sh
   git add .
   git commit -m "Fixed critical bug in X.Y.Z"
   git push origin hotfix/x.y.z+1
   ```
3. Merge into `master`, tag the new hotfix release, and push:
   ```sh
   git checkout master
   git merge hotfix/x.y.z+1
   git tag -a vX.Y.Z+1 -m "Hotfix version X.Y.Z+1"
   git push origin master --tags
   ```
4. Merge back into `develop`:
   ```sh
   git checkout develop
   git merge hotfix/x.y.z+1
   git push origin develop
   ```
5. Delete the hotfix branch:
   ```sh
   git branch -d hotfix/x.y.z+1
   git push origin --delete hotfix/x.y.z+1
   ```

---

## Guidelines

- **Always create a feature branch** instead of committing directly to `develop`.
- **Keep PRs small and focused**, making them easier to review.
- **Write meaningful commit messages** describing what changed and why.
- **Sync `develop` with `master` after every release** to avoid divergence.
- **Delete merged branches** to keep the repository clean.

---

## Deployment Process

1. Once `master` has a new release, the latest tag should be pushed.
2. Deployments are automated via GitHub Actions (if configured).
3. Ensure all plugin assets are built and tested before tagging a release.

---
