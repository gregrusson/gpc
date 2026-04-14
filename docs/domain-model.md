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

- Caliber: global reference record with a canonical display name plus bullet diameter, case length, primer type, and notes.
- Component: reusable catalog definition, not inventory.
- Firearm: user-owned structured record tied to Caliber.
- Recipe: user-owned reloading configuration that should prefer a required recipe code plus optional nickname or label.
- Batch: user-owned produced record that should prefer a batch number or code instead of a generic title.

## Modeling Notes

- Keep v1 small and explicit.
- Prefer entity references for real relationships.
- Use notes fields for secondary detail that does not yet justify a dedicated schema.
- Give Caliber a specific display-label strategy rather than relying on a generic title field.
- Avoid pre-building inventory, analytics, or sharing flows before the core model is proven useful.

## Decision Summary

- Components are global because they are reusable catalog definitions, not stock on hand.
- Inventory is deferred because it is a separate user-owned concept and should not be folded into the component catalog.
- Owner-based access is the v1 choice because it is the simplest durable rule for user-owned records and keeps admin override available without building sharing now.
