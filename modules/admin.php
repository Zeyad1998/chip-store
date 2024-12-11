<?php

namespace Chip_Store;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Admin class.
 *
 * Responsible for initializing everything in the admin area.
 */
class Admin {

    /**
     * Constructor for the Admin class.
     */
    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        $this->register_chip_post_type();
    }

    /**
     * Register the Chip post type.
     */
    private function register_chip_post_type() {
        $meta_boxes = [
            [
                'id' => Chip::NAME . '_value',
                'callback' => [ Chip::class, 'render_value_meta_box' ],
            ],
            [
                'id' => Chip::NAME . '_owner',
                'callback' => [ Chip::class, 'render_owner_meta_box' ],
            ],
            [
                'id' => Chip::NAME . '_consumed',
                'callback' => [ Chip::class, 'render_consumed_meta_box' ],
            ],
            [
                'id' => Chip::NAME . '_code',
                'callback' => [ Chip::class, 'render_code_meta_box' ],
            ]
        ];

        new CPT_Registerer(
            Chip::NAME,
            Chip::get_description(),
            Chip::get_supports(),
            Chip::ICON,
            $meta_boxes,
            Chip::NONCE,
            Chip::NONCE_ACTION,
            [ Chip::class, 'save_unique_chip_code' ], // Save post callback
            [ Chip::class, 'filter_columns' ], // Columns callback
            [ Chip::class, 'custom_columns' ], // Custom columns callback
        );
    }
}
