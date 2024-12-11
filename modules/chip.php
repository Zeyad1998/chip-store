<?php

namespace Chip_Store;

use Random\Randomizer;
use Random\Engine\Secure;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Chip
 */
class Chip {

    /**
     * Constant representing the name of the entity.
     *
     * @var string NAME The name of the entity.
     */
    public const NAME = 'chip';

    /**
     * The Encryptor instance.
     *
     * @var Encryptor $encryptor The Encryptor instance.
     */
    private $encryptor;

    /**
     * The icon for the custom post type.
     *
     * @var string ICON The icon for the custom post type.
     */
    public const ICON = 'dashicons-money-alt';
    /**
     * The nonce for the meta boxes.
     *
     * @var string NONCE The nonce for the meta boxes.
     */
    public const NONCE = 'chip_save_meta_box_data';

    /**
     * The nonce action for the meta boxes.
     *
     * @var string NONCE_ACTION The nonce action for the meta boxes.
     */
    public const NONCE_ACTION = 'chip_save_meta_box_data';

    /**
     * Get the description of the chip post type.
     *
     * @return string The description of the chip post type.
     */
    public static function get_description() {
        return __( 'Chips that can be used in the rewards store', 'chip-store' );
    }

    /**
     * Get the custom post type supports.
     *
     * @return array The custom post type supports.
     */
    public static function get_supports() {
        return [ 'title' ];
    }

    /**
     * Get the chip value meta key.
     *
     * @return string The chip value meta key.
     */
    private static function get_value_meta_key() {
        return '_' . self::NAME . '_value';
    }

    /**
     * Get the chip owner meta key.
     *
     * @return string The chip owner meta key.
     */
    private static function get_owner_meta_key() {
        return '_' . self::NAME . '_owner';
    }

    /**
     * Get the chip consumed meta key.
     *
     * @return string The chip consumed meta key.
     */
    private static function get_consumed_meta_key() {
        return '_' . self::NAME . '_consumed';
    }

    /**
     * Get the chip code meta key.
     *
     * @return string The chip code meta key.
     */
    private static function get_code_meta_key() {
        return '_' . self::NAME . '_code';
    }

    /**
     * Get the chip value meta.
     *
     * @param int $chip_id The chip ID.
     * @return float The chip value.
     */
    public static function get_value( $chip_id ) {
        return get_post_meta( $chip_id, self::get_value_meta_key(), true );
    }

    /**
     * Update the chip value meta.
     *
     * @param int $post_id The post ID.
     * @param float $value The new value.
     * @return void
     */
    public static function update_value( $post_id, $value ) {
        update_post_meta( $post_id, self::get_value_meta_key(), $value );
    }

    /**
     * Decrease the chip value.
     *
     * @param int $chip_id The chip ID.
     * @param float $amount The amount to decrease the value by.
     *
     * @return void
     */
    public static function decrease_value( $chip_id, $amount ) {
        $current_value = self::get_value( $chip_id );
        $new_value = $current_value - $amount;
        self::update_value( $chip_id, $new_value );
    }

    /**
     * Is the chip consumed?
     *
     * @param int The chip ID.
     *
     * @return bool Whether the chip is consumed or not.
     */
    public static function is_consumed( $chip_id ) {
        return get_post_meta( $chip_id, self::get_consumed_meta_key(), true );
    }

    /**
     * Update the chip consumed meta.
     *
     * @param int $post_id The post ID.
     * @param bool $consumed The new consumed status.
     * @return void
     */
    public static function consume( $post_id ) {
        update_post_meta( $post_id, self::get_consumed_meta_key(), true );
    }

    /**
     * Update the chip owner meta.
     *
     * @param int $post_id The post ID.
     * @param string $owner The new owner.
     *
     * @return void
     */
    public static function update_owner( $post_id, $new_owner ) {
        $owners = get_post_meta( $post_id, self::get_owner_meta_key(), true );
        if ( ! empty( $owners ) ) {
            $owners .= ', ' . $new_owner;
        } else {
            $owners = $new_owner;
        }
        update_post_meta( $post_id, self::get_owner_meta_key(), $owners );
    }

    /**
     * Renders the chip value meta box.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_value_meta_box( $post ) {
        $value = get_post_meta( $post->ID, self::get_value_meta_key(), true );
        self::insert_nonce();

        ?>
        <label for="chip_value"><?php echo __( 'Value', 'chip-store' ); ?></label>
        <input type="text" id="chip_value" name="chip_value" value="<?php echo esc_attr( $value ); ?>" style="width: 100%;" />
        <?php
    }

    /**
     * Renders the chip owner meta box.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_owner_meta_box( $post ) {
        $owner = get_post_meta( $post->ID, self::get_owner_meta_key(), true );
        self::insert_nonce();

        ?>
        <label for="chip_owner"><?php echo __( 'Owner', 'chip-store' ); ?></label>
        <input type="text" id="chip_owner" name="chip_owner" value="<?php echo esc_attr( $owner ); ?>" style="width: 100%;" />
        <?php
    }

    /**
     * Renders the meta box for chip consumed.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_consumed_meta_box( $post ) {
        $consumed = get_post_meta( $post->ID, self::get_consumed_meta_key(), true );
        self::insert_nonce();

        ?>
        <label for="chip_consumed"><?php echo __( 'Consumed', 'chip-store' ); ?></label>
        <select id="chip_consumed" name="chip_consumed" style="width: 100%;">
            <option value="0" <?php selected( $consumed, '0' ); ?>><?php echo __( 'False', 'chip-store' ); ?></option>
            <option value="1" <?php selected( $consumed, '1' ); ?>><?php echo __( 'True', 'chip-store' ); ?></option>
        </select>
        <?php
    }

    /**
     * Render the chip code meta box.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_code_meta_box( $post ) {
        $encrypted_code = get_post_meta( $post->ID, self::get_code_meta_key(), true );

        $decrypted_code = '';
        if ( ! empty( $encrypted_code ) ) {
            $decrypted_code = Encryptor::getInstance()->decrypt( $encrypted_code );
        }

        ?>
        <div class="inside">
            <p style="font-size: 16px; font-weight: bold; color: #333;"> <?= $decrypted_code ?></p>
        </div>
        <?php
    }

    /**
     * Save the meta box data.
     *
     * @param int $post_id The post ID.
     */
    private static function insert_nonce() {
        wp_nonce_field( self::NONCE, self::NONCE_ACTION );
    }

    /**
     * Save generated unique chip code.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     */
    public static function save_unique_chip_code( $post_id, $post ) {
        // Doing this to avoid pulluting the database. As drafts are not supprted for this CPT and thus cannot be deleted after creation to clean up the database.
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Check if the post type is 'chip'.
        if ( self::NAME !== $post->post_type ) {
            return;
        }

        // Check if the chip code already exists.
        $chip_code = get_post_meta( $post_id, self::get_code_meta_key(), true );
        if ( ! empty( $chip_code ) ) {
            return;
        }

        // Generate a unique chip code using a cryptographically secure randomizer.
        $randomizer = new Randomizer( new Secure() );
        $unique_code = 'CHIP-' . $randomizer->getInt(100000, 999999) . '-' . $randomizer->getInt(100000, 999999);

        // Encrypt the unique chip code before saving it as post meta.
        $encrypted_code = Encryptor::getInstance()->encrypt( $unique_code );

        // Save the encrypted unique chip code as post meta.
        update_post_meta( $post_id, self::get_code_meta_key(), $encrypted_code );
    }

    /**
     * Set custom columns for the chip post type.
     *
     * @param array $columns The existing columns.
     * @return array The modified columns.
     */
    public static function filter_columns( $columns ) {
        $columns[ self::NAME . '_value' ] = __( 'Value', 'chip-store' );
        $columns[ self::NAME . '_owner' ] = __( 'Owner', 'chip-store' );
        $columns[ self::NAME . '_consumed' ] = __( 'Consumed', 'chip-store' );
        $columns[ self::NAME . '_url' ] = __( 'URL', 'chip-store' );
        unset( $columns[ 'date' ] );

        return $columns;
    }

    /**
     * Render custom columns for the chip post type.
     *
     * @param string $column The column name.
     * @param int $post_id The post ID.
     */
    public static function custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'chip_value':
                $value = get_post_meta( $post_id, self::get_value_meta_key(), true );
                echo esc_html( $value );
                break;

            case 'chip_owner':
                $owner = get_post_meta( $post_id, self::get_owner_meta_key(), true );
                $owners = explode( ', ', $owner );
                foreach ( $owners as $single_owner ) {
                    echo esc_html( $single_owner ) . '<br>';
                }
                break;

            case 'chip_consumed':
                $consumed = get_post_meta( $post_id, self::get_consumed_meta_key(), true );
                echo esc_html( $consumed ? __( 'True', 'chip-store' ) : __( 'False', 'chip-store' ) );
                break;

            case 'chip_url':
                $encrypted_code = get_post_meta( $post_id, '_chip_code', true );
                $chip_code = '';
                if ( ! empty( $encrypted_code ) ) {
                    $chip_code = Encryptor::getInstance()->decrypt( $encrypted_code );
                }
                $url = site_url( 'my-account' ) . '?chip_code=' . urlencode( $chip_code );
                echo '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a>';
                break;
        }
    }
}
