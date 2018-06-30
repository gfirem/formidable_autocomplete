/*
 * @package WordPress
 * @subpackage Formidable, gfirem
 * @author GFireM
 * @copyright 2017
 * @link http://www.gfirem.com
 * @license http://www.apache.org/licenses/
 *
 */

jQuery(document).ready(function ($) {
	/* <fs_premium_only> */
	$(".fma_field").each(function () {
		var current = $(this),
			target_form = current.attr("target_form"),
			field_filter = current.attr("field_filter"),
			field_filter_group = current.attr("field_filter_group"),
			target_field_type = current.attr("target_field_type"),
			target_field_data_target = current.attr("target_field_data_target"),
			loading = $("#autocomplete_loading_" + current.attr("id")),
			target_field = current.attr("target_field"),
			fieldId = current.attr("id"),
			autocomplete_config = {
				serviceUrl: formidable_autocomplete_field.ajaxurl,
				autoSelectFirst: true,
				noCache: formidable_autocomplete_field.config[fieldId].autocomplete_cache === "true",
				showNoSuggestionNotice: formidable_autocomplete_field.config[fieldId].autocomplete_validate === "true",
				noSuggestionNotice: formidable_autocomplete_field.config[fieldId].autocomplete_noResulText,
				params: {'action': 'get_autocomplete_suggestions', '_ajax_nonce': formidable_autocomplete_field.ajaxnonce, 'target_form': target_form, 'target_field': target_field, 'target_field_type': target_field_type, 'target_field_data_target': target_field_data_target},
				onSearchComplete: function (query, suggestions) {
					loading.hide();
					var suggestionChange = true;
					for (var i = 0; i < suggestions.length; i++) {
						if (suggestions[i].value === query) {
							suggestionChange = false;
							break;
						}
					}
					if (suggestionChange) {
						var parentRepeatArgs = current[0].name.replace('item_meta[', '');
						var name = parentRepeatArgs.replace(']', '');
						var actualVal = $("[name='item_meta[" + name + "]']").val();
						clearChildren(name);
					}
				},
				onSearchStart: function (query) {
					var parentRepeatArgs = current[0].name.replace('item_meta[', '');
					var name = parentRepeatArgs.replace(']', '');
					var actualVal = $("[name='item_meta[" + name + "]']").val();
					var fieldIdentifier = current.attr("id");
					var minChars = formidable_autocomplete_field.config[fieldIdentifier].autocomplete_minChars;
					if (query.query.length < minChars) {
						return false;
					}
					if (actualVal !== query.query) {
						clearChildren(name);
					}

					loading.show();
					if (field_filter) {
						var start_field = $("[name='item_meta[" + field_filter + "]']").attr("target_field_data_target");
						query["start_field"] = start_field;
						query["parent_field"] = $("[name='item_meta[" + field_filter + "]']").attr("target_field");
						query["field_filter"] = $("[name='item_meta[" + field_filter + "]']").val();
					}
					if (field_filter_group == "1") {
						query["field_filter_group"] = true;
					}
				},
				onSelect: function (suggestion) {
					checkFieldsWatchingLookup(current.attr("id"), current, 'value change');
					$('#'+current.attr("id")).change();
				}
			};

		if (field_filter_group) {
			autocomplete_config["groupBy"] = "category";
		}

		current.autocomplete(autocomplete_config);
	});

	function clearChildren(parent) {

		$(".fma_field").each(function () {

			var actual = $(this);
			var children = actual.attr("field_filter");
			if (children == parent) {
				actual.val("");
				var newParent = actual[0].name.replace('item_meta[', '');
				var name = newParent.replace(']', '');
				clearChildren(name);

			}

		});

		//Buscamos campos filtrados hijos del campo actual
		for (var key in formidable_autocomplete_field.dependant_fields) {
			var child = formidable_autocomplete_field.dependant_fields[key];
			if (child.fieldId == parent) {
				for (var i = 0; i < child.dependents_id.length; i++) {
					var decendece = child.dependents_id[i];

					$("[name='item_meta[" + decendece + "]']").val("");
				}

			}

		}
		return;

	}

	/*****************************************************
	 * Lookup Field Functions
	 ******************************************************/

	/**
	 * Check all fields that are "watching" a lookup field that changed
	 */
	function checkFieldsWatchingLookup(field_id, changedInput, originalEvent) {
		if (typeof formidable_autocomplete_field.dependant_fields === 'undefined' ||
			typeof formidable_autocomplete_field.dependant_fields[field_id] === 'undefined' ||
			formidable_autocomplete_field.dependant_fields[field_id].length < 1 ||
			changedInput === null ||
			typeof(changedInput) === 'undefined'
		) {
			return;
		}

		var triggerFieldArgs = formidable_autocomplete_field.dependant_fields[field_id];
		var currentTargetField = $("[name='item_meta[" + triggerFieldArgs.fieldId + "]']").attr("target_field");
		var currentTargetForm = $("[name='item_meta[" + triggerFieldArgs.fieldId + "]']").attr("target_form");
		var parentsVal = $("[name='item_meta[" + triggerFieldArgs.fieldId + "]']").val();
		for (var i = 0; i < triggerFieldArgs.dependents.length; i++) {


            var dependant_id = triggerFieldArgs.dependents_id[i];
            var autocomplete_values = [];
            var indexx =0;

            jQuery.ajax({

                type: 'POST', url: frm_js.ajax_url,
                data: {action: 'get_watched_fields', field_id: dependant_id, nonce: frm_js.nonce},
                success: function (newRow) {

                    var newRowValues = $.parseJSON(newRow);
                    var counter = 0;
                    for (var j = 0; j < newRowValues.length; j++) {
                        var currentVal = $("[name='item_meta[" + newRowValues[j] + "]']").val();
                        if (currentVal != '') {
                            autocomplete_values[counter++] = currentVal;
                        }
                    }



                    jQuery.ajax({
                        type: 'GET',
                        url: frm_js.ajax_url,
                        data: {
                            action: 'get_autocomplete_line',
                            parent_fields: currentTargetField,
                            autocomplete_values: autocomplete_values,
                            parent_vals: parentsVal,
                            field_id: triggerFieldArgs.dependents[indexx],
                            target_form: currentTargetForm,
                            index: indexx++,
                            nonce: frm_js.nonce
                        },
                        success: function (newOptions) {
                            //replaceSelectLookupFieldOptions( childFieldArgs.fieldKey, childSelect, newOptions );
                            var newOptionParese = JSON.parse(newOptions);
                            $("[name='item_meta[" + triggerFieldArgs.dependents_id[newOptionParese.index] + "]']").val(newOptionParese.value);
                            $("[name='item_meta[" + triggerFieldArgs.dependents_id[newOptionParese.index] + "]']").change();
                        }
                    });

                }
            })



		}

		var parentRepeatArgs = getRepeatArgsFromFieldName(changedInput[0].name);

		for (var i = 0, l = triggerFieldArgs.length; i < l; i++) {
			updateWatchingFieldById(i, field_id, parentRepeatArgs, originalEvent);
		}
	}

	/**
	 * Update all instances of a "watching" field
	 *
	 * @since 2.01.0
	 * @param {string} field_id
	 * @param {Object} parentRepeatArgs
	 * @param {string} originalEvent
	 */
	function updateWatchingFieldById(index, field_id, parentRepeatArgs, originalEvent) {
		var childFieldArgs = getLookupArgsForSingleField(index, field_id);

		// If lookup field has no parents, no need to update this field
		if (childFieldArgs === false || childFieldArgs.parents.length < 1) {
			return;
		}

		if (childFieldArgs.fieldType == 'autocomplete') {
			updateLookupFieldOptions(childFieldArgs, parentRepeatArgs);
		} else {
			// If the original event was NOT triggered from a direct value change to the Lookup field,
			// do not update the text field value
			if (originalEvent === 'value changed') {
				updateWatchingFieldValue(childFieldArgs, parentRepeatArgs);
			}
		}
	}

	function updateLookupFieldOptions(childFieldArgs, parentRepeatArgs) {
		var childFieldElements = [];
		if (parentRepeatArgs.repeatRow !== '') {
			childFieldElements = getRepeatingFieldDivOnCurrentPage(childFieldArgs, parentRepeatArgs);
		} else {
			childFieldElements = getAllFieldDivsOnCurrentPage(childFieldArgs);
		}

		for (var i = 0, l = childFieldElements.length; i < l; i++) {
			addRepeatRow(childFieldArgs, childFieldElements[i].id);
			updateSingleLookupField(childFieldArgs, childFieldElements[i]);
		}
	}

	/**
	 * Get the div for a repeating field on the current page
	 * @param {Object} childFieldArgs
	 * @param {string} childFieldArgs.fieldId
	 * @param {Object} parentRepeatArgs
	 * @param {string} parentRepeatArgs.repeatingSection
	 * @param {string} parentRepeatArgs.repeatRow
	 * @returns {Array}
	 */
	function getRepeatingFieldDivOnCurrentPage(childFieldArgs, parentRepeatArgs) {
		var childFieldDivs = [];

		var selector = 'frm_field_' + childFieldArgs.fieldId + '-';
		selector += parentRepeatArgs.repeatingSection + '-' + parentRepeatArgs.repeatRow + '_container';
		var container = document.getElementById(selector);
		if (container !== null) {
			childFieldDivs.push(container);
		}

		return childFieldDivs;
	}

	function updateWatchingFieldValue(childFieldArgs, parentRepeatArgs) {
		var childFieldElements = getAllTextFieldInputs(childFieldArgs, parentRepeatArgs);

		for (var i = 0, l = childFieldElements.length; i < l; i++) {
			addRepeatRowForInput(childFieldElements[i].name, childFieldArgs);
			updateSingleWatchingField(childFieldArgs, childFieldElements[i]);
		}
	}

	/**
	 * Get the Lookup Args for a field ID
	 *
	 * @param {string} field_id
	 * @return {boolean|Object}
	 */
	function getLookupArgsForSingleField(index, field_id) {
		if (typeof formidable_autocomplete_field.dependant_fields === 'undefined' || typeof formidable_autocomplete_field.dependant_fields[field_id] === 'undefined') {
			return false;
		}

		return formidable_autocomplete_field.dependant_fields[field_id];
	}

	/**
	 * Update a single Lookup field
	 *
	 * @since 2.01.0
	 * @param {Object} childFieldArgs
	 * @param {string} childFieldArgs.inputType
	 * @param {object} childElement
	 */
	function updateSingleLookupField(childFieldArgs, childElement) {
		childFieldArgs.parentVals = getParentLookupFieldVals(childFieldArgs);

		if (childFieldArgs.inputType == 'select') {
			maybeReplaceSelectLookupFieldOptions(childFieldArgs, childElement);
		} else if (childFieldArgs.inputType == 'radio' || childFieldArgs.inputType == 'checkbox') {
			maybeReplaceCbRadioLookupOptions(childFieldArgs, childElement);
		}
	}

	/**
	 * Update a standard field that is "watching" a Lookup
	 *
	 * @since 2.01.0
	 * @param {Object} childFieldArgs
	 * @param {object} childElement
	 */
	function updateSingleWatchingField(childFieldArgs, childElement) {
		childFieldArgs.parentVals = getParentLookupFieldVals(childFieldArgs);

		maybeInsertValueInFieldWatchingLookup(childFieldArgs, childElement);
	}

	/**
	 * Get all the occurences of a specific Text field
	 *
	 * @since 2.01.0
	 * @param {Object} childFieldArgs
	 * @param {boolean} childFieldArgs.isRepeating
	 * @param {string} childFieldArgs.fieldKey
	 * @param {Object} parentRepeatArgs
	 * @param {string} parentRepeatArgs.repeatingSection
	 * @param {string} parentRepeatArgs.repeatRow
	 * @return {NodeList}
	 */
	function getAllTextFieldInputs(childFieldArgs, parentRepeatArgs) {
		var selector = 'field_' + childFieldArgs.fieldKey;
		if (childFieldArgs.isRepeating) {
			if (parentRepeatArgs.repeatingSection !== '') {
				// If trigger field is repeating/embedded, use its section row in selector
				selector = '[id="' + selector + '-' + parentRepeatArgs.repeatRow + '"]';
			} else {
				// If trigger field is not repeating/embedded, get all repeating field inputs
				selector = '[id^="' + selector + '-"]';
			}
		} else {
			selector = '[id="' + selector + '"]';
		}

		return document.querySelectorAll(selector);
	}

	// Set the value in a regular field that is watching a lookup field when it is conditionally shown
	function maybeSetWatchingFieldValue(input) {
		var fieldId = getFieldId(input, false);

		var childFieldArgs = getLookupArgsForSingleField(fieldId);

		// If lookup field has no parents, no need to update this field
		if (childFieldArgs === false || childFieldArgs.fieldType == 'autocomplete') {
			return;
		}

		updateSingleWatchingField(childFieldArgs, input, 'value changed');
	}

	/**
	 * Get all divs on the current page for a given field
	 *
	 * @since 2.01.0
	 * @param {Object} childFieldArgs
	 * @param {boolean} childFieldArgs.isRepeating
	 * @param {string} childFieldArgs.fieldId
	 * @returns {Array}
	 */
	function getAllFieldDivsOnCurrentPage(childFieldArgs) {
		var childFieldDivs = [];

		if (childFieldArgs.isRepeating) {
			childFieldDivs = document.querySelectorAll('.frm_field_' + childFieldArgs.fieldId + '_container');
		} else {
			var container = document.getElementById('frm_field_' + childFieldArgs.fieldId + '_container');
			if (container !== null) {
				childFieldDivs.push(container);
			}
		}

		return childFieldDivs;
	}

	// Get the field values from all parents
	function getParentLookupFieldVals(childFieldArgs) {
		var parentVals = [];
		var parentIds = childFieldArgs.parents;

		var parentFieldArgs, currentParentId;
		var parentValue = false;
		for (var i = 0, l = parentIds.length; i < l; i++) {
			parentFieldArgs = getLookupArgsForSingleField(parentIds[i]);
			parentValue = getFieldValue(parentFieldArgs, childFieldArgs);

			// If any parents have blank values, don't waste time looking for values
			if (parentValue === '' || parentValue === false) {
				parentVals = false;
				break;
			}

			parentVals[i] = parentValue;
		}

		return parentVals;
	}

	// Get the value from array of radio inputs (could be type="hidden" or type="radio")
	function getValueFromRadioInputs(radioInputs) {
		var radioValue = false;

		var l = radioInputs.length;
		for (var i = 0; i < l; i++) {
			if (radioInputs[i].type == 'hidden' || radioInputs[i].checked) {
				radioValue = radioInputs[i].value;
				break;
			}
		}

		return radioValue;
	}

	/**
	 * Maybe replace the options in a Select Lookup field
	 *
	 * @since 2.01.0
	 * @param {Object} childFieldArgs
	 * @param {Array} childFieldArgs.parents
	 * @param {Array} childFieldArgs.parentVals
	 * @param {string} childFieldArgs.fieldId
	 * @param {string} childFieldArgs.fieldKey
	 * @param {object} childDiv
	 */
	function maybeReplaceSelectLookupFieldOptions(childFieldArgs, childDiv) {
		// Get select within childDiv
		var childSelect = childDiv.getElementsByTagName('SELECT')[0];
		if (childSelect === null) {
			return;
		}

		var currentValue = childSelect.value;

		if (childFieldArgs.parentVals === false) {
			// If any parents have blank values, don't waste time looking for values
			childSelect.options.length = 1;

			if (currentValue !== '') {
				childSelect.value = '';
				maybeUpdateChosenOptions(childSelect);
				triggerChange(jQuery(childSelect), childFieldArgs.fieldKey);
			}
		} else {
			addLoadingTextToLookup(childSelect);

			// If all parents have values, check for updated options
			jQuery.ajax({
				type: 'POST',
				url: frm_js.ajax_url,
				data: {
					action: 'frm_replace_lookup_field_options',
					parent_fields: childFieldArgs.parents,
					parent_vals: childFieldArgs.parentVals,
					field_id: childFieldArgs.fieldId,
					nonce: frm_js.nonce
				},
				success: function (newOptions) {
					replaceSelectLookupFieldOptions(childFieldArgs.fieldKey, childSelect, newOptions);
				}
			});
		}
	}

	// Update chosen options if autocomplete is enabled
	function maybeUpdateChosenOptions(childSelect) {
		if (childSelect.className.indexOf('frm_chzn') > -1 && jQuery().chosen) {
			jQuery(childSelect).trigger('chosen:updated');
		}
	}

	function addLoadingTextToLookup(childSelect) {
		if (!childSelect.value) {
			childSelect.options.length = 1;
			childSelect.options[1] = new Option(frm_js.loading, '', false, false);
		}
	}

	/**
	 * Replace the options in a Select Lookup field
	 *
	 * @since 2.01.0
	 * @param {string} fieldKey
	 * @param {object} childSelect
	 * @param {Array} newOptions
	 */
	function replaceSelectLookupFieldOptions(fieldKey, childSelect, newOptions) {
		var origVal = childSelect.value;

		newOptions = JSON.parse(newOptions);

		// Remove old options
		for (var i = childSelect.options.length; i > 0; i--) {
			childSelect.remove(i);
		}

		// Add new options
		var optsLength = newOptions.length;
		for (i = 0; i < optsLength; i++) {
			childSelect.options[i + 1] = new Option(newOptions[i], newOptions[i], false, false);
		}

		setSelectLookupVal(childSelect, origVal);

		maybeUpdateChosenOptions(childSelect);

		// Trigger a change if the new value is different from the old value
		if (childSelect.value != origVal) {
			triggerChange(jQuery(childSelect), fieldKey);
		}
	}

	// Set the value in a refreshed Lookup Field
	function setSelectLookupVal(childSelect, origVal) {
		// Try setting the dropdown to the original value
		childSelect.value = origVal;
		if (childSelect.value === '') {
			// If the original value is no longer present, try setting to default value
			var defaultValue = childSelect.getAttribute('data-frmval');
			if (defaultValue !== null) {
				childSelect.value = defaultValue;
			}
		}
	}

	/**
	 * Either hide checkbox/radio Lookup field or update its options
	 *
	 * @since 2.01.01
	 * @param {Object} childFieldArgs
	 * @param {object} childDiv
	 */
	function maybeReplaceCbRadioLookupOptions(childFieldArgs, childDiv) {
		if (childFieldArgs.parentVals === false) {
			// If any parents have blank values, don't waste time looking for values

			var inputs = childDiv.getElementsByTagName('input');
			maybeHideRadioLookup(childFieldArgs, childDiv);
			clearValueForInputs(inputs);

		} else {
			replaceCbRadioLookupOptions(childFieldArgs, childDiv);
		}
	}

	/**
	 * Update the options in a checkbox/radio lookup field
	 *
	 * @since 2.01.01
	 * @param {Object} childFieldArgs
	 * @param {string} childFieldArgs.inputType
	 * @param {Array} childFieldArgs.parents
	 * @param {Array} childFieldArgs.parentVals
	 * @param {string} childFieldArgs.fieldId
	 * @param {string} childFieldArgs.repeatRow
	 * @param {string} childFieldArgs.fieldKey
	 * @param {object} childDiv
	 */
	function replaceCbRadioLookupOptions(childFieldArgs, childDiv) {
		var optContainer = childDiv.getElementsByClassName('frm_opt_container')[0];
		var inputs = optContainer.getElementsByTagName('input');

		addLoadingIconJS(childDiv, optContainer);

		var currentValue = '';
		if (childFieldArgs.inputType == 'radio') {
			currentValue = getValueFromRadioInputs(inputs);
		} else {
			currentValue = getValuesFromCheckboxInputs(inputs);
		}

		jQuery.ajax({
			type: 'POST',
			url: frm_js.ajax_url,
			data: {
				action: 'frm_replace_cb_radio_lookup_options',
				parent_fields: childFieldArgs.parents,
				parent_vals: childFieldArgs.parentVals,
				field_id: childFieldArgs.fieldId,
				row_index: childFieldArgs.repeatRow,
				current_value: currentValue,
				nonce: frm_js.nonce
			},
			success: function (newHtml) {
				optContainer.innerHTML = newHtml;

				removeLoadingIconJS(childDiv, optContainer);

				if (inputs.length == 1 && inputs[0].value === '') {
					maybeHideRadioLookup(childFieldArgs, childDiv);
				} else {
					maybeShowRadioLookup(childFieldArgs, childDiv);
				}

				triggerChange(jQuery(inputs[0]), childFieldArgs.fieldKey);
			}
		});
	}

	/**
	 * Hide a Radio Lookup field if it doesn't have any options
	 *
	 * @since 2.01.01
	 * @param {Object} childFieldArgs
	 * @param {string} childFieldArgs.formId
	 * @param {object} childDiv
	 */
	function maybeHideRadioLookup(childFieldArgs, childDiv) {
		if (isFieldConditionallyHidden(childDiv.id, childFieldArgs.formId)) {
			return;
		}

		hideFieldContainer(childDiv.id);
		addToHideFields(childDiv.id, childFieldArgs.formId);
	}

	/**
	 * Show a radio Lookup field if it has options and conditional logic says it should be shown
	 *
	 * @since 2.01.01
	 * @param {Object} childFieldArgs
	 * @param {string} childFieldArgs.formId
	 * @param {string} childFieldArgs.fieldId
	 * @param {string} childFieldArgs.repeatRow
	 * @param {object} childDiv
	 */
	function maybeShowRadioLookup(childFieldArgs, childDiv) {
		if (isFieldCurrentlyShown(childDiv.id, childFieldArgs.formId)) {
			return;
		}

		var logicArgs = getRulesForSingleField(childFieldArgs.fieldId);
		if (logicArgs === false || logicArgs.conditions.length < 1) {
			removeFromHideFields(childDiv.id, childFieldArgs.formId);
			showFieldContainer(childDiv.id);
		} else {
			logicArgs.containerId = childDiv.id;
			logicArgs.repeatRow = childFieldArgs.repeatRow;
			hideOrShowSingleField(logicArgs);
		}
	}

	/**
	 * Get new value for a text field if all Lookup Field parents have a value
	 *
	 * @since 2.01.0
	 * @param {Object} childFieldArgs
	 * @param {string} childFieldArgs.formId
	 * @param {Array} childFieldArgs.parents
	 * @param {Array} childFieldArgs.parentVals
	 * @param {string} childFieldArgs.fieldKey
	 * @param {string} childFieldArgs.fieldId
	 * @param {object} childInput
	 */
	function maybeInsertValueInFieldWatchingLookup(childFieldArgs, childInput) {
		if (isChildInputConditionallyHidden(childInput, childFieldArgs.formId)) {
			// TODO: What if field is in conditionally hidden section?
			return;
		}

		if (childFieldArgs.parentVals === false) {
			// If any parents have blank values, set the field value to the default value
			var newValue = childInput.getAttribute('data-frmval');
			if (newValue === null) {
				newValue = '';
			}
			insertValueInFieldWatchingLookup(childFieldArgs, childInput, newValue);
		} else {
			// If all parents have values, check for a new value
			jQuery.ajax({
				type: 'POST',
				url: frm_js.ajax_url,
				data: {
					action: 'frm_get_lookup_text_value',
					parent_fields: childFieldArgs.parents,
					parent_vals: childFieldArgs.parentVals,
					field_id: childFieldArgs.fieldId,
					nonce: frm_js.nonce
				},
				success: function (newValue) {
					if (childInput.value != newValue) {
						insertValueInFieldWatchingLookup(childFieldArgs.fieldKey, childInput, newValue);
					}
				}
			});
		}
	}

	/**
	 * Insert a new text field Lookup value
	 *
	 * @since 2.01.0
	 * @param {string} fieldKey
	 * @param {object} childInput
	 * @param {string} newValue
	 */
	function insertValueInFieldWatchingLookup(fieldKey, childInput, newValue) {
		childInput.value = newValue;
		triggerChange(jQuery(childInput), fieldKey);
	}

	/**
	 * Add the repeat Row to the child field args
	 *
	 * @since 2.01.0
	 * @param {string} fieldName
	 * @param {Object} childFieldArgs
	 */
	function addRepeatRowForInput(fieldName, childFieldArgs) {
		var repeatArgs = getRepeatArgsFromFieldName(fieldName);

		if (repeatArgs.repeatRow !== '') {
			childFieldArgs.repeatRow = repeatArgs.repeatRow;
		} else {
			childFieldArgs.repeatRow = '';
		}
	}

	function getOriginalEvent(e) {
		var originalEvent;
		if (typeof e.originalEvent !== 'undefined' || e.currentTarget.className.indexOf('frm_chzn') > -1) {
			originalEvent = 'value changed';
		} else {
			originalEvent = 'other';
		}
		return originalEvent;
	}

	// Get the section ID and repeat row from a field name
	function getRepeatArgsFromFieldName(fieldName) {
		var repeatArgs = {repeatingSection: "", repeatRow: ""};

		if (typeof fieldName !== 'undefined' && isRepeatingFieldByName(fieldName)) {
			var inputNameParts = fieldName.split('][');
			repeatArgs.repeatingSection = inputNameParts[0].replace('item_meta[', '');
			repeatArgs.repeatRow = inputNameParts[1];
		}

		return repeatArgs;
	}

	function isRepeatingFieldByName(fieldName) {
		var fieldNameParts = fieldName.split('][');
		return fieldNameParts.length >= 3;
	}

		/* </fs_premium_only> */

    $(".fma_field").on('keyup paste',function () {
        if($(this).val()!=""){
            $(this).siblings("span.clear-button").fadeIn();
        }
        else{
            $(this).siblings("span.clear-button").fadeOut();
        }

    });
    $("span.clear-button").on("click",function (event) {
        event.stopPropagation();
		$(this).siblings(".fma_field").val('');
		$(this).fadeOut();
    } );

});