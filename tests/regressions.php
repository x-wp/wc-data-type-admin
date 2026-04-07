<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use XWC\Data\Admin\Interfaces\Data_Table;
use XWC\Data\Admin\Mixins\Item_Handler;
use XWC\Data\Admin\Mixins\URI_Handler;

final class Regression_Fake_Table extends XWC\Data\Admin\List_Table {
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

final class Regression_Legacy_Page extends XWC_Data_List_Page {
    protected function get_page_args(): array {
        return array(
            'namespace' => 'xwc',
            'id'        => 'demo',
            'title'     => 'Demo',
            'entity'    => 'demo',
            'connect'   => true,
        );
    }

    protected function get_table_args(): array {
        return array(
            'class' => Regression_Fake_Table::class,
            'args'  => array(
                'ajax' => false,
            ),
        );
    }

    public function set_table( Data_Table $table ): void {
        $this->table = $table;
    }
}

final class Regression_Render_Table extends XWC\Data\Admin\List_Table {
    public bool $prepared = false;
    public bool $displayed = false;

    public function current_action() {
        return false;
    }

    public function process_bulk_actions(): void {}

    public function views() {}

    public function extra_inputs() {}

    public function display_rows( ?array $items = null ) {}

    public function prepare_items( $per_page = 20, $page_number = 1 ) {
        $this->prepared = true;
    }

    public function display() {
        $this->displayed = true;
    }

    public function inline_edit() {}
}

final class Regression_URI_Host {
    use URI_Handler;

    protected function get_base_url() {
        return '/wp-admin/admin.php?page=demo';
    }
}

final class Regression_Item_Host {
    use Item_Handler;

    public function __construct( private array $sortable ) {
        $this->data_store = new XWC_Data_Store_XT();
    }

    public function expose_get_ordering_args(): array {
        return $this->get_ordering_args();
    }

    protected function get_entity(): string {
        return 'demo';
    }

    protected function get_column_info() {
        return array( array(), array(), $this->sortable, 'name' );
    }

    protected function get_items_per_page( string $option, int $default ): int {
        return $default;
    }

    protected function get_pagenum(): int {
        return 1;
    }

    protected function set_pagination_args( array $args ): void {}

    protected function single_row_columns( $item ): void {}
}

function assert_true( bool $condition, string $message ): void {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
}

function assert_same( $expected, $actual, string $message ): void {
    if ( $expected !== $actual ) {
        throw new RuntimeException(
            $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true )
        );
    }
}

$tests = array(
    'legacy page connects to wc admin' => function (): void {
        $GLOBALS['__wc_admin_pages'] = array();

        $page = new Regression_Legacy_Page();
        $page->add_menu_page();
        $page->maybe_connect_page();

        assert_same( 1, count( $GLOBALS['__wc_admin_pages'] ), 'Legacy page should register one WC Admin page.' );
        assert_same( 'xwc-demo', $GLOBALS['__wc_admin_pages'][0]['id'], 'Legacy page should use namespaced page id.' );
    },
    'legacy output loads moved template' => function (): void {
        $page  = new Regression_Legacy_Page();
        $table = new Regression_Render_Table();
        $page->set_table( $table );

        ob_start();

        try {
            $page->output();
        } finally {
            ob_end_clean();
        }

        assert_true( $table->prepared, 'Legacy output should prepare items via the moved template.' );
        assert_true( $table->displayed, 'Legacy output should display the table via the moved template.' );
    },
    'uri handler honors bottom bulk action selector' => function (): void {
        $GLOBALS['__test_request'] = array(
            'action'        => '-1',
            'action2'       => 'archive',
            'filter_action' => '',
        );

        $host = new Regression_URI_Host();

        assert_same( 'archive', $host->current_action(), 'Bottom bulk selector should be recognized.' );
    },
    'item handler prefers explicit initial sortable direction' => function (): void {
        $_GET = array();

        $host = new Regression_Item_Host(
            array(
                'name' => array( 'name', false, '', false, 'ASC' ),
            ),
        );

        assert_same(
            array( 'name', 'ASC' ),
            $host->expose_get_ordering_args(),
            'Initial sortable direction should control default ordering.'
        );
    },
    'grouped action buttons close their label tags' => function (): void {
        $html = xwc_render_action_buttons(
            array(
                array(
                    'group'   => 'Bulk',
                    'actions' => array(
                        array(
                            'action' => 'edit',
                            'url'    => '/edit',
                            'name'   => 'Edit',
                        ),
                    ),
                ),
            ),
        );

        assert_true( str_contains( $html, '</label>' ), 'Grouped action buttons should render a closing label tag.' );
    },
);

$failures = array();

foreach ( $tests as $name => $test ) {
    try {
        $test();
        echo "PASS: {$name}\n";
    } catch ( Throwable $e ) {
        $failures[] = "FAIL: {$name}\n" . $e->getMessage();
    }
}

if ( $failures ) {
    fwrite( STDERR, implode( "\n\n", $failures ) . "\n" );
    exit( 1 );
}

echo "All regression tests passed.\n";
