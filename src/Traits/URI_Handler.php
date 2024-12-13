<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended
/**
 * URI_Clearner trait file.
 *
 * @package eXtended WooCommerce
 * @subpackage Traits
 */

namespace XWC\Data\Traits;

/**
 * Cleans the unneeded parameters from the request URI.
 */
trait URI_Handler {
    /**
     * Get the base URL for the list table
     *
     * @return string Base URL
     */
    abstract protected function get_base_url();

    /**
	 * Gets the current action selected from the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 *
	 * @return string|false The action name. False if no action was selected.
	 */
	public function current_action() {
        $request = \wc_clean( \wp_unslash( $_REQUEST ) );
        $filter  = $request['filter_action'] ?? '';
        $action  = $request['action'] ?? '-1';

        if ( '' !== $filter ) {
            return false;
        }

        if ( '-1' !== $action ) {
            return $action;
        }

        return false;
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
     * Get the cleaners to use.
     *
     * @param  array $cleaners The cleaners to use.
     * @return array
     */
    protected function get_cleaners( array $cleaners ): array {
        return \array_keys(
            \array_filter(
                \wp_parse_args(
                    $cleaners,
                    array(
                        'empty_params'    => true,
                        'non_actions'     => true,
                        'referer_nonce'   => true,
                        'shotgun_filters' => true,
                    ),
                ),
            ),
        );
    }

    /**
     * Clean the request parameters.
     *
     * @param  array<string, bool> $what The cleaners to use.
     * @return array|false
     */
    public function clean_uri_params( array $what = array() ): array|false {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$req  = \wc_clean( \wp_unslash( $_REQUEST ) );
        $hash = \md5( \wp_json_encode( $req ) );

        foreach ( $this->get_cleaners( $what ) as $cleaner ) {
            $this->{"remove_{$cleaner}"}( $req );
        }

        if ( \md5( \wp_json_encode( $req ) ) === $hash ) {
            return false;
        }

        return $req;
    }

    /**
     * Remove non-action parameters from the request.
     *
     * @param  array $req The request parameters.
     */
    public function remove_non_actions( array &$req ) {
        $keys = array( 'action', 'action2' );

        foreach ( $keys as $key ) {
            if ( '-1' !== ( $req[ $key ] ?? '' ) ) {
                continue;
            }

            unset( $req[ $key ] );
        }
    }

    /**
     * Remove empty parameters from the request.
     *
     * @param  array $req The request parameters.
     */
    public function remove_empty_params( array &$req ) {
        foreach ( $req as $key => $value ) {
            if ( '' !== $value ) {
                continue;
            }

            unset( $req[ $key ] );
        }
    }

    /**
     * Remove shotgun filters from the request.
     *
     * Shotgun filters are filters which do not have a specific value, such as `*` or `all`.
     *
     * @param  array $req The request parameters.
     */
    public function remove_shotgun_filters( array &$req ) {
        $values = array( 'all', '*' );

        foreach ( $req as $key => $value ) {
            if ( ! \in_array( $value, $values, true ) ) {
                continue;
            }

            unset( $req[ $key ] );
        }
    }

    /**
     * Remove referer and nonce parameters from the request.
     *
     * @param  array $req The request parameters.
     */
    public function remove_referer_nonce( array &$req ) {
        $keys = array( '_wp_http_referer', '_wpnonce', '_nonce', '_wp_referer', 'nonce', 'referer', 'security' );

        foreach ( $keys as $key ) {
            if ( ! isset( $req[ $key ] ) ) {
                continue;
            }

            unset( $req[ $key ] );
        }
    }
}
