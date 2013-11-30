/**
 * Automagically make all links in plugin notes have target="_blank"
 */
jQuery(document).ready(function($) {
		jQuery('div.wp-plugin_note_text a').each(function(){
			jQuery(this).attr( 'target', '_blank' );
		});
});

/*function add_plugin_note( plugin_slug, plugin_elm_name ) {
	edit_plugin_note(plugin_slug, plugin_elm_name);
}
*/
function edit_plugin_note( plugin_elm_name ) {
	var note_elements = get_plugin_note_elements(plugin_elm_name);

	// Hide note, show form and focus on textarea
	note_elements.box.hide('normal');
	note_elements.form.show('normal');
	note_elements.form.input.focus();
}

function delete_plugin_note( plugin_elm_name ) {
	if(confirm(i18n_plugin_notes.confirm_delete)) {
		var note_elements = get_plugin_note_elements(plugin_elm_name);
		note_elements.box.find('.waiting').show();
		note_elements.form.input.val('');
		save_plugin_note( plugin_elm_name );
	}
}

function templatesave_plugin_note( plugin_elm_name ) {
	if(confirm(i18n_plugin_notes.confirm_template)) {
		var template_switch = document.getElementById('wp-plugin_note_new_template_'+plugin_elm_name);
		template_switch.value = 'y';
		save_plugin_note( plugin_elm_name );
	}
}

function cancel_plugin_note( plugin_elm_name ) {
	var note_elements = get_plugin_note_elements(plugin_elm_name);
	note_elements.box.show('normal');
	note_elements.form.hide('normal');
}

function save_plugin_note( plugin_elm_name ) {
	var note_elements = get_plugin_note_elements(plugin_elm_name);
	// Get form values
	var _nonce = jQuery('input[name=wp-plugin_notes_nonce]').val();
	var plugin_slug = jQuery('input[name=wp-plugin_note_slug_'+plugin_elm_name+']').val();
	var plugin_name = jQuery('input[name=wp-plugin_note_name_'+plugin_elm_name+']').val();
	var plugin_key = jQuery('input[name=wp-plugin_note_key_'+plugin_elm_name+']').val();
	var plugin_note_color = jQuery('#wp-plugin_note_color_'+plugin_elm_name).val();
	var plugin_new_template = jQuery('input[name=wp-plugin_note_new_template_'+plugin_elm_name+']').val();
	var plugin_note = note_elements.form.input.val();
	
	// Show waiting container
	note_elements.form.find('.waiting').show();

	// Prepare data
	var post = {};
	post.action = 'plugin_notes_edit_comment';
	post.plugin_name = plugin_name;
	post.plugin_note = plugin_note;
	post.plugin_slug = plugin_slug;
	post.plugin_key = plugin_key;
	post.plugin_note_color = plugin_note_color;
	post.plugin_new_template = plugin_new_template;
	post._nonce = _nonce;

	// Send the request
	jQuery.ajax({
		type : 'POST',
		url : (ajaxurl) ? ajaxurl : 'admin-ajax.php',
		data : post,
		success : function(xml) { plugin_note_saved(xml, note_elements); },
		error : function(xml) { plugin_note_error(xml, note_elements); }
	});

	return false;
}

function plugin_note_saved ( xml, note_elements ) {
	var response;
						
	// Uh oh, we have an error
	if ( typeof(xml) == 'string' ) {
		plugin_note_error({'responseText': xml}, note_elements);
		return false;
	}
	
	// Parse the response
	response = wpAjax.parseAjaxResponse(xml);
	
	if ( response.errors ) {
		// Uh oh, errors found
		plugin_note_error({'responseText': wpAjax.broken}, note_elements);
		return false;
	}
	
	response = response.responses[0];
	
	// Add/Delete new content
	note_elements.form.find('.waiting').hide();
	
	/**
	 * Update the plugin note after edit/delete action
	 */
	if(response.action.indexOf('save_template') == -1 ) {
		note_elements.box.parent().after(response.data);
		note_elements.box.parent().remove();
		note_elements.form.hide('normal');
		
		jQuery('#wp-plugin_note_'+note_elements.name+' div.wp-plugin_note_text a').each(function(){
			jQuery(this).attr( 'target', '_blank' );
		});
	}
	/**
	 * Update *all* empty notes with the new template after save_template action
	 * Reset the save_template switch
	 * Display success message
	 */
	else {
		jQuery('textarea.new_note').each(function(){
			jQuery(this).val( note_elements.form.input.val() );
		});
		var template_switch = document.getElementById('wp-plugin_note_new_template_'+note_elements.name);
		template_switch.value = 'n';

		note_elements.form.find('span.success').html(i18n_plugin_notes.success_save_template).show().parent().show();
	}
}

function plugin_note_error ( xml, note_elements ) {
	note_elements.form.find('.waiting').hide();
	if ( xml.responseText ) {
		error = xml.responseText.replace( /<.[^<>]*?>/g, '' );
	} else {
		error = xml;
	}
	if ( error ) {
		note_elements.form.find('span.error').html(error).show().parent().show();
	}
}

function get_plugin_note_elements(name) {
	var elements = {};
	elements.name = name;
	elements.box = jQuery('#wp-plugin_note_'+name);
	elements.form = jQuery('#wp-plugin_note_form_'+name);
	elements.form.input = elements.form.children('textarea');
	return elements;
}

/*function localize_text() {
}*/


/*
i18n_plugin_notes.
			$strings = array(
				'confirm_delete'	=> esc_js( __( 'Are you sure you want to delete this note?', PLUGIN_NOTES_NAME ) ),
				'confirm_template'	=> esc_js( __( 'Are you sure you want to save this note as a template?\n\rAny changes you made will not be saved to this particular plugin note.\n\r\n\rAlso beware: saving this note as the plugin notes template will overwrite any previously saved templates!', PLUGIN_NOTES_NAME ) ),
				'success_save_template'	=> esc_js( __( 'New notes template saved succesfully', PLUGIN_NOTES_NAME ) ),
				'success_editsave'	=> esc_js( __( 'Your changes have been saved succesfully', PLUGIN_NOTES_NAME ) ),
				'success_delete'	=> esc_js( __( 'Note deleted', PLUGIN_NOTES_NAME ) ),
				'error_nonce'		=> esc_js( __('Don\'t think you\'re supposed to be here...', PLUGIN_NOTES_NAME ) ),
				'error_loggedin'	=> esc_js( __( 'Your session seems to have expired. You need to log in again.', PLUGIN_NOTES_NAME ) ),
				'error_capacity'	=> esc_js( __( 'Sorry, you do not have permission to activate plugins.', PLUGIN_NOTES_NAME ) ),
			);

i18n_plugin_notes.button_....
			$this->buttons = array(
				'add_new_note'	=>	__( 'Add plugin note', PLUGIN_NOTES_NAME ),
				'edit_note'		=>	__( 'Edit_note', PLUGIN_NOTES_NAME ),
				'delete_note'	=>	__( 'Delete note', PLUGIN_NOTES_NAME ),
				'save_note'		=>	__( 'Save' ),
				'cancel_edit'	=>	__( 'Cancel' ),
				'save_as_template'	=>	__( 'Save as template for new notes', PLUGIN_NOTES_NAME ),
				'title_doubleclick'	=>	__( 'Double click to edit me!', PLUGIN_NOTES_NAME ),
				'title_loading'	=>	__( 'Loading...', PLUGIN_NOTES_NAME ),
			);
*/