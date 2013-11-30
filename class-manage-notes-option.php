<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Plugin_Notes' ) && ! class_exists( 'Plugin_Notes_Manage_Notes_Option' ) ) {
	/**
	 * @package		WordPress\Plugins\Plugin Notes
	 * @subpackage	Manage Notes Option
	 * @version		2.0
	 * @since		2.0
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 *
	 * @copyright	2013 Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Plugin_Notes_Manage_Notes_Option {
		

		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * @const	string	Name of the wp option in the options table containing the notes
		 */
		const NAME = 'plugin_notes';


		/**
		 * @const	string	Minimum required capability to access the settings page and change the plugin options
		 */
		const REQUIRED_CAP = Plugin_Notes::REQUIRED_CAP;

		
		/* @todo: make a difference between who can see the notes and who can edit ? */


		/* *** DEFINE CLASS PROPERTIES *** */


		/* *** Static Properties *** */

		/**
		 * @var string	Unique group identifier for option
		 */
		public static $settings_group = '%s-group';

		public static $note_defaults = array(
//			'plugin_name'		=> null,
			'plugin_version'	=> null,
			'title'				=> null,
			'note'				=> null,
			'color'				=> null,
			'private'			=> false,
			'export_exclude'	=> false,
			'classes'			=> array(
				'blank',
			),
			'user_id'			=> null,
			'timestamp'			=> null,
			'original_date'		=> null,
			'import_timestamp'	=> null,
			'import_user_id'	=> null,
//			'actions'			=> array(),
		);

		/**
		 * @var array	Default option values
		 */
/*		public static $defaults = array(
			'version'		=> null,
			'include'		=> array(
				'all'			=> false,
				'feed'			=> false,
				'home'			=> false,
				'archives'		=> false,
				'tax'			=> false,
				'tag'			=> true,
				'category'		=> false,
				'author'		=> false,
				'date'			=> false,
				'search'		=> true,
			),
			'uninstall'		=> array(
				'delete_posts'		=> '',
				'delete_taxonomy'	=> '',
			),
			'upgrading'		=> false, // will never change, only used to distinguish a call from the upgrade method
		);*/
		/**
		 * @var array	Defaults for this plugin specific settings
		 */
/*		public $defaults = array(
			'version'			=> self::VERSION,
			'defaultcolor'		=> '#EAF2FA',
			'dateformat'		=> null,
			'sortorder'			=> 'asc',
			'allow_markdown'	=> true,
			'allow_html'		=> true,
			'adminbar_url'		=> false,
			'template'			=> null,
//			'show_on_update'	=> false,
		);
*/

		
		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * @var	array	Property holding the current options - automagically updated
		 */
		public static $current;



		/* *** CLASS METHODS *** */

		/**
		 * Initialize our option and add all relevant actions and filters
		 */
		public static function init() {
			
			/* Initialize properties */
			add_action( 'admin_init', array( __CLASS__, 'set_properties' ), 2 );

			/* Register our option (and it's validation) as early as possible */
			add_action( 'admin_init', array( __CLASS__, 'register_setting' ), 3 );



			/* Add filters which get applied to get_options() results */
//			self::add_default_filter();
//			add_filter( 'option_' . self::NAME, array( __CLASS__, 'filter_option' ) );

			/* The option validation routines remove the default filters to prevent failing to insert
			   an option if it's new. Let's add them back afterwards */
//			add_action( 'add_option', array( __CLASS__, 'add_default_filter' ) );

/*			if ( version_compare( $GLOBALS['wp_version'], '3.7', '!=' ) ) {
				add_action( 'update_option', array( __CLASS__, 'add_default_filter' ) );
			}
			else {
				// Abuse a filter for WP 3.7 where the update_option filter is placed in the wrong location
				add_filter( 'pre_update_option_' . self::NAME, array( __CLASS__, 'pre_update_option' ) );
			}
*/


			/* Refresh the $current property on successful option update */
			add_action( 'add_option_' . self::NAME, array( __CLASS__, 'on_add_option' ), 10, 2 );
			add_action( 'update_option_' . self::NAME, array( __CLASS__, 'on_update_option' ), 10, 2 );

			/* Lastly, we'll be saving our option during the upgrade routine *before* the setting
			   is registered (and therefore the validation is registered), so make sure that the
			   option is validated anyway. */
			add_filter( 'plugin_notes_save_notes_on_upgrade', array( __CLASS__, 'validate_notes' ) );

			/* Initialize the $current property */
			self::refresh_current();
		}


		/**
		 * Adjust property value
		 *
		 * @return void
		 */
		public static function set_properties() {
			/* Parse the settings group */
			self::$settings_group = sprintf( self::$settings_group, Plugin_Notes::$name );
			
			self::$note_defaults['color']     = Plugin_Notes_Manage_Settings_Option::$template_defaults['color'];
			self::$note_defaults['classes'][] = Plugin_Notes::PREFIX . 'note_box';
		}

		/**
		 * Register our option
		 */
		public static function register_setting() {
			register_setting(
				self::$settings_group,
				self::NAME, // option name
				array( __CLASS__, 'validate_notes' ) // validation callback
			);
		}

		/**
		 * Add filtering of the option default values
		 */
/*		public static function add_default_filter() {
			if ( has_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) ) === false ) {
				add_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
			};
		}


		static function pre_update_option( $new_value ) {
			self::add_default_filter();
			return $new_value;
		}
*/

		/**
		 * Remove filtering of the option default values
		 *
		 * This is need to allow for inserting of option if it doesn't exist
		 * Should be called from our validation routine
		 */
/*		public static function remove_default_filter() {
			remove_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
		}
*/

		/**
		 * Filter option defaults
		 *
		 * This in effect means that get_option() will not return false if the option is not found,
		 * but will instead return our defaults. This way we always have all of our option values available.
		 */
/*		public static function filter_option_defaults() {
			self::refresh_current( self::$defaults );
			return self::$defaults;
		}
*/

		/**
		 * Filter option
		 *
		 * This in effect means that get_option() will not just return our option from the database,
		 * but will instead return that option merged with our defaults.
		 * This way we always have all of our option values available. Even when we add new option
		 * values (to the defaults array) when the plugin is upgraded.
		 */
/*		public static function filter_option( $options ) {
			$options = self::array_filter_merge( self::$defaults, $options );
			self::refresh_current( $options );
			return $options;
		}
*/

		/**
		 * Set the $current property to the value of our option
		 */
		public static function refresh_current( $value = null ) {
			if ( !isset( $value ) ) {
				$value = get_option( self::NAME );
			}
			self::$current = $value;
		}


		/**
		 * Refresh the $current property when our property is added to wp
		 */
		public static function on_add_option( $option_name, $value ) {
			self::refresh_current( $value );
		}


		/**
		 * Refresh the $current property when our property is updated
		 */
		public static function on_update_option( $old_value, $value ) {
			self::refresh_current( $value );
		}



		/* *** HELPER METHODS *** */

		/**
		 * Helper method - Combines a fixed array of default values with an options array
		 * while filtering out any keys which are not in the defaults array.
		 *
		 * @static
		 *
		 * @param	array	$defaults	Entire list of supported defaults.
		 * @param	array	$options	Current options.
		 * @return	array	Combined and filtered options array.
		 */
/*		public static function array_filter_merge( $defaults, $options ) {
			$options = (array) $options;
			$return  = array();

			foreach ( $defaults as $name => $default ) {
				if ( array_key_exists( $name, $options ) )
					$return[$name] = $options[$name];
				else
					$return[$name] = $default;
			}
			return $return;
		}
*/

		/**
		 * Intelligently set/get the plugin notes
		 *
		 * @staticvar bool|array	$original_notes		remember originally retrieved notes array for reference
		 * @param	array|null		$update				New notes array to save to db - make sure the
		 *												new array is validated first!
		 * @return array			$this->notes		Notes array
		 */
/*		function _get_set_notes( $update = null ) {
			static $original_notes = false;

			// Do we have something to update ?
			if ( !is_null( $update ) ) {

				$update = $this->sort_notes( $update );
				
				if ( $update !== $original_notes ) {

					if ( $original_notes === get_option( self::NOTES_OPTION ) ) {
						// Ok, nobody else updated in the mean time
						$updated = update_option( self::NOTES_OPTION, $update );
						$this->notes = $original_notes = $update;
					}
				}
				else {
					$updated = true; // no update necessary
				}
				return $updated;
			}
			
			// No update received or update failed -> get the notes from db
			if ( ( is_null( $this->notes ) || $this->notes === false ) || ( is_array( $this->notes ) === false ||  count( $this->notes ) === 0 ) ) {
				// returns either the option array or false if option not found
				$notes = get_option( self::NOTES_OPTION );
				// Default to an empty array rather than to false
				if ( $notes === false ) {
					$notes = array();
				}
				$this->notes = $original_notes = $notes;
				unset( $notes );
			}

            return;
		}
*/


		/**
		 * Sort the notes per plugin based on the note timestamp (=key)
		 *
		 * @param $updated_notes
		 * @internal param array $options complete options array for this plugin
		 * @return    array    sorted options array
		 */
		function sort_notes( $updated_notes ) {

			foreach ( $updated_notes as $key => $notes ) {
				// Only sort if more than one note found
				if ( is_array( $notes ) && count( $notes ) > 1 ) {
					// Sort the notes by key (=timestamp)
					if ( $this->settings['sortorder'] === 'asc' ) {
						ksort( $notes );
					}
					else {
						krsort( $notes );
					}
					$updated_notes[$key] = $notes;
				}
			}
			unset( $key, $notes );
			
			return $updated_notes;
		}



		/* *** OPTION VALIDATION *** */

		/**
		 * Validated the settings received from our options page
		 *
		 * Complete validation routine only used on Upgrade and Import of notes.
		 * More often than not a single note will be saved to an already validated notes array and
		 * that single note will have already been validated in the ajax handling call.
		 *
		 * @todo inform user of validation errors on upgrade via transient API
		 *
		 * @param  array    $received     Our $_POST variables
		 * @return array    Cleaned settings to be saved to the db
		 */
		public static function validate_notes( $received ) {

//			self::remove_default_filter();

			/* Don't change anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( self::REQUIRED_CAP ) ) {
				return self::$current;
			}

			/* Single note save/delete via AJAX, single note already validated and added/deleted.
			   No need for validating everything */
			if ( isset( $received[Plugin_Notes::PREFIX . 'is_validated'] ) && $received[Plugin_Notes::PREFIX . 'is_validated'] === true ) {
				unset( $received[Plugin_Notes::PREFIX . 'is_validated'] );
				return $received;
			}


			/* Start off with the current settings and where applicable, replace values with valid received values */
			$clean = self::$current;


			/* Validate */
			if ( is_array( $received ) && $received !== array() ) {
				foreach ( $received as $plugin_slug => $notes ) {
					/* Validate plugin name */
					$plugin_slug = validate_plugin_slug( $plugin_slug );

					/* Validate notes */
					if ( $plugin_slug !== null && ( is_array( $notes ) && $notes !== array() ) ) {
						foreach ( $notes as $note_array ) {
							// Validate note
							$note_array = validate_single_note( $note_array, $plugin_slug );
							if ( $note_array !== null ) {
								$clean[$plugin_slug][] = $note_array;
							}
						}
						
						// Multi-sort group of plugins based on sort preferences
						//	$clean[$plugin_slug]

					}
					// else				exit( __( 'Invalid form input received.', Plugin_Notes::$name ) );
				}
			}


			return $clean;
		}
		
		
		function validate_single_note( $note_array, $plugin_slug ) {
			/* Start off with the default template settings and where applicable, replace values with valid received values */
			$clean = self::$note_defaults;

			foreach ( $clean as $key => $value ) {
				switch ( $key ) {
					case 'timestamp':
						if ( isset( $note_array[$key] ) ) {
							// Existing note
							$clean[$key] = self::validate_timestamp( $note_array[$key] );
						}
						else {
							// New or updated note
							$clean[$key] = time();
						}
						break;
						
					case 'original_date':
						if ( isset( $note_array[$key] ) ) {
							// Existing note
							$clean[$key] = $note_array[$key];
						}
						break;
						
					case 'import_timestamp':
					// @todo figure out how to determine whether to set this
						if ( isset( $note_array[$key] ) ) {
							// Existing note
							$clean[$key] = self::validate_timestamp( $note_array[$key] );
						}
						else {
							// New note
							$clean[$key] = time();
						}
						break;
						
					case 'user_id':
						if ( isset( $note_array[$key] ) ) {
							// Existing note
							$clean[$key] = self::validate_user_id( $note_array[$key] );
						}
						else {
							// New or updated note
							$clean[$key] = get_current_user_id();
						}
						break;

					case 'import_user_id':
					// @todo figure out how to determine whether to set this
						if ( isset( $note_array[$key] ) ) {
							// Existing note
							$clean[$key] = self::validate_user_id( $note_array[$key] );
						}
						else {
							// New note
							$clean[$key] = get_current_user_id();
						}
						break;

					case 'title':
						$clean[$key] = self::validate_note_title( $note_array[$key] );
						break;

					case 'note':
						$clean[$key] = self::validate_note_text( $note_array[$key] );
						break;

					case 'color':
						$clean[$key] = self::validate_note_color( $note_array[$key] );
						break;
						
					case 'classes':
						// array - do anything or always leave default ?
						break;
						
/*					case 'plugin_name':
						break;
*/
					case 'plugin_version':
					// @todo check whether we have a plugin slug/file name or a plugin name
						$plugin_data = apply_filters( 'plugin_notes_plugin_data', null, $plugin_slug );
						$clean[$key] = $plugin_data['Version'];
						unset( $plugin_data );
						break;

					case 'private':
					case 'export_exclude':
					default:
						$clean[$key] = ( isset( $note_array[$key] ) ? filter_var( $note_array[$key], FILTER_VALIDATE_BOOLEAN ) : false );
						break;
						
/*
			'classes'			=> array(
				'blank',
			),
			'timestamp'			=> null,
			'original_date'		=> null,
			'import_timestamp'	=> null,
			'import_user_id'	=> null,
*/
				}
			}
			
			return $clean;
		}
		

		
		function add_note( $plugin_name, $note_id, $note_array ) {
		}
		
		
		function update_note( $plugin_name, $note_id, $note_array ) {
		}
		
		function delete_note( $plugin_name, $note_id, $note_array ) {
		}


		/** ******************* INPUT PROCESSING METHODS ******************* **/


		/**
		 * Function that handles editing of the plugin notes via AJAX
		 *
		 * @todo allow for multiple notes per plugin -> add key
		 * @todo check if localization can be enabled
		 */
		function ajax_edit_plugin_note() {
			global $current_user;

//			if ( !defined( 'WPLANG' ) ) { define('WPLANG', get_option( 'WPLANG' ) ); }

			// @todo Localization of notes with AJAX edit does not seem to work....
//			$this->load_textdomain();
//			load_textdomain( self::$name, self::$path . 'languages/' );

/*
if ( empty($_POST) || !wp_verify_nonce($_POST['name_of_nonce_field'],'name_of_my_action') )
{
   print 'Sorry, your nonce did not verify.';
   exit;
}




<?php
//Set Your Nonce
$ajax_nonce = wp_create_nonce("my-special-string");
?>

<script type="text/javascript">
jQuery(document).ready(function($){
	var data = {
		action: 'my_action',
		security: '<?php echo $ajax_nonce; ?>',
		my_string: 'Hello World!'
	};
	$.post(ajaxurl, data, function(response) {
		alert("Response: " + response);
	});
});
</script>


In your ajax file, check the referrer like this:

add_action( 'wp_ajax_my_action', 'my_action_function' );
function my_action_function() {
	check_ajax_referer( 'wp-plugin_notes_nonce', '_pluginnotes_nonce' );
	echo $_POST['my_string'];
	die;
}
*/

			if ( empty($_POST) ) {
				//return ?
			}

			// Verify nonce
//			check_ajax_referer( 'wp-plugin_notes_nonce', '_pluginnotes_nonce' );

			if ( ! wp_verify_nonce( $_POST['_pluginnotes_nonce'], 'wp-plugin_notes_nonce' ) ) {
				// duplicate phrase with js i18n function
				exit( __( 'Don\'t think you\'re supposed to be here...', Plugin_Notes::$name ) );
			}

			if ( ! is_user_logged_in() ) {
				// duplicate phrase with js i18n function
				$error = sprintf( __( 'Your session seems to have expired. You need to %slog in%s again.', Plugin_Notes::$name ), '<a href="' . wp_login_url( $this->plugin_options_url() ) . '" title="' . __( 'Login', Plugin_Notes::$name ) . '">', '</a>' );
				exit( $error );
			}

//			$current_user = wp_get_current_user(); // is this needed and if so, why get the global variable above ?
			// @todo: test!
			if ( ! is_admin() || current_user_can( self::REQUIRED_CAP ) === false ) {
				// user can't activate plugins, so throw error
				exit( __( 'Sorry, you do not have the necessary permissions.', Plugin_Notes::$name ) );
			}

			// Ok, we're still here which means we have an valid user on a valid form


// use sanitize_text_field() for text field inputs

			if ( $this->_validate_plugin( sanitize_text_field( $_POST['plugin_slug'] ), true ) === true ) {
				$plugin = sanitize_text_field( $_POST['plugin_slug'] );
			}
			if ( $this->_validate_plugin( Plugin_Notes::get_plugin_safe_name( $_POST['plugin_name'] ), false ) === true ) {
				$plugin_name = Plugin_Notes::get_plugin_safe_name( $_POST['plugin_name'] );
			}

			// will this work for unconverted dates ?
/*trigger_error(
pr_var( array(
'isset'		=> isset( $_POST['note_key'] ),
'postvalue'	=> $_POST['note_key'],
'intval'	=>	intval( $_POST['note_key'] ),
'comp'		=>  ( intval( $_POST['note_key'] ) == $_POST['note_key'] ),
'morethan0'	=>	( intval( $_POST['note_key'] ) >= 0 ),

), 'note key info', true )
);*/
			if ( ( isset( $_POST['note_key'] ) && $_POST['note_key'] !== '' ) && ( ( intval( $_POST['note_key'] ) == $_POST['note_key'] ) && intval( $_POST['note_key'] ) >= 0 ) ) {
				$key = intval( $_POST['note_key'] );
			}

			if ( !isset( $plugin ) || !isset( $plugin_name ) /*|| !isset( $key ) - quite possible, i.e. new note*/ ) {
				exit( __( 'Invalid form input received.', Plugin_Notes::$name ) );
			}


//trigger_error( pr_var( $_POST, 'post vars', true ), E_USER_NOTICE );
			// Get notes array
//			$options = $this->_get_set_options();

//			$original_id = 'wp-pn_' . ( isset( $key ) ? $plugin_name . '_' . $key : $plugin_name );

			$note_text = $this->_validate_note_text( $_POST['plugin_note'] );



			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();


			// @todo : check if I can still clear a template !!! Readme says, save empty note, but looks like if we do that, it will never save as template...
			if ( $note_text !== '' ) {
				// Are we trying to save the note as a note template ?
				if ( $_POST['plugin_new_template'] === 'y' ) {
					$this->settings['template']['default']['note'] = $note_text;
					
					$title = $this->_validate_note_text( $_POST['plugin_note_title'] );
					if ( $title !== '' ) {
						$this->settings['template']['default']['title'] = $title;
					}
					unset( $title );

					$color = $this->_validate_note_color( sanitize_text_field( $_POST['plugin_note_color'] ) );
					if ( $color !== $this->settings['defaultcolor'] ) {
						$this->settings['template']['default']['color'] = $color;
					}
					unset( $color );
					
					if ( isset( $_POST['plugin_note_private'] ) && $_POST['plugin_note_private'] === 'true' ) {
						$this->settings['template']['default']['private'] = true;
					}
					else {
						unset( $this->settings['template']['default']['private'] );
					}

					if ( isset( $_POST['plugin_note_export_exclude'] ) && $_POST['plugin_note_export_exclude'] === 'true' ) {
						$this->settings['template']['default']['export_exclude'] = true;
					}
					else {
						unset( $this->settings['template']['default']['export_exclude'] );
					}


		//			$response_data = array_merge( $response_data, $note );
					$response_data['action'] = 'save_template';

					/*
					 @todo: 2x fix: if the template is cleared, clear the new 'add note'-forms
					 + if no notes exist for the plugin where they did this action, make sure that the form + add note
					 link returns
					 + make sure that succesfull template delete message is shown
					 */
					$plugin_note_content = '';
					
					$this->settings = $this->_get_set_settings( $this->settings );
				}
				// Ok, no template, but we have a note, save the note to the specific plugin
				else {
//					$plugin_data = $this->_getremember_plugin_data( $plugin );
					$plugin_data = apply_filters( 'plugin_notes_plugin_data', null, $plugin );
//					$date_format = $this->settings['dateformat'];

					// setup the note data
//					$note['date'] = date_i18n( $date_format );
					$note['date']           = time();
					$note['user']           = $current_user->ID;
					$note['note']           = $note_text;
					$note['color']          = $this->_validate_note_color( sanitize_text_field( $_POST['plugin_note_color'] ) );
					$note['title']          = $this->_validate_note_text( $_POST['plugin_note_title'] );
					$note['private']        = ( ( isset( $_POST['plugin_note_private'] ) && $_POST['plugin_note_private'] === 'true' ) ? true : false );
					$note['export_exclude'] = ( ( isset( $_POST['plugin_note_export_exclude'] ) && $_POST['plugin_note_export_exclude'] === 'true' ) ? true : false );
					$note['pi_version']     = $plugin_data['Version'];

					// Delete the old version so as to reset the timestamp key and remove potential import info
					if ( !empty( $key ) && !empty( $this->notes[$plugin][$key] ) ) {
						unset( $this->notes[$plugin][$key] );
					}

					// Add new note to notes array
					$this->notes[$plugin][$note['date']] = $note;

					$response_data           = array_merge( $response_data, $note ); // is this needed ?
					$response_data['action'] = 'edit';

					if ( !empty( $key ) ) {
						// Retrieve the edited note html to replace the previous version
						$plugin_note_content = $this->_add_plugin_note( $note, $plugin_data, $plugin, false );
					}
					else {
						// Ok, we had a new note, retrieve the new note html + new empty form
						$plugin_note_content  = $this->_add_plugin_note( $note, $plugin_data, $plugin, false );
						$plugin_note_content .= $this->_add_plugin_note( null, $plugin_data, $plugin, false );
					}
					
					// Reset key
					$key = $note['date'];
					
					// Save the new notes array
					$this->notes = $this->_get_set_notes( $this->notes );
				}
			}
			else {
				// no note sent nor template, so let's delete it
				if ( !empty( $key ) && !empty( $this->notes[$plugin][$key] ) ) {
					unset( $this->notes[$plugin][$key] );
				}

				// If delete means there are no more notes for the plugin, delete plugin array
				if ( count( $this->notes[$plugin] ) === 0 ) {
					unset( $this->notes[$plugin] );
				}

				$response_data['action'] = 'delete';

				$plugin_note_content = '';
				
				// Save the new notes array
				$this->notes = $this->_get_set_notes( $this->notes );
			}


			//							<span class="wp-plugin_note_result" style="display: none;"></span>

			// Save the new notes array
//			$this->options = $this->_get_set_options( $this->options );

			//			echo '<input type="hidden" name="wp-plugin_notes_nonce" value="' . wp_create_nonce( 'wp-plugin_notes_nonce' ) . '" />

			// Prepare response
			$response = new WP_Ajax_Response();

			$plugin_data = ( isset( $plugin_data ) ? $plugin_data : array( 'Name' => $plugin_name ) );
//			$plugin_note_content = $this->_add_plugin_note( $note, $plugin_data, $plugin, false );
//print 'action = ' . $response_data['action'];
			$response->add(
				array(
					'what'         => 'plugin_note',
	//				'action'       => ( ( $note_text ) ? ( ( $_POST['plugin_new_template'] === 'y' ) ? 'save_template' : 'edit' ) : 'delete' ),
					'action'       => $response_data['action'],
					'id'           => ( isset( $key ) ) ? $plugin_name . '_' . $key : $plugin_name,
					'data'         => $plugin_note_content,
	// @todo: generated nonce lijkt niet anders te zijn dan originele nonce
					'supplemental' => array( 'new_nonce' => wp_create_nonce( 'wp-plugin_notes_nonce' ) ),
				)
			);
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
*/
			exit;
		}





		/**
		 * Validate title input for note text and note template
		 *
		 * @param	string	$title
		 * @param	string	$context	'note' or 'template' to distinguish between validation for one or the other
		 * @return	string
		 */
		public static function validate_note_title( $title = null, $context = 'note' ) {
/*			$title = stripslashes( trim( $title ) );
			if ( is_string( $title ) && $title !== '' )
				return wp_filter_nohtml_kses( $title );
			else
				return '';
*/
			return self::validate_note_text( $title, $context );
		}
		
		/**
		 * Validate text input for note text and note template
		 *
		 * @param	string	$note_text
		 * @param	string	$context	'note' or 'template' to distinguish between validation for one or the other
		 * @return	string
		 */
		public static function validate_note_text( $note_text = null, $context = 'note' ) {
			$note_text = stripslashes( trim( $note_text ) );
			// @todo Do some replacements which aren't meant to change live?
			if ( is_string( $note_text ) && $note_text !== '' )
				return wp_kses( $note_text, Plugin_Notes::$allowed_tags );
			else
				return '';
		}

		/**
		 * Validate input for note color
		 *
		 * @todo adjust for jQuery color wheel input
		 * @todo remove # ?
		 *
		 * @param	string	$color
		 * @return	string
		 */
		public static function validate_note_color( $color = null ) {
			$color = sanitize_text_field( $color );
			if ( ( is_string( $color ) && $color !== '' ) && in_array( $color, Plugin_Notes::$boxcolors ) )
				return $color;
			else
				return Plugin_Notes_Manage_Settings_Option::$template_defaults['color'];
		}




		/**
		 * Validate a received timestamp
		 *
		 * @param   int|null    $timestamp
		 * @return  int|null
		 */
		public static function validate_timestamp( $timestamp = null ) {
			if ( ( isset( $timestamp ) && intval( $timestamp ) == $timestamp ) && ( $timestamp <= time() ) )
				return intval( $timestamp );
			else
				return null;
		}
		
		public static function validate_user_id( $id = null ) {
			// check is a user exists for that id (might be removed)
			// check if user has the capacity ?
			// what to do if not ?
			if ( isset( $id ) ) {
				return (int) $id;
			}
			else {
				return null;
			}
		}


		public static function validate_plugin_name( $plugin_name = null ) {
			
			$plugin_name = sanitize_title( $plugin_name );

			if ( self::is_valid_plugin( $plugin_name, false ) === true ) {
				return $plugin_name;
			}
			else {
				return '';
			}
		}

		public static function validate_plugin_slug( $plugin_slug = null ) {
			
			$plugin_slug = sanitize_text_field( $plugin_slug );
			
			if ( self::is_valid_plugin( $plugin_slug, true ) === true ) {
				return $plugin_slug;
			}
			else {
				return '';
			}
		}



		/**
		 *
		 *
		 * @todo use WP native validate_plugin( $plugin_path) function ?
		 * http://core.trac.wordpress.org/browser/trunk/wp-admin/includes/plugin.php
		 *
		 * @param	string	$plugin		Either a name or slug for a plugin
		 * @param	bool	$is_slug	Whether to validate for a plugin slug or a plugin name
		 *								Defaults to true (=slug)
         * @return bool
         */
		public static function is_valid_plugin( $plugin = null, $is_slug = true ) {
			static $plugins = null;

			if ( is_null( $plugins ) ) {
				// Get list of installed plugins
				// Key is the plugin file path and the value is an array of the plugin data.
				$plugins = get_plugins();
			}

			$valid = false;
			
			if ( isset( $plugin ) ) {
				if ( $is_slug === true && array_key_exists( $plugin, $plugins ) === true ) {
					$valid = true;
				}
				else if ( $is_slug === false ) {
					foreach ( $plugins as $data ) {
						if ( sanitize_title( $data['Name'] ) === $plugin ) {
							$valid = true;
							break;
						}
					}
				}
			}
			return $valid;
		}

	} // End of class
	
	/* Add our actions and filters */
	Plugin_Notes_Manage_Notes_Option::init();

} // End of class exists wrapper