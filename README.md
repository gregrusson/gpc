# Gunners Project Companion

Gunners Project Companion is a Drupal 11 project for personal firearms and reloading logbook data. It is intended to be a structured application, not a generic CMS with ad hoc content types.

## Project Purpose

The goal is to model a small, durable domain for tracking calibers, firearms, reloading components, reloading recipes, and reloading batches. The first version is personal-use and admin-focused. Sharing and analytics are later, limited additions.

## Current Scope

The current foundation is the custom Drupal module `gpc`, which begins the domain model with the Caliber content entity.

Current implementation goals:

- Drupal 11 custom module architecture
- Admin-first entity management
- Clean entity naming and routes
- Minimal but maintainable content entity design

## Planned Domain Model

### v1 core entities

- Caliber
- Firearm
- Reloading Component
- Reloading Recipe
- Reloading Batch

### Later phase entities

- Maintenance Record
- Inventory Lot
- Ammo Inventory Item
- Shooting Session
- Session Analytics Summary

### Modeling notes

- Caliber is a shared reference concept across multiple records.
- Recipe and batch are distinct concepts.
- Notes fields should hold flexible secondary detail.
- Inventory automation and analytics are explicitly later work.
- Sharing is a limited later feature, likely focused on recipe summaries.

## Local Development with DDEV

This repository is set up for DDEV-based local development.

Common commands:

```bash
ddev start
ddev status
ddev drush status
ddev drush cr
```

If dependencies are missing:

```bash
ddev composer install
```

## Basic Install Steps

1. Start DDEV: `ddev start`
2. Install dependencies if needed: `ddev composer install`
3. Install Drupal or open the existing site in DDEV
4. Enable the custom module if it is not already enabled: `ddev drush en gpc -y`
5. Rebuild cache: `ddev drush cr`

## Development Philosophy

- Keep v1 small and useful.
- Favor straightforward Drupal patterns first.
- Use custom content entities for structured records with relationships.
- Keep business logic inside custom modules.
- Avoid abstractions until the problem actually needs them.
- Prefer working admin UX over theoretical perfection.
- Build thin vertical slices instead of broad speculative systems.

## Near-Term Roadmap

1. Caliber foundation is in place.
2. Add Firearm as the next core content entity.
3. Add Reloading Component.
4. Add Reloading Recipe.
5. Add Reloading Batch.
6. Connect the entities with simple references where they are clearly useful.
7. Defer inventory automation, session analytics, and sharing until the core model is proven useful.

## Project Plan

See [PROJECT_PLAN.md](PROJECT_PLAN.md) for the fuller working plan and development principles.

