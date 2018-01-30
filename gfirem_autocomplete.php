<?php

/**
 *
 * @since             1.0.0
 * @package           GFireMAutoComplete
 *
 * @wordpress-plugin
 * Plugin Name:       GFireM Autocomplete Field
 * Description:       Formidable text field with autocomplete.
 * Version:           1.0.0
 * Author:            gfirem
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'GFireMAutoComplete' ) ) {

	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class-gfirem-autocomplete-freemius.php';
	GFireMAutocompleteFreemius::get_instance();

	class GFireMAutoComplete {

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		public static $assets;
		public static $view;
		public static $classes;
		public static $slug = 'gfirem-autocomplete';
		public static $version = '1.0.0';

		/**
		 * Initialize the plugin.
		 */
		private function __construct() {
			self::$assets  = plugin_dir_url( __FILE__ ) . 'assets/';
			self::$view    = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
			self::$classes = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
			$this->load_plugin_textdomain();
			require_once self::$classes . 'class-gfirem-autocomplete-manager.php';
			new GFireMAutoCompleteManager();
		}

		static function getFreemius(){
			return GFireMAutocompleteFreemius::getFreemius();
		}

		/**
		 * Get plugin version
		 *
		 * @return string
		 */
		static function getVersion() {
			return self::$version;
		}

		/**
		 * Get plugins slug
		 *
		 * @return string
		 */
		static function getSlug() {
			return self::$slug;
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
			load_plugin_textdomain( 'gfirem_autocomplete-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}
	}

	add_action( 'plugins_loaded', function () {
		global $gfirem;
		$gfirem[ GFireMAutoComplete::$slug ]['instance'] = GFireMAutoComplete::get_instance();
	} );

}
