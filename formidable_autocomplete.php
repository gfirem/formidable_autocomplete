<?php

/**
 *
 * @since             1.0
 * @package           FormidableAutoComplete
 *
 * @wordpress-plugin
 * Plugin Name:       Formidable Autocomplete
 * Description:       Formidable text field with autocomplete.
 * Version:           1.0
 * Author:            gfirem
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'FormidableAutoComplete' ) ) :

	class FormidableAutoComplete {

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin.
		 */
		private function __construct() {
			define( 'FAC_JS_PATH', plugin_dir_url( __FILE__ ) . 'assets/js/' );
			define( 'FAC_CSS_PATH', plugin_dir_url( __FILE__ ) . 'assets/css/' );
			define( 'FAC_VIEW_PATH', plugin_dir_path( __FILE__ ) . 'view/' );

			require_once 'classes/FormidableAutoCompleteManager.php';
			$manager = new FormidableAutoCompleteManager();
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'formidable_autocomplete-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}

	}

	add_action( 'plugins_loaded', array( 'FormidableAutoComplete', 'get_instance' ) );

endif;
