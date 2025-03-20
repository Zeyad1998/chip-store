<?php

namespace Chip_Store;

use Random\Randomizer;
use Random\Engine\Secure;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

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
     * Get the meta key for the given key.
     */
    private static function get_meta_key( $key ) {
        return '_' . self::NAME . '_' . $key;
    }

    /**
     * Get the chip value meta.
     *
     * @param int $chip_id The chip ID.
     * @return float The chip value.
     */
    public static function get_value( $chip_id ) {
        return get_post_meta( $chip_id, self::get_meta_key( 'value' ), true );
    }

    /**
     * Update the chip value meta.
     *
     * @param int $post_id The post ID.
     * @param float $value The new value.
     * @return void
     */
    public static function update_value( $post_id, $value ) {
        update_post_meta( $post_id, self::get_meta_key( 'value' ), $value );
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
        return get_post_meta( $chip_id, self::get_meta_key( 'consumed' ), true );
    }

    /**
     * Is the chip expired?
     * 
     * @param int $chip_id The chip ID.
     * 
     * @return bool Whether the chip is expired or not.
     */
    public static function is_expired( $chip_id ) {
        $expiration_date = get_post_meta( $chip_id, '_chip_expiration_date', true );
        return strtotime( $expiration_date ) < time();
    }

    /**
     * Update the chip consumed meta.
     *
     * @param int $post_id The post ID.
     * @param bool $consumed The new consumed status.
     * @return void
     */
    public static function consume( $post_id ) {
        update_post_meta( $post_id, self::get_meta_key( 'consumed' ), true );
    }

    /**
     * Update the chip owners meta.
     *
     * @param int $post_id The post ID.
     * @param string $owners The new owners.
     *
     * @return void
     */
    public static function update_owners( $post_id, $new_owner ) {
        $meta_key = self::get_meta_key( 'owners' );

        $owners = get_post_meta( $post_id, $meta_key, true );
        if ( ! empty( $owners ) ) {
            $owners .= ', ' . $new_owner;
        } else {
            $owners = $new_owner;
        }

        update_post_meta( $post_id, $meta_key, $owners );
    }

    /**
     * Renders the chip value meta box.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_value_meta_box( $post ) {
        $value = self::get_value( $post->ID );
        self::insert_nonce();

        ?>
        <label for="chip_value"><?php echo __( 'Value', 'chip-store' ); ?></label>
        <input type="text" id="chip_value" name="chip_value" value="<?php echo esc_attr( $value ); ?>" style="width: 100%;" />
        <?php
    }

    /**
     * Renders the chip owners meta box.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_owners_meta_box( $post ) {
        $owners = get_post_meta( $post->ID, self::get_meta_key( 'owners' ), true );
        if ( empty( $owners ) ) {
            $owners = get_post_meta( $post->ID, self::get_meta_key( 'owner' ), true ); // Backward compatibility.
        }
        self::insert_nonce();

        ?>
        <div id="chip_owners" style="width: 100%;">
            <?php
            if ( ! empty( $owners ) ) {
                $owners = explode( ', ', $owners );
                foreach ( $owners as $owner ) {
                    echo '<p>' . esc_html( $owner ) . '</p>';
                }
            } else {
                echo '<p>' . __( 'No one has used this chip yet.', 'chip-store' ) . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Renders the meta box for chip consumed.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_consumed_meta_box( $post ) {
        $consumed = get_post_meta( $post->ID, self::get_meta_key( 'consumed' ), true );
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
        $encrypted_code = get_post_meta( $post->ID, self::get_meta_key( 'code' ), true );

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
     * Renders the QR code meta box.
     *
     * @param WP_Post $post The post object.
     */
    public static function render_qrcode_meta_box( $post ) {
        $attachment_id = get_post_meta( $post->ID, self::get_meta_key( 'qrcode' ), true );
        $qrcode_url = wp_get_attachment_url( $attachment_id );

        if ( $qrcode_url ) {
            echo '<a href="' . esc_url( $qrcode_url ) . '" download><img src="' . esc_url( $qrcode_url ) . '" alt="' . esc_attr__( 'QR Code', 'chip-store' ) . '" style="max-width: 100%; height: auto;" /></a>';
        } else {
            echo '<p>' . esc_html__( 'QR Code not available.', 'chip-store' ) . '</p>';
        }
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
     * Save data on the chip that's not handled by meta boxes.
     * 
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     */
    public static function save_chip( $post_id, $post ) {
        // Doing this to avoid pulluting the database. As drafts are not supprted for this CPT and thus cannot be deleted after creation to clean up the database.
        if ( 'publish' !== $post->post_status ) {
            return;
        }

        // Check if the post type is 'chip'.
        if ( self::NAME !== $post->post_type ) {
            return;
        }

        self::save_unique_chip_code( $post_id, $post );
        self::save_qrcode( $post_id, $post );
        self::save_expiration_date( $post_id, $post );
    }

    /**
     * Save generated unique chip code.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     */
    private static function save_unique_chip_code( $post_id, $post ) {
        // Check if the chip code already exists.
        $chip_code = get_post_meta( $post_id, self::get_meta_key( 'code' ), true );
        if ( ! empty( $chip_code ) ) {
            return;
        }

        // Generate a unique chip code using a cryptographically secure randomizer.
        $randomizer = new Randomizer( new Secure() );
        $unique_code = 'CHIP-' . $randomizer->getInt(100000, 999999) . '-' . $randomizer->getInt(100000, 999999);

        // Encrypt the unique chip code before saving it as post meta.
        $encrypted_code = Encryptor::getInstance()->encrypt( $unique_code );

        // Save the encrypted unique chip code as post meta.
        update_post_meta( $post_id, self::get_meta_key( 'code' ), $encrypted_code );
    }

    private static function save_qrcode( $post_id, $post ) {
        // Check if the QR code image already exists.
        $attachment_id = get_post_meta( $post_id, self::get_meta_key( 'qrcode' ), true );
        if ( ! empty( $attachment_id ) ) {
            return;
        }

        // Generate the QR code image.
        $url = self::get_chip_url( $post_id );
        $qrCode = new QrCode( $url, new Encoding('UTF-8'), ErrorCorrectionLevel::Low, 75, 0 );
        $writer = new PngWriter();
        $qrCodeImage = $writer->write( $qrCode );

        // Save the QR code image as an attachment.
        $file_path = self::get_qrcode_image_filepath( $post_id );
        file_put_contents( $file_path, $qrCodeImage->getString() );

        $attachment = [
            'guid'           => $file_path,
            'post_mime_type' => 'image/png',
            'post_title'     => 'Chip QR Code ' . $post_id,
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );
        // require_once( ABSPATH . 'wp-admin/includes/image.php' );
        // $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
        // wp_update_attachment_metadata( $attach_id, $attach_data );

        // Save the attachment ID as post meta.
        update_post_meta( $post_id, self::get_meta_key( 'qrcode' ), $attachment_id );
    }

    /**
     * Save the expiration date for the chip.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     */
    private static function save_expiration_date( $post_id, $post ) {
        // Check if the expiration date already exists.
        $expiration_date = get_post_meta( $post_id, '_chip_expiration_date', true );
        if ( ! empty( $expiration_date ) ) {
            return;
        }

        // Calculate the expiration date (90 days from the current date).
        $expiration_date = date( 'Y/m/d', strtotime( '+90 days' ) );
        // $expiration_date = date( 'Y/m/d H:i:s', strtotime( '+5 minutes' ) ); // For testing purposes.

        // Save the expiration date as post meta.
        update_post_meta( $post_id, '_chip_expiration_date', $expiration_date );
    }

    public static function delete_chip( $post_id ) {
        // Check if the post type is 'chip'.
        if ( self::NAME !== get_post_type( $post_id ) ) {
            return;
        }

        // Delete the QR code image attachment.
        $attachment_id = get_post_meta( $post_id, self::get_meta_key( 'qrcode' ), true );
        wp_delete_attachment( $attachment_id, true );
    }

    /**
     * Set custom columns for the chip post type.
     *
     * @param array $columns The existing columns.
     * @return array The modified columns.
     */
    public static function filter_columns( $columns ) {
        $columns[ 'expiration_date' ] = __( 'Expiration Date', 'chip-store' );
        $columns[ 'value' ] = __( 'Value', 'chip-store' );
        $columns[ 'consumed' ] = __( 'Consumed', 'chip-store' );
        $columns[ 'owners' ] = __( 'Owners', 'chip-store' );
        $columns[ 'url' ] = __( 'URL', 'chip-store' );
        $columns[ 'qrcode' ] = __( 'QR Code', 'chip-store' );

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

            case 'value':
                $value = self::get_value( $post_id );
                echo esc_html( $value );
                break;

            case 'owners':
                $owners = get_post_meta( $post_id, self::get_meta_key( 'owners' ), true );
                if ( empty( $owners ) ) {
                    $owners = get_post_meta( $post_id, self::get_meta_key( 'owner' ), true ); // Backward compatibility.
                }
                if ( empty( $owners ) ) {
                    echo '-';
                } else {
                    $owners = explode( ', ', $owners );
                    foreach ( $owners as $owner ) {
                        echo esc_html( $owner ) . '<br>';
                    }
                }
                break;

            case 'consumed':
                $consumed = get_post_meta( $post_id, self::get_meta_key( 'consumed' ), true );
                echo esc_html( $consumed ? __( 'True', 'chip-store' ) : __( 'False', 'chip-store' ) );
                break;

            case 'url':
                $url = self::get_chip_url( $post_id );
                echo '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a>';
                break;

            case 'expiration_date':
                $expiration_date = get_post_meta( $post_id, self::get_meta_key( 'expiration_date' ), true );
                if ( empty( $expiration_date ) ) {
                    echo '-';
                } else {
                    echo esc_html( $expiration_date );
                }
                break;

            case 'qrcode':
                $attachment_id = get_post_meta( $post_id, self::get_meta_key( 'qrcode' ), true );
                $qrcode_url = wp_get_attachment_url( $attachment_id );
                if ( $qrcode_url ) {
                    echo '<a href="' . esc_url( $qrcode_url ) . '" download><img src="' . esc_url( $qrcode_url ) . '" alt="' . esc_attr__( 'QR Code', 'chip-store' ) . '" style="max-width: 100%; height: auto;" /></a>';
                } else {
                    echo '<p>' . esc_html__( 'QR Code not available.', 'chip-store' ) . '</p>';
                }
                break;
        }
    }

    /**
     * Generate the chip URL.
     *
     * @param int $chip_id The chip ID.
     *
     * @return string The chip URL.
     */
    private static function get_chip_url( $chip_id ) {
        return site_url( 'my-account' ) . '?chip_code=' . self::get_decrypted_chip_code( $chip_id );
    }

    /**
     * Get the file path for the QR code image.
     *
     * @param int $chip_id The chip ID.
     *
     * @return string The file path for the QR code image.
     */
    private static function get_qrcode_image_filepath( $chip_id ) {
        $upload_dir = wp_upload_dir();
        return $upload_dir[ 'path' ] . '/' . self::get_decrypted_chip_code( $chip_id ) . '-qrcode.png';
    }

    /**
     * Get the decrypted chip code.
     *
     * @param int $chip_id The chip ID.
     *
     * @return string The decrypted chip code.
     */
    private static function get_decrypted_chip_code( $chip_id ) {
        $encrypted_code = get_post_meta( $chip_id, self::get_meta_key( 'code' ), true );
        return Encryptor::getInstance()->decrypt( $encrypted_code );
    }
}
