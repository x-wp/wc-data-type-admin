<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended
/**
 * XWC_List_Table class file.
 *
 * @package eXtended WooCommerce
 * @subpackage Admin
 */

use XWC\Data\Traits;

/**
 * XWC Data standard list table class.
 *
 * @template TObj of XWC_Data
 * @template T of XWC_Data_Store_XT
 */
abstract class XWC_Data_List_Table extends \WP_List_Table {
    use Traits\Bulk_Action_Handler;
    use Traits\Item_Handler;
    /**
     * Row action handler trait.
     *
     * @use Traits\Row_Action_Handler<TObj>
     */
    use Traits\Row_Action_Handler;
    use Traits\Tablenav_Handler;
    use Traits\URI_Handler;

    /**
     * The data store object.
     *
     * @var XWC_Data_Store_XT
     * @phpstan-var WC_Data_Store
     */
    protected $data_store;

    /**
     * Columns to search
     *
     * @var array<string>
     */
    protected array $searchable;

    /**
     * WHERE clauses for use in Data Store
     *
     * @var array
     */
    protected array $query_args;

    /**
     * Default query arguments for the list table.
     *
     * @var array<string,mixed>
     */
    protected array $default_args = array();

    /**
     * Whether to force column definition
     *
     * @var bool
     */
    protected bool $force_cols = false;

    /**
     * Constructor.
     *
     * @param string              $entity     Data type key.
     * @param string              $view_prop  Column we use to filter the views.
     * @param array<string,mixed> $table_args Arguments for the list table.
     * @param array<string,mixed> $query_args Default query arguments.
     */
    public function __construct(
        /**
         * Data type key.
         *
         * @var string
         */
        protected string $entity,
        /**
         * Column we use to filter the views
         *
         * @var string
         */
        protected string $view_prop,
        array $table_args = array(),
        array $query_args = array(),
    ) {
        $this->maybe_clear_referer();

        $this->data_store   = $this->load_data_store();
        $this->searchable   = $this->get_searchable_columns();
        $this->query_args   = $this->parse_query_args();
        $this->default_args = $query_args;
        $this->force_cols   = $table_args['force_cols'] ?? false;

        parent::__construct( xwp_array_slice_assoc( $table_args, 'plural', 'singular', 'ajax', 'screen' ) );
    }

    /**
     * Get the list of filterable columns for this table.
     *
     * @return array<string>
     */
    abstract protected function get_searchable_columns(): array;

    /**
     * Get the list of views available on this table.
     *
     * @return string[]
     */
    abstract protected function get_view_types();

    /**
     * Load the data store for the list table
     *
     * @return WC_Data_Store
     */
    protected function load_data_store(): WC_Data_Store {
        return xwc_data_store( $this->entity );
    }

    /**
     * Get the column info
     *
     * @return array
     */
    protected function get_column_info() {
        if ( $this->force_cols ) {
            $this->_column_headers = array(
                $this->get_columns(),
                array(),
                $this->format_sortable_columns( $this->get_sortable_columns() ),
                $this->get_primary_column_name(),
            );
        }

        return parent::get_column_info();
    }

    /**
     * Format the sortable columns
     *
     * @param  array<string,array<int,mixed>> $sortable Sortable columns.
     * @return array<string,array<int,mixed>>
     */
    protected function format_sortable_columns( array $sortable ): array {
        foreach ( $sortable as $id => $data ) {
			if ( ! $data ) {
                continue;
            }

			$data = (array) $data;

            $data[1] ??= false;
            $data[2] ??= '';
            $data[3] ??= false;
            $data[4] ??= false;

			$sortable[ $id ] = $data;
		}

        return array_filter( $sortable );
    }

    /**
     * Parses the WHERE clauses from request array
     *
     * @return array WHERE clauses
     */
    protected function parse_query_args(): array {
        if ( $this->current_action() ) {
            return array();
        }

        $args = array();

        foreach ( $this->get_searchable_columns() as $p => $get ) {
            $p = is_integer( $p ) ? $get : $p;

            $args[ $p ] = $this->parse_query_arg( xwp_fetch_get_var( $get, null ) );
        }

        return array_filter( $args );
    }

    /**
     * Parses a query argument
     *
     * @param  mixed $v Value to parse.
     * @return mixed
     */
    protected function parse_query_arg( mixed $v ): mixed {
        return ! \is_null( $v ) && ! \in_array( $v, array( 'all', '' ), true )
            ? $v
            : null;
    }

    /**
     * Get the views for the list table
     *
     * @return array<string,string>
     */
    protected function get_views() {
        $selected = xwp_fetch_get_var( $this->view_prop, 'all' );
        $statuses = $this->get_view_types();

        if ( ! isset( $statuses['all'] ) ) {
            $statuses = array_merge( array( 'all' => __( 'All', 'default' ) ), $statuses );
        }

        foreach ( $statuses as $status => &$title ) {
            $args  = array_merge( $this->default_args, array( $this->view_prop => $status ) );
            $count = $this->data_store->count( $args );

            $title = $this->get_view_link( $status, $title, $count, $selected );
        }

        return array_filter( $statuses );
    }

    /**
     * Get the view link
     *
     * @param  string $status   View status key.
     * @param  string $title    View title.
     * @param  int    $count    Count of items.
     * @param  string $selected Selected view.
     * @return string|null
     */
    protected function get_view_link( string $status, string $title, int $count, string $selected ): ?string {
        if ( 0 >= $count ) {
            return null;
        }

        return \sprintf(
            '<a href="%s" class="%s">
                %s
                <span class="count">(%s)</span>
            </a>',
            \add_query_arg( array( $this->view_prop => $status ), $this->get_base_url() ),
            $status === $selected ? 'current' : '',
            $title,
            $count,
        );
    }

    /**
     * Extra inputs used when displaying the table on subpage of another page
     */
    public function extra_inputs() {
        $get          = xwp_get_arr();
        $input_string = '<input type="hidden" name="%s" value="%s">';

        //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        if ( \str_contains( $this->get_base_url(), 'post_type' ) && '' !== ( $get['post_type'] ?? '' ) ) {
            \printf(
                $input_string,
                'post_type',
                \esc_attr( $get['post_type'] ),
            );
        }

        if ( \str_contains( $this->get_base_url(), 'page' ) && '' !== ( $get['page'] ?? '' ) ) {
            \printf(
                $input_string,
                'page',
                \esc_attr( $get['page'] ),
            );
        }

        \printf(
            $input_string,
            'active',
            \esc_attr( $get['active'] ?? 'all' ),
        );
        //phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
