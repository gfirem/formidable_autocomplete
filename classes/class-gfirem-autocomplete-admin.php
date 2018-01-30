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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFireMAutocompleteAdmin {
	public static $type = "autocomplete";
	private $base_url;

	function __construct() {
		$this->base_url = GFireMAutoComplete::$assets;
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ) );
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
	public function front_enqueue_style( $hook ) {
		wp_enqueue_style( 'formidable_autocomplete', $this->base_url . 'css/formidable_autocomplete.css' );
	}

	/**
	 * Include script
	 */
	public function enqueue_js( $hook ) {
		if ( ! empty( $hook ) && 'toplevel_page_formidable' === $hook ) {
			wp_register_script( 'formidable_autocomplete', $this->base_url . 'js/formidable_autocomplete.js', array( "jquery" ), true );
			wp_enqueue_script( 'formidable_autocomplete' );
		}
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

		$field_filter = false;
		$start_field  = false;
		$target_field = false;
		$parent_field = false;
		$filter       = false;
		$group        = false;

		if ( ! empty( $_GET["target_form"] ) && ! empty( $_GET["target_field"] ) && ! empty( $_GET["target_field_type"] ) && ! empty( $_GET["target_field_data_target"] ) ) {

			$start_field  = isset( $_GET["start_field"] ) ? sanitize_text_field( $_GET["start_field"] ) : false;
			$target_field = isset( $_GET["target_field"] ) ? sanitize_text_field( $_GET["target_field"] ) : false;
			$parent_field = isset( $_GET["parent_field"] ) ? sanitize_text_field( $_GET["parent_field"] ) : false;
			if ( $_GET["target_field_type"] == "data" && $_GET["target_field_data_target"] > 0 ) {
				$target_field = sanitize_text_field( $_GET["target_field_data_target"] );
			}

			if ( ! empty( $_GET["field_filter"] ) && $_GET["field_filter"] != "false" ) {

				$filter = sanitize_text_field( $_GET["field_filter"] );
			}


			if ( ! empty( $_GET["field_filter_group"] ) && $_GET["field_filter_group"] == "true" ) {
				$group = true;
			}

			$result                = array();
			$search                = sanitize_text_field( $_GET["query"] );
			$target_field_type     = sanitize_text_field( $_GET["target_field_type"] );
			$result["suggestions"] = $this->get_result( $target_field, $search, $target_field_type, $filter, $group, $start_field, $parent_field );
		}
		$this->print_result( $result );
	}

	private function print_result( $result ) {
		$str = json_encode( $result );
		echo "$str";
		wp_die();
	}

	private function get_result( $field_id, $search, $target_field_type, $field_filter = false, $group = false, $start_field, $parent_field, $limit = - 1 ) {
		$suggestions = array();
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
						$sql              = "SELECT em.meta_value, em.item_id id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  WHERE em.field_id= " . $field_id . " AND em.meta_value LIKE '%" . $search . "%'  and em.item_id in  (Select item_id from " . $wpdb->prefix . "frm_item_metas where item_id = em.item_id and meta_value LIKE '%" . $start_filter . "%' and field_id =" . $parent_field . ")";
					} else {
						$sql = "SELECT em.meta_value, em.item_id id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  WHERE em.field_id= " . $field_id . " AND em.meta_value LIKE '%" . $search . "%'  and em.item_id in  (Select item_id from " . $wpdb->prefix . "frm_item_metas where item_id = em.item_id and meta_value LIKE '%" . $field_filter . "%' and field_id =" . $parent_field . ")";
					}
					//$sql = $sql . " AND (" . $sub_query . ") LIKE '%" . $field_filter . "%'";
				} else {
					$sql = "SELECT em.meta_value, e.id " . $group_sql . " FROM " . $wpdb->prefix . "frm_item_metas em  INNER JOIN " . $wpdb->prefix . "frm_items e ON (e.id=em.item_id) WHERE em.field_id=" . $field_id . " AND e.is_draft=0 ";
				}

				break;
		}

		if ( ! empty( $search ) ) {
			if ( empty( $field_filter ) ) {
				$sql = $sql . " AND em.meta_value LIKE '%" . $search . "%'";
			}

		}

		/*if ( ! empty( $field_filter ) ) {
			$sql = $sql . " AND (" . $sub_query . ") LIKE '%" . $field_filter . "%'";
		}*/

		if ( $limit > 0 ) {
			$sql = $sql . " LIMIT " . $limit;
		}

		GFireMAutocompleteLogs::log( array(
			'action'         => "List",
			'object_type'    => GFireMAutoComplete::getSlug(),
			'object_subtype' => "get_suggestions",
			'object_name'    => $sql,
		) );

		$db_result = $wpdb->get_results( $sql );

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

		$all_field_saved = true;
		foreach ( $lookup_fields as $field_option ) {
			$option_exist = $this->field_option_exist( $current_field->field_options['fac_watch_lookup'], $field_option->id );
			//If at least one field have not been saved, then add the dropdown.
			if ( ! $option_exist ) {
				$all_field_saved = false;
				break;
			}
		}
		//If there are fields avaliable, then show the dropdown with the avaliable options.
		if ( ! $all_field_saved ) {
			echo "<div id=\"fac_frm_watch_lookup_" . $field_id . '_' . $row_key . "\">";
			echo " <select name=\"field_options[fac_watch_lookup_" . $field_id . "][]\">";
			echo "<option value=\"\">&mdash; Select Field &mdash;</option>  ";
			foreach ( $lookup_fields as $field_option ) {
				$option_exist = $this->field_option_exist( $current_field->field_options['fac_watch_lookup'], $field_option->id );
				//If the field is already saved in the options do not add it again in the dropdown, avoid duplicate options.
				if ( $option_exist ) {
					continue;
				}
				$selected = ( $field_option->id == $selected_field ) ? ' selected="selected"' : '';
				echo "<option value=\"$field_option->id\" $selected > $field_option->name</option>";
			}
			echo "</select>";
			echo " <a href=\"javascript:void(0)\" class=\"fac_frm_remove_tag frm_icon_font\" data-removeid=\"fac_frm_watch_lookup_" . $field_id . '_' . $row_key . "\" data-fieldid=\"" . $field_id . "\"></a>";
			echo "<a href=\"javascript:void(0)\" class=\"fac_frm_add_tag frm_icon_font fac_frm_add_watch_lookup_row\"></a>";
			echo "</div>";
		}
		wp_die();
	}

	//Look if the form field is already saved in the options.
	function field_option_exist( $field_options, $lookup_field_id ) {

		foreach ( $field_options as $value ) {
			// If the field is already saved in the options.
			if ( $lookup_field_id == $value ) {
				return true;
			}
		}

		return false;
	}

	public static function get_lookup_fields_for_watch_row( $field ) {
		$lookup_fields  = false;
		$parent_form_id = isset( $field['parent_form_id'] ) ? $field['parent_form_id'] : $field['form_id'];
		$lookup_fields  = self::get_limited_lookup_fields_in_form( $parent_form_id, $field['form_id'] );

		return $lookup_fields;
	}

	private static function get_limited_lookup_fields_in_form( $parent_form_id, $current_form_id ) {
		$lookup_fields = false;
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
		$result         = array();
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


		foreach ( $auto_complete_fields as $key => $item ) {
			if ( ! in_array( $item->type, $auto_populate_field_types ) ) {
				continue;
			}
			$watch   = FrmField::get_option( $item, "fac_watch_lookup" );
			$depende = FrmField::get_option( $item, "fac_get_values_field" );//se obtiene el field_id del hijo
			if ( ! empty( $watch ) && count( $watch ) > 0 ) {
				foreach ( $watch as $k => $i ) {
					if ( ! empty( $i ) ) {

						$target                            = FrmField::getOne( $i );
						$target_id                         = "field_" . $target->field_key;
						$result[ $target_id ]['fieldId']   = $i;
						$result[ $target_id ]['fieldKey']  = $target->field_key;
						$result[ $target_id ]['fieldType'] = $target->type;
						$result[ $target_id ]['formId']    = $field['parent_form_id'];
						$result[ $target_id ]['current']   = $field['id'];
						//Se asigan los hijos a este campo
						$result[ $target_id ]['dependents'] []   = $depende;
						$result[ $target_id ]['dependents_id'][] = $item->id;
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