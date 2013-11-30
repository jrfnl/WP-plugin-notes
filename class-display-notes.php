<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Plugin_Notes' ) && ! class_exists( 'Plugin_Notes_Display_Notes' ) ) {
	/**
	 * @package		WordPress\Plugins\Plugin Notes
	 * @subpackage	Display Notes
	 * @version		2.0
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 *
	 * @copyright	2013 Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Plugin_Notes_Display_Notes {
		

		public static $nonce_added = false;

		/**
		 * Temporary properties, get set and unset for each plugin row and hold the info relevant to that row
		 */
		public static $plugin_file;
//		public static $plugin_meta;
		public static $plugin_data;
		public static $plugin_notes;




	/**
		 * Abuse a filter to add out plugin notes to a plugin row
		 *
		 * Adds a nonce to the plugin page so we don't get nasty people doing nasty things
		 * and start adding the notes
		 *
		 * @static
		 * @param	array	$plugin_meta	Plugin meta data
		 * @param	string	$plugin_file	Plugin file name in the form dir/filename.php
		 *									[ex: 'wp-security-scan/index.php']
		 * @param	array	$plugin_data	Plugin data
		 * @param	string	$context		Plugin page context [ex: 'all' / 'active' / 'mustuse' / 'upgrade']
		 * @return	array	$plugin_meta (unchanged)
		 */
		public static function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $context ) {
pr_var( array(
 'plugin_meta' => $plugin_meta,
 'plugin_file'	=> $plugin_file,
 'plugin_data'	=> $plugin_data,
 'context'		=> $context,
), 'received vars', true );

			self::$plugin_file  = $plugin_file;
//			self::$plugin_meta  = $plugin_meta;
			self::$plugin_data  = apply_filters( 'plugin_notes_plugin_data', $plugin_data, $plugin_file );
			self::$plugin_notes = isset( Plugin_Notes_Manage_Notes_Option::$current[$plugin_file] ) ? Plugin_Notes_Manage_Notes_Option::$current[$plugin_file] : null;


			if ( self::$nonce_added !== true ) {
				wp_nonce_field( Plugin_Notes::PREFIX . 'nonce', Plugin_Notes::PREFIX . 'nonce', true, true );
				self::$nonce_added = true;
			}

			self::display_notes( true );

			self::$plugin_file  = null;
//			self::$plugin_meta  = null;
			self::$plugin_data  = null;
			self::$plugin_notes = null;

			return $plugin_meta;
		}
		



		
		public static function display_notes( $echo = true ) {

			$output = '';

			if ( isset( self::$plugin_notes ) && ( is_array( self::$plugin_notes ) && self::$plugin_notes !== array() ) ) {
				// Add note blocks
				foreach ( self::$plugin_notes as $note ) {
					// Only add block if we actually have a note
/*					if ( ( is_array( $note ) && $note !== array() ) && $note['note'] !== '' ) {
						$output .= $this->_add_plugin_note( $note, $plugin_data, $plugin_file, false );
					}
*/
				$output .= self::display_single_note( $note, false );
				}
				unset( $note );
			}
			// Always add 'add new note link' + 'empty' form (with template)
//			$output .= self::display_single_note( null, false );
			
			if ( $echo === true ) {
				echo $output;
			}
			else {
				return $output;
			}
		}


		public static function display_single_note( $note, $echo = true ) {
			if ( !is_array( $note ) || ( is_array( $note ) && ( $note === array() || ( $note['note'] === '' && $note['title'] === '' ) ) ) ) {
				// Nothing to do, break early
				return '';
			}
			
			if ( ( isset( $note['private'] ) && $note['private'] === true ) && ( isset( $note['user_id'] ) && $note['user_id'] !== get_current_user_id() ) ) {
				// Current user is not allowed to see private note
				return '';
			}


		}
		
/*		public static function get_note_html() {
		}

		public static function get_form_html() {
		}
*/


/*		public static function display_blank_note() {
		}

		public static function display_add_new() {
		}
*/



		
/*
received vars
Array:
(
        [plugin_meta (string)] => Array:
        (
                [0 (int)] => string[13] : �Versie: 4.0.3�
                [1 (int)] => string[89] : �Door <a href="http://www.acunetix.com/" title="Bezoek homepagina van auteur">Acunetix</a>�
                [2 (int)] => string[126] : �<a href="http://www.acunetix.com/websitesecurity/wordpress-security-plugin/" title="Bezoek plugin site">Bezoek plugin site</a>�
        )

        [plugin_file (string)] => string[26] : �wp-security-scan/index.php�

        [plugin_data (string)] => Array:
        (
                [Name (string)] => string[20] : �Acunetix WP Security�
                [PluginURI (string)] => string[66] : �http://www.acunetix.com/websitesecurity/wordpress-security-plugin/�
                [Version (string)] => string[5] : �4.0.3�
                [Description (string)] => string[233] : �The Acunetix WP Security plugin is the ultimate must-have tool when it comes to WordPress security. The plugin is free and monitors your website for security weaknesses that hackers might exploit and tells you how to easily fix them.�
                [Author (string)] => string[8] : �Acunetix�
                [AuthorURI (string)] => string[24] : �http://www.acunetix.com/�
                [TextDomain (string)] => string[14] : �WSDWP_SECURITY�
                [DomainPath (string)] => string[10] : �/languages�
                [Network (string)] => bool : ( = false )
                [Title (string)] => string[20] : �Acunetix WP Security�
                [AuthorName (string)] => string[8] : �Acunetix�
        )

        [context (string)] => string[3] : �all�
        // [context (string)] => string[6] : �active�
        // [context (string)] => string[7] : �upgrade�
        // [context (string)] => string[7] : �mustuse�
)
*/


		
		/**
		 * Add a class to the version number to indicate the age of the note compared to the current plugin version
		 *
		 * Older is only possible is someone has downgraded a plugin or for imported notes
		 *
		 * @static
		 * @param	string	$pi_version		Version number of the plugin when the note was written
		 * @param	string	$real_version	Current version number for the plugin
		 * @return	string
		 */
		public static function filter_age_pi_version( $pi_version, $real_version ) {
			$version_age   = version_compare( $pi_version, $real_version );
			$version_class = ( ( $version_age === 0 ) ? 'same_version' : ( ( $version_age === -1 ) ? 'older_version' : 'newer_version' ) );
			return '<span class="' . Plugin_Notes::PREFIX . $version_class . '">' . esc_html( $pi_version ) . '</span>';
		}


		/**
		 * Applies the wp_kses html filter to the note string or removes all html if no html allowed
		 *
		 * @static
		 * @param	string	$note_text
		 * @return	string
		 */
		public static function filter_html( $note_text ) {
			if ( Plugin_Notes_Manage_Settings_Option::$current['allow_html'] === true && ( is_array( Plugin_Notes::$allowed_tags ) && Plugin_Notes::$allowed_tags !== array() ) ) {
				return wp_kses( $note_text, $allowed_tags );
			}
			else {
				return wp_filter_nohtml_kses( $note_text );
			}
		}
		


		/**
		 * Adds additional line breaks to the note string
		 *
		 * @static
		 * @param	string	$note_text
		 * @return	string
		 */
		public static function filter_breaks( $note_text ) {
			return wpautop( $note_text );
		}


		/**
		 * Applies markdown syntax filter to the note string
		 *
		 * @static
		 * @param	string	$note_text
		 * @return	string
		 */
		public static function filter_markdown( $note_text ) {
			return Markdown( $note_text );
		}


		/**
		 * Replaces a number of variables in the note string
		 *
		 * @static
		 * @param	string	$note_text
		 * @return	string
		 */
		public static function filter_variables_replace( $note_text ) {
			// Return early if there is nothing to do
			// @todo - figure out a way to deal with using this filter on new notes saved via ajax
			// where $plugin_file and $plugin_data will not be available (yet) - or will they ?
			// -> May be set them in the 'mother' function used to display after an ajax call
			if ( ( ! isset( self::$plugin_file ) || ! isset( self::$plugin_data ) ) || preg_match( '/[%][A-Z_]+[%]/u', $note_text ) < 1 ) {
				return $note_text;
			}

			$find = array(
				'%NAME%',			// Name of the plugin
				'%PLUGIN_PATH%',	// Path to the plugin on this webserver
				'%URI%',			// URI of the plugin website
				'%WPURI%',			// URI of the WordPress repository of the plugin (might not exist)
				'%WPURI_LINK%',		// HTML-string link for the WPURI
				'%AUTHOR%',			// Author name
				'%AUTHORURI%',		// URI of the Author's website
				'%VERSION%',		// Plugin version number
				'%DESCRIPTION%',	// Plugin description
			);
			// Available plugindata variables are always set, but sometimes empty
			$replace = array(
				esc_html( self::$plugin_data['Name'] ),
//					esc_html( plugins_url() . '/' . plugin_dir_path( $plugin_file ) ),
//					esc_html( plugins_url( '', plugin_dir_path( $plugin_file ) ),
				esc_html( plugin_dir_path( self::$plugin_file ) ),
				( ( self::$plugin_data['PluginURI'] !== '' ) ? esc_url( self::$plugin_data['PluginURI'] ) : '' ),
				esc_url( 'http://wordpress.org/plugins/' . substr( self::$plugin_file, 0, strpos( self::$plugin_file, '/' ) ) ),
				'<a href="' . esc_url( 'http://wordpress.org/plugins/' . substr( self::$plugin_file, 0, strpos( self::$plugin_file, '/' ) ) ) . '" target="_blank">' . esc_html( self::$plugin_data['Name'] ) . '</a>',
				( ( self::$plugin_data['Author'] !== '' ) ? esc_html( wp_kses( self::$plugin_data['Author'], array() ) ) : '' ),
				( ( self::$plugin_data['AuthorURI'] !== '' ) ? esc_html( self::$plugin_data['AuthorURI'] ) : '' ),
				( ( self::$plugin_data['Version'] !== '' ) ? esc_html( self::$plugin_data['Version'] ) : '' ),
				( ( self::$plugin_data['Description'] !== '' ) ? esc_html( self::$plugin_data['Description'] ) : '' ),
			);

			return str_replace( $find, $replace, $note_text );
		}
		
/*
        [plugin_data (string)] => Array:
        (
                [Name (string)] => string[20] : �Acunetix WP Security�
                [PluginURI (string)] => string[66] : �http://www.acunetix.com/websitesecurity/wordpress-security-plugin/�
                [Version (string)] => string[5] : �4.0.3�
                [Description (string)] => string[233] : �The Acunetix WP Security plugin is the ultimate must-have tool when it comes to WordPress security. The plugin is free and monitors your website for security weaknesses that hackers might exploit and tells you how to easily fix them.�
                [Author (string)] => string[8] : �Acunetix�
                [AuthorURI (string)] => string[24] : �http://www.acunetix.com/�
                [TextDomain (string)] => string[14] : �WSDWP_SECURITY�
                [DomainPath (string)] => string[10] : �/languages�
                [Network (string)] => bool : ( = false )
                [Title (string)] => string[20] : �Acunetix WP Security�
                [AuthorName (string)] => string[8] : �Acunetix�
        )
*/

/*
 <?php $path = plugin_dir_path( $file ); ?>
Parameters

$file
    (string) (required) A full file path (e.g. __FILE__).

        Default: None 

Return Values

(string) 
    The absolute path of the directory that contains the file, with trailing slash. 

Examples

Get the directory of the current file:

$dir = plugin_dir_path( __FILE__ );

// Example: /home/user/var/www/wordpress/wp-content/plugins/my-plugin/

Including all PHP files from a plugin sub folder and avoiding adding a unnecessary global just to determine a path that is already available everywhere just using WP core functions.

foreach ( glob( plugin_dir_path( __FILE__ )."subfolder/*.php" ) as $file )
    include_once $file;



<?php plugins_url( $path, $plugin ); ?>
Default Usage

<?php $url = plugins_url(); ?>
Parameters

$path
    (string) (optional) Path to the plugin file of which URL you want to retrieve, relative to the plugins directory or to $plugin if specified.

        Default: None 

$plugin
    (string) (optional) Path under the plugins directory of which parent directory you want the $path to be relative to.

        Default: None 

Return Values

(string) 
    Absolute URL to the plugins directory (without the trailing slash) or optionally to a file under that directory. 

Example
Default Usage

<?php
$plugins_url = plugins_url();
?>

The $plugins_url variable will equal to the absolute URL to the plugins directory, e.g. "http://www.example.com/wp-content/plugins".
Common Usage

The plugins_url() function is commonly used in a plugin file. Passing the __FILE__ PHP magic constant in the place of $plugin parameter makes the $path relative to the parent directory of that file:

<?php
echo '<img src="' . plugins_url( 'images/wordpress.png' , __FILE__ ) . '" > ';
?>

The above might ouput this HTML markup: <img src="http://www.example.com/wp-content/plugins/my-plugin/images/wordpress.png">.

If you are using the plugins_url() function in a file that is nested inside a subdirectory of your plugin directory, you should use PHP's dirname() function:

<?php
echo '<img src="' . plugins_url( 'images/wordpress.png' , dirname(__FILE__) ) . '" > ';
*/



		/**
		 * Outputs pluging note for the specified plugin
		 */
		function _add_plugin_note( $note = null, $plugin_data, $plugin_file, $echo = true ) {

			$plugin_data = $this->_getremember_plugin_data( $plugin_file, $plugin_data );

//pr_var( $note, 'note as received from db', true );
			// Merge defaults with received note
			$note = wp_parse_args( $note, array(
//				'new'				=> true,
				'name'				=> Plugin_Notes_Manage_Notes_Option::validate_plugin_name( $plugin_data['Name'] ),
//				'class'				=> 'wp-plugin_note_box_blank',
				'class'				=> 'wp-pn_note_box blank',
				'title'				=> null,
				'note'				=> null,
//				'filtered_note'		=> null,
				'user'				=> null,
//				'username'			=> null,
				'date'				=> null,
//				'formatted_date'	=> null,
				'original_date'		=> null,
				'color'				=> $this->settings['defaultcolor'],
//				'style'				=> null,
				'pi_version'		=> null,
//				'actions'			=> array(),
				'import_date'		=> null,
//				'import_formatted_date'	=> null,
				'import_user'		=> null,
//				'import_username'	=> null,
				'private'			=> false,
				'export_exclude'	=> false,
				)
			);
			
			$attr_key = ( is_null( $note['date'] ) ? '' : '_' . esc_attr( $note['date'] ) );

			$filtered_note  = null;
			$filtered_title = null;
			$actions        = array();
//			$author = null;
//			$formatted_date = null;
//			$importer = null;
//			$formatted_import_date = null;

			$credits = array();


//pr_var( $note, 'note after data merge', true );
			if ( !is_array( $note ) || count( $note ) === 0 || empty( $note['note'] ) ) {
//				$note['actions'][] = 'add';
				$actions[] = 'add';
			}
			// Prep some data for display
			else {
//				$note['new'] = false;
				$note['class']  = 'wp-pn_note_box';
				$filtered_note  = apply_filters( 'plugin_notes_note_text', $note['note'] );
				$filtered_title = apply_filters( 'plugin_notes_note_title', $note['title'] );

				if ( !is_null( $note['user'] ) ) {
					$author = get_userdata( $note['user'] );
//					$author = get_user_by( 'id', $note['user'] );
//					$author = $author->display_name;
					// Allow for notes made by removed users
					if ( is_object( $author ) ) {
						$credits[] = esc_html( $author->display_name );
					}
					unset( $author );
				}
				// Only show import info if the note hasn't been changed since
				else if ( !is_null( $note['import_user'] ) && !is_null( $note['import_date'] ) ) {
					$importer = get_userdata( $note['import_user'] );
//					$importer = get_user_by( 'id', $note['import_user'] );
//					$importer = $importer->display_name;
					$formatted_import_date = ( is_int( $note['import_date'] ) ? date_i18n( $this->settings['dateformat'], $note['import_date'] ) : null );

					// Allow for notes imported by removed users
					if ( is_object( $importer ) && !is_null( $formatted_import_date ) ) {
						$credits[] = sprintf( esc_html__( 'Imported by %1$s on %2$s', self::$name ), esc_html( $importer->display_name ), esc_html( $formatted_import_date ) );
					}
					else if ( is_object( $importer ) ) {
						$credits[] = sprintf( esc_html__( 'Imported by %s', self::$name ), esc_html( $importer->display_name ) );
					}
					else if ( !is_null( $formatted_import_date ) ) {
						$credits[] = sprintf( esc_html__( 'Imported on %s', self::$name ), esc_html( $formatted_import_date ) );
					}
					unset( $importer, $formatted_import_date );
				}

				// Format new style timestamp-date or leave as-is for old style formatted date
				// @todo: check that the date we receive from the db is cast back to an int and not still a string
				/* @todo: base date format on settings:
					- if use_wp_dateformat === true, then:
						-> if ( isset( links_updated_date_format ) ) => use
						-> if not ( use: date_format . ' ' . __( 'at', Plugin_Notes:;$name ) . ' ' . time_format
					- if has date_format => use format given

					date_format 	j F Y 	yes
					time_format 	H:i 	yes
					links_updated_date_format 	j F Y H:i
				*/


				$credits[] = esc_html( Plugin_notes::get_formatted_date( $note['date'], $note['original_date'] ) );



				if ( !is_null( $note['pi_version'] ) ) {
					$credits[] = sprintf( __( 'v. %s', self::$name ), apply_filters( 'plugin_notes_pi_versionnr', $note['pi_version'], $plugin_data['Version'] ) );
				}
	
				if ( WP_DEBUG ) {
					$nr        = strval( $note['date'] );
					$len       = strlen( $nr );
					$credits[] = substr( $nr, ( $len - 6 ) );
					unset( $nr, $len );
				}


/*				$note['actions'][] = 'edit';
				$note['actions'][] = 'delete';*/
				$actions[] = 'edit';
				$actions[] = 'delete';
			}


/*			$credits = array();
			if ( !is_null( $author ) ) {
				$credits[] = esc_html( $author );
			}
			else if ( !is_null( $importer ) ) {
				if ( !is_null( $formatted_import_date ) ) {
					$credits[] = sprintf( esc_html__( 'Imported by %1$s on %2$s', self::$name ), esc_html( $importer ), esc_html( $formatted_import_date ) );
				}
				else {
					$credits[] = sprintf( esc_html__( 'Imported by %s', self::$name ), esc_html( $importer ) );
				}
			}
			if ( !is_null( $formatted_date ) ) { $credits[] = esc_html( $formatted_date ); }
			// Color versionnr depending on age
//			if ( !is_null( $note['pi_version'] ) ) { $credits[] = sprintf( $this->buttons['versionnr'], apply_filters( 'plugin_notes_pi_versionnr', $note['pi_version'], $plugin_data['Version'] ) ); }
			if ( !is_null( $note['pi_version'] ) ) { $credits[] = sprintf( __( 'v. %s', self::$name ), apply_filters( 'plugin_notes_pi_versionnr', $note['pi_version'], $plugin_data['Version'] ) ); }

			if ( WP_DEBUG ) {
				$nr = strval( $note['date'] );
				$len = strlen( $nr );
				$credits[] = substr( $nr, ($len-6 ) );
				unset( $nr, $len );
			}
*/

			$note_color_style = ( ( $note['color'] !== $this->settings['defaultcolor'] ) ? ' style="background-color: ' . $note['color'] . ';"' : '' );




//pr_var( $note, 'note after data alter', true );
			// Generate html to display the note
			$output = '';

			$output .= '
							<div class="wp-pn_note"' .( !is_null( $filtered_note ) ? ' title="' . esc_attr( __( 'Double click to edit!', self::$name ) ) . '"' : '' ) . '>';

			// @todo add icons for private and export exclude


			if ( isset( $note['export_exclude'] ) && $note['export_exclude'] === true ) {
				$output .= '
								<img src="' . plugins_url( 'images/clipboard-warning-24.png', __FILE__ ) . '" width="24" height="24" class="alignright" alt="' . esc_attr( __( 'No export', self::$name ) ) . '" title="' . esc_attr( __( 'This note will be excluded from exports.', self::$name ) ) . '" />';
			}

			if ( isset( $note['private'] ) && $note['private'] === true ) {
				$output .= '
								<img src="' . plugins_url( 'images/clipboard-eye-24.png', __FILE__ ) . '" width="24" height="24" class="alignright" alt="' . esc_attr( __( 'Private', self::$name ) ) . '" title="' . esc_attr( __( 'Private note - for your eyes only.', self::$name ) ) . '" />';
			}

			if ( !is_null( $filtered_title ) && $filtered_title !== '' ) {
				$output .= '
								<h3 class="wp-pn_title">' . $filtered_title . '</h3>';
			}

			if ( !is_null( $filtered_note ) ) {
				$output .= '
								<div class="wp-pn_text">' . $filtered_note . '
								</div>';
			}
			if ( count( $credits ) > 0 ) {
				$output .= '
								<p class="wp-pn_credits">' . implode( ' | ', $credits ) . '</p>';
			}
			unset( $filtered_note, $credits, $author, $importer, $formatted_date, $formatted_import_date );
			


			// @todo add onclick actions via js
			// @todo see if we can make this code more efficient with a foreach, one <a> template with vars & implode
			$output .= '
								<p class="wp-pn_actions">';

//			$total = count( $note['actions'] );
			$total = count( $actions );
			for ( $i = 0; $i < $total; $i++ ) {

//				switch ( $note['actions'][$i] ) {
				switch ( $actions[$i] ) {

					case 'edit':
						$output .= '
									<a href="#" class="edit">' . __( 'Edit note', self::$name ) . '</a>';

//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-pn_edit_'. esc_attr( $note['name'] ) . $attr_key . '" class="edit">' . __( 'Edit note', self::$name ) . '</a>';

//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-plugin_note_edit'. esc_attr( $note['name'] ) . $attr_key . '" class="edit">' . $this->buttons['edit_note'] . '</a>';
						break;

					case 'delete':
						$output .= '
									<a href="#" class="delete">' . __( 'Delete note', self::$name ) . '</a>';

//									<a href="#" onclick="delete_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-pn_delete_'. esc_attr( $note['name'] ) . $attr_key . '" class="delete">' . __( 'Delete note', self::$name ) . '</a>';

//									<a href="#" onclick="delete_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-plugin_note_delete'. esc_attr( $note['name'] ) . $attr_key . '" class="delete">' . $this->buttons['delete_note'] . '</a>';
						break;

					case 'add':
						$output .= '
									<a href="#" class="add">'. __( 'Add new plugin note', self::$name ) .'</a>';

//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . '\'); return false;" id="wp-pn_add_' . esc_attr( $note['name'] ) . '" class="edit">'. __( 'Add new plugin note', self::$name ) .'</a>';
//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . '\'); return false;">'. $this->buttons['add_new_note'] .'</a>';
						break;
				}
				$output .= ( ( $i === ( $total - 1 ) ) ? '' : ' | ' );
			}
			unset( $i, $total, $actions );

/*			$output .= '
									<span class="waiting"><img alt="' . __( 'Loading...', self::$name ) . '" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" /></span>
								</p>
							</div>';*/
			$output .= '
									<span class="spinner"></span>
								</p>
							</div>';


			$output = apply_filters( 'plugin_notes_row', $output, $plugin_data, $plugin_file );


			// Add the form to the note
			// @todo remove test ?
			$output = '
						<div id="wp-pn_' . esc_attr( $note['name'] ) . $attr_key . '" class="' . $note['class'] . '"' . $note_color_style . '>
							' . $this->_add_plugin_form( $note, $plugin_file, true, false ) . '
							' . $output . '
							<p class="wp-pn_result">test</p>
						</div>';

			unset( $note_color_style );


			if ( $echo === true ) {
				echo $output;
			}
			else {
				return $output;
			}
		}
		
		

		public static function get_form_html( $plugin_slug, $note = null, $hidden = true, $echo = true ) {

			$note_defaults = Plugin_Notes_Manage_Notes_Option::$note_defaults;

			if ( !isset( $note ) ) {
				// New form based on template
				$template = Plugin_Notes_Manage_Settings_Option::$current['default_template'];
				if ( isset( $template ) && isset( Plugin_Notes_Manage_Settings_Option::$current['templates'][$template] ) ) {
					$template = Plugin_Notes_Manage_Settings_Option::$current['templates'][$template];
					$note     = array_merge( $note_defaults, $template );
				}
				else {
				// New (blank) form
					$note = $note_defaults;
				}
			}
			else {
				// Edit form for existing note
				$note = array_merge( $note_defaults, $note );
			}
			
			$hidden = ( $hidden === true ) ? ' style="display:none"' : '';


			// buttons at the bottom:
			//save
			//cancel
			//save as new template : template name [text] + make default [checkbox]
		}
		
/*
	function _add_plugin_form ( $note = '', $plugin_safe_name, $plugin_file, $hidden = true ) {
		$plugin_form_style = ($hidden) ? 'style="display:none"' : '';
		?>
			<div id="wp-plugin_note_form_<?php echo $plugin_safe_name ?>" class="wp-plugin_note_form" <?php echo $plugin_form_style ?>>
				<textarea name="wp-plugin_note_text_<?php echo $plugin_safe_name ?>" cols="40" rows="3"><?php echo $note; ?></textarea>
				<span class="wp-plugin_note_error error" style="display: none;"></span>
				<span class="wp-plugin_note_edit_actions">
					<?php // TODO: Unobtrusify the javascript ?>
					<a href="#" onclick="save_plugin_note('<?php echo $plugin_safe_name ?>');return false;" class="button-primary"><?php _e('Save', 'plugin-notes') ?></a>
					<a href="#" onclick="cancel_plugin_note('<?php echo $plugin_safe_name ?>');return false;" class="button"><?php _e('Cancel', 'plugin-notes') ?></a>
					<span class="waiting" style="display: none;"><img alt="<?php _e('Loading...', 'plugin-notes') ?>" src="images/wpspin_light.gif" /></span>
				</span>
				<input type="hidden" name="wp-plugin_note_slug_<?php echo $plugin_safe_name ?>" value="<?php echo $plugin_file ?>" />
			</div>
		<?php
	}
*/

		/**
		 * Outputs form to add/edit/delete a plugin note
		 *
		 * @todo allow for multiple notes per plugin
		 * @todo unobtrusify the js onclick events
		 * @todo check if html can be cleaned up more including getting rid of most ids
		 *
		 * @todo add 'private / my eyes only' option
		 * @todo add 'exclude from export' option
		 * @todo add 'note icon' option ?
		 */
		function _add_plugin_form( $note, $plugin_file, $hidden = true, $echo = true ) {

			$plugin_form_style = ( $hidden === true ) ? 'style="display:none"' : '';

			$new_note_class  = '';
			$new_note_class2 = '';
			if ( is_null( $note['note'] ) || empty( $note['note'] ) ) {
				// Have we got a template ? If so, use template values
				if ( isset( $this->settings['template']['default'] ) && ( is_array( $this->settings['template']['default'] ) && count( $this->settings['template']['default'] ) > 0 ) ) {
					
					$template = $this->settings['template']['default'];

					$note['note'] = ( isset( $template['note'] ) ? $template['note'] : '' );
					$note['color'] = ( isset( $template['color'] ) ? $template['color'] : $note['color'] );
					$note['title'] = ( isset( $template['title'] ) ? $template['title'] : $note['title'] );
					$note['private'] = ( isset( $template['private'] ) ? $template['private'] : $note['private'] );
					$note['export_exclude'] = ( isset( $template['export_exclude'] ) ? $template['export_exclude'] : $note['export_exclude'] );
					
					unset( $template );
				}

				$new_note_class  = ' class="new_note"';
				$new_note_class2 = ' new_note';
			}
			
			$attr_key = ( is_null( $note['date'] ) ? '' : '_' . esc_attr( $note['date'] ) );
			

			$output = '
							<div class="wp-pn_form' . $new_note_class2 . '" ' . $plugin_form_style . '>
								<label class="col1" for="wp-pn_color_' . esc_attr( $note['name'] ) . $attr_key . '">' . /*$this->buttons['label_notecolor']*/ __( 'Note color:', self::$name ) . '</label>
								<select class="wp-pn_color" name="wp-pn_color_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_color_' . esc_attr( $note['name'] ) . $attr_key . '">';

			// Add color options
			foreach ( $this->boxcolors as $k => $color ){
				$output .= '
									<option value="' . esc_attr( $color ) . '" style="background-color: ' . esc_attr( $color ) . ' !important; color: ' . esc_attr( $color ) . ';"' .
					( ( $color === $note['color'] ) ? ' selected="selected"' : '' ) .
					'>' . esc_html__( $k, self::$name ) . '</option>';
			}
			unset( $k, $color );


			// @todo see if we can merge the error and success spans
			// @todo un-obtrusify the javascript / add onclick handler via js
			// @todo change the code to proper submit buttons ?
			$output .= '
								</select>
								
								<label class="col2" for="wp-pn_export_exclude_' . esc_attr( $note['name'] ) . $attr_key . '">
									   <input type="checkbox" class="wp-pn_export_exclude" name="wp-pn_export_exclude_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_export_exclude_' . esc_attr( $note['name'] ) . $attr_key . '" value="on" ' . ( ( isset( $note['export_exclude'] ) && $note['export_exclude'] === true ) ? 'checked="checked"' : '' )  . ' />
									   ' . __( 'Exclude note from export', self::$name ) . '
								</label>
								
								<label class="col2" for="wp-pn_private_' . esc_attr( $note['name'] ) . $attr_key . '">
									   <input type="checkbox" class="wp-pn_private" name="wp-pn_private_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_private_' . esc_attr( $note['name'] ) . $attr_key . '" value="on" ' . ( ( isset( $note['private'] ) && $note['private'] === true ) ? 'checked="checked"' : '' )  . ' />
									   ' . __( 'Private note - my eyes only', self::$name ) . '
								</label>

								<br />

								<label class="col1" for="wp-pn_title_' . esc_attr( $note['name'] ) . $attr_key . '">' . __( 'Title:', self::$name ) . '</label>
								<input type="text" class="wp-pn_title" name="wp-pn_title_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_title_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . ( ( is_null( $note['title'] ) || $note['title'] === '' ) ? '' : $note['title'] ) . '" />
								<br />

								<textarea name="wp-pn_text_' . esc_attr( $note['name'] ) . $attr_key . '" cols="98" rows="10"' . /*$new_note_class*/ '>' . esc_textarea( $note['note'] ) . '</textarea>
' /*								<span class="wp-plugin_note_error error" style="display: none;"></span>
								<span class="wp-plugin_note_success success" style="display: none;"></span>
*/ . '
								<span class="wp-pn_edit_actions">';

			/* TRANSLATORS: no need to translate - standard WP core translation will be used */
			$output .= '
									<input type="button" value="' . esc_html__( 'Save' ) . '" name="save" class="button-primary" />
									<input type="button" value="' . esc_html__( 'Cancel' ) . '" name="cancel" class="button" />
									<input type="button" value="' . esc_html__( 'Save as template for new notes', self::$name ) . '" name="templatesave" class="button-secondary" />';

/*									<a href="#" onclick="save_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');return false;" class="button-primary">' . /*$this->buttons['save_note']* / /* TRANSLATORS: no need to translate - standard WP core translation will be used * / esc_html__( 'Save' ) . '</a>
									<a href="#" onclick="cancel_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');return false;" class="button">' . /*$this->buttons['cancel_edit']* / /* TRANSLATORS: no need to translate - standard WP core translation will be used * / esc_html__( 'Cancel' ) . '</a>
									<a href="#" onclick="templatesave_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');return false;" class="button-secondary">' . /*$this->buttons['save_as_template']* / esc_html__( 'Save as template for new notes', self::$name ) . '</a>
*/
/*			$output .= '
									<span class="waiting"><img alt="' . esc_attr( /*$this->buttons['title_loading']* / __( 'Loading...', self::$name ) ) . '" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" /></span>
								</span>
								<input type="hidden" name="wp-pn_slug_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $plugin_file ) . '" />
								<input type="hidden" name="wp-pn_name_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $note['name'] ) . '" />
								<input type="hidden" name="wp-pn_key_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $note['date'] ) . '" />
								<input type="hidden" name="wp-pn_new_template_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_new_template_' . esc_attr( $note['name'] ) . $attr_key . '" value="n" />
							</div>';*/
			$output .= '
									<span class="spinner"></span>
								</span>
								<input type="hidden" name="wp-pn_slug_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $plugin_file ) . '" />
								<input type="hidden" name="wp-pn_name_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $note['name'] ) . '" />
								<input type="hidden" name="wp-pn_key_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $note['date'] ) . '" />
								<input type="hidden" name="wp-pn_new_template_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_new_template_' . esc_attr( $note['name'] ) . $attr_key . '" value="n" />
							</div>';

			if ( $echo === true ) {
				echo apply_filters( 'plugin_notes_form', $output, $note['name'] );
			}
			else {
				return apply_filters( 'plugin_notes_form', $output, $note['name'] );
			}
		}



		/**
		 * Function that handles retrieving of the plugin notes via AJAX for the update page
		 *
		 * Does not send back error messages on failure, but fails silently
		 *
		 */
/*		function ajax_get_plugin_note() {
			global $current_user, $pagenow;




			if ( empty($_POST) ) {
				//return ?
			}

			if ( $this->show_on_update !== true || $pagenow !== $this->update_page ) {
				exit();
			}

			// Verify nonce
//			check_ajax_referer( 'wp-plugin_notes_nonce', '_pluginnotes_nonce' );
// We don't have a nonce yet...
/*			if ( ! wp_verify_nonce( $_POST['_pluginnotes_nonce'], 'wp-plugin_notes_nonce' ) ) {
				exit();
			}
* /
			if ( ! is_user_logged_in() ) {
				exit();
			}

			// @todo: test!
			if ( ! is_admin() || current_user_can( self::REQUIRED_CAP ) === false ) {
				exit();
			}

			// Ok, we're still here which means we have an valid user on a valid form


// use sanitize_text_field() for text field inputs

			if ( $this->_validate_plugin( sanitize_text_field( $_POST['plugin_slug'] ), true ) === true ) {
				$plugin = sanitize_text_field( $_POST['plugin_slug'] );
			}


			if ( !isset( $plugin ) ) {
				exit();
			}

//			plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $context )
/*
			$notes = isset( $this->options[$plugin_file] ) ? $this->options[$plugin_file] : null;
			$plugin_data = $this->_getremember_plugin_data( $plugin_file, $plugin_data );

			if ( !$this->nonce_added ) {
				wp_nonce_field( 'wp-plugin_notes_nonce', 'wp-plugin_notes_nonce', true, true );
//				echo '<input type="hidden" name="wp-plugin_notes_nonce" value="' . wp_create_nonce( 'wp-plugin_notes_nonce' ) . '" />';
				$this->nonce_added = true;
			}
			
			$this->_add_plugin_notes( $notes, $plugin_data, $plugin_file, true );
* /


//trigger_error( pr_var( $_POST, 'post vars', true ), E_USER_NOTICE );
			// Get notes array
//			$options = $this->_get_set_options();

//			$original_id = 'wp-pn_' . ( isset( $key ) ? $plugin_name . '_' . $key : $plugin_name );

			$note_text = $this->_validate_note_text( $_POST['plugin_note'] );



			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();

			if ( $note_text !== '' ) {
				// Are we trying to save the note as a note template ?
				if ( $_POST['plugin_new_template'] === 'y' ) {

					$this->options['plugin-notes_template'] = $note_text;

		//			$response_data = array_merge( $response_data, $note );
					$response_data['action'] = 'save_template';

					/*
					 @todo: 2x fix: if the template is cleared, clear the new 'add note'-forms
					 + if no notes exist for the plugin where they did this action, make sure that the form + add note
					 link returns
					 + make sure that succesfull template delete message is shown
					 * /
					$plugin_note_content = '';
				}
				// Ok, no template, but we have a note, save the note to the specific plugin
				else {

					$plugin_data = $this->_getremember_plugin_data( $plugin );
//					$date_format = $this->settings['dateformat'];

					// setup the note data
//					$note['date'] = date_i18n( $date_format );
					$note['date'] = time();
					$note['user'] = $current_user->ID;
					$note['note'] = $note_text;
					$note['color'] = $this->_validate_note_color( sanitize_text_field( $_POST['plugin_note_color'] ) );
					$note['pi_version'] = $plugin_data['Version'];

					// Delete the old version so as to reset the timestamp key and removed potential import info
					if ( !empty( $key ) && !empty( $this->options[$plugin][$key] ) ) {
						unset( $this->options[$plugin][$key] );
					}

					// Add new note to notes array
					$this->options[$plugin][$note['date']] = $note;

					$response_data = array_merge( $response_data, $note ); // is this needed ?
					$response_data['action'] = 'edit';

					if ( !empty( $key ) ) {
						// Retrieve the edited note html to replace the previous version
						$plugin_note_content = $this->_add_plugin_note( $note, $plugin_data, $plugin, false );
					}
					else {
						// Ok, we had a new note, retrieve the new note html + new empty form
						$plugin_note_content = $this->_add_plugin_note( $note, $plugin_data, $plugin, false );
						$plugin_note_content .= $this->_add_plugin_note( null, $plugin_data, $plugin, false );
					}
					
					// Reset key
					$key = $note['date'];

				}
			}
			else {
				// no note sent nor template, so let's delete it
				if ( !empty( $key ) && !empty( $this->options[$plugin][$key] ) ) {
					unset( $this->options[$plugin][$key] );
				}
				$response_data['action'] = 'delete';
				
				$plugin_note_content = '';
			}
			
			
			//							<span class="wp-plugin_note_result" style="display: none;"></span>

			// Save the new notes array
			$this->options = $this->_get_set_options( $this->options );

			//			echo '<input type="hidden" name="wp-plugin_notes_nonce" value="' . wp_create_nonce( 'wp-plugin_notes_nonce' ) . '" />

			// Prepare response
			$response = new WP_Ajax_Response();

			$plugin_data = ( isset( $plugin_data ) ? $plugin_data : array( 'Name' => $plugin_name ) );
//			$plugin_note_content = $this->_add_plugin_note( $note, $plugin_data, $plugin, false );
//print 'action = ' . $response_data['action'];
			$response->add( array(
				'what' => 'plugin_note',
//				'action' => ( ( $note_text ) ? ( ( $_POST['plugin_new_template'] === 'y' ) ? 'save_template' : 'edit' ) : 'delete' ),
				'action' => $response_data['action'],
				'id' => ( isset( $key ) ) ? $plugin_name . '_' . $key : $plugin_name,
				'data' => $plugin_note_content,
// @todo: generated nonce lijkt niet anders te zijn dan originele nonce
				'supplemental'	=> array( 'new_nonce' =>  wp_create_nonce( 'wp-plugin_notes_nonce' ) ),

			));
			$response->send();
/*
'what'
    A string containing the XMLRPC response type (used as the name of the xml element). 
'action'
    A boolean or string that will behave like a nonce. This is added to the response element's action attribute. 
'id'
    This is either an integer (usually 1) or a WP_Error object (if you need to return an error). Most commonly, the id value is used as a boolean, where 1 is a success and 0 is a failure. 
'old_id'
    This is false by default, but you can alternatively provide an integer for the previous id, if needed. 
'position'
    This is an integer or a string where -1 = top, 1 = bottom, 'html ID' = after, '-html ID' = before
'data'
    A string containing output content or a message (such as html). This is disregarded if you pass a WP_Error object as the id. 
'supplemental'
    This can an associative array of strings, which will be rendered into children of the <supplemental> element. Keys become element names, and values are embedded in CDATA within those elements. Useful for passing additional information to the browser
* /
			exit;
		}
*/







		/** ******************* SOME HELPFUL GET/SET METHODS ******************* **/





	} // End of class

} // End of class exists wrapper