<?php
/**
 * Row_Action_Handler trait file.
 *
 * @package eXtended WooCommerce
 */

namespace XWC\Data\Traits;

use XWC_Data;

/**
 * Row action methods for XWC_Data_List_Table.
 *
 * @template TObj of XWC_Data
 */
trait Row_Action_Handler {
    /**
     * Row actions
     *
     * @var array<string, array{title: string, url: callable(TObj): string|false, when: callable(TObj): bool}|string>
     */
    protected array $row_actions = array();

    /**
     * Get current row actions
     *
     * @return array<string,string|array{
     *   title: string,
     *   url: callable(TObj): string|false,
     *   when: callable(TObj): bool
     *   class?: array<string>|string|callable(TObj): string|array<string>
     * }>
     */
    abstract protected function get_row_actions();

    /**
     * Prepares the row actions for display
     */
    private function prep_row_actions() {
        $default = array(
            'class' => static fn() => '',
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

        if ( ! $url ) {
            return $data['title'];
        }

        $tgt = \str_starts_with( $url, \home_url( 'wp-admin' ) ) ? '_self' : '_blank';
        $cls = \is_callable( $data['class'] ) ? $data['class']( $obj ) : $data['class'];

        return \sprintf(
            <<<'HTML'
                <a href="%1$s" target="%2$s" class="%3$s">%4$s</a>
            HTML,
            \esc_url( $url ),
            \esc_attr( $tgt ),
            \implode( ' ', \array_map( 'sanitize_html_class', \wc_string_to_array( $cls ) ) ),
            \esc_html( $data['title'] ),
        );
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
