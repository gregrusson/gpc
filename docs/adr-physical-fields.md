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

## Caliber Unit Policy

Caliber length and diameter fields allow only inches and millimeters.

Inches remain the default display unit for Caliber measurement output.
Millimeters are allowed as an input unit so users can enter domain data in either common reloading unit.
Storage, input, and display remain separate concerns, so a value entered in millimeters may be rendered in inches when the display configuration calls for that.

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
- The module can accept millimeter input without broadening the unit list beyond the domain's needs.
- Keeping the fields in code preserves the GPC domain model and avoids manual UI drift.
- Physical provides a standard field and widget/formatter model instead of another custom measurement abstraction.

## Tradeoffs

- Existing Caliber rows are updated in place by the module update hook, so the schema change stays operationally simple on existing sites.
- The entity still owns domain meaning, so the physical package is intentionally not used as a domain model layer.
- The field UI is less flexible than ad hoc custom decimal fields, but that is the point: measurements should be explicit and consistent.

## Implementation Rules

- Use `physical_measurement` for Caliber length and diameter fields.
- Lock Caliber measurement widgets to inches and millimeters, with inch as the default display unit.
- Keep physical field definitions in module code.
- Avoid introducing extra abstractions just to wrap the physical package.
- Do not use the physical package for naming, aliases, ownership, or access control.
