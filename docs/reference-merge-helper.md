# Reference Merge Helper

The GPC reference merge helper is an admin-only manual consolidation tool for duplicate `Caliber` and `Component` records.

It exists to support controlled cleanup after duplicates are identified by an administrator or reviewer. It is not a moderation system and it is not intended to infer duplicate candidates on its own.

## Supported Behavior

- Preview the inbound references that are currently known for the source / duplicate record.
- Require an explicit confirmation step before any data changes are made.
- Repoint known inbound references from the source / duplicate record to the target / canonical record.
- Retain the source record for auditability by annotating it with the canonical pointer and retention notes.

## Supported Entity Types

- Caliber
- Component

## Unsupported Behavior

- Automatic duplicate detection.
- Generalized moderation workflows.
- Guaranteed handling of future or unknown reference fields unless they are explicitly added to the discovery registry and merge support.

## Admin Usage Note

Use the helper when two `Caliber` or `Component` records are confirmed to represent the same real-world item and the goal is to consolidate references without deleting the source record.

Before confirming, review:

- Whether the source record is actually a duplicate.
- Whether the target record is the intended canonical record.
- Which inbound references will be repointed.
- Whether any fields or relationships are not listed in the preview.

After merge execution:

- Known inbound references are repointed to the target / canonical record.
- The source record remains in place for auditability.
- The source record is annotated with retention and duplicate-of information where those fields exist.

## Developer Maintenance Note

Supported inbound references are registered in `web/modules/custom/gpc/gpc.services.yml` under `gpc.reference_discovery.sources`.

Discovery reads only those explicitly registered fields through:

- `Drupal\gpc\ReferenceDiscovery\ReferenceDiscoveryRegistry`
- `Drupal\gpc\ReferenceDiscovery\ReferenceDiscoveryService`

To add support for a new reference field elsewhere in the codebase:

1. Add the field to `gpc.reference_discovery.sources` for the relevant target entity type.
2. Ensure the referencing entity type actually exposes that field.
3. Extend the merge execution paths only if the new field should be repointed during the manual helper workflow.
4. Update tests so the new reference is covered by discovery and merge preview expectations.

## Terminology

- `source / duplicate`: the record being consolidated away from.
- `target / canonical`: the record that keeps the consolidated references.
- `manual merge helper`: the limited admin-only workflow described on this page.

