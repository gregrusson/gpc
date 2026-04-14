# Refinement Roadmap

This roadmap keeps the v1 scope small while aligning the model with the new ownership direction.

## Near Term

- Keep Caliber and Component as the global reference layer.
- Finish making Firearm a user-owned entity that requires Caliber.
- Move Recipe to `field_recipe_code` plus optional `field_label`.
- Move Batch to `field_batch_code`.
- Keep the user-owned workflow usable outside `/admin`.
- Use `uid` ownership plus owner-based entity access control with admin override for user-owned entities.

## Model Refinements

- Keep Component on bundles for Bullet, Powder, Primer, and Brass.
- Caliber now carries the core structured reference fields needed for filtering and compatibility checks.
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
