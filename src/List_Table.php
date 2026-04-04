<?php //phpcs:disable Squiz.Commenting.FunctionComment.IncorrectTypeHint, Squiz.Commenting.FunctionComment.MissingParamTag, Squiz.Commenting.FunctionComment.MissingParamName

namespace XWC\Data\Admin;

use Automattic\Jetpack\Constants;
use WP_List_Table;
use XWC_Data;
use XWC_Data_Store_XT;

/**
 * Base list table class for XWC_Data objects.
 *
 * @template TObj of XWC_Data
 * @template TDs of XWC_Data_Store_XT<TObj>
 */
abstract class List_Table extends WP_List_Table implements Interfaces\Data_Table {
    use Mixins\Bulk_Action_Handler;
    /**
     * Item handler trait.
     *
     * @use Mixins\Item_Handler<TObj,TDs>
     */
    use Mixins\Item_Handler;
    /**
     * Row action handler trait.
     *
     * @use Mixins\Row_Action_Handler<TObj>
     */
    use Mixins\Row_Action_Handler;
    use Mixins\Tablenav_Handler;
    use Mixins\URI_Handler;

    /**
     * Query arguments for the list table.
     *
     * @var array<string,mixed>
     */
    protected array $query_args;

    /**
     * Whether to force column definition.
     *
     * @var bool
     */
    protected bool $force_cols = false;

    /**
     * Column used to filter the views.
     *
     * @var string
     */
    protected string $view_prop;

    /**
     * Constructor.
     *
     * @param TDs                      $data_store   Entity data store.
     * @param array<int|string,string> $searchable   Columns to search.
     * @param array<string,mixed>      $default_args Query arguments for the list table.
     * @param array{
     *   view_prop: string,
     *   force_cols?: bool,
     *   core?: array{
     *     singular?: string,
     *     plural?: string,
     *     ajax?: bool,
     *     screen?: string,
     *   },
     * }                               $args         List table arguments.
     */
    public function __construct(
        protected XWC_Data_Store_XT $data_store,
        protected array $searchable,
        protected array $default_args,
        array $args,
    ) {
        $this->maybe_clear_referer();

        $this->query_args = $this->parse_query_args();
        $this->view_prop  = $args['view_prop'];
        $this->force_cols = $args['force_cols'] ?? false;

        parent::__construct( $args['core'] );
    }

    /**
     * Get the list of views available on this table.
     *
     * @return string[]
     */
    abstract protected function get_view_types();

    /**
     * Get the entity type for this list table.
     *
     * @return string
     */
    public function get_entity(): string {
        return $this->data_store->get_object_type();
    }

    /**
     * Outputs the hidden row displayed when inline editing
     */
    public function inline_edit(): void {
        // Noop.
    }

    /**
     * Extra inputs used when displaying the table on subpage of another page
     */
    public function extra_inputs() {
        /**
         * Variable override
         *
         * @var array<string,mixed> $get
         */
        $get          = \xwp_get_arr();
        $input_string = '<input type="hidden" name="%s" value="%s">';

		//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        if ( \str_contains( $this->get_base_url(), 'post_type' ) && '' !== ( $get['post_type'] ?? '' ) ) {
            \printf(
                $input_string,
                'post_type',
                \esc_attr( $get['post_type'] ),
            );
        }

        if ( \str_contains( $this->get_base_url(), 'page' ) && '' !== ( $get['page'] ?? '' ) ) {
            \printf(
                $input_string,
                'page',
                \esc_attr( $get['page'] ),
            );
        }

        \printf(
            $input_string,
            'active',
            \esc_attr( $get['active'] ?? 'all' ),
        );
		//phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Parses the WHERE clauses from request array
     *
     * @return array<string,mixed>
     */
    protected function parse_query_args(): array {
        if ( $this->current_action() ) {
            return array();
        }

        $args = array();

        foreach ( $this->searchable as $p => $get ) {
            $p = \is_integer( $p ) ? $get : $p;

            $args[ $p ] = $this->parse_query_arg( \xwp_fetch_get_var( $get, null ) );
        }

        return \array_filter( $args );
    }

    /**
     * Parses a query argument
     *
     * @param  mixed $v Value to parse.
     * @return mixed
     */
    protected function parse_query_arg( mixed $v ): mixed {
        return ! \is_null( $v ) && ! \in_array( $v, array( 'all', '' ), true )
            ? $v
            : null;
    }

    /**
     * Get the column info
     *
     * @return array
     */
    protected function get_column_info() {
        if ( $this->force_cols ) {
            $this->_column_headers = array(
                $this->get_columns(),
                array(),
                $this->format_sortable_columns( $this->get_sortable_columns() ),
                $this->get_primary_column_name(),
            );
        }

        return parent::get_column_info();
    }

    /**
     * Format the sortable columns
     *
     * @param  array<string,array<int,mixed>> $sortable Sortable columns.
     * @return array<string,array<int,mixed>>
     */
    protected function format_sortable_columns( array $sortable ): array {
        foreach ( $sortable as $id => $data ) {
            if ( ! $data ) {
                continue;
            }

            $data = (array) $data;

            $data[1] ??= false;
            $data[2] ??= '';
            $data[3] ??= false;
            $data[4] ??= false;

            $sortable[ $id ] = $data;
        }

        return \array_filter( $sortable );
    }

    /**
     * Get the views for the list table
     *
     * @return array<string,string>
     */
    protected function get_views() {
        $selected = \xwp_fetch_get_var( $this->view_prop, 'all' );
        $statuses = $this->get_view_types();

        if ( ! isset( $statuses['all'] ) ) {
            $statuses = \array_merge( array( 'all' => \__( 'All', 'default' ) ), $statuses );
        }

        foreach ( $statuses as $status => &$title ) {
            $args  = \array_merge( $this->default_args, array( $this->view_prop => $status ) );
            $count = $this->data_store->count( $args );

            $title = $this->get_view_link( $status, $title, $count, $selected );
        }

        return \array_filter( $statuses );
    }

    /**
     * Get the view link
     *
     * @param  string $status   View status key.
     * @param  string $title    View title.
     * @param  int    $count    Count of items.
     * @param  string $selected Selected view.
     * @return string|null
     */
    protected function get_view_link( string $status, string $title, int $count, string $selected ): ?string {
        if ( 0 >= $count ) {
            return null;
        }

        $qa = 'all' !== $status ? array( $this->view_prop => $status ) : array();

        return \sprintf(
            '<a href="%s" class="%s">
                %s
                <span class="count">(%s)</span>
            </a>',
            \add_query_arg( $qa, $this->get_base_url() ),
            $status === $selected ? 'current' : '',
            $title,
            $count,
        );
    }

    /**
     * Get the inline edit row for the object
     *
     * @param  TObj $obj Data object to display.
     * @return string
     */
    protected function row_inline( XWC_Data $obj ): string {
        return '';
    }
}
