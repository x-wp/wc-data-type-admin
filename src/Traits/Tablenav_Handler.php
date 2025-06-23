<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace XWC\Data\Traits;

trait Tablenav_Handler {
    /**
     * Get the extra table navigation filters
     *
     * @return array<string,array{
     *   all: string,
     *   options: array<string,string>,
     *   type?: string,
     *   callback?: callable(string): string,
     *   class?: string,
     *   type?: string,
     * }>
     */
    abstract protected function get_extra_tablenav_filters();

    /**
     * Extra tablenav display
     *
     * @param  string $which Which tablenav. Can be 'top' or 'bottom'.
     */
    protected function extra_tablenav( $which ) {
        $tablenav_filters = $this->get_extra_tablenav_filters();

        if ( 'top' !== $which || ! $tablenav_filters ) {
            return;
        }

        echo '<input type="hidden" name="s" value="">';
        echo '<div class="alignleft actions">';

        foreach ( $tablenav_filters as $key => $data ) {
            $this->display_tablenav_filter( $data, $key );
        }

        echo '<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">';
        echo '</div>';
    }

    /**
     * Displays individual tablenav filter
     *
     * @param array<string,mixed> $args Filter data.
     * @param string              $key  Filter key.
     */
    final protected function display_tablenav_filter( array $args, string $key ) {
        $args['type']   ??= $key;
        $args['selected'] = \xwp_fetch_req_var( $args['type'], '0' );
        $args['callback'] = $this->get_filter_option_cb( $args['callback'] ?? false );
        $args['class']    = \implode( ' ', \wc_string_to_array( $args['class'] ?? '' ) );

        \printf(
            <<<'HTML'
                <select class="postform %1$s" name="%2$s">
                    <option value="all" %3$s>%4$s</option>
                    %5$s
                </select>
            HTML,
            \esc_attr( $args['class'] ),
            \esc_attr( $args['type'] ),
            \selected( $args['selected'], 'all', false ),
            \esc_html( $args['all'] ),
            $this->get_filter_options_html( $args ), // phpcs:ignore
        );
    }

    /**
     * Get the filter options HTML
     *
     * @param  array $args Filter data.
     * @return string
     */
    private function get_filter_options_html( array $args ): string {
        $opts = array();

        foreach ( $args['options'] as $value => $label ) {
            $opts[] = \sprintf(
                '<option value="%s" %s>%s</option>',
                \esc_attr( $value ),
                \selected( $args['selected'], $value, false ),
                \esc_html( $args['callback']( $label ) ),
            );
        }

        return \implode( '', $opts );
    }

    /**
     * Get the filter option callback
     *
     * @param  mixed $cb Callback.
     * @return callable(string): string
     */
    private function get_filter_option_cb( mixed $cb ): callable {
        return \is_callable( $cb )
            ? static fn( $l ) => $cb( $l )
            : static fn( $l ) => $l;
    }
}
