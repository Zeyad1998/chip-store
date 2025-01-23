<?php

namespace Chip_Store;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class My_Account
 *
 * Handles the functionality related to the My Account page.
 */
class My_Account {
    
    /**
     * My_Account constructor.
     *
     * Initializes the class by hooking into WordPress actions.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_my_account_script' ) );
    }

    /**
     * Enqueues the script for the My Account page.
     *
     * This function checks if the current page is the account page and enqueues
     * the necessary JavaScript file if it is.
     */
    public function enqueue_my_account_script() {
        if ( ! is_account_page() ) {
            return;
        }

        wp_enqueue_script(
            'chip-store-my-account-script',
            CHIP_STORE_URL . '/assets/js/my-account.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'chip-store-my-account-script',
            'chipStore',
            [
                'ajax' => [
                    'action'    => Ajax_Handler::CHIP_CODE_ACTION,
                    'url'       => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( Ajax_Handler::NONCE ),
                ],
                'isLoggedIn' => is_user_logged_in(),
                'chipCodeKey'   => Ajax_Handler::CHIP_CODE_KEY,
            ]
        );
    }
}
