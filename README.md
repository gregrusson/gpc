# Gunners Project Companion

Gunners Project Companion is a Drupal 11 project for personal firearms and reloading logbook data. It is intended to be a structured application, not a generic CMS with ad hoc content types.

## Project Purpose

The goal is to model a small, durable domain for tracking calibers, firearms, reloading components, reloading recipes, and reloading batches. The first version is personal-use and admin-focused. Sharing and analytics are later, limited additions.

## Current Scope

The current foundation is the custom Drupal module `gpc`, which already implements the core v1 domain entities:

- Caliber
- Component
- Recipe
- Batch

Firearm is the next planned entity.

Current implementation goals:

- Drupal 11 custom module architecture
- Admin-first entity management
- Clean entity naming, routes, and permissions
- Minimal but maintainable content entity design
- Simple references between the core records

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

- Caliber is a shared reference concept across multiple records.
- Component is a reusable definition, not an inventory lot.
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

1. Caliber, Component, Recipe, and Batch are in place.
2. Add Firearm as the next core content entity.
3. Tighten admin UX and validation around the existing entities as real data usage exposes gaps.
4. Add only the next clearly useful relationship or field when a current workflow needs it.
5. Defer inventory automation, session analytics, and sharing until the core model is proven useful.

## Project Plan

See [PROJECT_PLAN.md](PROJECT_PLAN.md) for the fuller working plan and development principles.
