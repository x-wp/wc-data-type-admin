<?php
/**
 * Row_Action_Handler trait file.
 *
 * @package eXtended WooCommerce
 */

namespace XWC\Data\Traits;

use XWC_Data;

trait Row_Action_Handler {
    /**
     * Row actions
     *
     * @var array<string, array{title: string, url: callable(\XWC_Data=): string|false, when: callable(\XWC_Data=): bool}|string>
     */
    protected array $row_actions = array();

    /**
     * Get current row actions
     *
     * @return array<string, array{title: string, url: callable(XWC_Data): string|false, when: callable(Data): bool}|string>
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
     * Formats the row action for display.
     *
     * @param  array    $data Row action data.
     * @param  XWC_Data $obj  Data object.
     * @return string|false
     */
    protected function format_row_action( array $data, XWC_Data $obj ): string|false {
        if ( ! $data['title'] || ! $data['when']( $obj ) ) {
            return false;
        }

        $url = $data['url']( $obj );

        return $url
            ? \sprintf( '<a href="%s">%s</a>', $url, $data['title'] )
            : $data['title'];
    }

    /**
     * Handles the row actions
     *
     * @param  XWC_Data $obj     Object being acted upon.
     * @param  string   $column  Current column name.
     * @param  string   $primary Primary column name.
     * @return string            Row actions HTML, if the column is the primary column.
     */
    protected function handle_row_actions( $obj, $column, $primary ) {
        if ( $primary !== $column ) {
            return '';
        }
        $actions = array();

        foreach ( $this->row_actions as $action => $data ) {
            $actions[ $action ] = $this->format_row_action( $data, $obj );
        }

        return $this->row_actions( \array_filter( $actions ) );
    }
}
