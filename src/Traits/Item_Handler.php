<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace XWC\Data\Traits;

use XWC_Data;
use XWC_Data_Store_XT;

trait Item_Handler {
    /**
     * The data store object.
     *
     * @var XWC_Data_Store_XT
     * @phpstan-var WC_Data_Store
     */
    protected $data_store;

    /**
     * Prepare items to display
     *
     * @param  int $per_page Number of synchronizations to retrieve.
     * @param  int $page_num Page number.
     */
    public function prepare_items( $per_page = 20, $page_num = null ) {
        $this->_column_headers = $this->get_column_info();

        // phpcs:disable WordPress.Security
        $sanitize = static fn( $v, $d ) => \sanitize_text_field( \wp_unslash( $_GET[ $v ] ?? $d ) );
        $defaults = array(
            'order'    => $sanitize( 'order', 'DESC' ),
            'orderby'  => $sanitize( 'orderby', 'id' ),
            'page'     => $page_num ?? $this->get_pagenum(),
            'paginate' => true,
            'per_page' => $this->get_items_per_page( "edit_{$this->_args['plural']}_per_page", $per_page ),
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
            //phpcs:ignore PHPCompatibility.Variables
            global ${$this->data_type};

            ${$this->data_type} = \xwc_get_object( $item, $this->data_type );

            $this->single_row( ${$this->data_type} );
        }
    }

    /**
     * Checkbox column
     *
     * @param  XWC_Data $obj XWC_Data object.
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
     * Default column callback
     *
     * @param  XWC_Data $obj  XWC_Data object.
     * @param  string   $col  Column name.
     * @return string       Column HTML.
     */
    protected function column_default( $obj, $col ) {
        $fn = static fn( string $mth ) => \method_exists( $obj, "get_{$mth}_{$col}" );

        $method = match ( true ) {
            $fn( 'list_table' ) => "get_list_table_{$col}",
            $fn( 'formatted' )  => "get_formatted_{$col}",
            $fn( 'localized' )  => "get_localized_{$col}",
            default           => "get_{$col}",
        };

        return $obj->$method();
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
