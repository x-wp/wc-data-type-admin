<?php
/**
 * List_Page_Handler class file.
 *
 * @package    eXtended WooCommerce
 * @subpackage Data\Admin
 */

namespace XWC\Data\Admin;

use Automattic\WooCommerce\Admin\PageController;
use Psr\Container\ContainerInterface;
use XWC\Data\Admin\Interfaces\Data_Table;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;

/**
 * Base class for list page handlers.
 *
 * @template TTbl of Data_Table
 *
 * @property-read string $entity     Entity type for the page.
 * @property-read string $id         Unique identifier for the page.
 * @property-read string $parent     Parent page ID or slug.
 * @property-read string $page_title Page title.
 * @property-read string $menu_title Menu title.
 * @property-read string $menu_slug  Menu slug for the admin menu.
 * @property-read string $capability Capability required to access the page.
 * @property-read bool   $js_page    Whether the page is a JavaScript page.
 *
 * @property-read class-string<TTbl> $table Class name of the data table to use for this page.
 *
 * @property string $hook Hook name for the admin menu.
 */
abstract class List_Page_Handler {
    /**
     * Writable properties for the page handler.
     */
    private const WRITABLE = array( 'hook' );

    /**
     * List of arguments for the page handler.
     *
     * @var array{
     *   id: string,
     *   parent: string,
     *   parent_slug: string,
     *   menu_slug: string,
     *   menu_title: string,
     *   page_title: string,
     *   capability: string,
     *   js_page: bool,
     * }
     */
    protected array $args;

    /**
     * Container instance for dependency injection.
     *
     * @var ?ContainerInterface
     */
    protected ?ContainerInterface $ctr;

    /**
     * Whether the list table is loaded.
     *
     * @var bool
     */
    protected bool $loaded = false;

    /**
     * Constructor for the list page handler.
     *
     * @param  array<string,mixed> $args Arguments for the page handler.
     * @param  ?ContainerInterface $ctr Container instance for dependency injection.
     */
    public function __construct(
        array $args,
        ?ContainerInterface $ctr = null,
    ) {
        $this->args = $this->parse_args( $args );
        $this->ctr  = $ctr;
    }

    /**
     * Magic method to access arguments.
     *
     * @param  string $name Name of the argument to access.
     * @return mixed
     */
    public function __get( string $name ): mixed {
        return $this->args[ $name ] ?? null;
    }

    /**
     * Magic method to set arguments.
     *
     * @param  string $name  Name of the argument to set.
     * @param  mixed  $value Value to set.
     * @return void
     *
     * @throws \InvalidArgumentException If the property is read-only.
     */
    public function __set( string $name, mixed $value ): void {
        if ( ! \in_array( $name, self::WRITABLE, true ) ) {
            throw new \InvalidArgumentException( \esc_html( "Cannot set read-only property: {$name}" ) );
        }

        $this->args[ $name ] = $value;
    }

    /**
     * Loads the list table class if not already loaded.
     */
    public function on_initialize(): void {
        if ( ! \class_exists( \WP_List_Table::class ) ) {
            require ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }

        $this->loaded = true;
    }



    /**
     * Registers the admin menu for the list page.
     */
    #[Action( tag: 'admin_menu', priority: 11, context: Action::CTX_ADMIN )]
    public function register_menu(): void {
        $this->hook = \add_submenu_page(
            parent_slug: $this->pc()->get_path_from_id( $this->parent ),
            menu_slug: $this->menu_slug,
            page_title: $this->page_title,
            menu_title: $this->menu_title,
            capability: $this->capability,
            callback: function () {
                \xwp_get_template(
                    \dirname( __DIR__ ) . '/templates/html-admin-page-edsl.php',
                    array(
                        'entity' => $this->entity,
                        'table'  => $this->lt(),
                    ),
                );
            },
        );

        \add_action( "load-{$this->hook}", array( $this, 'set_screen_options' ) );
    }

    /**
     * Connects the page to the admin menu.
     */
    #[Action( tag: 'admin_menu', priority: 21, context: Action::CTX_ADMIN )]
    public function connect_page(): void {
        \wc_admin_connect_page(
            array(
                'capability' => $this->capability,
                'id'         => $this->id,
                'js_page'    => false,
                'parent'     => $this->parent,
                'path'       => \add_query_arg(
                    'page',
                    $this->menu_slug,
                    $this->pc()->get_path_from_id( $this->parent ),
                ),
                'screen_id'  => $this->hook,
                'title'      => $this->page_title,
            ),
        );
    }

    /**
     * Adds the screen ID for the page to the list of WooCommerce screen IDs.
     *
     * @param  array<string> $screen_ids List of WooCommerce screen IDs.
     * @return array<string>
     */
    #[Filter( tag: 'woocommerce_screen_ids', priority: 10, context: Filter::CTX_ADMIN )]
    public function add_screen_id( array $screen_ids ): array {
        $screen_ids[] = $this->hook;

        return $screen_ids;
    }

    /**
     * Undocumented function
     *
     * @param  mixed  $screen_option The value to save.
     * @param  string $option        The option name.
     * @param  mixed  $value         The option value.
     * @return mixed                 The value to save.
     */
    #[Filter( tag: 'set-screen-option', priority: 10, context: Filter::CTX_ADMIN )]
    public function save_screen_options( $screen_option, $option, $value ): mixed {
        foreach ( $this->get_screen_options() as $args ) {
            if ( $args['option'] !== $option ) {
                continue;
            }

            $screen_option = $value;
        }

        return $screen_option;
    }

    /**
     * Set the screen options for the page.
     */
    public function set_screen_options(): void {
        foreach ( $this->get_screen_options() as $option => $args ) {
            \add_screen_option( $option, $args );
        }

        if ( ! $this->should_enqueue_inline_edit() ) {
            return;
        }

        \wp_enqueue_script( 'inline-edit-post' );
    }

    /**
     * Gets the PageController instance.
     *
     * @return PageController
     */
    protected function pc(): PageController {
        /**
         * Var override
         *
         * @var PageController $pc
         */
        $pc = PageController::get_instance();

        return $pc;
    }

    /**
     * Retrieves the data table instance for this page.
     *
     * @return TTbl
     */
    protected function lt(): Data_Table {
        if ( ! $this->ctr ) {
            throw new \LogicException(
                \sprintf(
                    'List page handler "%s" requires a container to resolve table "%s".',
                    static::class,
                    $this->table,
                ),
            );
        }

        $table = $this->ctr->get( $this->table );

        if ( ! $table instanceof Data_Table ) {
            throw new \UnexpectedValueException(
                \sprintf(
                    'Resolved table "%s" for handler "%s" must implement %s.',
                    $this->table,
                    static::class,
                    Data_Table::class,
                ),
            );
        }

        return $table;
    }

    /**
     * Determines whether inline edit assets should be loaded for this page.
     *
     * Child classes can override this when inline editing is supported.
     */
    protected function should_enqueue_inline_edit(): bool {
        return false;
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

    /**
     * Parses the arguments for the page handler.
     *
     * @param  array<string,mixed> $args Arguments to parse.
     * @return array<string,mixed>
     */
    private function parse_args( array $args ): array {
        return \xwp_parse_args(
            $args,
            array(
                'capability' => 'manage_woocommerce',
                'menu_slug'  => $args['id'],
                'page_title' => $args['menu_title'],
                'parent'     => 'woocommerce',
            ),
        );
    }
}
