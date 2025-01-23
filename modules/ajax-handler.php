<?php

namespace Chip_Store;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Ajax_Handler
 *
 * Handles the functionality related to AJAX requests.
 */
class Ajax_Handler {

    /**
     * The nonce value used for AJAX requests.
     *
     * @var string
     */
    public const NONCE = 'chip_store_ajax_nonce';

    /**
     * The meta key used to store the credit value for a chip.
     *
     * @var string
     */
    public const CHIP_CREDIT_META_KEY = '_chip_store_credit';

    /**
     * The key used to store the credit value for a chip. In the AJAX request.
     *
     * @var string
     */
    public const GUEST_CHIP_CREDIT_KEY = 'guest_chip_credit';

    /**
     * The key used to store the chip amount. In the Session Storage.
     *
     * @var string
     */
    public const GUEST_CHIP_AMOUNT_KEY = 'guest_chip_amount';

    /**
     * The key used to store the chip code. In the AJAX request and Session Storage.
     *
     * @var string
     */
    public const CHIP_CODE_KEY = 'chip_code';

    /**
     * The key used to store the chip ID. In the Session Storage.
     *
     * @var string
     */
    public const GUEST_CHIP_ID_KEY = 'guest_chip_id';

    /**
     * The key used to store the chip amount. In the AJAX request.
     *
     * @var string
     */
    public const CHIP_AMOUNT_KEY = 'chip_amount';

    /**
     * The action used to send the chip code.
     *
     * @var string
     */
    public const CHIP_CODE_ACTION = 'send_chip_code';

    /**
     * The action used to store the chip amount.
     *
     * @var string
     */
    public const CHIP_AMOUNT_ACTION = 'send_chip_amount';

    /**
     * Ajax_Handler constructor.
     */
    public function __construct() {;
        // Handle Chip Code AJAX request
        add_action( 'wp_ajax_send_chip_code', [ $this, 'chip_code_ajax' ] );
        add_action( 'wp_ajax_nopriv_send_chip_code', [ $this, 'chip_code_ajax' ] );
        // Handle Chip Amount AJAX request
        add_action( 'wp_ajax_nopriv_send_chip_amount', [ $this, 'handle_chip_amount' ] );

    }

    /**
     * Handles the AJAX request to send the chip code.
     */
    public function chip_code_ajax() {
        check_ajax_referer( self::NONCE );

        if ( empty( $_POST[ self::CHIP_CODE_KEY ] ) ) {
            wp_send_json_error( __( 'No chip code provided.', 'chip-store' ), 400 );
            return;
        }

        $chip_code = sanitize_text_field( $_POST[ self::CHIP_CODE_KEY ] );
        $encrypted_code = Encryptor::getInstance()->encrypt( $chip_code );

        $args = [
            'post_type'  => 'chip',
            'meta_query' => [
                [
                    'key'   => '_chip_code',
                    'value' => $encrypted_code,
                ],
            ],
        ];
        $query = new WP_Query( $args );
        
        if ( ! $query->have_posts() ) {
            wp_send_json_error( __( 'Invalid chip code.', 'chip-store' ), 400 );
            return;
        }
        
        if ( 1 < $query->found_posts ) {
            wp_send_json_error( __( 'Multiple matching chip codes found.', 'chip-store' ), 400 );
            return;
        }

        $chip = $query->posts[ 0 ];
        if ( Chip::is_consumed( $chip->ID ) ) {
            wp_send_json_error( __( 'This Chip Has Been Consumed.', 'chip-store' ) );
            return;
        }

        $chip_value = Chip::get_value( $chip->ID );
        if ( is_user_logged_in() ) {
            self::update_user_credit( $chip_value );
            self::update_chip( $chip->ID );
        } else {
            $_SESSION[ self::GUEST_CHIP_ID_KEY ] = $chip->ID;
            $_SESSION[ self::GUEST_CHIP_CREDIT_KEY ] = $chip_value;
            $_SESSION[ self::GUEST_CHIP_AMOUNT_KEY ] = $chip_value;
        }

        wp_send_json_success( __( 'Chip code is valid.', 'chip-store' ) );
    }

        /**
     * Handles the AJAX request to consume a certain chip amount.
     */
    public function handle_chip_amount() {
        check_ajax_referer( self::NONCE );

        if ( ! isset( $_POST[ Ajax_Handler::GUEST_CHIP_ID_KEY ] ) ) {
            $chip = $this->get_chip_by_code();
        } else {
            $chip_id = sanitize_text_field( $_POST[ Ajax_Handler::GUEST_CHIP_ID_KEY ] );
            $chip = get_post( $chip_id );
        }

        if ( Chip::is_consumed( $chip->ID ) ) {
            wp_send_json_error( __( 'This Chip Has Been Consumed.', 'chip-store' ), 400 );
        }

        if ( ! isset( $_POST[ Ajax_Handler::CHIP_AMOUNT_KEY ] ) ) {
            wp_send_json_error( __( 'No Chip Amount Provided.', 'chip-store' ), 400 );
        }

        $chip_amount = sanitize_text_field( $_POST[ Ajax_Handler::CHIP_AMOUNT_KEY ] );
        if ( ! is_numeric( $chip_amount ) || floatval( $chip_amount ) <= 0 ) {
            wp_send_json_error( __( 'Invalid Chip Amount.', 'chip-store' ), 400 );
        }

        $chip_amount = floatval( $chip_amount );
        $chip_value = Chip::get_value( $chip->ID );
        if ( $chip_amount > $chip_value ) {
            wp_send_json_error( __( 'Chip Amount Exceeds Maximum: ' . $chip_value, 'chip-store' ) );
        }

        $_SESSION[ self::GUEST_CHIP_AMOUNT_KEY ] = $chip_amount;

        wp_send_json_success( __( 'Chip amount successfully consumed.', 'chip-store' ) );
    }

    /**
     * Retrieves the chip by its code.
     *
     * @return \WP_Post The chip post.
     */
    private function get_chip_by_code() {
        if ( ! isset( $_POST[ Ajax_Handler::CHIP_CODE_KEY ] ) ) {
            wp_send_json_error( __( 'No chip code provided.', 'chip-store' ), 400 );
        }

        $chip_code = sanitize_text_field( $_POST[ Ajax_Handler::CHIP_CODE_KEY ] );
        $encrypted_code = Encryptor::getInstance()->encrypt( $chip_code );

        $args = [
            'post_type'  => 'chip',
            'meta_query' => [
                [
                    'key'   => '_chip_code',
                    'value' => $encrypted_code,
                ],
            ],
        ];
        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            wp_send_json_error( __( 'Invalid chip code.', 'chip-store' ), 400 );
        }

        if ( 1 < $query->found_posts ) {
            wp_send_json_error( __( 'Multiple matching chip codes found.', 'chip-store' ), 400 );
        }

        return $query->posts[0];
    }

    /**
     * Updates the user's credit after a chip has been used.
     *
     * @param float $chip_value The value of the chip that was used.
     */
    private static function update_user_credit( $chip_value ) {
        $user_id = get_current_user_id();
        $current_credit = get_user_meta( $user_id, self::CHIP_CREDIT_META_KEY, true );

        if ( empty( $current_credit ) ) {
            $current_credit = 0;
        }

        $new_credit = $current_credit + $chip_value;

        update_user_meta( $user_id, self::CHIP_CREDIT_META_KEY, $new_credit );
    }

    /**
     * Updates the chip after it has been used.
     *
     * @param int $chip_id The ID of the chip to update.
     */
    private static function update_chip( $chip_id ) {
        Chip::consume( $chip_id );
        Chip::update_owner( $chip_id, wp_get_current_user()->user_email );
        Chip::update_value( $chip_id, 0 );
    }
}
