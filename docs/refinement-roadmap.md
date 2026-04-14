# Refinement Roadmap

This roadmap keeps the v1 scope small while aligning the model with the new ownership direction.

## Near Term

- Keep Caliber and Component as the global reference layer.
- Use `drupal/physical` for Caliber measurements that need units.
- Keep Caliber measurement input restricted to inches and millimeters, with inch display defaults.
- Keep Caliber physical-field updates additive and in-place on existing sites whenever possible.
- Finish making Firearm a user-owned entity that requires Caliber.
- Move Recipe to `field_recipe_code` plus optional `field_label`.
- Move Batch to `field_batch_code`.
- Keep the user-owned workflow usable outside `/admin`.
- Use `uid` ownership plus owner-based entity access control with admin override for user-owned entities.

## Model Refinements

- Keep Component on bundles for Bullet, Powder, Primer, and Brass.
- Caliber measurements stay in module code and use `drupal/physical` rather than ad hoc UI-added fields.
- Caliber measurement fields should keep a small, explicit unit policy instead of exposing generic physical unit lists.
- Add ownership-aware access controls for user-owned records.

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
