<?php

declare(strict_types=1);

namespace {
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/fixtures/wp/' );
    }

    if ( ! class_exists( 'WP_List_Table' ) ) {
        class WP_List_Table {}
    }

    if ( ! class_exists( 'XWC_Data' ) ) {
        class XWC_Data {
            public function __construct( private int $id = 0 ) {}

            public function get_id(): int {
                return $this->id;
            }
        }
    }

    if ( ! class_exists( 'XWC_Data_Store_XT' ) ) {
        class XWC_Data_Store_XT {
            public function get_id_field(): string {
                return 'id';
            }
        }
    }
}

namespace DI {
    if ( ! class_exists( Container::class ) ) {
        class Container {
            public function make( string $class, array $args = array() ) {
                return new $class( ...$args );
            }
        }
    }
}

namespace {
    require_once __DIR__ . '/../src/Interfaces/Data_Table.php';
}

namespace XWC\Data\Admin {
    if ( ! class_exists( List_Table::class ) ) {
        class List_Table implements Interfaces\Data_Table {
            public function current_action() {
                return false;
            }

            public function process_bulk_actions(): void {}

            public function views() {}

            public function extra_inputs() {}

            public function display_rows( ?array $items = null ) {}

            public function prepare_items( $per_page = 20, $page_number = 1 ) {}

            public function display() {}

            public function inline_edit() {}
        }
    }
}

namespace {
    $GLOBALS['__test_request']       = array();
    $GLOBALS['__wc_admin_pages']     = array();
    $GLOBALS['__settings_errors']    = array();
    $GLOBALS['__enqueued_scripts']   = array();
    $GLOBALS['__registered_actions'] = array();
    $GLOBALS['__registered_filters'] = array();

    function xwp_req_arr( array $defaults = array() ): array {
        return array_merge( $defaults, $GLOBALS['__test_request'] );
    }

    function xwp_fetch_post_var( string $key, $default = null ) {
        return $_POST[ $key ] ?? $default;
    }

    function xwp_fetch_get_var( string $key, $default = null ) {
        return $_GET[ $key ] ?? $default;
    }

    function wp_json_encode( $value ): string {
        return (string) json_encode( $value );
    }

    function wp_parse_args( array $args, array $defaults ): array {
        return array_merge( $defaults, $args );
    }

    function xwp_parse_args( array $args, array $defaults ): array {
        return array_merge( $defaults, $args );
    }

    function xwp_array_diff_assoc( array $args, string $key ): array {
        unset( $args[ $key ] );

        return $args;
    }

    function xwp_app( string $container ) {
        return null;
    }

    function xwp_get_template( string $path, array $vars = array() ): void {
        extract( $vars, EXTR_SKIP );
        require $path;
    }

    function add_query_arg( $key, $value = '', $url = '' ): string {
        if ( is_array( $key ) ) {
            return rtrim( $url, '?' ) . '?' . http_build_query( $key );
        }

        return rtrim( (string) $url, '?' ) . '?' . http_build_query( array( $key => $value ) );
    }

    function wc_admin_connect_page( array $args ): void {
        $GLOBALS['__wc_admin_pages'][] = $args;
    }

    function add_submenu_page( $base, $title, $menu_title, $capability, $slug, $callback ): string {
        return "hook-{$slug}";
    }

    function add_action( string $tag, $callback, int $priority = 10, int $args = 1 ): void {
        $GLOBALS['__registered_actions'][] = array( $tag, $priority );
    }

    function add_filter( string $tag, $callback, int $priority = 10, int $args = 1 ): void {
        $GLOBALS['__registered_filters'][] = array( $tag, $priority );
    }

    function add_screen_option( string $option, array $args ): void {}

    function wp_enqueue_script( string $handle ): void {
        $GLOBALS['__enqueued_scripts'][] = $handle;
    }

    function sanitize_html_class( string $value ): string {
        return preg_replace( '/[^A-Za-z0-9_-]/', '-', $value ) ?? '';
    }

    function esc_url( string $value ): string {
        return $value;
    }

    function esc_attr( string $value ): string {
        return htmlspecialchars( $value, ENT_QUOTES );
    }

    function wp_kses_post( string $value ): string {
        return $value;
    }

    function do_action( string $tag, ...$args ): void {}

    function apply_filters( string $tag, $value, ...$args ) {
        return $value;
    }

    function settings_errors(): void {}

    require_once __DIR__ . '/../src/Functions/xwc-list-table-fns.php';
    require_once __DIR__ . '/../src/Mixins/URI_Handler.php';
    require_once __DIR__ . '/../src/Mixins/Item_Handler.php';
    require_once __DIR__ . '/../src/Core/XWC_Data_List_Page.php';
}
