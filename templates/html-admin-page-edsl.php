<?php
/**
 * Admin View: Page - EDSL
 *
 * @package WooCommerce Utils
 * @version 2.0.0
 *
 * @var string                               $entity Entity name, used for actions and table.
 * @var XWC\Data\Admin\Interfaces\Data_Table $table  Data table instance.
 */

defined( 'ABSPATH' ) || exit;

if ( $table->current_action() ) {
    $table->process_bulk_actions();
}
?>

<div class="wrap">


    <?php
    /**
     * Allows adding actions to the heading of the page.
     *
     * @param XWC\Data\Admin\Interfaces\Data_Table $table Entity table instance.
     * @since 2.0.0
     */
    do_action( "esdl_{$entity}_heading_actions", $table );
    ?>

    <hr class="wp-header-end">

    <?php
    /**
     * Allows adding actions to the heading of the page.
     *
     * @param XWC\Data\Admin\Interfaces\Data_Table $table Entity table instance.
     * @since 2.0.0
     */
    do_action( "esdl_{$entity}_after_heading", $table );
    ?>

    <?php settings_errors(); ?>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <form method="GET">
                        <?php
                        $table->extra_inputs();
                        $table->views();
                        $table->prepare_items();
                        $table->display();
                        ?>
                    </form>
                    <?php $table->inline_edit(); ?>
                    <div id="ajax-response"></div>
                    <div class="clear"></div>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>
