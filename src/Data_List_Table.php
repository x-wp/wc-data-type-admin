<?php //phpcs:disable WordPress.Security.NonceVerification.Recommended, PHPCompatibility.Variables.ForbiddenThisUseContexts.Global, PHPCompatibility.Variables.ForbiddenGlobalVariableVariable.NonBareVariableFound
/**
 * Extended_Data_List_Table class file.
 *
 * @package WooCommerce Utils
 * @subpackage Data
 */

namespace XWC;

use XWC\Interfaces\Data_Table;
use XWC\Interfaces\Enums\Labelable;

/**
 * Standardized list table for extended data stores
 */
abstract class Data_List_Table extends \WP_List_Table implements Data_Table {
    use \XWC\Traits\Data_Type_Meta;
    use \XWC\Traits\URI_Cleaner;
    use \XWC\Traits\Bulk_Action_Handler;

    /**
     * ID field of the data type.
     *
     * @var string
     */
    protected string $id_field;

    /**
     * Object type of the data type.
     *
     * @var string
     */
    protected string $object_type;

    /**
     * Datastore holding the data
     *
     * @var Data_Store_CT $data_store
     */
    protected $data_store = null;

    /**
     * WHERE clauses for use in Data Store
     *
     * @var array
     */
    protected $query_args = array();

    /**
     * Columns we can use for sorting and search.
     * Column name should correspond to a `_GET` parameter.
     *
     * @var array
     */
    protected $searchable_columns = array();

    /**
     * Column we use to filter the views
     *
     * @var string
     */
    protected $views_column = '';

    /**
     * Row actions
     *
     * @var array<string, array{title: string, url: callable(Data): string|false, when: callable(Data): bool}|string>
     */
    protected array $row_actions = array();

    /**
     * Class constructor
     *
     * @param string $data_type Data type key.
     * @param array  $args      Arguments for the list table.
     */
    public function __construct(
        /**
         * Data type key.
         *
         * @var string
         */
        protected string $data_type,
        $args,
    ) {
        $this->init_metadata();
        $this->load_data_store();
        $this->parse_query_args();
        $this->maybe_clear_referer();

        parent::__construct( $args );
    }

    /**
     * Get needed metadata keys.
     *
     * @return array
     */
    protected function get_metadata_keys(): array {
        return array(
            'id_field',
            'object_type',
        );
    }

    /**
     * Loads the data store for the list table.
     */
    protected function load_data_store() {
        $this->data_store = &\XWC\Data_Store::load( $this->object_type );
    }

    /**
     * Clears the referer and nonce from the URL
     */
    protected function maybe_clear_referer() {
        $referer_nonce = ! $this->current_action() && isset( $_REQUEST['_wp_http_referer'] );
        $clean         = array( 'referer_nonce' => $referer_nonce );

        $params = $this->clean_uri_params( $clean );

        if ( ! $params ) {
            return;
        }

        \wp_safe_redirect( \add_query_arg( $params, $this->get_base_url() ) );
        exit;
    }

    /**
     * Get a key-value pair for all enum cases
     *
     * @param  array<Labelable> $cases Enum cases.
     * @return array
     */
    protected function labelize_enum( array $cases ): array {
        $arr = array();

        foreach ( $cases as $case ) {
            $arr[ $case->value ] = $case->getLabel( $case );
        }

        return $arr;
    }

    /**
     * Parses the WHERE clauses from request array
     *
     * @return array WHERE clauses
     */
    protected function parse_query_args() {
        if ( $this->current_action() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $request = \wc_clean( \wp_unslash( $_GET ) );

        foreach ( $this->searchable_columns as $get_param ) {

            $param_value = $request[ $get_param ] ?? null;

            if ( \is_null( $param_value ) || \in_array( $param_value, array( 'all', '' ), true ) ) {
                continue;
            }

            $this->query_args[ $get_param ] = $param_value;
        }
    }

    /**
     * Get the base URL for the list table
     *
     * @return string Base URL
     */
    abstract protected function get_base_url();

    /**
     * Get the list of views available on this table.
     *
     * @return string[]
     */
    abstract protected function get_view_types();

    /**
     * Get current row actions
     *
     * @return array<string, array{title: string, url: callable(Data): string|false, when: callable(Data): bool}|string>
     */
    abstract protected function get_row_actions();

    /**
     * Prepares the row actions for display
     */
    private function prep_row_actions() {
        $default = array(
            'title' => '',
            'url'   => static fn() => false,
            'when'  => static fn() => true,
        );

        foreach ( $this->get_row_actions() as $action => $data ) {
            if ( \is_string( $data ) ) {
                $data = array( 'title' => $data );
            }

            $this->row_actions[ $action ] = \wp_parse_args( $data, $default );
        }
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

    /**
     * Undocumented function
     */
    protected function get_views() {
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected = \sanitize_text_field( \wp_unslash( $_GET[ $this->views_column ] ?? 'all' ) );
        $statuses = $this->get_view_types();
        $base_url = $this->get_base_url();
        $views    = array();

        foreach ( $statuses as $status => $title ) {
            $args = array( $this->views_column => $status );

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
     * Get the extra table navigation filters
     *
     * @return array
     */
    abstract protected function get_extra_tablenav_filters();

    /**
     * Extra tablenav display
     *
     * @param  string $which Which tablenav.
     */
    final protected function extra_tablenav( $which ) {
        $tablenav_filters = $this->get_extra_tablenav_filters();

        if ( 'top' !== $which || ! $tablenav_filters ) {
            return;
        }

        echo '<input type="hidden" name="s" value="">';
        echo '<div class="alignleft actions">';

        foreach ( $tablenav_filters as $type => $filter_data ) {
            $this->display_tablenav_filter( $type, $filter_data );
        }

        echo '<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">';
        echo '</div>';
    }

    /**
     * Displays individual tablenav filter
     *
     * @param string $type Filter type.
     * @param array  $data Filter data.
     */
    final protected function display_tablenav_filter( $type, $data ) {
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected = \sanitize_text_field( \wp_unslash( $_REQUEST[ $type ] ?? '0' ) );

        $select_opts = array(
			\sprintf(
                '<option value="%s" %s>%s</option>',
                'all',
                \selected( $selected, 'all', false ),
                $data['all'],
            ),
		);

        $callback = \is_callable( $data['callback'] ?? false )
            ? static fn( $l ) => $data['callback']( $l )
            : static fn( $l ) => $l;

        foreach ( $data['options'] as $value => $label ) {
            $select_opts[] = \sprintf(
                '<option value="%s" %s>%s</option>',
                \esc_attr( $value ),
                \selected( $selected, $value, false ),
                \esc_html( $callback( $label ) ),
            );
        }

        // phpcs:disable WordPress.Security
        \printf(
            <<<'HTML'
                <select class="postform %s" name="%s">
                    %s
                </select>
            HTML,
            \esc_attr( \implode( ' ', $data['class'] ?? array() ) ),
            \esc_attr( $type ),
            \implode( '', $select_opts ),
        );
        // phpcs:enable WordPress.Security
    }

    /**
     * Prepare items to display
     *
     * @param  int $per_page    Number of synchronizations to retrieve.
     * @param  int $page_number Page number.
     */
    public function prepare_items( $per_page = 20, $page_number = 1 ) {
        $this->_column_headers = $this->get_column_info();

        // phpcs:disable WordPress.Security
        $sanitize = static fn( $v, $d ) => \sanitize_text_field( \wp_unslash( $_GET[ $v ] ?? $d ) );
        $defaults = array(
            'order'    => $sanitize( 'order', 'DESC' ),
            'orderby'  => $sanitize( 'orderby', $this->id_field ),
            'page'     => $this->get_pagenum(),
            'paginate' => true,
            'per_page' => $this->get_items_per_page( "edit_{$this->_args['plural']}_per_page", 20 ),
            'return'   => 'ids',
        );
        // phpcs:enable WordPress.Security

        $args = \array_merge( $defaults, $this->query_args );
        $res  = $this->data_store->query( $args );

        $this->set_pagination_args(
            array(
                'per_page'    => $args['per_page'],
                'total_items' => $res['total'],
                'total_pages' => $res['pages'],
            ),
        );

        $this->items = $res['objects'];
    }

    /**
     * Display the table rows
     *
     * @param ?array $items List of items.
     */
    public function display_rows( ?array $items = null ) {
        if ( \is_null( $items ) ) {
            $items = &$this->items;
        }

        if ( ! \is_array( $items ) ) {
            $items = array( $items );
        }

        $this->prep_row_actions();

        foreach ( $items as $item ) {
            $dto = \xwc_get_data( $item, $this->data_type );

            $GLOBALS[ $this->data_type ] = &$dto;

            $this->single_row( $dto );
        }
    }

    /**
     * Handles the row actions
     *
     * @param  Data   $obj         Object being acted upon.
     * @param  string $column_name Current column name.
     * @param  string $primary     Primary column name.
     * @return string              Row actions HTML, if the column is the primary column.
     */
    protected function handle_row_actions( $obj, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }
        $actions = array();

        foreach ( $this->row_actions as $action => $data ) {
            $actions[ $action ] = $this->format_row_action( $data, $obj );
        }

        return $this->row_actions( \array_filter( $actions ) );
    }

    /**
     * Formats the row action for display.
     *
     * @param  array $data Row action data.
     * @param  Data  $obj  Data object.
     * @return string|false
     */
    protected function format_row_action( array $data, Data $obj ): string|false {
        if ( ! $data['title'] || ! $data['when']( $obj ) ) {
            return false;
        }

        $url = $data['url']( $obj );

        return $url
            ? \sprintf( '<a href="%s">%s</a>', $url, $data['title'] )
            : $data['title'];
    }

    /**
     * Default column callback
     *
     * @param  Data   $obj         Data object.
     * @param  string $column_name Column name.
     * @return string              Column HTML.
     */
    protected function column_default( $obj, $column_name ) {
        $method = \method_exists( $obj, "get_localized_{$column_name}" )
            ? "get_localized_{$column_name}"
            : "get_{$column_name}";

        return $obj->$method();
    }

    /**
     * Checkbox column
     *
     * @param  Data $obj Data object.
     * @return string    Checkbox HTML
     */
    protected function column_cb( $obj ) {
        return \sprintf(
            '<input type="checkbox" name="%s_id[]" value="%s" />',
            $this->_args['singular'],
            $obj->get_id(),
        );
    }

    /**
     * Displays the content for boolean output
     *
     * @param  string|bool $value Value of the prop.
     * @param  string      $text  Text to display.
     * @return string             HTML
     */
    protected function boolean_column( $value, $text = '' ) {
        $value = \wc_string_to_bool( $value );

        $class = 'no';
        $icon  = '<span class="dashicons dashicons-dismiss"></span>';
        $color = '#d00';

        if ( $value ) {
            $class = 'yes';
            $icon  = '<span class="dashicons dashicons-yes-alt"></span>';
            $color = '#039403';
        }

        return $text
            ? \sprintf(
                '<span class="table-icon icon-%s tips" data-tip="%s" style="color: %s;">
                    %s
                </span>',
                \esc_attr( $class ),
                $text,
                $color,
                \wp_kses_post( $icon ),
            )
            : \sprintf(
                '<span class="table-icon icon-%s" style="color: %s;">
                    %s
                </span>',
                \esc_attr( $class ),
                $color,
                \wp_kses_post( $icon ),
            );
    }
}
