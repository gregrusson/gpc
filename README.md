# Gunners Project Companion

Gunners Project Companion is a Drupal 11 application for firearms and reloading logbook data. It is intended to be a structured domain app, not a generic CMS with ad hoc content types.

## Project Purpose

The goal is to model a small, durable domain for tracking calibers, firearms, reloading components, reloading recipes, and reloading batches. The first version stays small, but it should be usable by logged-in users through the main dashboard rather than only through `/admin`. Caliber and Component are shared reference records that logged-in users can contribute, while admins and managers govern correctness and duplicates. Sharing and analytics are later, limited additions.

## Current Scope

The current foundation is the custom Drupal module `gpc`, which already implements the core v1 domain entities:

- Caliber
- Component
- Firearm
- Recipe
- Batch

Current implementation goals:

- Drupal 11 custom module architecture
- Logged-in user workflows with clear ownership rules
- Clean entity naming, routes, and permissions
- Minimal but maintainable content entity design
- Simple references between the core records
- An admin-only manual merge helper for Caliber and Component duplicates

## Planned Domain Model

### v1 core entities

- Caliber
- Firearm
- Component
- Recipe
- Batch

### Later phase entities

- Maintenance Record
- Inventory Lot
- Ammo Inventory Item
- Shooting Session
- Session Analytics Summary

### Modeling notes

- Caliber is global shared reference data.
- Component is global shared reference data and stays reusable through fixed bundles for Bullet, Powder, Primer, and Brass.
- Firearm, Recipe, and Batch are user-owned records.
- Firearm, Recipe, and Batch have direct logged-in UI paths.
- User-owned entities should use `uid` plus owner-based entity access control with admin override.
- Recipe and batch are distinct concepts.
- Recipe should use `field_recipe_code` plus optional `field_label`.
- Batch should use `field_batch_code`.
- Component should keep shared fields like manufacturer, product/model name, and notes straightforward, with bundle-specific fields only where they add immediate value.
- Notes fields should hold flexible secondary detail.
- Caliber and Component are shared/global reference records that authenticated users can contribute.
- Admins and managers govern shared references for correctness, duplicates, and spam.
- Inventory is deferred as a later separate user-owned concept.
- Caliber structured-field expansion is deferred for now.
- Sharing is a limited later feature, likely focused on recipe summaries.
- The manual merge helper only supports explicit, admin-only consolidation of known Caliber and Component duplicates. See [docs/reference-merge-helper.md](docs/reference-merge-helper.md).

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

1. Caliber, Component, Recipe, and Batch are in place.
2. Tighten user-facing and admin UX plus validation around the existing entities as real data usage exposes gaps.
3. Keep shared-reference governance and duplicate handling intentional, explicit, and limited.
4. Add only the next clearly useful relationship or field when a current workflow needs it.
5. Defer inventory automation, session analytics, and sharing until the core model is proven useful.

## Project Plan

See [PROJECT_PLAN.md](PROJECT_PLAN.md) for the fuller working plan and development principles.
