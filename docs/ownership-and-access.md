# Ownership And Access

This document defines the access model for the current GPC direction.

## Access Rules

- Caliber is shared reference data and may be managed by privileged users.
- Component is shared reference data and may be managed by privileged users.
- Firearm is owned by the creating user.
- Recipe is owned by the creating user.
- Batch is owned by the creating user.
- User-owned records should use `uid` plus owner-based entity access control with admin override.
- User-owned records should only be visible and editable by the creating user unless a later explicit sharing rule says otherwise.

## UI Direction

- The application should be usable by logged-in users, not only through `/admin`.
- Admin routes and permissions remain useful for management workflows, but they are not the only target interface.
- Add and edit flows for owned records should support direct user interaction from the application UI.

## Practical Implications

- Shared reference entities should stay reusable across all users.
- User-owned entities should carry ownership metadata from the start.
- Access checks should be simple and explicit.
- Do not introduce sharing logic until the feature is actually needed.

## Decision Summary

- Components are global because they are reusable catalog definitions, not stock on hand.
- Inventory is deferred because it is a separate user-owned concept and should not be folded into the component catalog.
- Owner-based access is the v1 choice because it keeps the rules simple, supports admin override, and avoids premature sharing infrastructure.
