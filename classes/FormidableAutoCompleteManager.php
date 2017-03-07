<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableAutoCompleteManager {
	
	private static $plugin_slug = 'formidable-autocomplete';
	private static $plugin_short = 'FormidableAutoComplete';
	protected static $version;
	
	public function __construct() {
		self::load_plugins_dependency();
		$plugins_header = get_plugin_data( FAC_BASE_FILE );
		self::$version  = $plugins_header['Version'];
		
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'fs_is_submenu_visible_' . FormidableAutoCompleteManager::getSlug(), array( $this, 'handle_sub_menu' ), 10, 2 );
		
		require_once 'FormidableAutocompleteLogs.php';
		new FormidableAutocompleteLogs();
		
		try {
			if ( self::is_formidable_active() ) {
				if ( FormidableAutoComplete::getFreemius()->is_paying() ) {
					require_once 'FormidableAutoCompleteAdmin.php';
					new FormidableAutoCompleteAdmin();
					
					require_once 'FormidableAutoCompleteField.php';
					new FormidableAutoCompleteField();
					
					require_once 'FormidableAutoCompleteOption.php';
					new FormidableAutoCompleteOption();
				}
			}
		} catch ( Exception $ex ) {
			FormidableAutocompleteLogs::log( array(
				'action'         => get_class( $this ),
				'object_type'    => FormidableAutoCompleteManager::getShort(),
				'object_subtype' => 'loading_dependency',
				'object_name'    => $ex->getMessage(),
			) );
		}
	}
	
	public static function load_plugins_dependency() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	
	public static function is_formidable_active() {
		self::load_plugins_dependency();
		
		return is_plugin_active( 'formidable/formidable.php' );
	}
	
	static function getShort() {
		return self::$plugin_short;
	}
	
	static function getVersion() {
		return self::$version;
	}
	
	/**
	 * Get plugins slug
	 *
	 * @return string
	 */
	static function getSlug() {
		return self::$plugin_slug;
	}
	
	/**
	 * Handle freemius menus visibility
	 *
	 * @param $is_visible
	 * @param $menu_id
	 *
	 * @return bool
	 */
	public function handle_sub_menu( $is_visible, $menu_id ) {
		if ( $menu_id == 'account' ) {
			$is_visible = false;
		}
		
		return $is_visible;
	}
	
	/**
	 * Adding the Admin Page
	 */
	public function admin_menu() {
		add_menu_page( __( "AutoComplete", "formidable_autocomplete-locale" ), __( "AutoComplete", "formidable_autocomplete-locale" ), 'manage_options', self::getSlug(), array( $this, 'screen' ), 'dashicons-search' );
	}
	
	/**
	 * Screen to admin page
	 */
	public function screen() {
		FormidableAutoComplete::getFreemius()->get_logger()->entrance();
		FormidableAutoComplete::getFreemius()->_account_page_load();
		FormidableAutoComplete::getFreemius()->_account_page_render();
	}
}