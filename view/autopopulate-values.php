<tr>
	<td><label><?php esc_html_e( 'Autocomplete value', 'formidable_autocomplete-locale' ) ?></label></td>
    <td>
	    <label for="fac_autopopulate_value_<?php echo absint( $field['id'] ) ?>">
			<input type="checkbox" value="1" name="field_options[fac_autopopulate_value_<?php echo absint( $field['id'] ) ?>]" <?php checked($field['fac_autopopulate_value'], 1) ?> class="fac_autopopulate_value" id="fac_autopopulate_value_<?php echo absint( $field['id'] ) ?>" />
	        <?php esc_html_e( 'Dynamically retrieve the value from an Autocomplete field', 'formidable_autocomplete-locale' ) ?>
		</label>
	</td>
</tr>
<tr class="frm_fac_autopopulate_value_section_<?php echo absint( $field['id'] ) . esc_attr( $field['fac_autopopulate_value'] ? '' : ' frm_hidden' )?>">
	<td>
		<label><?php esc_html_e( 'Get value from', 'formidable_autocomplete-locale' ) ?></label>
	</td>
	<td><?php
	require( FAC_VIEW_PATH . '/get-options-from.php' );
	?></td>
</tr>
<tr class="frm_fac_autopopulate_value_section_<?php echo absint( $field['id'] ) . esc_attr( $field['fac_autopopulate_value'] ? '' : ' frm_hidden' )?>">
	<td><label><?php esc_html_e( 'Watch Autocomplete fields', 'formidable_autocomplete-locale' ) ?></label></td>
	<td>
	    <a href="javascript:void(0)" id="fac_frm_add_watch_lookup_link_<?php echo absint( $field['id'] ) ?>" class="fac_frm_add_watch_lookup_row frm_add_watch_lookup_link frm_hidden">
			<?php _e( 'Watch Autocomplete fields', 'formidable_autocomplete-locale' ) ?>
		</a>
		<div id="fac_frm_watch_lookup_block_<?php echo absint( $field['id'] ) ?>"><?php
			if ( empty( $field['fac_watch_lookup'] ) ) {
				$field_id = $field['id'];
				$row_key = 0;
				$selected_field = '';
				include( FAC_VIEW_PATH . '/watch-row.php' );
			} else {
				$field_id = $field['id'];
				foreach ( $field['fac_watch_lookup'] as $row_key => $selected_field ) {
					include( FAC_VIEW_PATH . '/watch-row.php' );
				}
			}
		?></div>
	</td>
</tr>