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
 * @template T of XWC_Data_Store_XT
 */
abstract class XWC_Data_List_Table extends \WP_List_Table {
    use Traits\Bulk_Action_Handler;
    use Traits\Item_Handler;
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
     * Constructor.
     *
     * @param string                                                                  $data_type Data type key.
     * @param string                                                                  $view_prop Column we use to filter the views.
     * @param array{plural?: string, singular?: string, ajax?: bool, screen?: string} $args      Arguments for the list table.
     */
    public function __construct(
        /**
         * Data type key.
         *
         * @var string
         */
        protected string $data_type,
        /**
         * Column we use to filter the views
         *
         * @var string
         */
        protected $view_prop,
        array $args = array(),
    ) {
        $this->data_store = $this->load_data_store();
        $this->searchable = $this->get_searchable_columns();
        $this->query_args = $this->parse_query_args();

        parent::__construct( $args );
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
        return xwc_data_store( $this->data_type );
    }

    /**
     * Parses the WHERE clauses from request array
     *
     * @return array WHERE clauses
     */
    protected function parse_query_args(): array {
        $this->maybe_clear_referer();

        $args = array();

        if ( $this->current_action() ) {
            return $args;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $request = \wc_clean( \wp_unslash( $_GET ) );

        foreach ( $this->get_searchable_columns() as $get_param ) {

            $param_value = $request[ $get_param ] ?? null;

            if ( \is_null( $param_value ) || \in_array( $param_value, array( 'all', '' ), true ) ) {
                continue;
            }

            $args[ $get_param ] = $param_value;
        }

        return $args;
    }

    /**
     * Undocumented function
     */
    protected function get_views() {
        $selected = \wc_clean( \wp_unslash( $_GET[ $this->view_prop ] ?? 'all' ) );
        $statuses = $this->get_view_types();
        $base_url = $this->get_base_url();
        $views    = array();

        foreach ( $statuses as $status => $title ) {
            $args = array( $this->view_prop => $status );

            $views[ $status ] = \sprintf(
                '<a href="%s" class="%s">
                    %s
                    <span class="count">(%s)</span>
                </a>',
                \add_query_arg( $args, $base_url ),
                $status === $selected ? 'current' : '',
                $title,
                $this->data_store->count( $args ),
            );
        }

        return $views;
    }

    /**
     * Extra inputs used when displaying the table on subpage of another page
     */
    public function extra_inputs() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $get          = \wc_clean( \wp_unslash( $_GET ) );
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
