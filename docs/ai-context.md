# AI Context: Gunners Project Companion

## What This Project Is

Gunners Project Companion is a structured Drupal 11 application for firearms and reloading data. It is intended to help manage a shooting and reloading workflow as a logged-in application first, with the possibility of limited sharing later.

The project is being built as a Drupal application with a clear domain model, not as a generic CMS with loosely organized content types.

## What This Project Is Not

- Not a public community platform.
- Not a full ammo inventory system.
- Not a lot-tracking or inventory-deduction engine.
- Not a shooting-session analytics system in v1.
- Not a feature-first prototype built around future sharing.

If a feature is only useful in a later phase, do not implement it now.

## Current Phase

The project is in early v1 domain implementation, but the core entity set is already in place.

Current focus:

- Preserve and refine the existing domain model.
- Improve logged-in and admin workflows where the current data model needs it.
- Keep the user experience usable and straightforward.
- Avoid overengineering storage, workflows, and abstraction layers.

## Technical Constraints

- Drupal 11.
- Custom module approach.
- Logged-in user workflows, not admin-only UI.
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

Use it as a stable shared reference entity.
GPC uses `drupal/physical` for measurement fields only, so caliber measurements are shipped in module code rather than added manually in the UI.
Caliber identity, naming, aliases, and business rules remain in custom GPC code.
Existing Caliber rows are updated in place by the module update hook when the physical field policy changes.

## Caliber Unit Policy

- Caliber length and diameter fields allow inches and millimeters only.
- Inches remain the current default display unit for caliber measurement output.
- Input, storage, and display are separate concerns, so mm input may be displayed in inches when formatter or list settings call for that.
- Keep unit lists intentionally small and domain-specific.
- Other entities may use different measurement policies if their domain requires them.

### Firearm

Represents a user-owned firearm record.

It should require caliber and be visible and editable only by the creating user.
Use `uid` ownership plus owner-based entity access control with admin override.
The display label should favor manufacturer and model, with the label field treated as the user-facing firearm name rather than a generic title.

### Component

Reusable catalog definition, not inventory.

Use bundles for:

- bullet
- powder
- primer
- brass

Inventory is later and should be a separate user-owned concept.

### Recipe

User-owned reloading configuration.

Recipe is distinct from Batch.
Recipe should reference:

- caliber
- component records where relevant

Recipe should use `field_recipe_code` plus optional `field_label`.
Recipe should stay focused on configuration data, not production tracking.
Recipe component references should be validated against the intended component type in form validation rather than by custom storage complexity.
Use `uid` ownership plus owner-based entity access control with admin override.

### Batch

User-owned produced instance created from a recipe.

Batch records what was produced, when it was produced, and how much was produced.

Batch should use `field_batch_code`.
Batch is not inventory and should not deduct stock or consume lots.
Use `uid` ownership plus owner-based entity access control with admin override.

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

Use `drupal/physical` for true measurements where units matter.
Prefer inches for caliber display defaults, but allow millimeters for caliber measurement input.
Do not let the physical package define the domain model.

Use notes fields for secondary details, context, and anything that does not yet justify a dedicated schema.

Use entity references for real relationships between records.

Avoid turning everything into taxonomy or reference entities too early.

## Decision Summary

- Components are global because they are reusable catalog definitions, not stock on hand.
- Inventory is deferred because it is a separate user-owned concept that should not be folded into the component catalog.
- Owner-based access is the v1 choice because it is the simplest durable rule for user-owned records and keeps admin override available without building sharing now.

## Development Guardrails

When implementing a task:

- Start with the smallest useful slice.
- Do not implement future features unless explicitly requested.
- Do not invent abstractions before the code needs them.
- Do not add repositories, custom storage handlers, or plugin systems unless they solve an immediate problem.
- Prefer standard Drupal entity forms, list builders, permissions, menu links, and route providers.
- Keep user and admin UX functional and clear.
- Keep entity IDs short enough to satisfy Drupal limits.
- Preserve the distinction between recipe and batch.
- Keep entity validation simple and local unless a broader rule is required.

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
