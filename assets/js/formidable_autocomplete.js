function gfirem_autocomplete_admin() {

	function hideOrShowAutopopulateValue() {
		var fieldId = this.id.replace('fac_autopopulate_value_', '');
		var sections = document.querySelectorAll('.frm_fac_autopopulate_value_section_' + fieldId);

		var l = sections.length;
		for (var i = 0; i < l; i++) {
			if (this.checked) {
				sections[i].className = sections[i].className.replace('frm_hidden', '');
			} else {
				sections[i].className = sections[i].className + ' frm_hidden';
			}
		}
	}

	function updateGetValueFieldSelection() {
		var fieldID = this.id.replace('fac_get_values_form_', '');
		var fieldSelect = document.getElementById('fac_get_values_field_' + fieldID);
		var fieldType = this.getAttribute('data-fieldtype');

		if (this.value === '') {
			fieldSelect.options.length = 1;
		} else {
			var formID = this.value;
			jQuery.ajax({
				type: 'POST', url: ajaxurl,
				data: {
					action: 'frm_get_options_for_get_values_field',
					form_id: formID,
					field_type: fieldType,
					nonce: frmGlobal.nonce
				},
				success: function (fields) {
					fieldSelect.innerHTML = fields;
				}
			});
		}
	}

	function addWatchLookupRow() {
		var id = jQuery(this).closest('li.form-field').data('fid');
		var form_id = jQuery(this).closest('li.form-field').data('formid')
		var row_key = 0;
		var lookupBlockRows = document.getElementById('fac_frm_watch_lookup_block_' + id).childNodes;

		if (lookupBlockRows.length > 0) {
			var index = lookupBlockRows.length - 1;
			var aux = lookupBlockRows[index];
			var tt = aux.id;
			var lastRowId = lookupBlockRows[index].id;
			if (lastRowId !== undefined) {
				row_key = 1 + parseInt(lastRowId.replace('fac_frm_watch_lookup_' + id + '_', ''));
			}

		}

		jQuery.ajax({
			type: 'POST', url: ajaxurl,
			data: {action: 'get_autocomplete_row', form_id: form_id, field_id: id, row_key: row_key, nonce: frmGlobal.nonce},
			success: function (newRow) {
				jQuery(document.getElementById('frm_add_watch_lookup_link_' + id)).fadeOut('slow', function () {
					var watchRowBlock = jQuery(document.getElementById('fac_frm_watch_lookup_block_' + id));
					watchRowBlock.append(newRow);
					watchRowBlock.fadeIn('slow');
				});
			}
		});
		return false;
	}

	// Clear the Watch Fields option when Lookup field switches to "Text" option
	function maybeClearWatchFields() {
		if (this.value === 'text') {
			var fieldID = this.name.replace('field_options[data_type_', '').replace(']', '');

			var lookupBlock = document.getElementById('fac_frm_watch_lookup_block_' + fieldID);
			if (lookupBlock !== null) {
				// Clear the Watch Fields option
				lookupBlock.innerHTML = '';

				// Hide the Watch Fields row
				lookupBlock.parentNode.parentNode.style.display = 'none';
			}
		}
	}

	function removeThisTag() {
		var deleteButton = jQuery(this);
		var id = deleteButton.data('removeid');
		var show = deleteButton.data('showlast');
		if (typeof(show) === 'undefined') {
			show = '';
		}

		if (id.indexOf('fac_frm_watch_lookup_') === 0) {
			var fieldId = deleteButton.data('fieldid');
			var lookupBlock = document.getElementById('fac_frm_watch_lookup_block_' + fieldId);
			if (lookupBlock.children.length < 2) {
				show = '#fac_frm_add_watch_lookup_link_' + fieldId;
			}
		}

		var $fadeEle = jQuery(document.getElementById(id));
		$fadeEle.fadeOut('slow', function () {
			$fadeEle.remove();
			if (show !== '') {
				jQuery(show + ' a,' + show).fadeIn('slow');
			}

			var action = jQuery(this).closest('.frm_form_action_settings');
			if (typeof action !== 'undefined') {
				var type = jQuery(this).closest('.frm_form_action_settings').find('.frm_action_name').val();
				//checkActiveAction(type);
			}
		});
		if (show !== '')
			jQuery(this).closest('.frm_logic_rows').fadeOut('slow');

		return false;
	}

	function getAutocompleteTarget() {
		var fieldID = this.id.replace('autocomplete_target_form_', '');
		var fieldSelect = document.getElementById('autocomplete_target_field_' + fieldID);
		var fieldType = this.getAttribute('data-fieldtype');

		if (this.value === '') {
			fieldSelect.options.length = 1;
		} else {
			var formID = this.value;
			jQuery.ajax({
				type: 'POST', url: ajaxurl,
				data: {
					action: 'frm_get_options_for_get_values_field',
					form_id: formID,
					field_type: fieldType,
					nonce: frmGlobal.nonce
				},
				success: function (fields) {
					fieldSelect.innerHTML = fields;
				}
			});
		}
	}

	return {
		init: function () {
			if (document.getElementById('frm_build_form') !== null) {
				var $newFields = jQuery(document.getElementById('new_fields'));
				$newFields.on('change', '.fac_autopopulate_value', hideOrShowAutopopulateValue);
				$newFields.on('change', '.fac_frm_get_values_form', updateGetValueFieldSelection);
				$newFields.on('click', '.fac_frm_remove_tag', removeThisTag);
				$newFields.on('click', '.fac_frm_add_watch_lookup_row', addWatchLookupRow);
				$newFields.on('change', 'select[name^="field_options[data_type_"]', maybeClearWatchFields);
				$newFields.on('change', '.autocomplete_get_target', getAutocompleteTarget)
			}
		}
	}

}

jQuery(document).ready(function ($) {
	gfirem_autocomplete_admin().init();
});