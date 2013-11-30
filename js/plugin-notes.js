jQuery(document).ready(function() {
	
	/*****************************
	 * Settings page interactions
	 *****************************/

	/* Only allow editing of the custom dateformat when use_wp_dateformat is not selected */	 
	jQuery('#plugin_notes_settings_use_wp_dateformat').on('change', function() {
		changeState( jQuery('#plugin_notes_settings_dateformat'), !jQuery('#plugin_notes_settings_use_wp_dateformat').is(':checked') );
	}).change();
	 
	/* Make 'child' checkboxes of export setting available based on parent */
	jQuery('#wp-pn_export-form').on('change', 'input', function() {
		changeState( jQuery('#plugin-notes_export_private'), jQuery('#plugin-notes_export_notes').is(':checked') );
		changeState( jQuery('#plugin-notes_export_template'), jQuery('#plugin-notes_export_settings').is(':checked') );
	}).change();


	/* Ask for confirmation before executing deletion of all notes */
	jQuery('#wp-pn_reset-form').on('submit', 'form', function( event ){
		if( jQuery('#plugin-notes_reset_notes').is(':checked') ) {
			if( !confirm(i18n_plugin_notes.confirm_delete_all) ) {
				event.preventDefault();
			}
		}
	});




	function changeState( elm, enable ) {
		if( enable ) {
            elm.removeAttr('disabled');
            elm.css('color', '#333333');
            elm.parent().css('color', '#333333');
		}
		else {
            elm.removeAttr('checked');
			elm.attr({ 'disabled': 'disabled' });
            elm.css('color', '#888888');
            elm.parent().css('color', '#888888');
		}
	}






	// Collapsible debug information on the settings page
/*	jQuery('.demo_quote_page_demo-quotes-plugin-settings #dqp-debug-info').accordion({
		active: false,
		collapsible: true,
		icons: {
			header: 'ui-icon-circle-triangle-e',
			activeHeader: 'ui-icon-circle-triangle-s'
		},
		heightStyle: 'content'
	});*/
});