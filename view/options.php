<tr class="frm_options_heading">
	<td colspan="2">
		<div class="menu-settings">
			<h3 class="frm_no_bg"><?php _e( "Autocomplete options", "formidable_autocomplete-locale" ); ?></h3>
		</div>
	</td>
</tr>
<tr>
	<td>
		<label for="field_options[autocomplete_target_form_<?php echo $field['id'] ?>]"><?php _e( "Get option from", "formidable_autocomplete-locale" ); ?></label>
	</td>
	<td id="target_td_container">
		<select class="autocomplete_get_target" id="autocomplete_target_form_<?php echo absint( $field['id'] ) ?>" name="field_options[autocomplete_target_form_<?php echo esc_attr( $field['id'] ) ?>]" data-fieldtype="lookup">
			<option value="">&mdash; <?php _e( 'Select Form', 'formidable' ) ?> &mdash;</option>
			<?php foreach ( $lookup_args['form_list'] as $form_opts ) { ?>
				<option value="<?php echo absint( $form_opts->id ) ?>"<?php selected( $form_opts->id, $field['autocomplete_target_form'] ) ?>><?php echo FrmAppHelper::truncate( $form_opts->name, 30 ) ?></option>
			<?php } ?>
		</select>
		<select id="autocomplete_target_field_<?php echo absint( $field['id'] ) ?>" name="field_options[autocomplete_target_field_<?php echo esc_attr( $field['id'] ) ?>]">
			<?php
			FormidableAutoCompleteAdmin::show_options_for_get_values_field( $lookup_args['form_fields'], $field_target );
			?>
		</select>
	</td>
</tr>
<tr>
	<td>
		<label for="field_options[autocomplete_target_filter_<?php echo $field['id'] ?>]"><?php _e( "Select the filter field", "formidable_autocomplete-locale" ); ?></label>
	</td>
	<td>
		<select id="field_options[autocomplete_target_filter_<?php echo esc_attr( $field['id'] ) ?>]" name="field_options[autocomplete_target_filter_<?php echo esc_attr( $field['id'] ) ?>]" data-fieldtype="lookup">
			<option value="">&mdash; <?php _e( 'Select a Field to use as filter', 'formidable_autocomplete-locale' ) ?> &mdash;</option>
			<?php
			foreach ( $fields_for_filter as $fields ) {
				if ( $fields->id != $field["id"] ) {
					?>
					<option value="<?php echo absint( $fields->id ) ?>"<?php selected( $fields->id, $field['autocomplete_target_filter'] ) ?>><?php echo FrmAppHelper::truncate( $fields->name, 30 ) ?></option>
				<?php }
			} ?>
		</select>
	</td>
</tr>
<tr>
	<td>
		<label for="field_options[autocomplete_target_filter_group_<?php echo $field['id'] ?>]"><?php _e( "Group filtered result", "formidable_autocomplete-locale" ); ?></label>
	</td>
	<td>
		<input type="checkbox" <?= $show_filter_group ?> name="field_options[autocomplete_target_filter_group_<?php echo $field['id'] ?>]" id="field_options[autocomplete_target_filter_group_<?php echo $field['id'] ?>]" value="1"/>
	</td>
</tr>