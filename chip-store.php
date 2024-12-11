<?php
/**
 * Plugin Name: Chip Store
 * Description:
 * Author: Zeyad Mahagna
 * Author URI: 
 * Version: 1.0.0
 * Text Domain: chip-store
 * 
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CHIP_STORE__FILE__', __FILE__ );
define( 'CHIP_STORE_PLUGIN_BASE', plugin_basename( CHIP_STORE__FILE__ ) );
define( 'CHIP_STORE_PATH', plugin_dir_path( CHIP_STORE__FILE__ ) );

define( 'CHIP_STORE_URL', plugins_url( '/', CHIP_STORE__FILE__ ) );

if ( ! in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins' ) ) ) {
	exit; // Exit if WooCommerce is not active.
}

require CHIP_STORE_PATH . 'includes/plugin.php';
