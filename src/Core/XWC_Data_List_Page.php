<?php // phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys,
/**
 * XWC_Data_List_Page class file.
 *
 * @package eXtended WooCommerce
 */

use Automattic\WooCommerce\Admin\PageController;
use DI\Container;
use XWC\Data\Admin\Interfaces\Data_Table;
use XWC\Data\Admin\List_Table;

/**
 * Base data store list page class.
 *
 * @template T of List_Table
 */
abstract class XWC_Data_List_Page {
    /**
     * Page namespace
     *
     * @var string
     */
    protected string $namespace = '';

    /**
     * Page base
     *
     * This is the first part of the URL, after the admin URL.
     *
     * @var string
     */
    protected string $base = '';

    /**
     * Page ID
     *
     * @var string
     */
    protected string $id = '';

    /**
     * Page title
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Page menu title
     *
     * @var string
     */
    protected string $menu_title;

    /**
     * Page capability
     *
     * @var string
     */
    protected $capability;

    /**
     * Page hook
     *
     * @var string
     */
    protected string $hook;

    /**
     * EDS entity
     *
     * @var string
     */
    protected string $entity;

    /**
     * List table object
     *
     * @var T
     */
    protected Data_Table $table;

    /**
     * Table class name
     *
     * @var class-string<T>
     */
    protected string $table_class;

    /**
     * Table arguments
     *
     * @var array
     */
    protected array $table_args;

    /**
     * Whether to enable inline editing
     *
     * @var bool
     */
    public bool $inline_edit;

    /**
     * XWP DI Container ID.
     *
     * @var string|null
     */
    protected ?string $container;

    /**
     * Connect to the WC Admin Pages.
     *
     * @var bool|array{
     *   id?: string,
     *   parent?: string,
     *   screen_id?: string,
     *   title?: string,
     *   path?: string,
     *   capability?: string,
     *   icon?: string,
     *   js_page?: bool,
     * }
     */
    protected bool|array $connect;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->init_page_args( $this->get_page_args() );
        $this->init_table_args( $this->get_table_args() );
        $this->init_page_hooks();
    }

    /**
     * Returns the page arguments
     *
     * @return array
     */
    abstract protected function get_page_args(): array;

    /**
     * Returns the table arguments
     *
     * @return array{
     *   entity: string,
     *   class: class-string<T>,
     *   view_prop: string|null,
     *   args: array{
     *     force_cols?: bool,
     *     singular?: string,
     *     plural?: string,
     *     ajax?: bool,
     *     screen?: string,
     *   },
     *   query_args?: array<string,mixed>
     * }
     */
    abstract protected function get_table_args(): array;

    /**
     * Add page class to body
     *
     * @param  string $classes Body classes.
     * @return string
     */
    public function add_page_class( $classes ) {
        global $pagenow;

        $page = xwp_fetch_get_var( 'page', '' );

        if ( $this->connect || ! \str_contains( $this->base, $pagenow ) || $page !== $this->id ) {
            return $classes;
        }

        return \sprintf( ' %s %s-%s ', $classes, $this->namespace, $this->id );
    }

    /**
     * Adds the menu page to the admin menu
     */
    public function add_menu_page() {
        $this->hook = \add_submenu_page(
            $this->base,
            $this->title,
            $this->menu_title,
            $this->capability,
            "{$this->namespace}-{$this->id}",
            array( $this, 'output' ),
        );

        \add_action( "load-{$this->hook}", array( $this, 'set_screen_options' ) );
    }

    /**
     * Connects the page to the WC Admin Pages
     */
    public function maybe_connect_page(): void {
        if ( ! $this->connect ) {
            return;
        }

        wc_admin_connect_page(
            wp_parse_args(
                is_array( $this->connect ) ? $this->connect : array(),
                array(
                    'id'        => "{$this->namespace}-{$this->id}",
                    'parent'    => $this->base,
                    'screen_id' => $this->hook,
                    'title'     => $this->title,
                    'js_page'   => false,
                ),
            ),
        );
    }

    public function add_screen_id( array $screen_ids ): array {
        if ( $this->connect ) {
            $screen_ids[] = $this->hook;
        }

        return $screen_ids;
    }

    /**
     * Sets the screen options
     */
    public function set_screen_options() {
        $options = $this->get_screen_options();

        foreach ( $options as $option => $args ) {
            \add_screen_option( $option, $args );
        }

        // Include the list table class.
        ! \class_exists( 'WP_List_Table' ) &&
        require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

        $this->table = $this->load_table();

        if ( ! $this->inline_edit ) {
            return;
        }

        \wp_enqueue_script( 'inline-edit-post' );
    }

    /**
     * Undocumented function
     *
     * @param  mixed  $screen_option The value to save.
     * @param  string $option        The option name.
     * @param  mixed  $value         The option value.
     * @return mixed                 The value to save.
     */
    public function save_screen_options( $screen_option, $option, $value ) {
        foreach ( $this->get_screen_options() as $args ) {
            if ( $args['option'] !== $option ) {
                continue;
            }

            $screen_option = $value;
        }

        return $screen_option;
    }

    /**
     * Outputs the page content
     */
    public function output() {
        $entity = $this->get_entity();
        $table  = $this->get_table();

        require dirname( __DIR__, 2 ) . '/templates/html-admin-page-edsl.php';
    }

    /**
     * Returns the list table object
     *
     * @return T
     */
    public function get_table(): List_Table {
        return $this->table;
    }

    /**
     * Returns the entity
     *
     * @return string
     */
    public function get_entity(): string {
        return $this->entity;
    }

    /**
     * Parses the page arguments
     *
     * @param array $args Page arguments.
     */
    protected function parse_page_args( array $args ): array {
        $defs = array(
            'namespace'  => null,
            'base'       => 'woocommerce',
            'id'         => null,
            'title'      => null,
            'menu_title' => $args['title'] ?? null,
            'capability' => 'manage_woocommerce',
            'entity'     => null,
            'container'  => '',
            'connect'    => true,
        );

        return wp_parse_args( $args, $defs );
    }

    /**
     * Checks the page arguments
     *
     * @param array $args Page arguments.
     *
     * @throws \InvalidArgumentException If the required arguments are missing.
     */
    protected function init_page_args( array $args ) {
        $args = $this->parse_page_args( $args );

        $required = array( 'title', 'entity', 'namespace', 'id' );

        foreach ( $required as $key ) {
            if ( ! is_null( $args[ $key ] ) ) {
                continue;
            }

            throw new \InvalidArgumentException( 'Missing required argument ' . esc_html( $key ) );
        }

        foreach ( $args as $var => $val ) {
            $this->$var = $val;
        }
    }

    /**
     * Initializes the list table object
     *
     * @param  array $args List Table arguments.
     * @return array
     *
     * @throws \InvalidArgumentException If the table class is invalid.
     */
    protected function parse_table_args( array $args ): array {
        $cname = $args['class'] ?? false;

        if ( ! $cname || ! class_exists( $cname ) || ! is_a( $cname, Data_Table::class, true ) ) {
            throw new \InvalidArgumentException( 'Invalid table class' );
        }

        $args['table_args'] = $args['args'] ?? $args['table_args'] ?? array();
        unset( $args['args'] );

        $args = wp_parse_args(
            xwp_array_diff_assoc( $args, 'class' ),
            array(
                'entity'     => $this->entity,
                'view_prop'  => null,
                'table_args' => array(
                    'ajax'     => false,
                    'plural'   => \str_replace( '-', '_', $this->entity ) . 's',
                    'singular' => \str_replace( '-', '_', $this->entity ),
                ),
            ),
        );

        return compact( 'cname', 'args' );
    }

    /**
     * Returns the DI container
     *
     * @return ?Container
     */
    protected function get_container(): ?Container {
        return $this->container
            ? xwp_app( $this->container )
            : null;
    }

    /**
     * Initializes the list table object
     *
     * @param array $args List Table arguments.
     */
    protected function init_table_args( array $args ) {
        $args = $this->parse_table_args( $args );

        $this->table_class = $args['cname'];
        $this->table_args  = $args['args'];
        $this->inline_edit = $this->table_args['table_args']['ajax'];
    }

    /**
     * Loads the list table object
     *
     * @return T
     */
    protected function load_table(): List_Table {
        return $this->get_container()?->make( $this->table_class, $this->table_args )
            ??
            new ( $this->table_class )( ...$this->table_args );
    }

    /**
     * Loads hooks needed for the page to function
     */
    protected function init_page_hooks() {
        \add_filter( 'admin_body_class', array( $this, 'add_page_class' ) );
        \add_action( 'admin_menu', array( $this, 'add_menu_page' ), 9 );
        \add_action( 'admin_menu', array( $this, 'maybe_connect_page' ), 11 );
        add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ), 100 );
        \add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 50, 3 );
    }

    /**
     * Returns the screen options for the page
     *
     * Needs to be implemented by the child class
     *
     * @return array
     */
    protected function get_screen_options() {
        return array(
            'per_page' => array(
                'default' => 20,
                'option'  => \str_replace( '-', '_', "edit_{$this->entity}s_per_page" ),
            ),
        );
    }
}
