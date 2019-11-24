<?php

/**
 * @package    WordPress
 * @subpackage Formidable, gfirem
 * @author     GFireM
 * @copyright  2017
 * @link       http://www.gfirem.com
 * @license    http://www.apache.org/licenses/
 *
 */
class GFireMAutocompleteField extends GFireMFieldBase {
	private $base_url;
	private $view_path;
	public $version = '1.0.0';

	function __construct() {
		$this->base_url  = GFireMAutoComplete::$assets;
		$this->view_path = GFireMAutoComplete::$view;

		parent::__construct( 'autocomplete', __( 'Autocomplete', 'gfirem_autocomplete-locale' ),
			array(
				'autocomplete_target_form'         => '0',
				'autocomplete_target_field'        => '0',
				'autocomplete_target_filter'       => '0',
				'autocomplete_target_filter_group' => '0',
				'autocomplete_placeholder'         => '',
				'autocomplete_minChars'            => '1',
				'autocomplete_noResulText'         => 'No result',
				'autocomplete_cache'               => 'true',
				'autocomplete_validate'            => 'true',
			),
			__( 'Show a text field with autocomplete.', '' ),
			array(), 'profesional', 'dashicons dashicons-code-standards'
		);
	}

	protected function inside_field_options( $field, $display, $values ) {
		$lookup_args = array();

		// Get all forms for the -select form- option
		$lookup_args['form_list'] = FrmForm::get_published_forms();

		if ( is_numeric( $field['autocomplete_target_form'] ) ) {
			$lookup_args['form_fields'] = FrmField::get_all_for_form( $field['autocomplete_target_form'] );
		} else {
			$lookup_args['form_fields'] = array();
		}

		$fields_for_filter = FrmField::get_all_types_in_form( $field["form_id"], "autocomplete" );

		$field_target = array();
		if ( ! empty( $field['autocomplete_target_field'] ) && is_numeric( $field['autocomplete_target_field'] ) ) {
			$field_target = FrmField::getOne( $field['autocomplete_target_field'] );
		}

		$show_filter_group = "";
		if ( $field['autocomplete_target_filter_group'] == "1" ) {
			$show_filter_group = "checked='checked'";
		}

		include( $this->view_path . 'options.php' );
	}

	protected function field_front_view( $field, $field_name, $html_id ) {
		$dependant_fields = GFireMAutocompleteAdmin::get_dependant_fields( $field );
		wp_enqueue_script( 'jquery.autocomplete', $this->base_url . 'js/jquery.autocomplete.min.js', array( "jquery" ), true );

		$field['value'] = stripslashes_deep( $field['value'] );
		$print_value    = $field['default_value'];
		if ( ! empty( $field['value'] ) ) {
			$print_value = $field['value'];
		}

		$value = ( empty( $field['value'] ) ) ? $field['default_value'] : $field['value'];

		$html_id           = $field['field_key'];
		$file_name         = str_replace( 'item_meta[' . $field['id'] . ']', 'file' . $field['id'], $field_name );
		$target_form       = $field['autocomplete_target_form'];
		$target_field      = $field['autocomplete_target_field'];
		$target_field_type = '';
		if ( ! empty( $target_field ) ) {
			$target_field_obj         = FrmField::getOne( $target_field );
			$target_field_type        = $target_field_obj->type;
			$target_field_data_target = FrmField::get_option( $target_field_obj, "form_select" );
		}
		if ( empty( $target_field_data_target ) ) {
			$target_field_data_target = "false";
		}
		$field_filter_str   = $field['autocomplete_target_filter'];
		$field_filter_group = "false";
		if ( $field['autocomplete_target_filter_group'] == "1" ) {
			$field_filter_group = $field['autocomplete_target_filter_group'];
		}
		$placeholder      = '';
		$placeholder_text = $field['autocomplete_placeholder'];
		if ( ! empty( $field['autocomplete_placeholder'] ) ) {
			$placeholder = ' placeholder="' . esc_attr( $field['autocomplete_placeholder'] ) . '" ';
		}

		$this->load_scripts( $print_value, $html_id, $dependant_fields );
		$loading_url = apply_filters( 'gfirem_fields_autocomplete_loading_url', home_url() . '/wp-content/plugins/formidable/images/ajax_loader.gif' );

		include( $this->view_path . 'field.php' );
	}

	private function load_scripts( $print_value = "", $field_id = "", $dependant_fields = "" ) {
		wp_enqueue_script( 'formidable_autocomplete_field', $this->base_url . 'js/formidable_autocomplete_field.js', array( "jquery.autocomplete" ), true );
		$params              = array(
			'ajaxurl'          => admin_url( 'admin-ajax.php' ),
			'ajaxnonce'        => wp_create_nonce( 'fac_load_suggestion' ),
			"dependant_fields" => $dependant_fields,
			"text_no_result"   => __( 'No result' , 'gfirem_autocomplete-locale'),
		);
		$autocomplete_Fields = FrmField::get_all_types_in_form( $this->form_id, $this->slug );
		foreach ( $autocomplete_Fields as $key => $field ) {
			foreach ( $this->defaults as $def_key => $def_val ) {
				$opt                                                          = FrmField::get_option( $field, $def_key );
				$params['config'][ 'field_' . $field->field_key ][ $def_key ] = ( ! empty( $opt ) ) ? $opt : $def_val;
			}
		}
		wp_localize_script( "formidable_autocomplete_field", "formidable_autocomplete_field", $params );
	}

	protected function display_options( $display ) {
		$display['unique']         = true;
		$display['required']       = true;
		$display['read_only']      = true;
		$display['description']    = true;
		$display['options']        = true;
		$display['label_position'] = true;
		$display['css']            = true;
//		$display['conf_field']     = true; Not working in the new version
		$display['invalid']        = true;
		$display['default_value']  = true;
		$display['visibility']     = true;
		$display['size']           = true;

		return $display;
	}

	private function exist_meta( $search, $field, $type ) {
		global $wpdb;
		$search_query = " it.meta_value = '" . $search . "' ";
		if ( $type == "data" ) {
			$search_query = "(SELECT (SELECT g.meta_value FROM " . $wpdb->prefix . "frm_item_metas g WHERE g.item_id = i.meta_value LIMIT 1) AS meta_value FROM " . $wpdb->prefix . "frm_item_metas i WHERE i.item_id = it.item_id LIMIT 1) = '" . $search . "' ";
		}
		$sql = "SELECT count(it.id) FROM " . $wpdb->prefix . "frm_item_metas it LEFT OUTER JOIN " . $wpdb->prefix . "frm_fields fi ON (it.field_id = fi.id)" .
		       "WHERE it.field_id = " . $field . " AND " . $search_query .
		       "ORDER BY fi.field_order";

		$count = $wpdb->get_var( $sql );

		return $count > 0;
	}

	/**
	 * Add class to the field
	 *
	 * @param $classes
	 * @param $field
	 *
	 * @return string
	 */
	public function fields_class( $classes, $field ) {
		$classes .= ' fma_field ';

		return $classes;
	}

	/**
	 * Validate if exist the key in the form target
	 *
	 * @param $errors
	 * @param $posted_field
	 * @param $posted_value
	 *
	 * @return mixed
	 */
	protected function validate_frm_entry( $errors, $posted_field, $posted_value ) {
		$canValidate = $posted_field->field_options['autocomplete_validate'] == "true" ? true : false;
		if ( $canValidate ) {
			if ( ! empty( $_POST["item_meta"][ $posted_field->id ] ) ) {
				$autocomplete_target_field = FrmField::get_option( $posted_field, "autocomplete_target_field" );
				if ( ! empty( $autocomplete_target_field ) ) {
					$target_field = FrmField::getOne( $autocomplete_target_field );
					if ( ! $this->exist_meta( $posted_value, $autocomplete_target_field, $target_field->type ) ) {
						$msj    = FrmFieldsHelper::get_error_msg( $posted_field, 'invalid' );
						$errors = array_merge( $errors, array( 'field' . $posted_field->id => $msj ) );
					}
				}
			}
		}

		return $errors;
	}


}