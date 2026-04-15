# Ownership And Access

This document defines the access model for the current GPC direction.

## Access Rules

- Caliber is shared reference data and is governed by dedicated view, create, edit, and delete permissions.
- Component is shared reference data and may be managed by privileged users.
- Firearm is owned by the creating user and uses owner-based access with admin override.
- Recipe is owned by the creating user.
- Batch is owned by the creating user.
- User-owned records should use `uid` plus owner-based entity access control with admin override.
- User-owned records should only be visible and editable by the creating user unless a later explicit sharing rule says otherwise.

## UI Direction

- The application should be usable by logged-in users, not only through `/admin`.
- User-owned entities should have direct logged-in UI paths outside `/admin`.
- Shared reference entities may stay admin-managed until there is a concrete need for a broader user-facing maintenance flow.
- Add and edit flows for owned records should support direct user interaction from the application UI.
- Firearm uses a normal logged-in UI path and is not treated as admin-only content.
- Recipe uses a normal logged-in UI path and is not treated as admin-only content.
- Batch uses a normal logged-in UI path and is not treated as admin-only content.

## Practical Implications

- Shared reference entities should stay reusable across all users.
- User-owned entities should carry ownership metadata from the start.
- Access checks should be simple and explicit.
- Do not introduce sharing logic until the feature is actually needed.

## Decision Summary

- Components are global because they are reusable catalog definitions, not stock on hand.
- Inventory is deferred because it is a separate user-owned concept and should not be folded into the component catalog.
- Owner-based access is the v1 choice because it keeps the rules simple, supports admin override, and avoids premature sharing infrastructure.
- Recipe keeps its code as the main human-facing identifier while still remaining user-owned.
- Batch keeps its batch code as the main human-facing identifier while still remaining user-owned, with recipe code paired in listings for context.
