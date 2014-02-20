/**
 * Number of selected documents
 * 
 * @type {Number}
 */
var tw_lucenesearch_selectedDocuments = 0;

/**
 * (Un)select all index documents in the list
 * 
 * @param {Element} btn			Button
 * @param {Boolean} onOff		Select / unselect
 * @return {void}
 */
function tw_lucenesearch_selectDocuments(btn, onOff) {
	onOff = !!onOff * 1;
	tw_lucenesearch_selectedDocuments = 0;
	for (var e = 0; e < btn.form.elements.length; ++e){ 
		if (btn.form.elements[e].type == 'checkbox') {
			btn.form.elements[e].checked = onOff;
			tw_lucenesearch_selectedDocuments += onOff;
		}
	}
	tw_lucenesearch_updateDeleteButton();
}

/**
 * Select / unselect a single document in the list
 * 
 * @param {Element} cb			Checkbox
 * @return {void}
 */
function tw_lucenesearch_selectDocument(cb) {
	tw_lucenesearch_selectedDocuments += cb.checked ? 1 : -1;
	tw_lucenesearch_updateDeleteButton();
}

/**
 * Update the state of the submit buttons
 * 
 * @return {void}
 */
function tw_lucenesearch_updateDeleteButton() {
	for (var buttons = [document.getElementById('tw_lucenesearch_deleteDocuments'), document.getElementById('tw_lucenesearch_reindexDocuments')], b = 0; b < buttons.length; ++b) {
		buttons[b].disabled = !tw_lucenesearch_selectedDocuments;
		if (!buttons[b].disabled && ('removeAttribute' in buttons[b])) {
			buttons[b].removeAttribute('disabled');
		} else if (buttons[b].disabled && ('setAttribute' in buttons[b])) {
			buttons[b].setAttribute('disabled', 'disabled');
		}
	}
}

/**
 * Delete a specific document
 * 
 * @param {String} doc			Document checkbox ID
 * @return {Boolean}			Success
 */
function tw_lucenesearch_deleteDocument(doc) {
	var cb = document.getElementById(doc);
	if (cb) {
		tw_lucenesearch_selectDocuments(cb, false);
		cb.checked = true;
		return true;
	} else {
		return false;
	}
}

/**
 * Re-index a specific document
 * 
 * @param {String} doc			Document checkbox ID
 * @return {Boolean}			Success
 */
function tw_lucenesearch_reindexDocument(doc) {
	var cb = document.getElementById(doc);
	if (cb) {
		tw_lucenesearch_selectDocuments(cb, false);
		cb.checked = true;
		return true;
	} else {
		return false;
	}
}