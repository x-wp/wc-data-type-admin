# WooCommerce Data Type Admin

Admin list-page utilities for WooCommerce data types.

## Status

This package currently supports two layers:

- Legacy compatibility wrappers: `XWC_Data_List_Page`, `XWC_Data_List_Table`
- Namespaced base abstractions for new work: `XWC\Data\Admin\List_Page_Handler`, `XWC\Data\Admin\List_Table`

The current stabilization direction is:

- Keep the legacy layer working for existing consumers.
- Prefer the namespaced abstractions for new integrations.
- Tighten shared behavior and tooling incrementally instead of forcing a migration.

## Package Structure

- `src/List_Page_Handler.php`: new admin page handler with DI-based table resolution
- `src/List_Table.php`: new base table implementation shared by legacy and new flows
- `src/Mixins/*`: shared list-table behaviors
- `src/Core/*`: legacy compatibility wrappers
- `templates/html-admin-page-edsl.php`: shared admin page template

## Verification

Regression checks:

```bash
php tests/regressions.php
```

Static analysis:

```bash
php -d memory_limit=1G vendor/bin/phpstan analyse --debug
```

## Notes

- The regression script is intentionally lightweight and does not require a full WordPress test bootstrap.
- PHPStan is configured conservatively for this library; in constrained environments, `--debug` is the verified non-parallel invocation path.
