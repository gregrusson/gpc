# Domain Model

Gunners Project Companion is a structured Drupal 11 application for firearms and reloading data.

## Core Direction

- Caliber is global shared reference data.
- Component is global shared reference data.
- Firearm is user-owned and should only be visible and editable by the creating user.
- Recipe is user-owned and should only be visible and editable by the creating user.
- Batch is user-owned and should only be visible and editable by the creating user.
- Firearm must require Caliber.
- Firearm must not depend on Batch.
- Generic title fields should be avoided when they are redundant with a more specific identifier.

## Current Entity Shape

- Caliber: global reference record with a canonical display name, optional nickname, physical length and diameter measurements, reloading primer type, and notes.
- Caliber measurement fields accept inches and millimeters, with inches remaining the default display unit.
- Component: reusable catalog definition, not inventory, with bullet diameter, bullet length, and brass case length stored as Physical length fields while bullet weight remains a decimal grains value for now. Bullet ballistic coefficient is modeled as a numeric value plus a G1/G7 model selector, and UPC is stored as text so leading zeroes are preserved.
- Firearm: user-owned structured record tied to Caliber, with a display label that should be driven by manufacturer and model where possible.
- Recipe: user-owned reloading configuration with a required recipe code, optional nickname, notes as secondary detail, physical overall length, and a crimped yes/no flag.
- Batch: user-owned produced record with a required batch code, recipe reference, production date, quantity produced, and notes.
- Shared reference maintenance for Caliber and Component remains admin-managed for list, edit, and delete workflows, while authenticated users can contribute new records through the logged-in application flow.
- Duplicate Caliber and Component cleanup uses an admin-only manual merge helper that previews known inbound references, requires explicit confirmation, repoints supported references, and retains the source record for auditability.

## Modeling Notes

- Keep v1 small and explicit.
- Prefer entity references for real relationships.
- Keep contributor workflows simple: shared reference records can be created by authenticated users but remain globally reusable rather than user-owned.
- Add lightweight governance metadata to shared records: submitter attribution, review status, review notes, and a duplicate-of pointer for the admin-only manual merge helper.
- Use notes fields for secondary detail that does not yet justify a dedicated schema.
- Give Caliber a specific display-label strategy rather than relying on a generic title field.
- Use `drupal/physical` for Caliber and Component measurements that have units.
- Use `drupal/physical` for Recipe overall length because it is a true measurable dimension.
- Restrict Caliber and Component length measurements to domain-relevant choices, currently inches and millimeters.
- Keep Recipe overall length on the same measurement policy as the other length fields.
- Keep storage, input, and display concerns separate so mm values can be entered while inch-based display remains the default.
- Apply additive Caliber physical-field changes in place on existing sites when the schema change does not require data loss.
- Apply the same additive strategy to Component length fields when the schema change does not require data loss.
- Keep caliber identity, aliases, and business meaning in custom GPC code.
- Keep bullet weight as a decimal until there is a clean Physical unit policy for grains.
- Keep abstract technical attributes such as ballistic coefficient as simple numeric values plus constrained selectors rather than forcing them into Physical.
- Keep searchable identifiers such as UPC in text fields so leading zeroes remain intact.
- Keep Recipe label semantics centered on the recipe code, not a generic title.
- Model Recipe crimp as a boolean checkbox rather than free text because editors only need yes/no semantics.
- Keep Batch label semantics centered on the batch code, ideally paired with the recipe code in list and display contexts.
- Keep Batch recipe references explicit so production records stay clearly derived from a Recipe.
- Avoid pre-building inventory, analytics, or sharing flows before the core model is proven useful.

## Decision Summary

- Components are global because they are reusable catalog definitions, not stock on hand.
- Inventory is deferred because it is a separate user-owned concept and should not be folded into the component catalog.
- Owner-based access is the v1 choice because it is the simplest durable rule for user-owned records and keeps admin override available without building sharing now.
- Shared Caliber and Component records are contributed by authenticated users, then remain globally reusable rather than privately owned.
- Governance is intentionally lightweight and does not block immediate usability of newly created shared records.
- The manual merge helper is limited to Caliber and Component and does not imply a full moderation system.
