<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * Labelable_Enum interface file.
 *
 * @package WooCommerce Sync Service
 */

namespace XWC\Interfaces;

/**
 * Define the labelable enum interface
 */
interface Labelable_Enum extends \BackedEnum {
    /**
     * Get the label of the enum
     *
     * @return string
     */
    public function getLabel(): string;
}
