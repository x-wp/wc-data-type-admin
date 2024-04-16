<?php
/**
 * Data_Table interface file.
 *
 * @package eXtended WooCommerce
 * @subpackage Interfaces
 */

namespace XWC\Interfaces;

/**
 * Interface for data tables.
 */
interface Data_Table {
    /**
     * Extra inputs used when displaying the table on subpage of another page
     */
    public function extra_inputs();

    /**
     * Display the table rows
     *
     * @param ?array $items List of items.
     */
    public function display_rows( ?array $items = null );

    /**
     * Prepare items to display
     *
     * @param  int $per_page    Number of synchronizations to retrieve.
     * @param  int $page_number Page number.
     */
    public function prepare_items( $per_page = 20, $page_number = 1 );

    /**
     * Processes bulk actions.
     */
    public function process_bulk_actions();
}
