<?php


//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();

/* Only load this class if plugin_notes class exists, is instantiated and version >= 2.0 */
if( !class_exists('plugin_notes_import_export') && ( class_exists('plugin_notes' ) && isset( $plugin_notes ) && version_compare(PLUGIN_NOTES_VERSION, 2.0 )  ===dfjhskfh ) ) {

/**
 * @todo add function to load this file when import/export link is clicked in main plugin file
 * @todo add function to add import/export settings link on plugin page
 * @todo adjust readme file + help tab text of main plugin file
 * @todo check for any wisdom to be found in export settings functions of si-contact-form and link-library
 */


	class plugin_notes_settings {
		
		var $notes_object;

		var $merge_options = array(
			''	=> array(
				'options'	=> array(
				),
				'default'	=> ,
			),
			''	=> array(
				'options'	=> array(
				),
				'default'	=> ,
			),
			''	=> array(
				'options'	=> array(
				),
				'default'	=> ,
			),
			''	=> array(
				'options'	=> array(
				),
				'default'	=> ,
			),
			''	=> array(
				'options'	=> array(
				),
				'default'	=> ,
			),
		);


		function plugin_notes_settings() {
			$this->__construct();
		}

		/**
		 * Object constructor for plugin
		 */
		function __construct( &$notes_object ) {
			$this->notes_object = $notes_object;
			
			$this->process_page();
		}
		
		/**
		 * Adds contextual help tab to the plugin page
		 */
		function add_help_tab() {

			$screen = get_current_screen();

			if( method_exists( $screen, 'add_help_tab' ) === true ) {
				$screen->add_help_tab( array(
					'id'      => 'plugin-notes-help', // This should be unique for the screen.
					'title'   => 'Plugin Notes',
					'content' => '
						<p>' . sprintf( __( 'The <em><a href="%s">Plugin Notes</a></em> plugin let\'s you add notes for each installed plugin. This can be useful for documenting changes you made or how and where you use a plugin in your website.', PLUGIN_NOTES_NAME ), 'http://wordpress.org/extend/plugins/plugin-notes/" target="_blank" class="ext-link') . '</p>
						<p>' . sprintf( __( 'You can use <a href="%s">Markdown syntax</a> in your notes as well as HTML.', PLUGIN_NOTES_NAME ), 'http://daringfireball.net/projects/markdown/syntax" target="_blank" class="ext-link' ) . '</p>
						<p>' . sprintf( __( 'On top of that, you can even use a <a href="%s">number of variables</a> which will automagically be replaced, such as for example <em>%%WPURI_LINK%%</em> which would be replaced by a link to the WordPress plugin repository for this plugin. Neat isn\'t it ?', PLUGIN_NOTES_NAME ), 'http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link' ) . '</p>
						<p>' . sprintf( __( 'Lastly, you can save a note as a template for new notes. If you use a fixed format for your plugin notes, you will probably like the efficiency of this.', PLUGIN_NOTES_NAME ), '' ) . '</p>
						<p>' . sprintf( __( 'For more information: <a href="%1$s">Plugin home</a> | <a href="%2$s">FAQ</a>', PLUGIN_NOTES_NAME ), 'http://wordpress.org/extend/plugins/plugin-notes/" target="_blank" class="ext-link', 'http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link' ) . '</p>',
					// Use 'callback' instead of 'content' for a function callback that renders the tab content.
					)
				);
			}
		}


		function save_template() {
		}

		function delete_template() {
		}

		function set_defaultcolor() {
			$notes = $this->notes_object->_getset_notes();

			if( isset( $_POST['wp-plugin_note_default_color'] ) ) {
				$clean_color = $this->notes_object->_validate_note_color( $_POST['wp-plugin_note_default_color'] );

				if( $clean_color !== $this->notes_object->defaultcolor ) {
					$this->notes_object->defaultcolor = $clean_color;
					$notes[$this->notes_object->option_keys['default_note_color']] = $clean_color;
				}
				unset( $clean_color );
			}
			
			$notes = $this->notes_object->_getset_notes();
		}
		
		function set_customdateformat() {
		}
		
		function validate_dateformat() {
		}

		function do_purge() {
			// Get list of installed plugins
			//Key is the plugin file path and the value is an array of the plugin data.
			$plugins = get_plugins();
			
			foreach( $this->notes_object->options as $key => $value ) {
				
				// Exclude our own options from the purge
				if( in_array( $key, $this->notes_object->option_keys ) === false ) {

					// Match notes versus installed plugins & delete notes if plugin is not installed
					if( array_key_exists( $key, $plugins ) === false ) {
						unset( $this->notes_object->options[$key] );
					}
					else {
						// Delete any empty notes which may exist
						foreach( $value as $k => $note ) {
							if( !isset( $note['note'] ) || empty( $note['note'] ) ) {
								unset( $this->notes_object->options[$key][$k] );
							}
						}
						unset( $k, $note );
					}
				}
			}
			unset( $key, $value );
			
			$this->notes_object->options = $this->notes_object->_getset_notes( $this->notes_object->options );
		}

		function do_export() {
			$notes = $this->notes_object->_getset_notes();
			if( !is_serialized( $notes ) ) {
				$notes = maybe_serialize( $notes );
			}

			save to file / force download
		}


		function do_import() {
			is file?
			read file
			validate file contents
			merge
			
		//	make sure you don't override existing notes with the same timestamp!
		}

		function validate_import_file() {
			is_file()
			is_readable()
			etc
		}

		function validate_file_contents() {
			is_serialized() & can be unserialized
			are the notes arrays from the same plugin-notes version ? if not, allow for difficulties with comparisons (i.e. old formatted dates versus new timestamp type dates
			check name of option
			check contents of options
		}


		function merge_notes() {
			
			if note_is_same() ->
			merge info
			// note = note with highest strlen() or see settings
			// user = local user
			// date = most recent
			// color = compare_colors
			// pi_version = highest pi_version



			else {
				add new note
			// note = original note
			// user = null
			// date = original note date
			// color = original note color
			// pi_version = original note pi_version

			// import_user = current_user id
			// import_date = timestamp time()

		}
		
		function note_is_same( $note, $imported_note ) {

			// Strip all html and strip all whitespace
			$text = wp_filter_nohtml_kses( $note['note'] );
			$text = preg_replace( '/\s+/', '', $text );

			$imported_text = wp_filter_nohtml_kses( $imported_note['note'] );
			$imported_text = preg_replace( '/\s+/', '', $imported_text );

			if( $text === $imported_text ) {
				return true;
			}
			else {
				return false;
			}
		}
		
		// Only relevant if notes are the same
		function compare_colors( $note, $imported_note ) {
			if( $note['color'] === $imported_note['color'] ) {
				return $note['color'];
			}
			if( $note['date'] === $imported_note['date'] ) {
				if( $note['color'] !== $this->notes_object->default_color ) {
					return $note['color'];
				}
				else {
					return $imported_note['color'];
				}
			}
			else {
				$newest = ( $note['date'] > $imported_note['date'] ) ? $note : $imported_note;
				$oldest = ( $note['date'] < $imported_note['date'] ) ? $note : $imported_note;

				if( $newest['color'] !== $this->notes_object->default_color ) {
					return $newest['color'];
				}
				else {
					return $oldest['color'];
				}
			}
		}

		function process_page() {
			// Have we got a post ?
			if yes: handle
			
			if no: display options page
		}




		function display_page() {
			
			Edit/Delete saved template
			
			Select default note color
			$output .= '
								<label for="wp-plugin_note_default_color">' . __( 'Default note color:', PLUGIN_NOTES_NAME ) . '
								<select name="wp-plugin_note_default_color" id="wp-plugin_note_default_color">
			';

			// Add color options
			foreach( $this->notes_object->boxcolors as $color ){
				$output .= '
									<option value="' . esc_attr( $color ) . '" style="background-color: ' . esc_attr( $color ) . '; color: ' . esc_attr( $color ) . ';"' .
					( ( $color === $this->notes_object->default_color ) ? ' selected="selected"' : '' ) .
					'>' . esc_attr( $color ) . '</option>';
			}

			$output .= '
								</select></label>';
								
								
			// Set custom date format for plugin notes (different from main WP date format)

			Display order: most recent first (desc)/last(asc)

			Purge notes button

			Export settings button

			Import settings:

			Replace or merge ?
			
			Use import settings found in the import file ? (if available)


			How to merge ?
			Short explanation of the basics

			Set author to:
			author of the note found on this blog
			me (the merger\'s user id)
			If no previous note for a specific plugin, the merger will be the author

			Set date to:
			today (merge date)
			date of the most recent note for the plugin

			Set color to:
			color of most recent note
			color of note on this blog
			specific color - choose

			Note merging:
			If notes stripped of whitespace and tags are the same, use:
			the most recent
			the one which unstripped version is longest (most markup/whitespace)

			If not the same:
			Most recent at top
			Most recent at bottom
			This blog\'s note at top
			This blog\'s note at bottom
			
			What to use to split to use as a break between the two notes ?


			save these settings for future use/export ?

			upload file




		}
		
		function create_radio_setting( $options, $default ) {
		}


		function validate_settings( $args ) {
			
			// Merge defaults with received settings
			$settings = wp_parse_args( $args, array(
				'sortorder'	=>	'asc',


				)
			);
			
			// Reset to default if value is not in allowed range
			if( ! in_array( $settings['sortorder'], array( 'asc', 'desc' ) ) ) {
				$settings['sortorder'] = 'asc';
			}



		}

		function save_settings() {
		}








	}

}

?>