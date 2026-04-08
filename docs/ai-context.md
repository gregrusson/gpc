# AI Context: Gunners Project Companion

## What This Project Is

Gunners Project Companion is a personal logbook and structured data application built on Drupal 11. It is intended to help manage a shooting and reloading workflow as an internal tool first, with the possibility of limited sharing later.

The project is being built as a Drupal application with a clear domain model, not as a generic CMS with loosely organized content types.

## What This Project Is Not

- Not a public community platform.
- Not a full ammo inventory system.
- Not a lot-tracking or inventory-deduction engine.
- Not a shooting-session analytics system in v1.
- Not a feature-first prototype built around future sharing.

If a feature is only useful in a later phase, do not implement it now.

## Current Phase

The project is in early v1 domain implementation.

Current focus:

- Establish the custom module foundation.
- Model the core reload/references entities.
- Keep the admin experience usable and straightforward.
- Avoid overengineering storage, workflows, and abstraction layers.

## Technical Constraints

- Drupal 11.
- Custom module approach.
- Admin-first UI.
- Prefer custom content entities for core domain records.
- Use Drupal core patterns before introducing custom frameworks or abstractions.
- Keep business logic inside the module.
- Use typed PHP and modern Drupal coding standards.
- Keep implementations small, explicit, and easy to extend later.

## Module Structure

The project currently uses the `gpc` custom module.

Keep new code under:

- `web/modules/custom/gpc/src/Entity`
- `web/modules/custom/gpc/src/Form`
- `web/modules/custom/gpc/src/Routing`
- `web/modules/custom/gpc/src`

## Core Domain Model

### Caliber

Shared reference concept used by firearms, recipes, batches, and later sessions or ammo records.

Use it as a stable reference entity with a title, internal machine name, notes, and timestamps.

### Firearm

Planned later.

Represents a firearm record. It will likely reference caliber and include notes and practical descriptive fields.

### Component

Reusable reloading component definition, not an inventory lot.

Current practical categories are:

- bullet
- powder
- primer
- brass
- other

Use a simple list field for component type unless there is a concrete reason to move to taxonomy later.

### Recipe

Reusable reloading configuration.

Recipe is distinct from Batch.

Recipe should reference:

- caliber
- component records where relevant

Recipe should stay focused on configuration data, not production tracking.

### Batch

Produced instance created from a recipe.

Batch records what was produced, when it was produced, and how much was produced.

Batch is not inventory and should not deduct stock or consume lots.

## Planned Entities

Core v1 entities:

- Caliber
- Firearm
- Component
- Recipe
- Batch

Later-phase entities or capabilities:

- Maintenance records
- Inventory lots
- Ammo inventory
- Shooting sessions
- Session analytics
- Selective sharing of recipe summaries

## Fielding Guidance

Use structured fields for important stable data.

Use notes fields for secondary details, context, and anything that does not yet justify a dedicated schema.

Use entity references for real relationships between records.

Avoid turning everything into taxonomy or reference entities too early.

## Development Guardrails

When implementing a task:

- Start with the smallest useful slice.
- Do not implement future features unless explicitly requested.
- Do not invent abstractions before the code needs them.
- Do not add repositories, custom storage handlers, or plugin systems unless they solve an immediate problem.
- Prefer standard Drupal entity forms, list builders, permissions, menu links, and route providers.
- Keep admin UX functional and clear.
- Keep entity IDs short enough to satisfy Drupal limits.
- Preserve the distinction between recipe and batch.

## Overengineering Checks

Before adding a new concept, ask:

- Does this solve a current v1 need?
- Can this be represented as a field instead of a new entity?
- Can this remain a plain text notes field for now?
- Would a simple entity reference be enough?
- Is this only useful once inventory, sessions, or sharing exist?

If the answer points to future work, defer it.

## Practical Implementation Style

Prefer:

- one entity at a time
- direct forms
- direct list builders
- small update hooks for schema installs
- plain Drupal routing and permissions

Avoid:

- large service layers without need
- premature normalization
- workflow systems
- custom entity storage
- API-first architecture unless requested

## How AI-Assisted Development Should Work Here

An AI assistant working in this repository should:

- read the current module structure first
- follow the existing `gpc` patterns
- keep changes incremental and narrowly scoped
- avoid refactoring unrelated code
- explain design choices briefly when they matter
- preserve existing domain intent

The safest default is to implement the simplest solution that is clearly correct for the current phase.
