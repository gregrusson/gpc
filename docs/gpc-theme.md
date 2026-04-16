# GPC Theme

`gpc_theme` is the site front-end theme for Gunners Project Companion. Gin stays the admin theme.

## Structure

- `web/themes/custom/gpc_theme/gpc_theme.info.yml` defines the theme, attaches the global library, and enforces SDC prop schemas.
- `web/themes/custom/gpc_theme/css/theme.css` holds the design tokens and global Drupal primitive styling.
- `web/themes/custom/gpc_theme/css/utilities.css` contains a small set of layout and accessibility helpers.
- `web/themes/custom/gpc_theme/js/theme-init.js` applies the theme mode and drawer behavior.
- `web/themes/custom/gpc_theme/templates/layout/` contains only the document shell and page shell templates.
- `web/themes/custom/gpc_theme/components/` contains reusable Single Directory Components.
- Site identity comes from Drupal config, not the theme. The header reads `system.site:name` and `system.site:slogan`, which you manage in `Configuration > System > Basic site settings`.

## How SDC is used

Components are the primary reusable UI building block in the theme. Each component lives in its own directory with:

- `*.component.yml`
- `*.twig`
- component-local CSS
- JS only where it is actually useful

The theme relies on SDC for buttons, cards, surfaces, tabs, empty states, and app shell pieces instead of adding more Twig overrides.

## Components

Created in this first pass:

- `app-header`
- `app-drawer`
- `theme-toggle`
- `button`
- `card`
- `surface`
- `form-section`
- `badge`
- `tabs`
- `data-table`
- `empty-state`
- `account-menu`

## Dark mode

Dark mode is token-based and uses `data-theme` on the root `<html>` element.

- If the user has no saved preference, the theme follows `prefers-color-scheme`.
- If the user uses the toggle, that choice is stored in `localStorage` under `gpc-theme`.
- The initial mode is applied early in `html.html.twig` to reduce flashing.
- `theme-init.js` keeps the root `data-theme` in sync and updates the toggle state.

## Enable the theme

```bash
ddev drush theme:enable gpc_theme
ddev drush config:set system.theme default gpc_theme -y
```

If you want to keep Gin as admin theme, set it explicitly:

```bash
ddev drush config:set system.theme admin gin -y
```

## Menus

- The drawer uses the Drupal `main` menu tree.
- The account menu uses the Drupal `account` menu tree.
- Both menus are rendered by preprocess/theme code and passed into SDC components.

Menu links are defined in `web/modules/custom/gpc/gpc.links.menu.yml`, so they can be managed through Drupal config and rendered consistently in the theme.

## Account menu

- Authenticated users see a compact trigger with a user icon, their display name, and a chevron.
- Anonymous users see a generic account trigger with login and register actions in the menu.
- The menu uses a native `<details>` element, so it is keyboard accessible without extra JavaScript.

## Admin steps

- Manage the drawer menu under `Structure > Menus > Main navigation`.
- Manage the account actions under `Structure > Menus > Account`.
- If you add or remove GPC navigation items, update the menu link definitions in the GPC module so the theme continues to render the correct menu tree.
- If you want different labels or hierarchy in the drawer, edit the `main` menu structure in Drupal rather than changing Twig templates.

## What to customize next

- Add GPC-specific navigation into the `drawer` region.
- Add route-specific page header treatment for collection pages and entity edit forms.
- Create more SDC components for record summaries, filters, navigation items, and KPI cards.
- Move any repeated GPC dashboard patterns from module controllers into theme components.
- Add optional component variants only after the basic patterns prove useful.
