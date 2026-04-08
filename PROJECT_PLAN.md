# Gunners Project Companion

## Project Overview

Gunners Project Companion is a personal logbook and structured data application built on Drupal 11. The primary goal is to model a small but durable domain for firearms and reloading work without turning the site into a generic CMS with scattered content types and ad hoc fields.

The application is intended for personal use first. Selective sharing may exist later, but v1 should be designed around private administrative data entry and retrieval.

## Goals

- Provide a clean Drupal 11 custom module architecture for core domain records.
- Keep the first version small, useful, and admin-focused.
- Model the main hobby workflow with structured entities and straightforward relationships.
- Support future expansion without building future features now.
- Keep business logic inside custom modules where code is a better fit than configuration.

## Non-goals for v1

- Public community features.
- Full sharing workflows.
- Inventory deduction automation.
- Ammo consumption accounting.
- Shooting session analytics and dashboards.
- Maintenance history systems beyond a later planned slice.
- Heavy abstraction layers, plugin systems, repositories, or custom storage unless a concrete need appears.

## Domain Concepts

- Caliber is a shared reference concept used across firearms, recipes, ammo, and shooting sessions.
- Firearm is a structured record for a specific gun.
- Component is a reusable reloading component definition, not an inventory lot.
- Recipe is a reusable configuration.
- Batch is a produced instance created from a recipe.
- Recipe and batch are distinct concepts and should stay distinct in the model.
- Notes fields should carry secondary detail that does not yet justify structured fields.
- Component type is currently a simple list field, which is the simplest good option for v1.

## Planned Entities

### v1 Core Entities

- Caliber
- Firearm
- Component
- Recipe
- Batch

### Later Phase Entities

- Maintenance Record
- Inventory Lot
- Ammo Inventory Item
- Shooting Session
- Session Analytics Summary

## Initial Implementation Phases

### Phase 1: Module foundation

- Establish the `gpc` custom module.
- Keep the module namespaced and organized for future entities.
- Add only the minimum code needed for a reliable Drupal entity foundation.

### Phase 2: Core domain records

- Implement Caliber, Component, Recipe, and Batch as custom content entities.
- Provide add, edit, list, and delete workflows in admin.
- Use internal numeric entity IDs plus separate immutable machine-name fields where helpful.
- Use simple entity references between the core records.
- Keep Recipe and Batch distinct.

### Phase 3: Firearm and refinement

- Add Firearm.
- Tighten the current admin UX and field validation based on actual usage.

### Phase 4: Later operational features

- Add maintenance records.
- Add inventory lots and ammo inventory.
- Add shooting sessions and lightweight summaries.
- Only then consider selective sharing of recipe summaries.

## Development Principles

- Prefer straightforward Drupal patterns first.
- Use custom content entities for core records with lifecycle and relationships.
- Keep the admin UX working before worrying about presentation polish.
- Use entity references for meaningful relationships.
- Use notes fields for flexible secondary detail.
- Keep models narrow and explicit.
- Introduce only the handlers needed for the current task.
- Keep code production-oriented and readable, not clever.

## Avoiding Overengineering

- Do not add repositories unless a persistence boundary is genuinely needed.
- Do not add custom storage handlers unless default entity storage is insufficient.
- Do not add plugin architectures for single concrete use cases.
- Do not model future inventory automation before the data model actually needs it.
- Do not collapse recipe and batch into one entity just to reduce file count.
- Do not build sharing or analytics logic until the core records exist and are useful.
- When uncertain, choose the simpler implementation and document the tradeoff.

## Working Notes

- Treat this as a structured application, not a content site.
- Prefer incremental vertical slices over large cross-cutting implementations.
- Keep the module shape ready for additional entities, but do not pre-build them.
- Favor stable, explicit names and small entity APIs that are easy to extend later.
