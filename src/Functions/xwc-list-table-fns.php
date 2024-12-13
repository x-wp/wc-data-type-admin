<?php //phpcs:disable Squiz.Commenting.FunctionComment.MissingParamName, Squiz.Commenting.FunctionComment.MissingParamTag
/**
 * List Table Functions and Utilities
 *
 * @package eXtended WooCommerce
 */

/**
 * Render the action buttons
 *
 * @param  array<array{
 *   action: string,
 *   url: string,
 *   name: string,
 *   title?: string,
 *   icon?: string
 * }|array{
 *   group: string,
 *   actions: array<array{
 *     action: string,
 *     url: string,
 *     name: string,
 *     title?: string,
 *     icon?: string
 *   }>
 * }>  $actions List of actions.
 * @return string
 */
function xwc_render_action_buttons( array $actions ): string {
    $html = '';

    foreach ( $actions as $action ) {
        $html .= isset( $action['group'] )
            ? sprintf(
                <<<'HTML'
                    <div class="xwc-action-button-group">
                        <label>%s<label>
                        <span class="xwc-action-button-group-items">
                            %s
                        </span>
                    </div>
                HTML,
                wp_kses_post( $action['group'] ),
                xwc_render_action_buttons( $action['actions'] ),
            )
            : sprintf(
                <<<'HTML'
                    <a class="button xwc-action-button xwc-action-button-%1$s %1$s" href="%2$s" title="%3$s">
                        %4$s
                    </a>
                HTML,
                sanitize_html_class( $action['action'] ),
                esc_url( $action['url'] ),
                esc_attr( $action['title'] ?? $action['name'] ),
                wp_kses_post( $action['icon'] ?? $action['name'], 'post' ),
            );
    }

    return $html;
}
