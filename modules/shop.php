<?php

namespace Chip_Store;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Shop
 *
 * Handles the functionality related to the Shop page.
 */
class Shop {
    
    /**
     * Shop constructor.
     *
     * Initializes the class by hooking into WordPress actions.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_shop_script' ] )
;    }

    /**
     * Enqueues the script for the Shop page.
     *
     * This function checks if the current page is the shop page and enqueues
     * the necessary JavaScript file if it is.
     */
    public function enqueue_shop_script() {
        if ( ! is_shop() ) {
            return;
        }

        wp_enqueue_script(
            'chip-store-shop-script',
            CHIP_STORE_URL . '/assets/js/shop.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'chip-store-shop-script',
            'chipStoreShop',
            [
                'ajax' => [
                    'action'    => Ajax_Handler::CHIP_CODE_ACTION,
                    'url'       => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( Ajax_Handler::NONCE ),
                ],
                'chipCodeKey'   => Ajax_Handler::CHIP_CODE_KEY,
            ]
        );
    }
}
