<?php

namespace Chip_Store;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Chip Store plugin.
 *
 * The main plugin handler class is responsible for initialization. The
 * class registers and all the components required to run the plugin.
 *
 * @since 1.0.0
 */
class Plugin {
	/**
	 * The single instance of the class.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Main Plugin Instance.
	 *
	 * Ensures only one instance of Plugin is loaded or can be loaded.
	 *
	 * @return Plugin - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 *
	 * Initializing the plugin.
	 */
	private function __construct() {
		// Ensure the script is treated as a module
		// TODO: TEMPORARY FIX, Need to Minify and Bundle JS
		// add_filter('script_loader_tag', function($tag, $handle) {
		// 	$tag = str_replace(' src', ' type="module" src', $tag);
		// 	return $tag;
		// }, 10, 2);

		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		$this->register_autoloader();

		// Start a session to maintain temporary data for guests.
		if ( PHP_SESSION_NONE == session_status() ) {
			session_start();
		}
		add_action( 'wp_logout', [ $this, 'clear_session_on_logout' ] );

		// Register the shortcode to display the user's chip credit.
		add_shortcode( 'chip_store_credit', [ $this, 'display_chip_store_credit' ] );

		$this->init_modules();
	}

	/**
	 * Initialize all modules.
	 */
	public function init_modules() {
		new Admin();
		new Ajax_Handler();
		new My_Account();
		new Checkout();
	}

	/**
	 * Clear session on user logout.
	 */
	public function clear_session_on_logout() {
		if ( PHP_SESSION_NONE !== session_status() ) {
			session_destroy();
		}
	}

	/**
	 * Register autoloader.
	 */
	private function register_autoloader() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class Class name.
	 */
	private function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}
	
		$class_name = str_replace( __NAMESPACE__ . '\\', '', $class );
		$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
		$class_name = strtolower($class_name); // Convert class name to lowercase
		$class_name = str_replace('_', '-', $class_name); // Convert underscores to dashes
		$file       = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $class_name . '.php';
	
		if ( file_exists( $file ) ) {
			require $file;
		}
	}

	/**
     * Shortcode to display the user's chip credit.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string The user's chip credit.
     */
    public function display_chip_store_credit( $atts ) {
        $chip_credit = 0;
        if ( is_user_logged_in() ) {
            $chip_credit = get_user_meta( get_current_user_id(), Ajax_Handler::CHIP_CREDIT_META_KEY, true );
        } elseif ( ! empty( $_SESSION[ 'guest_chip_credit' ] ) ) { // Check if the guest has chip credit.
            $chip_credit = $_SESSION[ 'guest_chip_credit' ];
        }

        // Return the meta field value
        if ( ! empty( $chip_credit ) ) {
            return '<p>Your chip credit: ' . $chip_credit . '</p>';
        }

        return '<p>No chip credit available.</p>';
    }
}

Plugin::instance();
