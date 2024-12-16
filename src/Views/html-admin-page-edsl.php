<?php
/**
 * Admin View: Page - EDSL
 *
 * @package WooCommerce Utils
 * @version 2.0.0
 *
 * @var XWC_Data_List_Page<XWC_Data_List_Table> $this The current instance of the page.
 */

defined( 'ABSPATH' ) || exit;

$this->get_table()->current_action() && $this->get_table()->process_bulk_actions();

$entity = $this->get_entity();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php
    /**
     * Allows adding actions to the heading of the page.
     *
     * @param XWC_Data_List_Page<XWC_Data_List_Table> $table The current instance of the page.
     * @since 2.0.0
     */
    do_action( "esdl_{$entity}_heading_actions", $this );
    ?>
    <hr class="wp-header-end">

    <?php
    /**
     * Allows adding actions to the heading of the page.
     *
     * @param XWC_Data_List_Page<XWC_Data_List_Table> $table The current instance of the page.
     * @since 2.0.0
     */
    do_action( "esdl_{$entity}_after_heading", $this );
    ?>

    <?php settings_errors(); ?>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="GET">
                        <?php
                        $this->get_table()->extra_inputs();
                        $this->get_table()->views();
                        $this->get_table()->prepare_items();
                        $this->get_table()->display();
                        ?>
                    </form>
                    <?php
                    if ( $this->inline_edit ) {
                        $this->get_table()->inline_edit();
                    }
                    ?>
                    <div id="ajax-response"></div>
                    <div class="clear"></div>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
