<div align="center">

<h1 align="center" style="border-bottom: none; margin-bottom: 0px">WC Data Type Admin</h1>
<h3 align="center" style="margin-top: 0px">Admin list-page utilities for WooCommerce data types</h3>

[![Packagist Version](https://img.shields.io/packagist/v/x-wp/wc-data-type-admin?label=Release&style=flat-square)](https://packagist.org/packages/x-wp/wc-data-type-admin)
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/x-wp/wc-data-type-admin/php?label=PHP&logo=php&logoColor=white&logoSize=auto&style=flat-square)
![Static Badge](https://img.shields.io/badge/WP-%3E%3D6.9.4-3858e9?style=flat-square&logo=wordpress&logoSize=auto)
![Static Badge](https://img.shields.io/badge/WC-%3E%3D10.6.1-7f54b3?style=flat-square&logo=woocommerce&logoSize=auto)

</div>

This library provides admin list-page and list-table utilities for custom WooCommerce data types defined with [`x-wp/wc-data-type`](https://github.com/x-wp/wc-data-type). Define a data type once, then get a consistent admin listing experience with minimal boilerplate.

## Key Features

1. **List page handling**: Register admin pages for custom data types with `List_Page_Handler`.
2. **WooCommerce-style list tables**: Extend `List_Table` for a familiar `WP_List_Table` workflow tuned for WooCommerce objects.
3. **Shared mixins**: Reusable list-table behaviors via `src/Mixins/*`.
4. **Legacy compatibility**: Wrappers (`XWC_Data_List_Page`, `XWC_Data_List_Table`) keep existing consumers working.
5. **WordPress-native integration**: Designed for plugin code already built around WordPress and WooCommerce lifecycles.

## Installation

You can install this package via Composer:

```bash
composer require x-wp/wc-data-type-admin
```

> [!TIP]
> We recommend using `automattic/jetpack-autoloader` with this package to reduce autoloading conflicts in WordPress environments.

## Package Structure

- `src/List_Page_Handler.php`: admin page handler with DI-based table resolution
- `src/List_Table.php`: base table implementation shared by legacy and new flows
- `src/Mixins/*`: shared list-table behaviors
- `src/Core/*`: legacy compatibility wrappers
- `templates/html-admin-page-edsl.php`: shared admin page template

## Testing

Regression checks:

```bash
php tests/regressions.php
```

Static analysis:

```bash
php -d memory_limit=1G vendor/bin/phpstan analyse --debug
```

> [!NOTE]
> The regression script is intentionally lightweight and does not require a full WordPress test bootstrap. PHPStan is configured conservatively for this library; in constrained environments, `--debug` is the verified non-parallel invocation path.

## Documentation

For package-specific usage, start with the public entrypoints:

- `XWC\Data\Admin\List_Page_Handler`
- `XWC\Data\Admin\List_Table`
- `XWC_Data_List_Page` (legacy)
- `XWC_Data_List_Table` (legacy)

Additional project information is available in the [repository](https://github.com/x-wp/wc-data-type-admin).
