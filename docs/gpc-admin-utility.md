# GPC admin utility link

GPC tools remain Drupal admin routes under `/admin/gpc`.

- The frontend app shell keeps the `main` menu focused on normal site usage.
- `GPC Admin` is rendered in a separate sidebar footer area below the scrollable main navigation.
- The utility link is shown only when the current user can access `/admin/gpc`.
- No duplicate frontend admin pages are created.

If the GPC admin route access changes, update the route access check in the GPC module so the utility link stays aligned with the actual destination permissions.
