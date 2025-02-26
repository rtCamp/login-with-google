# Contributing to Login with Google

Thank you for your interest in contributing to the [Login with Google](https://github.com/rtCamp/login-with-google) WordPress plugin! 🎉 Your contributions help make this project better for everyone.

---

## 📌 Reporting a Bug 🐞

Before creating a new issue, please check the [existing issues](https://github.com/rtCamp/login-with-google/issues) to see if your problem has already been reported or resolved.

If you still need to [log an issue](https://github.com/rtCamp/login-with-google/issues/new), please provide as much detail as possible, including clear steps to reproduce the issue.

---

## 💡 Creating a Pull Request

Want to contribute a new feature? Start a conversation by logging an [issue](https://github.com/rtCamp/login-with-google/issues).

### Steps to Contribute:

1. Check the [existing issues](https://github.com/rtCamp/login-with-google/issues) to see if there's an open discussion about the feature or bug you want to work on. If not, create a new issue.
2. Fork this repository.
3. `git clone` your fork to your local machine.
4. `cd` into the cloned repository.
5. `composer install` to install the dependencies.
6. `cd ./assets` and run `nvm use && npm install` to install the required packages.
7. Follow the [development workflow](#development-workflow) to set up your local development environment.
8. Implement your changes and commit them to your fork.
9. Push your changes and create a pull request against the `login-with-google:develop` branch.
10. Once your PR is reviewed and approved, it will be merged into `develop` and scheduled for release. 🎉

---

# Development Workflow

This section outlines the Git workflow for contributing to the project. We follow a structured branching model to ensure a smooth development process.

## Branching Strategy

- **`develop`** → Main branch for ongoing development and integration.
- **Feature branches** → `feature/issue-name` (e.g., `feature/add-plugin`)
- **Bug fix branches** → `fix/issue-name` (e.g., `fix/phpcs-errors`)
- **Chore branches** → `chore/issue-name` (e.g., `chore/pr-template`)
- **Refactoring branches** → `refactor/issue-name` (e.g., `refactor/button-styles`)

---

## 1️⃣ New Feature Development

1. Checkout `develop` and update it:
   ```sh
   git checkout develop
   git pull origin develop
   ```
2. Create a new feature branch:
   ```sh
   git checkout -b feature/new-feature
   ```
3. Implement your changes and commit:
   ```sh
   git add .
   git commit -m "Added new feature: [describe feature]"
   ```
4. Push your branch and create a Pull Request (PR) to `develop`:
   ```sh
   git push origin feature/new-feature
   ```
5. Once approved, merge into `develop`.

---

## 2️⃣ Fixing Bugs

1. Checkout `develop` and update it:
   ```sh
   git checkout develop
   git pull origin develop
   ```
2. Create a bug fix branch:
   ```sh
   git checkout -b fix/bug-description
   ```
3. Fix the issue, commit, and push:
   ```sh
   git add .
   git commit -m "Fixed bug: [describe bug]"
   git push origin fix/bug-description
   ```
4. Create a Pull Request (PR) to `develop` and wait for approval.
5. Once reviewed and approved, merge into `develop`.

---

## ⚡ Guidelines

- **Always create a feature or bug fix branch** instead of committing directly to `develop`.
- **Keep PRs small and focused on the GitHub issue it resolves**, making them easier to review.
- **Write meaningful commit messages** describing what changed and why.
- **Sync `develop` regularly** to stay updated with the latest changes.
- **Delete merged branches** to keep the repository clean.

---

Thank you for contributing! 🙌
