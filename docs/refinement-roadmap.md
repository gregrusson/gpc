# Refinement Roadmap

This roadmap keeps the v1 scope small while aligning the model with the new ownership direction.

## Near Term

- Keep Caliber and Component as the global reference layer.
- Use `drupal/physical` for Caliber and Component measurements that need units.
- Keep Caliber measurement input restricted to inches and millimeters, with inch display defaults.
- Keep Caliber physical-field updates additive and in-place on existing sites whenever possible.
- Keep Component length-field updates additive and in-place on existing sites whenever possible.
- Keep Firearm user-owned, caliber-required, and usable through the logged-in UI.
- Refine Recipe to a required recipe code plus optional nickname while keeping owner-based access.
- Move Batch to `field_batch_code`.
- Keep the user-owned workflow usable outside `/admin`.
- Use `uid` ownership plus owner-based entity access control with admin override for user-owned entities.

## Model Refinements

- Keep Component on bundles for Bullet, Powder, Primer, and Brass.
- Caliber and Component length measurements stay in module code and use `drupal/physical` rather than ad hoc UI-added fields.
- Caliber and Component length fields should keep a small, explicit unit policy instead of exposing generic physical unit lists.
- Keep bullet weight as a decimal until the project has a clean grains-based Physical policy.
- Keep bullet ballistic coefficient as a paired value/model field set rather than a single ambiguous scalar.
- Keep UPC in text storage so leading zeroes survive round-trips and search.
- Add ownership-aware access controls for user-owned records.
- Keep Firearm display naming anchored to manufacturer and model instead of a generic title field.

## Deferred Work

- Inventory tracking.
- Inventory deduction or consumption automation.
- Shooting session analytics.
- Sharing workflows.

## Guardrails

- Keep v1 small and practical.
- Prefer one focused change over speculative generalization.
- Do not introduce new entities just to anticipate later features.
- Use notes fields when a structured field is not yet justified.
