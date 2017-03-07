<?php

/**
 *
 * @since             1.0.0
 * @package           FormidableAutoComplete
 *
 * @wordpress-plugin
 * Plugin Name:       Formidable Autocomplete
 * Description:       Formidable text field with autocomplete.
 * Version:           1.0.0
 * Author:            gfirem
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'FormidableAutoComplete' ) ) :
	
	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'FormidableAutoCompleteOverride.php';
	fac_fs();
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
			define( 'FAC_BASE_NAME', plugin_basename( __FILE__ ) );
			define( 'FAC_BASE_FILE', trailingslashit( str_replace( "\\", "/", plugin_dir_path( __FILE__ ) ) ) . 'formidable_autocomplete.php' );
			define( 'FAC_JS_PATH', plugin_dir_url( __FILE__ ) . 'assets/js/' );
			define( 'FAC_CSS_PATH', plugin_dir_url( __FILE__ ) . 'assets/css/' );
			define( 'FAC_VIEW_PATH', plugin_dir_path( __FILE__ ) . 'view/' );
			define( 'FAC_CLASSES_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR );
			
			require_once FAC_CLASSES_PATH . '/include/WP_Requirements.php';
			require_once FAC_CLASSES_PATH . 'FormidableAutoCompleteRequirements.php';
			$this->requirements = new FormidableAutoCompleteRequirements( 'formidable_autocomplete-locale' );
			if ( $this->requirements->satisfied() ) {
				require_once 'classes/FormidableAutoCompleteManager.php';
				new FormidableAutoCompleteManager();
			} else {
				$fauxPlugin = new WP_Faux_Plugin( _faa( 'Formidable Autocomplete' ), $this->requirements->getResults() );
				$fauxPlugin->show_result( FAC_BASE_NAME );
			}
		}
		
		/**
		 * @return Freemius
		 */
		public static function getFreemius() {
			global $fac_fs;
			
			return $fac_fs;
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
