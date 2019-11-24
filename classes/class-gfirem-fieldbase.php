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

if ( ! class_exists( 'GFireMFieldBase' ) ) {

	class GFireMFieldBase {

		public $slug;
		public $name;
		public $icon;
		public $description;
		public $defaults = array();
		public $global_options = array();
		public $plan = 'free';
		public $form_id;
		public $is_tweak;

		public function __construct( $slug, $name, $defaults, $description = '', $global_options = array(), $plan = 'profesional', $icon = 'dashicons dashicons-admin-site-alt3' ) {
			if ( empty( $slug ) || empty( $name ) || empty( $defaults ) || ! is_array( $defaults ) ) {
				throw new InvalidArgumentException();
			}
			if ( class_exists( 'FrmProAppController' ) ) {
				$this->slug           = $slug;
				$this->name           = $name;
				$this->icon           = $icon;
				$this->description    = $description;
				$this->defaults       = $defaults;
				$this->global_options = $global_options;
				$this->plan           = $plan;

				add_action( 'frm_pro_available_fields', array( $this, 'add_formidable_field' ) );
				add_action( 'frm_before_field_created', array( $this, 'set_formidable_field_options' ) );
				add_action( 'frm_display_added_fields', array( $this, 'show_formidable_field_admin_field' ) );
				add_action( 'frm_field_options_form', array( $this, 'field_formidable_field_option_form' ), 10, 3 );
				add_action( 'frm_update_field_options', array( $this, 'update_formidable_field_options' ), 10, 3 );
				add_action( 'frm_form_fields', array( $this, 'show_formidable_field_front_field' ), 10, 2 );
				add_action( 'frm_display_value', array( $this, 'display_formidable_field_admin_field' ), 10, 3 );
				add_filter( 'frm_display_field_options', array( $this, 'add_formidable_field_display_options' ) );
				add_filter( 'frmpro_fields_replace_shortcodes', array( $this, 'add_formidable_custom_short_code' ), 10, 4 );
				add_filter( "frm_validate_field_entry", array( $this, "process_validate_frm_entry" ), 10, 3 );
				add_filter( 'frm_field_classes', array( $this, 'process_fields_class' ), 10, 2 );
				add_filter( 'frm_display_' . $this->getSlug() . '_value_custom', array( $this, 'process_replace_value_in_mail' ), 15, 2 );
			}
		}

		/**
		 * Add new field to formidable list of fields
		 *
		 * @param $fields
		 *
		 * @return mixed
		 */
		public function add_formidable_field( $fields ) {
			$fields[ $this->slug ] = array( 'name' => esc_html( $this->name ), 'icon' => $this->icon );

			return $fields;
		}

		/**
		 * @see $this->set_field_options
		 */
		public function set_formidable_field_options( $fieldData ) {
			if ( $fieldData['type'] == $this->getSlug() ) {
				$fieldData['name'] = esc_attr( $this->name );

				foreach ( $this->defaults as $k => $v ) {
					$fieldData['field_options'][ $k ] = $v;
				}

				$this->set_field_options( $fieldData );
			}

			return $fieldData;
		}

		/**
		 * Set the default options for the field
		 *
		 * @param $fieldData
		 *
		 * @return mixed
		 */
		protected function set_field_options( $fieldData ) {

			return $fieldData;
		}

		/**
		 * @see $this->placeholder_admin_field
		 */
		public function show_formidable_field_admin_field( $field ) {
			if ( $field['type'] != $this->getSlug() ) {
				return;
			}
			$description = ( ! empty( $this->description ) ) ? $this->description : __( 'Default placeholder to show into the admin.', 'gfirem_autocomplete-locale' );
			$this->placeholder_admin_field( $field, $description );
		}

		/**
		 * Show the field placeholder in the admin area
		 *
		 * @param $field
		 * @param string $description
		 */
		protected function placeholder_admin_field( $field, $description = '' ) {
			?>
            <div class="frm_html_field_placeholder">
                <div class="frm_html_field"><?php echo esc_html( $description ) ?> </div>
            </div>
			<?php
		}

		/**
		 * @see $this->field_option_form
		 */
		public function field_formidable_field_option_form( $field, $display, $values ) {
			if ( $field['type'] != $this->getSlug() ) {
				return;
			}

			foreach ( $this->defaults as $k => $v ) {
				if ( ! isset( $field[ $k ] ) ) {
					$field[ $k ] = $v;
				}
			}

			$this->inside_field_options( $field, $display, $values );
		}

		/**
		 * Display the additional options for the new field
		 *
		 * @param $field
		 * @param $display
		 * @param $values
		 */
		protected function inside_field_options( $field, $display, $values ) {
			?>
            <tr>
                <td>
                    <label for="field_options[field_option_name_<?php echo esc_attr( $field['id'] ) ?>]"><?php _e( 'Example option', 'gfirem_autocomplete-locale' ) ?></label>
                    <span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="<?php _e( 'This is a formidable tooltip example', 'gfirem_autocomplete-locale' ) ?>"></span>
                </td>
                <td>
                    <input type="checkbox" name="field_options[field_option_name_<?php echo esc_attr( $field['id'] ) ?>]" id="field_options[field_option_name_<?php echo esc_attr( $field['id'] ) ?>]" value="1"/>
                </td>
            </tr>
			<?php
		}

		/**
		 * @see $this->update_field_options
		 */
		public function update_formidable_field_options( $field_options, $field, $values ) {
			if ( $field->type != $this->getSlug() ) {
				return $field_options;
			}

			return $this->update_inside_field_options( $field_options, $field, $values );
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
		protected function update_inside_field_options( $field_options, $field, $values ) {
			foreach ( $this->defaults as $opt => $default ) {
				$field_options[ $opt ] = isset( $values['field_options'][ $opt . '_' . $field->id ] ) ? $values['field_options'][ $opt . '_' . $field->id ] : $default;
			}

			return $field_options;
		}

		/**
		 * @see $this->front_view_field
		 */
		public function show_formidable_field_front_field( $field, $field_name ) {
			if ( $field['type'] != $this->getSlug() ) {
				return;
			}
			$field['value'] = stripslashes_deep( $field['value'] );
			$html_id        = $field['field_key'];
			$this->form_id  = $field['form_id'];
			$this->field_front_view( $field, $field_name, $html_id );
		}

		/**
		 * Add the HTML for the field on the front end
		 *
		 * @param $field
		 * @param $field_name
		 * @param $html_id
		 *
		 */
		protected function field_front_view( $field, $field_name, $html_id ) {
			$print_value = $field['default_value'];
			if ( ! empty( $field['value'] ) ) {
				$print_value = $field['value'];
			}
			?>
            <div class="gfirem_container">
                <input type="text" class="mj_schedule_picker" id="field_<?php echo esc_attr( $html_id ) ?>" name="<?php echo esc_attr( $field_name ) ?>" value="<?php echo esc_attr( $print_value ); ?>" <?php do_action( 'frm_field_input_html', $field ) ?> />
            </div>
			<?php
		}

		/**
		 * @see $this->admin_view_field
		 */
		public function display_formidable_field_admin_field( $value, $field, $attr ) {
			if ( $field->type != $this->getSlug() ) {
				return $value;
			}

			return $this->field_admin_view( $value, $field, $attr );
		}

		/**
		 * Add the HTML to display the field in the admin area
		 *
		 * @param $value
		 * @param $field
		 * @param $attr
		 *
		 * @return string
		 */
		protected function field_admin_view( $value, $field, $attr ) {
			if ( empty( $value ) ) {
				return $value;
			}

			return $value;
		}

		/**
		 * @see $this->display_options
		 */
		public function add_formidable_field_display_options( $display ) {
			if ( $display['type'] == $this->getSlug() ) {
				return $this->display_options( $display );
			}

			return $display;
		}

		/**
		 * Set display option for the field
		 *
		 * @param $display
		 *
		 * @return mixed
		 */
		protected function display_options( $display ) {
			return $display;
		}


		/**
		 * @see $this->process_short_code
		 */
		public function add_formidable_custom_short_code( $replace_with, $tag, $attr, $field ) {
			if ( $field->type != $this->getSlug() ) {
				return $replace_with;
			}

			return $this->process_short_code( $replace_with, $tag, $attr, $field );
		}

		/**
		 * Add custom shortcode
		 *
		 * @param $replace_with
		 * @param $tag
		 * @param $attr
		 * @param $field
		 *
		 * @return string
		 */
		protected function process_short_code( $replace_with, $tag, $attr, $field ) {
			return $replace_with;
		}

		/**
		 * @see $this->validate_frm_entry
		 */
		public function process_validate_frm_entry( $errors, $posted_field, $posted_value ) {
			if ( $posted_field->type != $this->getSlug() ) {
				return $errors;
			}

			return $this->validate_frm_entry( $errors, $posted_field, $posted_value );
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
			return $errors;
		}

		/**
		 * @see $this->fields_class
		 */
		public function process_fields_class( $classes, $field ) {
			if ( $field["type"] == $this->getSlug() ) {
				$classes .= $this->fields_class( $classes, $field );
			}

			return $classes;
		}

		/**
		 * Add class to the field
		 *
		 * @param $classes
		 * @param $field
		 *
		 * @return string
		 */
		protected function fields_class( $classes, $field ) {
			return $classes;
		}

		/**
		 * @param $value
		 * @param $args
		 *
		 * @return string
		 * @see $this->replace_value_in_mail
		 */
		public function process_replace_value_in_mail( $value, $args ) {
			if ( isset( $args['field'] ) && $args['field']->type == $this->getSlug() ) {
				return $this->replace_value_in_mail( $value, $args );
			}

			return $value;
		}

		/**
		 * Replace value in email notifications
		 *
		 * @param $value
		 * @param $args
		 *
		 * @return string
		 */
		public function replace_value_in_mail( $value, $args ) {
			return $value;
		}

		/**
		 * Get the slug of the current field
		 *
		 * @return String
		 */
		public function getSlug() {
			return $this->slug;
		}

		protected function replace_shortcode( $entry, $value, $form_id = '' ) {
			if ( ! empty( $entry ) ) {
				$form_id    = $entry->form_id;
				$shortCodes = FrmFieldsHelper::get_shortcodes( $value, $form_id );
				$content    = apply_filters( 'frm_replace_content_shortcodes', $value, $entry, $shortCodes );
			} else {
				$content = $value;
			}
			FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $content );

			return do_shortcode( $content );
		}
	}
}