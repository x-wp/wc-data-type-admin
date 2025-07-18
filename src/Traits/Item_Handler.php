<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended, Universal.Operators.DisallowShortTernary.Found

namespace XWC\Data\Traits;

use XWC_Data;
use XWC_Data_Store_XT;
use XWC_Meta_Store;

/**
 * Item handler trait.
 *
 * @template TObj of XWC_Data
 * @template TDs of XWC_Data_Store_XT
 */
trait Item_Handler {
    /**
     * The data store object.
     *
     * @var TDs
     */
    protected $data_store;

    /**
     * Object being handled
     *
     * @var TObj
     */
    protected XWC_Data $object;

    /**
     * Get order and orderby args
     *
     * @return array{0: string, 1: 'ASC'|'DESC'}
     */
    protected function get_ordering_args(): array {
        $req_oby = \xwp_fetch_get_var( 'orderby', '' );
        $req_ord = \xwp_fetch_get_var( 'order', '' );
        $def_fld = $this->data_store->get_id_field();

        if ( $req_oby && $req_ord ) {
            return array( $req_oby, $req_ord );
        }

        [ 2 => $sortable ] = $this->get_column_info();

        foreach ( $sortable as $col ) {
            $orderby = $col[0] ?? null;
            $text    = $col[3] ?? '';
            $init    = $col[4] ?? null;
        }

        if ( '' === ( $text ?? '' ) ) {
            return array( $def_fld, 'DESC' );
        }

        return array( $orderby ?? $def_fld, \strtoupper( $init ?? 'desc' ?: 'desc' ) );
    }

    /**
     * Prepare items to display
     *
     * @param  int $per_page Number of synchronizations to retrieve.
     * @param  int $page_num Page number.
     */
    public function prepare_items( $per_page = 20, $page_num = null ) {
        $this->get_column_info();
        [ $orderby, $order ] = $this->get_ordering_args();

        $defaults = array(
            'limit'    => $this->get_items_per_page( "edit_{$this->_args['plural']}_per_page", $per_page ),
            'order'    => $order,
            'orderby'  => $orderby,
            'page'     => $page_num ?? $this->get_pagenum(),
            'paginate' => true,
            'return'   => 'ids',
        );
        // phpcs:enable WordPress.Security

        $args = \array_merge( $defaults, $this->query_args );
        $res  = $this->data_store->query( $args );

        $this->set_pagination_args(
            array(
                'per_page'    => $args['limit'],
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
            $this->object = \xwc_get_object( $item, $this->entity );

            $this->single_row( $this->object );
        }
    }

    /**
     * Checkbox column
     *
     * @param  TObj $obj XWC_Data object.
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
     * @param  TObj   $obj  XWC_Data object.
     * @param  string $col  Column name.
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

        // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
        return $obj->$method() ?: '<span class="na">&ndash;</span>';
    }

    /**
     * Callback for the actions column
     *
     * @param TObj $obj Object being displayed.
     */
    protected function column_actions( $obj ): void {
        $filter = "xwc_admin_{$this->entity}_actions";

        echo '<div>';

        /**
         * Fires before the actions are displayed.
         *
         * @param XWC_Data $obj XWC_Data object.
         *
         * @since 1.0.0
         */
        \do_action( "{$filter}_start", $obj );

        /**
         * Filters the actions for the current item.
         *
         * @param  array<string,mixed> $actions Actions.
         * @param  XWC_Data            $obj     XWC_Data object.
         * @return array<string,mixed>          Actions.
         *
         * @since 1.0.0
         */
        $actions = \apply_filters( $filter, array(), $obj );

        echo \xwc_render_action_buttons( $actions );

        /**
         * Fires after the actions are displayed.
         *
         * @param XWC_Data $obj XWC_Data object.
         *
         * @since 1.0.0
         */
        \do_action( "{$filter}_end", $obj );

        echo '<div>';
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
