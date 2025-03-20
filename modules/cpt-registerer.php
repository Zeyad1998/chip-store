<?php

namespace Chip_Store;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class CPT_Registerer
 *
 * This class is responsible for registering custom post types (CPT) in WordPress.
 */
class CPT_Registerer {
    
    /**
     * The singular name for the custom post type.
     *
     * @var string
     */
    private $name;

    /**
     * An array of meta boxes to be added to the custom post type.
     *
     * @var array
     */
    private $meta_boxes;

    /**
     * The nonce for the meta boxes.
     *
     * @var string
     */
    private $nonce;

    /**
     * The nonce action for the meta boxes.
     *
     * @var string
     */
    private $nonce_action;

    /**
     * Constructor for the CPT_Registerer class.
     *
     * Initializes the custom post type with the given parameters and registers it with WordPress.
     *
     * @param string $name The singular name of the custom post type.
     * @param string $description A brief description of the custom post type.
     * @param array $supports An array of features the custom post type supports. Default is ['title', 'editor', 'thumbnail'].
     * @param string $icon The icon to be displayed in the WordPress admin menu.
     * @param array $meta_boxes An array of meta boxes to be added to the custom post type.
     * @param string $nonce The nonce for the meta boxes.
     * @param string $nonce_action The nonce action for the meta boxes.
     * @param string $save_callback The callback function to save the post.
     * @param string $delete_callback The callback function to delete the post.
     * @param string $filter_columns_callback The callback function to filter the columns.
     * @param string $custom_columns_callback The callback function to add custom columns.
     */
    public function __construct(
        $name,
        $description,
        $supports,
        $icon,
        $meta_boxes = [],
        $nonce = '',
        $nonce_action = '',
        $save_callback = '',
        $delete_callback = '',
        $filter_columns_callback = '',
        $custom_columns_callback = ''
    ) {
        $this->name = $name;
        $label = ucfirst( $name );
        $plural_label = $label . 's';

        $labels = [
            'name'               => $plural_label,
            'singular_name'      => $label,
            'add_new_item'       => 'Add New ' . $label,
            'new_item'           => 'New ' . $label,
            'edit_item'          => 'Edit ' . $label,
            'view_item'          => 'View ' . $label,
            'all_items'          => 'All ' . $plural_label,
            'search_items'       => 'Search ' . $plural_label,
            'not_found'          => 'No ' . strtolower( $plural_label ) . ' found.',
            'not_found_in_trash' => 'No ' . strtolower( $plural_label ) . ' found in Trash.',
        ];

        $args = [
            'labels'             => $labels,
            'description'        => $description,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'post',
            'menu_icon'          => $icon,
            'supports'           => $supports,
        ];

        register_post_type( $this->name, $args );

        if ( ! empty( $meta_boxes ) ) {
            $this->add_meta_boxes( $meta_boxes, $nonce, $nonce_action );
        }

        if ( ! empty( $save_callback ) ) {
            add_action( 'save_post_' . $this->name, $save_callback, 10, 2 );
        }

        if ( ! empty( $delete_callback ) ) {
            add_action( 'before_delete_post', $delete_callback );
        }

        if ( ! empty( $filter_columns_callback ) && ! empty( $custom_columns_callback ) ) {
            add_filter( 'manage_' . $this->name . '_posts_columns', $filter_columns_callback );
            add_action( 'manage_' . $this->name . '_posts_custom_column', $custom_columns_callback, 10, 2 );
        }
    }

    /**
     * Adds multiple meta boxes to the custom post type.
     *
     * @param array $meta_boxes An array of meta boxes, each containing a 'key' and 'callback'.
     */
    public function add_meta_boxes( $meta_boxes, $nonce, $nonce_action ) {
        $this->meta_boxes = $meta_boxes;
        $this->nonce = $nonce;
        $this->nonce_action = $nonce_action;

        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta_boxes' ] );
    }

    public function register_meta_boxes() {
        foreach ( $this->meta_boxes as $meta_box ) {
            $title = ucwords( str_replace( '_', ' ', $meta_box[ 'id' ] ) );
            $title = preg_replace_callback( '/\b\w{1,2}\b/', function( $matches ) {
                return strtoupper( $matches[ 0 ] );
            }, $title );

            add_meta_box(
                $meta_box[ 'id' ] . '_meta_box', // ID
                $title, // Meta box title
                $meta_box[ 'callback' ], // Callback function to render the meta box
                $this->name, // Post type
                'normal', // 'Context' (normal, advanced, side)
                'default' // 'Priority' (high, core, default, low)
            );
        }
    }


    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST[ $this->nonce_action ] ) || ! wp_verify_nonce( $_POST[ $this->nonce_action ], $this->nonce ) ) {
            return;
        }

        foreach ( $this->meta_boxes as $meta_box ) {
            if ( array_key_exists( $meta_box[ 'id' ], $_POST ) ) {
                $sanitized_value = sanitize_text_field( $_POST[ $meta_box[ 'id' ] ] );
                if ( is_numeric( $sanitized_value ) ) {
                    $sanitized_value = (float) $sanitized_value;
                }
                update_post_meta(
                    $post_id,
                    '_' . $meta_box[ 'id' ],
                    $sanitized_value
                );
            }
        }
    }
}