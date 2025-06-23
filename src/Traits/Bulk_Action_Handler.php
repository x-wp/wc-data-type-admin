<?php
/**
 * Bulk_Action_Handler trait file.
 *
 * @package eXtended WooCommerce
 * @subpackage Traits
 */

namespace XWC\Data\Traits;

/**
 * Trait for handling bulk actions.
 */
trait Bulk_Action_Handler {
    /**
	 * Various information about the current table.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	protected $_args; //phpcs:ignore PSR2

    /**
	 * Gets the current action selected from the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 *
	 * @return string|false The action name. False if no action was selected.
	 */
	abstract public function current_action();

    /**
     * Get message for bulk action
     *
     * @param  string $action Bulk action.
     * @param  int    $count  Number of logs.
     * @return string         Message.
     */
	abstract protected function get_bulk_message( string $action, int $count ): string;

    /**
     * Processes bulk actions.
     */
    public function process_bulk_actions() {
        $action  = $this->current_action();
        $log_ids = \wp_parse_id_list( \xwp_fetch_req_var( "{$this->_args['singular']}_id", array() ) );
        $type    = 'success';
        $total   = 0;

        if ( 0 === \count( $log_ids ) ) {
            return;
        }

        \check_admin_referer( 'bulk-' . $this->_args['plural'] );

        try {
            $total = $this->{"bulk_{$action}_items"}( $log_ids );

            $message = $this->get_bulk_message( $action, $total );
        } catch ( \Throwable $e ) {
            $type    = 'error';
            $message = $e->getMessage();
        }

        \add_settings_error( 'bulk_action', 'bulk_action', $message, $type );
    }
}
