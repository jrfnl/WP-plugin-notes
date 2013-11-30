jQuery(document).ready(function($) {
	
	/**
	 * Automagically add the plugin notes to the plugin update page
	 */
	jQuery('#update-plugins-table tbody.plugins tr').each(function(){
		var slug = jQuery(this).children('th input').val();
		var content = jQuery(this).children('td p');
		
		// get the plugin note code via ajax
//		content.box.after(response.data);
//		jQuery(this).attr( 'target', '_blank' );
	});
	
/*
	<tr class='active'>
		<th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='link-library/link-library.php' /></th>
		<td><p><strong>Link Library</strong><br />Je hebt versie 5.4.9 ge&#239;nstalleerd. Naar versie 5.6.5 bijwerken. <a href="http://vvehosting.nl/wp-admin/plugin-install.php?tab=plugin-information&#038;plugin=link-library&#038;section=changelog&#038;TB_iframe=true&#038;width=640&#038;height=662" class="thickbox" title="Link Library">Details van versie 5.6.5 bekijken</a>.<br />Compatibel met WordPress 3.4.1: 100% (volgens de auteur)<br />Compatibel met WordPress 3.5: Onbekend</p></td>
	</tr>
*/

	attach_handlers();
});



function attach_handlers() {
	/**
	 * Automagically make all links in plugin notes have target="_blank"
	 */
	jQuery('div.wp-pn_text a').each(function(){
		jQuery(this).attr( 'target', '_blank' );
	});

	/**
	 * Add onclick and ondblclick event triggers
	 */
	jQuery('.plugin-version-author-uri, #update-plugins-table tbody.plugins tr td')
	.on('dblclick', '.wp-pn_note', function( event ){
		event.preventDefault();
		edit_plugin_note( event );
		return false;
	});

	jQuery('.plugin-version-author-uri, #update-plugins-table tbody.plugins tr td')
	.on('click', '.wp-pn_actions a.edit, .wp-pn_actions a.add', function( event ){
		event.preventDefault();
		edit_plugin_note( event );
		return false;
	});

	jQuery('.plugin-version-author-uri, #update-plugins-table tbody.plugins tr td')
	.on('click', '.wp-pn_actions a.delete', function( event ){
		event.preventDefault();
		delete_plugin_note( event );
		return false;
	});


	jQuery('.plugin-version-author-uri, #update-plugins-table tbody.plugins tr td')
	.on('click', ".wp-pn_edit_actions input[name='save']", function( event ) {
		event.preventDefault();
		save_plugin_note( event );
		return false;
	});

	jQuery('.plugin-version-author-uri, #update-plugins-table tbody.plugins tr td')
	.on('click', ".wp-pn_edit_actions input[name='cancel']", function( event ) {
		event.preventDefault();
		cancel_plugin_note( event );
		return false;
	});


	jQuery('.plugin-version-author-uri, #update-plugins-table tbody.plugins tr td')
	.on('click', ".wp-pn_edit_actions input[name='templatesave']", function( event ) {
		event.preventDefault();
		templatesave_plugin_note( event );
		return false;
	});

}


function get_plugin_note_elements( event ) {
	var parent_box = jQuery( event.target ).closest('.wp-pn_note_box');
	var elements = {};
	elements.id = jQuery(parent_box).attr('id');
	elements.id = elements.id.slice(6);
	elements.box = parent_box;
	elements.note = parent_box.children('.wp-pn_note');
	elements.form = parent_box.children('.wp-pn_form');
	elements.form.note = elements.form.children('textarea');
	elements.form.istemplate = elements.form.children("[name='wp-pn_new_template_"+elements.id+"']");
	return elements;
}



function edit_plugin_note( click_event ) {
	var note_elms = get_plugin_note_elements( click_event );

	// Hide any previous messages, note, show form and focus on textarea
	jQuery('.wp-pn_result').stop( false, true ).hide('normal');
	note_elms.note.hide('normal');
	note_elms.form.show('normal');
	note_elms.form.note.focus();
}

function delete_plugin_note( click_event ) {
	if(confirm(i18n_plugin_notes.confirm_delete)) {
		var note_elms = get_plugin_note_elements( click_event );
		jQuery('.wp-pn_result').stop( false, true ).hide('normal');
		note_elms.note.find('.waiting').show();
		note_elms.form.note.val('');
		save_plugin_note( click_event );
	}
}

function templatesave_plugin_note( click_event ) {
	if(confirm(i18n_plugin_notes.confirm_template)) {
		var note_elms = get_plugin_note_elements( click_event );
		jQuery('.wp-pn_result').stop( false, true ).hide('normal');
		note_elms.form.istemplate.val('y');
		save_plugin_note( click_event );
	}
}

function cancel_plugin_note( click_event ) {
	var note_elms = get_plugin_note_elements( click_event );
	jQuery('.wp-pn_result').stop( false, true ).hide('normal');
	note_elms.note.show('normal');
	note_elms.form.hide('normal');
}


/*
jQuery(document).ready(function($) {

	var data = {
		action: 'plugin_notes_edit_comment',
		whatever: 1234
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$.post(ajaxurl, data, function(response) {
		alert('Got this from the server: ' + response);
	});
});
*/
function save_plugin_note( click_event ) {
	var note_elms = get_plugin_note_elements( click_event );
	jQuery('.wp-pn_result').stop( false, true ).hide('normal');

	// Get form values
	var _nonce = jQuery('input[name=wp-plugin_notes_nonce]').val();
	var plugin_slug = jQuery('input[name=wp-pn_slug_'+note_elms.id+']').val();
	var plugin_name = jQuery('input[name=wp-pn_name_'+note_elms.id+']').val();
	var note_key = jQuery('input[name=wp-pn_key_'+note_elms.id+']').val();
	var plugin_note_color = jQuery('#wp-pn_color_'+note_elms.id).val();
	var plugin_new_template = note_elms.form.istemplate.val();
	var plugin_note = note_elms.form.note.val();
	
	// Show waiting container
	note_elms.form.find('.waiting').show();

	// Prepare data
	var post = {};
	post.action = 'plugin_notes_edit_comment'; /*ok*/
	post.plugin_name = plugin_name;
	post.plugin_note = plugin_note;
	post.plugin_slug = plugin_slug;
	post.note_key = note_key;
	post.plugin_note_color = plugin_note_color;
	post.plugin_new_template = plugin_new_template;
	post._pluginnotes_nonce = _nonce;

/*	alert(
		'post.plugin_name =' + plugin_name +"\r\n"+
		'post.plugin_note =' + plugin_note +"\r\n"+
		'post.plugin_slug =' + plugin_slug +"\r\n"+
		'post.note_key =' + note_key +"\r\n"+
		'post.plugin_note_color =' + plugin_note_color +"\r\n"+
		'post.plugin_new_template =' + plugin_new_template

	);
*/
	// Send the request
	jQuery.ajax({
		type : 'POST',
		url : (ajaxurl) ? ajaxurl : i18n_plugin_notes.ajaxurl,
		data : post,
		success : function(xml) { plugin_note_saved(xml, note_elms); },
		error : function(xml) { plugin_note_error(xml, note_elms); }
	});

	return false;
}

function show_props(obj, objName) {
   var result = ""
   for (var i in obj) {
      result += objName + "." + i + " = " + obj[i] + "\n"
   }
   return result
}

function plugin_note_saved ( xml, note_elms ) {
	var response;
var string = '';
	// Uh oh, we have an error
	if ( typeof(xml) == 'string' ) {
string += show_props( xml, 'xml' );
alert( string );
		plugin_note_error({'responseText': xml}, note_elms);
		return false;
	}

	// Parse the response
	response = wpAjax.parseAjaxResponse(xml);
string += show_props( response, 'response' );
	if ( response.errors ) {
		// Uh oh, errors found
		plugin_note_error({'responseText': wpAjax.broken}, note_elms);
		return false;
	}

	response = response.responses[0];
string += show_props( response, 'response[0]' );
string += show_props( response.supplemental, 'supplemental' );
alert( string );
	// Add/Delete new content
	note_elms.box.find('.waiting').hide();
	
	// Add regenerated nonce to the form
//	jQuery('input[name=wp-plugin_notes_nonce]').val(response.suppemental.new_nonce);
	
	/**
	 * Update the plugin note after edit/delete action
	 */
	if(response.action.indexOf('edit') == 0 ) {
		note_elms.box.after(response.data);
		note_elms.box.remove();
		note_elms.form.hide('normal');

		jQuery('#wp-pn_'+response.id+' div.wp-pn_text a').each(function(){
			jQuery(this).attr( 'target', '_blank' );
		});

		jQuery('#wp-pn_'+response.id).find( '.wp-pn_result' )
			.removeClass( 'error' )
			.addClass( 'success' )
			.html( i18n_plugin_notes.success_editsave )
			.show( 'highlight', 'easeInCubic', '2000' )
			.delay( 5000 )
			.hide( 'fade', 'easeInCubic', '1500');
	}
	else if(response.action.indexOf('delete') == 0 ) {
		note_elms.form.hide();
		note_elms.note.hide();
		note_elms.box.removeAttr('style');
		note_elms.box.removeClass();

		note_elms.box.find( '.wp-pn_result' )
			.removeClass( 'error' )
			.addClass( 'success' )
			.html( i18n_plugin_notes.success_delete )
			.show( 'highlight', 'easeInCubic', '2000' )
			.delay( 2000 )
			.hide( 'fade', 'easeInCubic', '1500', function(){
				note_elms.box.remove();
			});
	}
	/**
	 * Update *all* empty notes with the new template after save_template action
	 * Reset the save_template switch
	 * Display success message
	 */
	else if(response.action.indexOf('save_template') == 0 ) {
		jQuery('textarea.new_note').each(function(){
			jQuery(this).val( note_elms.form.note.val() );
		});
		note_elms.form.istemplate.val('n');

		note_elms.box.find( '.wp-pn_result' )
			.removeClass( 'error' )
			.addClass( 'success' )
			.html( i18n_plugin_notes.success_save_template )
			.show( 'highlight', 'easeInCubic', '2000' )
			.delay( 5000 )
			.hide( 'fade', 'easeInCubic', '1500');
	}
	else {
		plugin_note_error({'responseText': wpAjax.broken}, note_elms);
	}
}

function plugin_note_error ( xml, note_elms ) {
	note_elms.box.find('.waiting').hide();
	// Add regenerated nonce to the form
//	jQuery('input[name=wp-plugin_notes_nonce]').val(response.suppemental.new_nonce);

	if ( xml.responseText ) {
		if( xml.responseText == '-1' ) {
			error = i18n_plugin_notes.error_nonce;
		}
		else if( xml.responseText == '0' ) {
			error = i18n_plugin_notes.error_loggedin;
		}
		else {
			error = xml.responseText.replace( /<.[^<>]*?>/g, '' );
		}
	} else {
		error = xml;
	}
	if ( error ) {
		note_elms.box.find( '.wp-pn_result' )
			.removeClass( 'success' )
			.addClass( 'error' )
			.html( error )
			.show( 'highlight', 'easeInCubic', '2000' )
			.delay( 15000 )
			.hide( 'fade', 'easeInCubic', '1500');
	}
	return false;
}
