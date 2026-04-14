# ADR: Use `drupal/physical` For Caliber Measurements

## Status

Accepted

## Date

2026-04-14

## Context

Caliber is global shared reference data in GPC.
It needs stable measurement fields for caliber dimensions and cartridge length data.
Those values have units, and the project already uses custom Drupal content entities for the core domain model.

We want unit-aware measurement storage without turning the physical package into the source of domain meaning.

## Decision

GPC uses `drupal/physical` for Caliber measurement fields only.

Caliber identity, naming, aliases, and business meaning remain in custom GPC code.
The module defines and ships the measurement fields in code rather than creating them manually in the UI.

## Applies To

- `bullet_diameter`
- `case_length`
- `neck_diameter`
- `shoulder_diameter`
- `base_diameter`
- `rim_diameter`
- `max_overall_length`

## Does Not Apply To

- Caliber name or display label
- Caliber nickname or alias semantics
- Primer type business rules
- Ownership or access rules
- Notes fields
- Other entities such as Component, Recipe, Batch, or Firearm

## Rationale

- These fields are true measurements and benefit from explicit unit handling.
- The module can keep inch-centric defaults and still convert later if needed.
- Keeping the fields in code preserves the GPC domain model and avoids manual UI drift.
- Physical provides a standard field and widget/formatter model instead of another custom measurement abstraction.

## Tradeoffs

- Existing Caliber rows may need a manual migration if the old storage schema already contains data.
- The entity still owns domain meaning, so the physical package is intentionally not used as a domain model layer.
- The field UI is less flexible than ad hoc custom decimal fields, but that is the point: measurements should be explicit and consistent.

## Implementation Rules

- Use `physical_measurement` for Caliber length and diameter fields.
- Lock Caliber measurement widgets to inches unless a specific future field clearly needs another unit.
- Keep physical field definitions in module code.
- Avoid introducing extra abstractions just to wrap the physical package.
- Do not use the physical package for naming, aliases, ownership, or access control.
