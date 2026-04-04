<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended
/**
 * XWC_List_Table class file.
 *
 * @package eXtended WooCommerce
 * @subpackage Admin
 */

use XWC\Data\Admin\List_Table;

/**
 * XWC Data standard list table class.
 *
 * @template TObj of XWC_Data
 * @template TDs of XWC_Data_Store_XT<TObj>
 *
 * @extends List_Table<TObj,TDs>
 */
abstract class XWC_Data_List_Table extends List_Table {
    /**
     * Constructor.
     *
     * @param string              $entity     Data type key.
     * @param string              $view_prop  Column we use to filter the views.
     * @param array<string,mixed> $table_args Arguments for the list table.
     * @param array<string,mixed> $query_args Default query arguments.
     */
    public function __construct(
        string $entity,
        string $view_prop,
        array $table_args = array(),
        array $query_args = array(),
    ) {
        parent::__construct(
            xwc_ds( $entity ),
            $this->get_searchable_columns(),
            $query_args,
            array(
                'core'       => array(
                    'ajax'     => $table_args['ajax'] ?? false,
                    'plural'   => $table_args['plural'] ?? "{$entity}s",
                    'singular' => $table_args['singular'] ?? $entity,
                ),
                'force_cols' => $table_args['force_cols'] ?? false,
                'view_prop'  => $view_prop,

            ),
        );
    }

    /**
     * Get the list of filterable columns for this table.
     *
     * @return array<string>
     */
    abstract protected function get_searchable_columns(): array;
}
