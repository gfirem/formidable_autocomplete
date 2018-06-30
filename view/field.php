<div class="autocomplete-conatiner">
    <input type="text" field_filter_group="<?php echo esc_attr( $field_filter_group ) ?>" <?php echo "$placeholder" ?> target_field_type="<?php echo esc_attr( $target_field_type ) ?>" field_filter="<?php echo esc_attr( $field_filter_str ) ?>" target_field_data_target="<?php echo esc_attr( $target_field_data_target ) ?>" target_field="<?php echo esc_attr( $target_field ) ?>" target_form="<?php echo esc_attr( $target_form ) ?>" id="field_<?php echo esc_attr( $html_id ) ?>" name="<?php echo esc_attr( $field_name ) ?>" value="<?php echo esc_attr( $value ); ?>" <?php do_action( 'frm_field_input_html', $field ) ?> /><span class="clear-button">x</span>
    <div class="autocomplete-suggestions"></div>
    <div class="autocomplete-loading-container">
        <img id="autocomplete_loading_field_<?php echo esc_attr( $html_id ) ?>" class="autocomplete-loading" src="<?php echo esc_url( $loading_url ); ?>">
    </div>
</div>