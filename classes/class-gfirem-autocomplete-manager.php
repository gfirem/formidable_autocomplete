<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFireMAutocompleteManager {

	/*
	 * Me quede por:
	 * hay que hacer que enlazar los ficheros para que carge all en su lugar.
	 * El plugins es de pago y no tiene contenido protejido.
	 * El campo tiene que tener el mismo slug para que siga funcionando.
	 * Hay que hacer que el plugin sea como cascaron para todos los campos.
	 * Hay campos que son free que van a ir directo a wp.org
	 * Hacer el campo lo mas ligero posible
	 * Ver si se puede implementar cache.
	 *
	 */
	public function __construct() {
		
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'fs_is_submenu_visible_gfirem-autocomplete', array( $this, 'handle_sub_menu' ), 10, 2 );
		
		require_once 'class-gfirem-autocomplete-logs.php';
		new GFireMAutocompleteLogs();
		
		try {
			//Check formidable pro
			if ( self::is_formidable_active() ) {
				if ( GFireMAutoComplete::getFreemius()->is_paying() ) {
					require_once 'class-gfirem-autocomplete-admin.php';
					new GFireMAutocompleteAdmin();

					require_once 'class-gfirem-fieldbase.php';
					
					require_once 'class-gfirem-autocomplete-field.php';
					new GFireMAutocompleteField();
					
					require_once 'class-gfirem-autocomplete-option.php';
					new GFireMAutocompleteOption();
				}
			}
		} catch ( Exception $ex ) {
			GFireMAutocompleteLogs::log( array(
				'action'         => get_class( $this ),
				'object_type'    => GFireMAutoComplete::getSlug(),
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
		add_menu_page( __( "AutoComplete", "gfirem_autocomplete-locale" ), __( "AutoComplete", "gfirem_autocomplete-locale" ), 'manage_options', GFireMAutoComplete::getSlug(), array( $this, 'screen' ), 'dashicons-search' );
	}
	
	/**
	 * Screen to admin page
	 */
	public function screen() {
		GFireMAutoComplete::getFreemius()->get_logger()->entrance();
		GFireMAutoComplete::getFreemius()->_account_page_load();
		GFireMAutoComplete::getFreemius()->_account_page_render();
	}
}