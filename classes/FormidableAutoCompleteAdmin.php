<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableAutoCompleteAdmin {
	
	public static $type = "autocomplete";
	
	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'front_enqueue_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'front_enqueue_js' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_enqueue_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'front_enqueue_style' ) );
		//Get autocomplete suggestions
		add_action( "wp_ajax_nopriv_get_autocomplete_suggestions", array( $this, "get_autocomplete_suggestions" ) );
		add_action( "wp_ajax_get_autocomplete_suggestions", array( $this, "get_autocomplete_suggestions" ) );
		//Get autocomplete row fields
		add_action( "wp_ajax_nopriv_get_autocomplete_row", array( $this, "get_autocomplete_row" ) );
		add_action( "wp_ajax_get_autocomplete_row", array( $this, "get_autocomplete_row" ) );
		
		add_action( "wp_ajax_nopriv_get_autocomplete_line", array( $this, "get_autocomplete_line" ) );
		add_action( "wp_ajax_get_autocomplete_line", array( $this, "get_autocomplete_line" ) );
		
	}
	
	/**
	 * Include styles
	 */
	public function front_enqueue_style() {
		wp_enqueue_style( 'formidable_autocomplete', FAC_CSS_PATH . 'formidable_autocomplete.css' );
	}
	
	/**
	 * Include script
	 */
	public function front_enqueue_js() {
		wp_register_script( 'formidable_autocomplete', FAC_JS_PATH . 'formidable_autocomplete.js', array( "jquery" ), true );
		wp_enqueue_script( 'formidable_autocomplete' );
	}
	
	public function get_autocomplete_suggestions() {
		
		if ( ! ( is_array( $_GET ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		
		$result = array(
			"value" => ":(",
			"data"  => - 1,
		);
		
		if ( ! check_ajax_referer( 'fac_load_suggestion' ) ) {
			$this->print_result( $result );
		}
		
		if ( ! empty( $_GET["target_form"] ) && ! empty( $_GET["target_field"] ) && ! empty( $_GET["target_field_type"] ) && ! empty( $_GET["target_field_data_target"] ) ) {
			$field_filter = false;
			$start_field  = $_GET["start_field"];
			$target_field = $_GET["target_field"];
			if ( $_GET["target_field_type"] == "data" && $_GET["target_field_data_target"] > 0 ) {
				$target_field = $_GET["target_field_data_target"];
			}
			$filter = false;
			if ( ! empty( $_GET["field_filter"] ) && $_GET["field_filter"] != "false" ) {
				$filter = $_GET["field_filter"];
			}
			
			$group = false;
			if ( ! empty( $_GET["field_filter_group"] ) && $_GET["field_filter_group"] == "true" ) {
				$group = true;
			}
			
			$result                = array();
			$result["suggestions"] = $this->get_result( $target_field, $_GET["query"], $_GET["target_field_type"], $filter, $group, $start_field );
		}
		$this->print_result( $result );
	}
	
	private function print_result( $result ) {
		$str = json_encode( $result );
		echo "$str";
		wp_die();
	}
	
	private function get_result( $field_id, $search, $target_field_type, $field_filter = false, $group = false, $start_field, $limit = - 1 ) {
		global $wpdb;
		
		$sub_query = "SELECT (SELECT g.meta_value FROM " . $wpdb->prefix . "frm_item_metas g WHERE g.item_id = i.meta_value limit 0, 1) AS meta_value FROM " . $wpdb->prefix . "frm_item_metas i WHERE i.item_id = em.item_id limit 0, 1";
		
		$group_sql = "";
		if ( $group ) {
			$group_sql = ", (" . $sub_query . ") as category";
		}
		switch ( $target_field_type ) {
			case "data":
				$sql = "SELECT em.meta_value, e.id as foreign_id, (SELECT i.id FROM " . $wpdb->prefix . "frm_item_metas i WHERE i.meta_value = em.item_id limit 0, 1) AS id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) WHERE em.field_id=" . $field_id . " AND e.is_draft=0 ";
				break;
			default:
				if ( ! empty( $field_filter ) ) {
					if ( $start_field != "false" ) {
						$getStartValue    = "SELECT em.item_id from " . $wpdb->prefix . "frm_item_metas em where em.meta_value LIKE '%" . $field_filter . "%' and em.field_id =" . $start_field;
						$db_getStartValue = $wpdb->get_results( $getStartValue );
						$start_filter     = $db_getStartValue[0]->item_id;
						$sql              = "SELECT em.meta_value, e.id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) INNER JOIN " . $wpdb->prefix . "frm_item_metas em2 ON (em2.item_id = em.item_id) AND em2.meta_value LIKE '%" . $start_filter . "%' WHERE em.field_id=" . $field_id . " AND e.is_draft=0 ";
					} else {
						$sql = "SELECT em.meta_value, e.id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) INNER JOIN " . $wpdb->prefix . "frm_item_metas em2 ON (em2.item_id = em.item_id) AND em2.meta_value LIKE '%" . $field_filter . "%' WHERE em.field_id=" . $field_id . " AND e.is_draft=0 ";
					}
					//$sql = $sql . " AND (" . $sub_query . ") LIKE '%" . $field_filter . "%'";
				} else {
					$sql = "SELECT em.meta_value, e.id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) WHERE em.field_id=" . $field_id . " AND e.is_draft=0 ";
				}
				
				break;
		}
		
		if ( ! empty( $search ) ) {
			$sql = $sql . " AND em.meta_value LIKE '%" . $search . "%'";
		}
		
		/*if ( ! empty( $field_filter ) ) {
			$sql = $sql . " AND (" . $sub_query . ") LIKE '%" . $field_filter . "%'";
		}*/
		
		if ( $limit > 0 ) {
			$sql = $sql . " LIMIT " . $limit;
		}
		
		FormidableAutocompleteLogs::log( array(
			'action'         => "List",
			'object_type'    => FormidableAutoCompleteManager::getShort(),
			'object_subtype' => "get_suggestions",
			'object_name'    => $sql,
		) );
		
		$db_result = $wpdb->get_results( $sql );
		
		$suggestions = array();
		
		foreach ( $db_result as $key => $row ) {
			if ( ! $this->exist_in_array( $suggestions, $row->meta_value ) ) {
				if ( $group ) {
					$suggestions[] = array(
						"value" => $row->meta_value,
						"data"  => array( "category" => $row->category )
					);
				} else {
					$suggestions[] = array(
						"value" => $row->meta_value,
						"data"  => $row->id,
					);
				}
			}
		}
		
		//echo json_encode($suggestions);
		return $suggestions;
	}
	
	private function exist_in_array( $array, $search ) {
		$result = false;
		foreach ( $array as $key => $value ) {
			if ( $value["value"] == $search ) {
				return true;
			}
		}
		
		return $result;
	}
	
	/**
	 * Fill option for field drop down in field options
	 *
	 * @param $form_fields
	 * @param array $field
	 */
	public static function show_options_for_get_values_field( $form_fields, $field = array() ) {
		$select_field_text = __( '&mdash; Select Field &mdash;', 'formidable' );
		echo '<option value="">' . esc_html( $select_field_text ) . '</option>';
		
		foreach ( $form_fields as $field_option ) {
			if ( FrmField::is_no_save_field( $field_option->type ) ) {
				continue;
			}
			
			if ( ! empty( $field ) && $field_option->id == $field->id ) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			
			$field_name = FrmAppHelper::truncate( $field_option->name, 30 );
			echo '<option value="' . esc_attr( $field_option->id ) . '"' . esc_attr( $selected ) . '>' . esc_html( $field_name ) . '</option>';
		}
	}
	
	
	public static function get_args_for_get_options_from_setting( $field ) {
		$lookup_args = array();
		
		// Get all forms for the -select form- option
		$lookup_args['form_list'] = FrmForm::get_published_forms();
		
		if ( isset( $field['fac_get_values_form'] ) && is_numeric( $field['fac_get_values_form'] ) ) {
			$lookup_args['form_fields'] = FrmField::get_all_for_form( $field['fac_get_values_form'] );
		} else {
			$lookup_args['form_fields'] = array();
		}
		
		return $lookup_args;
	}
	
	
	private static function get_fields_for_get_values_field_dropdown( $form_id, $field_type ) {
		if ( in_array( $field_type, array( 'lookup', 'text', 'hidden' ) ) ) {
			$form_fields = FrmField::get_all_for_form( $form_id );
		} else {
			$where       = array(
				'fi.form_id' => $form_id,
				'type'       => $field_type
			);
			$form_fields = FrmField::getAll( $where );
		}
		
		return $form_fields;
	}
	
	public function get_autocomplete_line() {
		global $wpdb;
		check_ajax_referer( 'frm_ajax', 'nonce' );
		
		$result = new stdClass();
		if ( ! empty( $_GET["parent_fields"] ) && ! empty( $_GET["parent_vals"] ) && ! empty( $_GET["field_id"] ) && ! empty( $_GET["target_form"] ) ) {
			$index         = $_GET["index"];
			$parent_fields = $_GET["parent_fields"];
			$parent_vals   = $_GET["parent_vals"];
			$field_id      = $_GET["field_id"];
			$target_form   = $_GET["target_form"];
			
			$query            = "SELECT em.item_id FROM " . $wpdb->prefix . "frm_item_metas em INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) WHERE  e.form_id= " . $target_form . " AND e.is_draft=0 AND em.field_id=" . $parent_fields . " AND em.meta_value='" . $parent_vals . "'";
			$db_getStartValue = $wpdb->get_results( $query );
			$start_filter     = $db_getStartValue[0]->item_id;
			$finalQuery       = "SELECT em.meta_value FROM " . $wpdb->prefix . "frm_item_metas em INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) WHERE  e.form_id=" . $target_form . " AND e.is_draft=0 AND em.field_id=" . $field_id . " AND e.id in ('" . $start_filter . "')";
			$db_result        = $wpdb->get_results( $finalQuery );
			$result->value    = $db_result[0]->meta_value;
			$result->index    = $index;
		}
		
		echo json_encode( $result );
		wp_die();
		
	}
	
	public function get_autocomplete_row() {
		check_ajax_referer( 'frm_ajax', 'nonce' );
		
		$row_key  = FrmAppHelper::get_post_param( 'row_key', '', 'absint' );
		$field_id = FrmAppHelper::get_post_param( 'field_id', '', 'absint' );
		$form_id  = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );
		
		$selected_field = '';
		$current_field  = FrmField::getOne( $field_id );// Maybe (for efficiency) change this to a specific database call
		$lookup_fields  = self::get_limited_lookup_fields_in_form( $form_id, $current_field->form_id );
		
		require( FAC_VIEW_PATH . '/watch-row.php' );
		wp_die();
	}
	
	public static function get_lookup_fields_for_watch_row( $field ) {
		$parent_form_id = isset( $field['parent_form_id'] ) ? $field['parent_form_id'] : $field['form_id'];
		$lookup_fields  = self::get_limited_lookup_fields_in_form( $parent_form_id, $field['form_id'] );
		
		return $lookup_fields;
	}
	
	private static function get_limited_lookup_fields_in_form( $parent_form_id, $current_form_id ) {
		if ( $parent_form_id == $current_form_id ) {
			// If the current field's form ID matches $form_id, only get fields in that form (not embedded or repeating)
			$inc_repeating = 'exclude';
		} else {
			// If current field is repeating, get lookup fields in repeating section and outside of it
			$inc_repeating = 'include';
		}
		
		$lookup_fields = FrmField::get_all_types_in_form( $parent_form_id, self::$type, '', $inc_repeating );
		
		return $lookup_fields;
	}
	
	/**
	 * Get all field targeting to itself
	 *
	 * @param $field
	 *
	 * @return array
	 */
	public static function get_dependant_fields( $field ) {
		$parent_form_id = isset( $field['parent_form_id'] ) ? $field['parent_form_id'] : $field['form_id'];
		
		if ( $parent_form_id == $field['form_id'] ) {
			// If the current field's form ID matches $form_id, only get fields in that form (not embedded or repeating)
			$inc_repeating = 'exclude';
		} else {
			// If current field is repeating, get lookup fields in repeating section and outside of it
			$inc_repeating = 'include';
		}
		
		$auto_complete_fields      = FrmField::get_all_for_form( $parent_form_id, '', 'include', $inc_repeating );
		$auto_populate_field_types = FrmProLookupFieldsController::get_autopopulate_field_types();
		$result                    = array();
		$children_id               = array();
		$children                  = array();
		
		foreach ( $auto_complete_fields as $key => $item ) {
			if ( ! in_array( $item->type, $auto_populate_field_types ) ) {
				continue;
			}
			$watch   = FrmField::get_option( $item, "fac_watch_lookup" );
			$depende = FrmField::get_option( $item, "fac_get_values_field" );
			if ( ! empty( $watch ) && count( $watch ) > 0 ) {
				foreach ( $watch as $k => $i ) {
					if ( ! empty( $i ) ) {
						
						$target    = FrmField::getOne( $i );
						//echo json_encode($target);
						$target_id = "field_" . $target->field_key;						
						$children_id[] = $item->id;						
						$children[] = $depende;						
						//$result[ $target_id ][] = $i;
						$result[ $target_id ]['fieldId']       = $i;
						$result[ $target_id ]['fieldKey']      = $target->field_key;
						$result[ $target_id ]['fieldType']     = $target->type;
						$result[ $target_id ]['formId']        = $field['parent_form_id'];
						$result[ $target_id ]['dependents']    = $children;
						$result[ $target_id ]['dependents_id'] = $children_id;
					}
				}
			}
		}
		
		return $result;
	}
	
	private static function maybe_initialize_frm_vars_lookup_fields_for_id( $field_id, &$frm_vars ) {
		if ( ! isset( $frm_vars[ $field_id ] ) ) {
			$frm_vars[ $field_id ] = array(
				'dependents' => array()
			);
		}
	}
	
}