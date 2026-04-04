<?php
/**
 * Data_Table interface file.
 *
 * @package eXtended WooCommerce
 * @subpackage Interfaces
 */

namespace XWC\Data\Admin\Interfaces;

/**
 * Interface for data tables.
 */
interface Data_Table {
    /**
     * Gets the current action selected from the bulk actions dropdown.
     *
     * @return string|false The action name. False if no action was selected.
     */
    public function current_action();

    /**
     * Processes bulk actions.
     */
    public function process_bulk_actions(): void;

    /**
     * Displays the list of views available on this table.
     *
     * @return void
     */
    public function views();

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
     * Display the table
     *
     * @return void
     */
    public function display();

    /**
     * Display the inline edit form.
     *
     * @return void
     */
    public function inline_edit();
}
