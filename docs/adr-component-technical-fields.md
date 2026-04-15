# ADR: Model Bullet Technical Fields Explicitly

## Status

Accepted

## Date

2026-04-15

## Context

Bullet components need a length measurement, a ballistic coefficient, and a product code.
Those fields do not all fit the same storage model.

## Decision

- Use `drupal/physical` for bullet length because it is a true measurable quantity with a unit policy.
- Store ballistic coefficient as a decimal value plus a constrained model selector.
- Store UPC as text so leading zeroes are preserved.

## Ballistic Coefficient Model

Ballistic coefficient is a paired concept:

- `ballistic_coefficient_value`
- `ballistic_coefficient_model`

The model field is limited to `G1` and `G7`.

This keeps the schema explicit and queryable without introducing a custom entity or collapsing two different meanings into one ambiguous field.

## UPC

UPC is an identifier, not a measurement.

It is stored as text because numeric storage would drop leading zeroes and make barcode-like search keys less reliable.

## Rationale

- Physical is the right fit for measurable dimensions.
- Abstract technical attributes should stay simple: numeric field plus constrained option field.
- Searchable identifiers should stay textual.
- The resulting model is easier to query, display, and validate than a custom abstraction.

## Applies To

- Bullet length
- Bullet ballistic coefficient
- UPC

