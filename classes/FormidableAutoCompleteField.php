<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableAutoCompleteField {
	
	function __construct() {
		if ( class_exists( "FrmProAppController" ) ) {
			add_action( 'frm_pro_available_fields', array( $this, 'add_formidable_key_field' ) );
			add_action( 'frm_before_field_created', array( $this, 'set_formidable_key_field_options' ) );
			add_action( 'frm_display_added_fields', array( $this, 'show_formidable_key_field_admin_field' ) );
			add_action( 'frm_field_options_form', array( $this, 'field_formidable_key_field_option_form' ), 10, 3 );
			add_action( 'frm_update_field_options', array( $this, 'update_formidable_key_field_options' ), 10, 3 );
			add_action( 'frm_form_fields', array( $this, 'show_formidable_key_field_front_field' ), 10, 2 );
			add_action( 'frm_display_value', array( $this, 'display_formidable_key_field_admin_field' ), 10, 3 );
			add_filter( 'frm_display_field_options', array( $this, 'add_formidable_key_field_display_options' ) );
			add_filter( "frm_validate_field_entry", array( $this, "validate_frm_entry" ), 10, 3 );
			add_filter( 'frmpro_fields_replace_shortcodes', array( $this, 'replace_shortcode' ), 10, 4 );
			add_filter( 'frm_field_classes', array( $this, 'fields_class' ), 10, 2 );
		}
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
		if ( $field["type"] == 'autocomplete' ) {
			$classes .= ' fma_field ';
		}

		return $classes;
	}

	/**
	 * Add new field to formidable list of fields
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field( $fields ) {
		$fields['autocomplete'] = '<b class="gfirem_field">' . __( "Autocomplete", "formidable_autocomplete-locale" ) . '</b>';
		
		return $fields;
	}
	
	/**
	 * Set the default options for the field
	 *
	 * @param $fieldData
	 *
	 * @return mixed
	 */
	public function set_formidable_key_field_options( $fieldData ) {
		if ( $fieldData['type'] == 'autocomplete' ) {
			$fieldData['name'] = __( "Autocomplete", "formidable_autocomplete-locale" );
			
			$defaults = array(
				'autocomplete_target_form'         => '0',
				'autocomplete_target_field'        => '0',
				'autocomplete_target_filter'       => '0',
				'autocomplete_target_filter_group' => '0',
			);
			
			foreach ( $defaults as $k => $v ) {
				$fieldData['field_options'][ $k ] = $v;
			}
		}
		
		return $fieldData;
	}
	
	/**
	 * Show the field placeholder in the admin area
	 *
	 * @param $field
	 */
	public function show_formidable_key_field_admin_field( $field ) {
		if ( $field['type'] != 'autocomplete' ) {
			return;
		}
		?>
		<div class="frm_html_field_placeholder">
			<div class="frm_html_field"><?php _e( "Show a text field with autocomplete", "formidable_autocomplete-locale" ); ?></div>
		</div>
	<?php
	}
	
	
	/**
	 * Display the additional options for the new field
	 *
	 * @param $field
	 * @param $display
	 * @param $values
	 */
	public function field_formidable_key_field_option_form( $field, $display, $values ) {
		if ( $field['type'] != 'autocomplete' ) {
			return;
		}
		
		$defaults = array(
			'autocomplete_target_form'         => '0',
			'autocomplete_target_field'        => '0',
			'autocomplete_target_filter'       => '0',
			'autocomplete_target_filter_group' => '0',
		);
		
		foreach ( $defaults as $k => $v ) {
			if ( ! isset( $field[ $k ] ) ) {
				$field[ $k ] = $v;
			}
		}

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

		include( FAC_VIEW_PATH . '/options.php' );
	}
	
	/**
	 * Update the field options from the admin area
	 *
	 * @param $field_options
	 * @param $field
	 * @param $values
	 *
	 * @return mixed
	 */
	public function update_formidable_key_field_options( $field_options, $field, $values ) {
		if ( $field->type != 'autocomplete' ) {
			return $field_options;
		}
		
		$defaults = array(
			'autocomplete_target_form'         => '0',
			'autocomplete_target_field'        => '0',
			'autocomplete_target_filter'       => '0',
			'autocomplete_target_filter_group' => '0',
		);
		
		foreach ( $defaults as $opt => $default ) {
			$field_options[ $opt ] = isset( $values['field_options'][ $opt . '_' . $field->id ] ) ? $values['field_options'][ $opt . '_' . $field->id ] : $default;
		}
		
		return $field_options;
	}
	
	/**
	 * Add the HTML for the field on the front end
	 *
	 * @param $field
	 * @param $field_name
	 */
	public function show_formidable_key_field_front_field( $field, $field_name ) {
		if ( $field['type'] != 'autocomplete' ) {
			return;
		}
		
		$dependant_fields = FormidableAutoCompleteAdmin::get_dependant_fields( $field );

		wp_enqueue_script( 'jquery.autocomplete', FAC_JS_PATH . 'jquery.autocomplete.min.js', array( "jquery" ), true );

		$field['value'] = stripslashes_deep( $field['value'] );

		$value = ( empty( $field['value'] ) ) ? $field['default_value'] : $field['value'];

		$html_id                  = $field['field_key'];
		$file_name                = str_replace( 'item_meta[' . $field['id'] . ']', 'file' . $field['id'], $field_name );
		$target_form              = $field['autocomplete_target_form'];
		$target_field             = $field['autocomplete_target_field'];
		$target_field_obj         = FrmField::getOne( $target_field );
		$target_field_type        = $target_field_obj->type;
		$target_field_data_target = FrmField::get_option( $target_field_obj, "form_select" );
		if ( empty( $target_field_data_target ) ) {
			$target_field_data_target = "false";
		}
		$field_filter_str   = $field['autocomplete_target_filter'];
		$field_filter_group = "false";
		if ( $field['autocomplete_target_filter_group'] == "1" ) {
			$field_filter_group = $field['autocomplete_target_filter_group'];
		}

		wp_enqueue_script( 'formidable_autocomplete_field', FAC_JS_PATH . 'formidable_autocomplete_field.js', array( "jquery.autocomplete" ), true );
		wp_localize_script( "formidable_autocomplete_field", "formidable_autocomplete_field", array(
			"ajaxurl"          => admin_url( 'admin-ajax.php' ),
			"ajaxnonce"        => wp_create_nonce( 'fac_load_suggestion' ),
			"dependant_fields" => $dependant_fields,
		) );


		include( FAC_VIEW_PATH . 'field.php' );
	}
	
	/**
	 * Add the HTML to display the field in the admin area
	 *
	 * @param $value
	 * @param $field
	 * @param $atts
	 *
	 * @return string
	 */
	public function display_formidable_key_field_admin_field( $value, $field, $atts ) {
		if ( $field->type != 'autocomplete' || empty( $value ) ) {
			return $value;
		}
		
		return $value;
	}
	
	/**
	 * Set display option for the field
	 *
	 * @param $display
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field_display_options( $display ) {
		if ( $display['type'] == 'autocomplete' ) {
			$display['unique']         = false;
			$display['required']       = true;
			$display['read_only']      = true;
			$display['description']    = true;
			$display['options']        = true;
			$display['label_position'] = true;
			$display['css']            = true;
			$display['conf_field']     = false;
			$display['invalid']        = true;
			$display['default_value']  = true;
			$display['visibility']     = true;
			$display['size']           = true;
		}
		
		return $display;
	}
	
	/**
	 * Validate if exist the key in the form target
	 *
	 *
	 * @param $errors
	 * @param $posted_field
	 * @param $posted_value
	 *
	 * @return mixed
	 */
	public function validate_frm_entry( $errors, $posted_field, $posted_value ) {
		if ( $posted_field->type != 'autocomplete' ) {
			return $errors;
		}

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

		return $errors;
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
	 * Replace shortcode of the field. Set custom tags inside the field shortcode
	 *
	 * @param $value
	 * @param $tag
	 * @param $attr
	 * @param $field
	 *
	 * @return string
	 */
	public
	function replace_shortcode(
		$value, $tag, $attr, $field
	) {
		if ( $field->type != 'autocomplete' ) {
			return $value;
		}

		$internal_attr = shortcode_atts( array(
			'force' => '0',
		), $attr );

		if ( $internal_attr['force'] == '1' ) {
			return $value;
		}

		return $value;
	}
}