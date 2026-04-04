# Codebase Snapshot — 2026-04-04

## Purpose

This document captures the **current state of the repository** before cleanup work starts.

It is intentionally descriptive (not corrective): we preserve what exists now so we can fix issues incrementally in follow-up commits.

---

## Repository state (working tree)

Current branch: `master` (tracking `origin/master`)

Observed changes in working tree at snapshot time:

- Modified:
  - `composer.json`
  - `composer.lock`
  - `phpstan.neon`
  - `src/Interfaces/Data_Table.php`
- Renamed/Moved:
  - `src/Admin/XWC_Data_List_Page.php` → `src/Core/XWC_Data_List_Page.php`
  - `src/Admin/XWC_Data_List_Table.php` → `src/Core/XWC_Data_List_Table.php`
  - `src/Views/html-admin-page-edsl.php` → `templates/html-admin-page-edsl.php`
- Deleted:
  - `src/Interfaces/Labelable_Enum.php`
  - `src/Traits/Bulk_Action_Handler.php`
  - `src/Traits/Item_Handler.php`
  - `src/Traits/Row_Action_Handler.php`
  - `src/Traits/Tablenav_Handler.php`
  - `src/Traits/URI_Handler.php`
- New:
  - `src/List_Page_Handler.php`
  - `src/List_Table.php`
  - `src/Mixins/*`

---

## High-level architecture (current)

The codebase currently contains **two overlapping admin-list-table abstractions**:

1. Legacy-style base classes (global namespace style)
   - `src/Core/XWC_Data_List_Page.php`
   - `src/Core/XWC_Data_List_Table.php`

2. New namespaced abstractions
   - `src/List_Page_Handler.php`
   - `src/List_Table.php`
   - `src/Mixins/*` (replacing old Traits path)

This indicates an in-progress migration/refactor where legacy and new models coexist.

---

## Module map

### `src/List_Page_Handler.php`

New abstract handler for admin list pages.

Responsibilities:
- Register admin submenu page
- Connect to WooCommerce admin page registry
- Register/set screen options
- Resolve table through DI container (`ContainerInterface`)

Notes:
- Uses attributes/decorators (`XWP\DI\Decorators\Action`, `Filter`) for hook registration.

### `src/List_Table.php`

New abstract base list table extending `WP_List_Table` and implementing `Interfaces\Data_Table`.

Composed via mixins:
- `Bulk_Action_Handler`
- `Item_Handler`
- `Row_Action_Handler`
- `Tablenav_Handler`
- `URI_Handler`

Responsibilities include:
- query argument parsing
- views generation
- sortable column normalization
- hidden extra inputs and inline edit hooks

### `src/Mixins/*`

Contains behavior moved from old `src/Traits/*`:

- `Bulk_Action_Handler.php` — bulk action dispatch/messages
- `Item_Handler.php` — item preparation/display and column defaults
- `Row_Action_Handler.php` — row actions formatting/rendering
- `Tablenav_Handler.php` — extra table nav filters rendering
- `URI_Handler.php` — URI cleanup/current action detection

### `src/Core/*`

Renamed location of legacy base classes:

- `XWC_Data_List_Page`
- `XWC_Data_List_Table`

These are still present and functional overlap exists with new abstractions.

### `src/Interfaces/Data_Table.php`

Shared table interface expanded with additional methods (display/inline edit/etc.), but still has weak typing on several signatures.

### `templates/html-admin-page-edsl.php`

Moved out of `src/Views` to `templates/` and renders list-table page flow:

- process bulk actions
- output views
- prepare/display items
- render inline-edit container

---

## Composer/autoload snapshot

`composer.json` currently reflects migration decisions:

- Package requirements broadened:
  - `x-wp/wc-data-type`: `^1|^2`
  - `x-wp/di` (dev): `^1.0|^2.0`
- PSR-4 changed from `XWC\\Data\\` to `XWC\\Data\\Admin\\` mapped to `src/`
- Classmap changed from `src/Admin` to `src/Core/`

This confirms namespace and directory layout are actively being reshaped.

---

## Known weak points (baseline for upcoming fixes)

These are recorded as current known issues; **not fixed in this snapshot commit**.

1. `src/Core/XWC_Data_List_Page.php`
   - `maybe_connect_page()` has an unconditional `return;` before `wc_admin_connect_page(...)` (dead code path).

2. `src/Core/XWC_Data_List_Page.php`
   - `output()` still references old path `.../Views/html-admin-page-edsl.php` though template moved to `templates/`.

3. Coexisting overlapping abstractions
   - Legacy `XWC_Data_List_*` and new `List_*` handler/table classes both present.

4. `src/List_Page_Handler.php`
   - `lt()` assumes non-null DI container while property is nullable.

5. `src/Mixins/URI_Handler.php`
   - `current_action()` checks `action` but does not handle `action2` (top/bottom bulk selector parity issue).

6. `src/Mixins/Item_Handler.php`
   - `column_actions()` prints `<div>` twice (missing closing tag).

7. `src/Mixins/Item_Handler.php`
   - default sorting loop in `get_ordering_args()` overwrites candidates and may produce unstable default selection.

---

## Follow-up strategy (incremental)

Recommended execution order for next commits:

1. Runtime breakages first (dead return, wrong template path)
2. Behavioral parity fixes (bulk actions, inline-edit asset logic, DOM validity)
3. Architectural direction decision (legacy vs new primary abstraction)
4. Interface typing hardening + static analysis cleanup
5. Tests/smoke checklist for admin list page lifecycle

---

## Snapshot policy

This document is a timestamped baseline for controlled refactor work.
Subsequent fix commits should reference this file when closing each known weak point.
