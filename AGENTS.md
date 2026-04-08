# AGENTS.md

## Project setup
- This project is a Drupal 11 site running in DDEV.
- Run all PHP/Drupal commands through DDEV.
- Project root contains `phpunit.xml`.
- Custom modules live in `web/modules/custom/`.
- The active custom module for the project is `gpc`.
- Core domain entities currently implemented in `gpc`: Caliber, Component, Recipe, and Batch.

## Install / refresh dependencies
- Run `ddev composer install` if vendor dependencies are missing.
- PHPUnit/dev test dependencies are managed with Composer.

## Test commands
- To run the full PHPUnit suite:
  - `ddev exec ./vendor/bin/phpunit -c phpunit.xml`
- To run tests for a specific custom module:
  - `ddev exec ./vendor/bin/phpunit -c phpunit.xml web/modules/custom/<module_name>/tests`

## Browser test prerequisites
- Ensure `web/sites/simpletest` exists before browser-based Drupal tests.
- Use the values already defined in `phpunit.xml` for Drupal test environment config.

## Rules
- Do not run `phpunit` directly on the host.
- Prefer the narrowest relevant test scope first.
- After changing PHP code, run the most relevant PHPUnit tests before finishing.
- If PHPUnit fails because dependencies are missing, run Composer inside DDEV and retry.
- Keep doc updates aligned with the actual module and entity state before making roadmap claims.
