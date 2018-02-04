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

class GFireMAutocompleteOption {
	private $view_path;

	function __construct() {
		$this->view_path = GFireMAutoComplete::$view;
		add_action( 'frm_field_options_form', array( $this, 'field_field_option_form' ), 10, 3 );
		add_action( 'frm_update_field_options', array( $this, 'update_field_options' ), 10, 3 );
	}

	/**
	 * Display the additional options for the new field
	 *
	 * @param $field
	 * @param $display
	 * @param $values
	 */
	public function field_field_option_form( $field, $display, $values ) {
		$autopopulate_field_types = FrmProLookupFieldsController::get_autopopulate_field_types();
		if ( ! in_array( $field['type'], $autopopulate_field_types ) ) {
			return;
		}

		$lookup_args   = GFireMAutocompleteAdmin::get_args_for_get_options_from_setting( $field );
		$lookup_fields = GFireMAutocompleteAdmin::get_lookup_fields_for_watch_row( $field );
		$fac_autopopulate_value = !empty($field['fac_autopopulate_value']) ? $field['fac_autopopulate_value'] : false;
		$fac_get_values_form = !empty($field['fac_get_values_form']) ? $field['fac_get_values_form'] : false;
		require( $this->view_path . 'autopopulate-values.php' );
	}

	/**
	 * Show the dropdown options for the "Get Options/Values From" Field option
	 *
	 * @since 2.01.0
	 *
	 * @param array $form_fields
	 * @param array $field ($field is not empty on page load)
	 */
	private static function show_options_for_get_values_field( $form_fields, $field = array() ) {
		$select_field_text = __( '&mdash; Select Field &mdash;', 'formidable' );
		echo '<option value="">' . esc_html( $select_field_text ) . '</option>';

		foreach ( $form_fields as $field_option ) {
			if ( FrmField::is_no_save_field( $field_option->type ) ) {
				continue;
			}

			if ( ! empty( $field ) && $field_option->id == $field['fac_get_values_field'] ) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}

			$field_name = FrmAppHelper::truncate( $field_option->name, 30 );
			echo '<option value="' . esc_attr( $field_option->id ) . '"' . esc_attr( $selected ) . '>' . esc_html( $field_name ) . '</option>';
		}
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
	public function update_field_options( $field_options, $field, $values ) {
		$autopopulate_field_types = FrmProLookupFieldsController::get_autopopulate_field_types();
		if ( ! in_array( $field->type, $autopopulate_field_types ) ) {
			return $field_options;
		}

		$defaults = array(
			'fac_get_values_field'   => '0',
			'fac_get_values_form'    => '0',
			'fac_autopopulate_value' => '0',
			'fac_watch_lookup'       => '0',
		);

		foreach ( $defaults as $opt => $default ) {
			$field_options[ $opt ] = isset( $values['field_options'][ $opt . '_' . $field->id ] ) ? $values['field_options'][ $opt . '_' . $field->id ] : $default;
		}

		return $field_options;
	}
}