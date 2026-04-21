# GPC main navigation

The GPC module now exposes a `GPC` group in the frontend `main` menu without creating new destination pages.

- The top-level item points at the existing `/admin/gpc` overview route.
- Child links reuse the existing admin routes for Calibers, Components, the two review queues, and Reference merge.
- Menu visibility follows normal Drupal route access, so a link only appears when the current user can actually reach the destination route.
- The `/admin/gpc` overview route now uses a small route-access check that allows the page when the user can access any GPC tool, while still keeping each child route permission-protected.

The admin menu entries under `/admin/gpc` remain in place.
