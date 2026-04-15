# ADR: Use `drupal/physical` For Component Measurements

## Status

Accepted

## Date

2026-04-14

## Context

Component is reusable catalog data, not inventory.
Only some Component fields are true measurements with stable units.
The project already uses `drupal/physical` for Caliber length fields, so the Component policy should follow the same rule of thumb where the semantics match.

## Decision

GPC uses `drupal/physical` for Component length measurements only.

Component identity, bundle type, manufacturer, notes, and other categorical fields remain in custom GPC code.
The measurement fields are defined in module code rather than being created manually in the UI.

## Component Unit Policy

Component length measurements allow only inches and millimeters.

Inches remain the default display unit for Component measurement output.
Millimeters are allowed as an input unit so users can enter data in either common reloading unit.

## Applies To

- `diameter`
- `length`
- `case_length`

## Does Not Apply To

- `weight`
- `sectional_density`
- `ballistic_coefficient_value`
- `ballistic_coefficient_model`
- `upc`
- `label`
- `machine_name`
- `manufacturer`
- `component_type`
- `primer_type`
- `notes`

## Rationale

- `diameter` and `case_length` are true length measurements and fit the Physical model cleanly.
- `length` is also a true length measurement and fits the Physical model cleanly.
- Keeping the unit list small avoids broadening the domain model beyond the project's actual needs.
- Component weight is stored in grains, and the current Physical weight units do not provide a clean grains-based policy.
- Ballistic coefficient is an abstract technical value, not a physical measurement, so Physical would blur the semantic model without adding value.
- `sectional_density` is unitless, so Physical would add noise without adding semantic value.
- UPC is an identifier/code, so it belongs in text storage rather than a measurement field.
- Identity and categorical fields should stay in custom GPC code.

## Tradeoffs

- Existing Component rows are updated in place by the module update hook, so the schema change stays operationally simple on existing sites.
- Bullet weight remains a decimal until there is a better unit policy or explicit grains support.
- The field UI is less flexible than ad hoc custom decimal fields, but the tradeoff is a clearer measurement model.

## Rule Of Thumb

- Use `drupal/physical` for true measurable quantities with a clear unit policy.
- Do not use `drupal/physical` for identifiers, labels, notes, references, or categorical values.

## Implementation Rules

- Use `physical_measurement` for Component length fields.
- Lock Component measurement widgets to inches and millimeters, with inch as the default display unit.
- Keep physical field definitions in module code.
- Avoid introducing extra abstractions just to wrap the Physical package.
