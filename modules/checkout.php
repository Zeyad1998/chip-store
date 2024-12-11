<?php

namespace Chip_Store;

use Chip_Store\Discounter;
use Chip_Store\Shop;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Checkout
 *
 * Handles the functionality related to the Checkout page.
 */
class Checkout {

    /**
     * Checkout constructor.
     *
     * Initializes the class by hooking into WordPress actions.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout_script' ] );

        // Discount the chip credit and update it on order
        if ( is_user_logged_in() ) {
            $this->handle_logged_in_user();
        } else {
            $this->handle_guest();
        }
    }

    /**
     * Enqueues the script for the Checkout page.
     *
     * This function checks if the current page is the checkout page and enqueues
     * the necessary JavaScript file if it is.
     */
    public function enqueue_checkout_script() {
        if ( ! is_checkout() ) {
            return;
        }

        wp_enqueue_script(
            'chip-store-checkout-script',
            CHIP_STORE_URL . '/assets/js/checkout.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'chip-store-checkout-script',
            'chipStoreCheckout',
            [
                'ajax'          => [
                    'action' => [
                        'code'       => Ajax_Handler::CHIP_CODE_ACTION,
                        'amount'     => Ajax_Handler::CHIP_AMOUNT_ACTION,
                    ],
                    'url'                   => admin_url( 'admin-ajax.php' ),
                    'nonce'                 => wp_create_nonce( Ajax_Handler::NONCE ),
                    'keys'                  => [
                        'guestChipId'       => Ajax_Handler::GUEST_CHIP_ID_KEY,
                        'code'              => Ajax_Handler::CHIP_CODE_KEY,
                        'amount'            => Ajax_Handler::CHIP_AMOUNT_KEY,
                    ],
                ],
                'guestChip'     => [
                    'id'                    => isset( $_SESSION[ Ajax_Handler::GUEST_CHIP_ID_KEY ] ) ? $_SESSION[ Ajax_Handler::GUEST_CHIP_ID_KEY ] : '',
                ],
                'woocommerce'   => [
                    'subtotal'                 => WC()->cart->get_subtotal(),
                ],
                'isLoggedIn'    => is_user_logged_in(),
                'text'          => [
                    'code'   => [
                        'nudge'             => __( 'Have a chip?', 'chip-store' ),
                        'expand'            => __( 'Click here to enter your chip code', 'chip-store' ),
                        'label'             => __( 'If you have a chip code, please apply it below.', 'chip-store' ),
                        'field'             => __( 'Chip Serial Code', 'chip-store' ),
                    ],
                    'amount' => [
                        'nudge'              => __( 'Change chip amount?', 'chip-store' ),
                        'expand'             => __( 'Click here to change the chip amount', 'chip-store' ),
                        'label'              => __( 'Please enter a number.', 'chip-store' ),
                        'field'              => __( 'Chip Amount', 'chip-store' ),
                    ],
                ],
            ]
        );
    }

    /**
     * Handles the chip credit for the logged in user.
     */
    private function handle_logged_in_user() {
        $user_credit = $this->get_current_user_chip_credit();
        if ( empty( $user_credit ) ) {
            return;
        }
        Discounter::get_instance()->discount( $user_credit );
        add_action( 'woocommerce_thankyou', [ $this, 'update_user_chip_credit' ] );
    }

    /**
     * Handles the chip credit for the guest user.
     */
    private function handle_guest() {
        if ( ! isset( $_SESSION ) || ! isset( $_SESSION[ Ajax_Handler::GUEST_CHIP_AMOUNT_KEY ] ) ) {
            return;
        }
        Discounter::get_instance()->discount( $_SESSION[ Ajax_Handler::GUEST_CHIP_AMOUNT_KEY ] );
        add_action( 'woocommerce_thankyou', [ $this, 'update_guest_chip' ] );
    }

    /**
     * Hooks into WooCommerce order completion to update chip credit.
     */
    public function update_user_chip_credit( $order_id ) {
        $order = wc_get_order( $order_id );
        $subtotal = $order->get_subtotal();
        $current_credit = $this->get_current_user_chip_credit();
        $new_credit = 0;
        if ( $subtotal < $current_credit ) {
            $new_credit = $current_credit - $subtotal;
        }

        update_user_meta(
            $order->get_user_id(),
            Ajax_Handler::CHIP_CREDIT_META_KEY,
            $new_credit
        );
    }

    public function update_guest_chip( $order_id ) {
        $chip_id = $_SESSION[ Ajax_Handler::GUEST_CHIP_ID_KEY ];
        $chip_amount = $_SESSION[ Ajax_Handler::GUEST_CHIP_AMOUNT_KEY ];
        $order = wc_get_order( $order_id );
        $subtotal = $order->get_subtotal();

        if ( $chip_amount > $subtotal ) {
            $chip_amount = $subtotal;
        }

        CHIP::decrease_value( $chip_id, $chip_amount );
        if ( 0 >= (float) CHIP::get_value( $chip_id ) ) {
            CHIP::consume( $chip_id );
        }
        CHIP::update_owner( $chip_id, 'Guest' );

        $_SESSION[ Ajax_Handler::GUEST_CHIP_ID_KEY ] = '';
        $_SESSION[ Ajax_Handler::GUEST_CHIP_CREDIT_KEY ] = 0;
        $_SESSION[ Ajax_Handler::GUEST_CHIP_AMOUNT_KEY ] = 0;
    }

    /**
     * Gets the current user's chip credit.
     *
     * @return float The current user's chip credit.
     */
    private function get_current_user_chip_credit() {
        return get_user_meta( get_current_user_id(), Ajax_Handler::CHIP_CREDIT_META_KEY, true );
    }
}
?>