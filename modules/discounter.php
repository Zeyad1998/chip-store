<?php

namespace Chip_Store;


use WC_Tax;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Discounter
 *
 * Handles the discount functionality.
 */
class Discounter {

    /**
     * The single instance of the class.
     *
     * @var Discounter
     */
    private static $instance = null;

    /**
     * The discount amount.
     *
     * @var float
     */
    private $discount_amount = 0;

    /**
     * Discounter constructor.
     */
    private function __construct() {
        // Private constructor to prevent direct instantiation.
    }

    /**
     * Gets the single instance of the class.
     *
     * @return Discounter
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Sets the discount amount.
     *
     * @param float $amount The discount amount.
     */
    public function discount( $amount ) {
        $this->discount_amount = $amount;
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'modify_fees' ] );
    }

    /**
     * Modifies the fees in the WooCommerce cart.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    public function modify_fees( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        $cart->add_fee( __( 'Chip Credit', 'chip-store' ), - ( $this->discount_amount /  $this->get_tax_rate() ) );
    }

    /**
     * Gets the tax rate.
     *
     * @return float
     */
    private function get_tax_rate() {
        $tax_rates = WC_Tax::get_rates();
        $tax_rate = 0;

        if ( ! empty( $tax_rates ) ) {
            $tax_rate = array_shift( $tax_rates );
            $tax_rate = $tax_rate['rate'];
        }

        $tax_rate = 1 +( $tax_rate / 100 );

        return $tax_rate;
    }
}
