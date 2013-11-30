<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Plugin_Notes' ) && ! class_exists( 'Plugin_Notes_Manage_Settings_Option' ) ) {
	/**
	 * @package		WordPress\Plugins\Plugin Notes
	 * @subpackage	Manage Settings Option
	 * @version		2.0
	 * @since		2.0
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 *
	 * @copyright	2013 Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Plugin_Notes_Manage_Settings_Option {


		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * @const	string	Name of the wp option in the options table containing our settings
		 */
		const NAME = 'plugin_notes_settings';


		/**
		 * @const	string	Minimum required capability to access the settings page and change the plugin options
		 */
		const REQUIRED_CAP = Plugin_Notes::REQUIRED_CAP;



		/* *** DEFINE CLASS PROPERTIES *** */


		/* *** Static Properties *** */

		/**
		 * @var string	Unique group identifier for all our options together
		 */
		public static $settings_group = '%s_settings-group';


		/**
		 * @var array	Default option values
		 */
		public static $defaults = array(
			'version'					=> null,
			'templates'					=> array(), // numerically indexed array of templates
			'last_selected_template'	=> null,
			'default_template'			=> null,
			'display'					=> 'plain',
			'use_wp_dateformat'			=> true,
			'dateformat'				=> null,
			'sortorder'					=> 'manual',
			'allow_markdown'			=> true,
			'allow_html'				=> true,
			'adminbar_url'				=> false,
//			'show_on_update'			=> false,
			'upgrading'					=> false, // will never change, only used to distinguish a call from the upgrade method
		);
		
		public static $template_defaults = array(
			'name'				=> null,
			'title'				=> null,
			'note'				=> null,
			'color'				=> '#EAF2FA',
			'private'			=> null,
			'export_exclude'	=> null,
			'timestamp'			=> null,
			'saved_by_user'		=> null,
		);
		


		/**
		 * @var	array	Available sort options
		 *				Will be set by set_properties() as the labels need translating
		 */
		public static $sort_options = array();


		/**
		 * @var	array	Available display style options
		 *				Will be set by set_properties() as the labels need translating
		 */
		public static $display_options = array();


		
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
			self::add_default_filter();
			add_filter( 'option_' . self::NAME, array( __CLASS__, 'filter_option' ) );

			/* The option validation routines remove the default filters to prevent failing to insert
			   an option if it's new. Let's add them back afterwards */
			add_action( 'add_option', array( __CLASS__, 'add_default_filter' ) );

			if ( version_compare( $GLOBALS['wp_version'], '3.7', '!=' ) ) {
				add_action( 'update_option', array( __CLASS__, 'add_default_filter' ) );
			}
			else {
				// Abuse a filter for WP 3.7 where the update_option filter is placed in the wrong location
				add_filter( 'pre_update_option_' . self::NAME, array( __CLASS__, 'pre_update_option' ) );
			}



			/* Refresh the $current property on succesfull option update */
			add_action( 'add_option_' . self::NAME, array( __CLASS__, 'on_add_option' ), 10, 2 );
			add_action( 'update_option_' . self::NAME, array( __CLASS__, 'on_update_option' ), 10, 2 );

			/* Lastly, we'll be saving our option during the upgrade routine *before* the setting
			   is registered (and therefore the validation is registered), so make sure that the
			   option is validated anyway. */
			add_filter( 'plugin_notes_save_settings_on_upgrade', array( __CLASS__, 'validate_options' ) );


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
			
			/* Retrieve and set the default date format */
			self::$defaults['dateformat'] = Plugin_Notes::get_date_format();

			/* Translate the sort option labels */
			self::$sort_options = array(
				'manual'			=> __( 'Manual sorting (jQuery)', Plugin_Notes::$name ),
				'date_asc'			=> __( 'Most recent last (asc)', Plugin_Notes::$name ),
				'date_desc'			=> __( 'Most recent first (desc)', Plugin_Notes::$name ),
				'author_asc'		=> __( 'Author name Ascending', Plugin_Notes::$name ),
				'author_desc'		=> __( 'Author name Descending', Plugin_Notes::$name ),
				'title_asc'			=> __( 'Title Ascending', Plugin_Notes::$name ),
				'title_desc'		=> __( 'Title Descending', Plugin_Notes::$name ),
//				'created_date_asc'	=> __( 'By original addition date (Don\'t change order)', Plugin_Notes::$name ),
			);

			/* Translate the display option labels */
			// @todo add height/width to image tag
			self::$display_options = array(
				'plain'				=> sprintf( '<img src="' . plugins_url( 'images/display-style-plain.png', __FILE__ ) . '" alt="%1$s" title="%1$s" /> <em>(%1$s)</em>', __( 'Plain', Plugin_Notes::$name ) ),
				'accordion'			=> sprintf( '<img src="' . plugins_url( 'images/display-style-accordion.png', __FILE__ ) . '" alt="%1$s" title="%1$s" /> <em>(%1$s)</em>', __( 'Accordion', Plugin_Notes::$name ) ),
				'tabs'				=> sprintf( '<img src="' . plugins_url( 'images/display-style-tabs.png', __FILE__ ) . '" alt="%1$s" title="%1$s" /> <em>(%1$s)</em>', __( 'Tabs', Plugin_Notes::$name ) ),
			);
		}
		
		/**
		 * Register our option
		 */
		public static function register_setting() {
			register_setting(
				self::$settings_group,
				self::NAME, // option name
				array( __CLASS__, 'validate_options' ) // validation callback
			);
		}


		/**
		 * Add filtering of the option default values
		 */
		public static function add_default_filter() {
			if ( has_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) ) === false ) {
				add_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
			};
		}

		/**
		 * Abuse filter to add filtering of the option default values
		 * WP 3.7 specific
		 */
		public static function pre_update_option( $new_value ) {
			self::add_default_filter();
			return $new_value;
		}


		/**
		 * Remove filtering of the option default values
		 *
		 * This is needed to allow for inserting of option if it doesn't exist
		 * Should be called from our validation routine
		 */
		public static function remove_default_filter() {
			remove_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
		}


		/**
		 * Filter option defaults
		 *
		 * This in effect means that get_option() will not return false if the option is not found,
		 * but will instead return our defaults. This way we always have all of our option values available.
		 */
		public static function filter_option_defaults() {
			self::refresh_current( self::$defaults );
			return self::$defaults;
		}


		/**
		 * Filter option
		 *
		 * This in effect means that get_option() will not just return our option from the database,
		 * but will instead return that option merged with our defaults.
		 * This way we always have all of our option values available. Even when we add new option
		 * values (to the defaults array) when the plugin is upgraded.
		 */
		public static function filter_option( $options ) {
			$options = self::array_filter_merge( self::$defaults, $options );
			self::refresh_current( $options );
			return $options;
		}


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
		public static function array_filter_merge( $defaults, $options ) {
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




		/* *** OPTION VALIDATION *** */

		/**
		 * Validated the settings received from our options page
		 *
		 * @todo inform user of validation errors on upgrade via transient API
		 *
		 * @param  array    $received     Our $_POST variables
		 * @return array    Cleaned settings to be saved to the db
		 */
		public static function validate_options( $received ) {

			self::remove_default_filter();

			/* Don't change anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( self::REQUIRED_CAP ) ) {
				return self::$current;
			}

			// Set the default dateformat
			if ( is_null( self::$defaults['dateformat'] ) ) {
				self::$defaults['dateformat'] = Plugin_Notes::get_date_format();
			}

			/* Start off with the default settings and where applicable, replace values with valid received values */
			$clean    = self::$defaults;
			$received = array_map( array( 'Plugin_Notes', 'trim_recursive' ), $received );

			foreach ( $clean as $key => $value ) {
				switch ( $key ) {
					/* Always set the version */
					case 'version':
						$clean['version'] = Plugin_Notes::VERSION;
						break;

					/* Validate the Template section */
					case 'templates':
						$clean['templates'] = self::validate_templates( $received[$key] );
						break;
						
						
					/* Validate the Preferences section */
					case 'display':
						if ( isset( $received[$key] ) && isset( self::$display_options[$received[$key]] ) ) {
							$clean[$key] = $received[$key];
						}
						break;
						
					case 'sortorder':
						if ( isset( $received[$key] ) && isset( self::$sort_options[$received[$key]] ) ) {
							$clean[$key] = $received[$key];
						}
						break;

					case 'last_selected_template':
					case 'default_template':
						if ( isset( $received[$key] ) && isset( self::$current['templates'][$received[$key]] ) ) {
							$clean[$key] = $received[$key];
						}
						break;

					case 'dateformat':
						if ( isset( $received[$key] ) && !empty( $received[$key] ) ) {
							$clean[$key] = self::validate_date_format( $received[$key] );
						}
						break;


					case 'use_wp_dateformat':
					case 'allow_markdown':
					case 'allow_html':
					case 'adminbar_url':
//					case 'show_on_update':
					default:
						$clean[$key] = ( isset( $received[$key] ) ? filter_var( $received[$key], FILTER_VALIDATE_BOOLEAN ) : false );
						break;
				}
			}

			return $clean;
		}
		
		
		public static function validate_templates( $templates ) {
			
			$clean = self::$defaults['templates'];
			
			if ( is_array( $templates ) && $templates !== array() ) {
				foreach ( $templates as $template ) {
					if ( is_array( $template ) && $template !== array() ) {
						$clean[] = self::validate_single_template( $template );
					}
				}
				unset( $template );
				
				// re-sort templates on alphabetic name ?
			}
			return $clean;
		}


		public static function validate_single_template( $template ) {

			/* Start off with the default template settings and where applicable, replace values with valid received values */
			$clean = self::$template_defaults;

			foreach ( $clean as $key => $value ) {
				switch ( $key ) {
					case 'name':
						$clean[$key] = self::validate_template_name( $template[$key] );
						break;

					case 'title':
						$clean[$key] = Plugin_Notes_Manage_Notes_Option::validate_note_title( $template[$key], 'template' );
						break;

					case 'note':
						$clean[$key] = Plugin_Notes_Manage_Notes_Option::validate_note_text( $template[$key], 'template' );
						break;

					case 'color':
						$clean[$key] = Plugin_Notes_Manage_Notes_Option::validate_note_color( $template[$key] );
						break;
						
					case 'timestamp':
						if ( isset( $template[$key] ) ) {
							// @todo - update this to time() if other values have changed!
							$clean[$key] = Plugin_Notes_Manage_Notes_Option::validate_timestamp( $template[$key] );
						}
						else {
							// new template
							$clean[$key] = time();
						}
						break;

					case 'saved_by_user':
						if ( isset( $template[$key] ) ) {
							// @todo - update this to current user id if other values have changed!
							$clean[$key] = Plugin_Notes_Manage_Notes_Option::validate_user_id( $template[$key] );
						}
						else {
							// New template
							$clean[$key] = get_current_user_id();
						}
						break;

					case 'private':
					case 'export_exclude':
					default:
						$clean[$key] = ( isset( $template[$key] ) ? filter_var( $template[$key], FILTER_VALIDATE_BOOLEAN ) : false );
						break;
				}
				unset( $key, $value );
			}
			return $clean;
		}
		


		

		// @todo - via ajax call ? or just let users do it via jQuery (remove div), but still need to save settings
		// maybe show warning to save settings after delete template button is clicked ?
		// same for add template button
/*		public static function delete_template() {
		}
*/

		public static function validate_template_name( $name ) {
			return sanitize_key( $name );
		}


		/**
		 * Validate a received date format
		 *
		 * @todo
		 * No real way to validate as a date format can contain free text and any post field
		 * is automatically a string so just check whether it's not empty and not the same
		 * as the known date format
		 * @todo compare to blog date format setting ?
		 * @todo add option to always keep in line with blog date format setting ?
		 * Possible solution:
		 * strip all whitespace, escaped chars and non-letters (numbers, dashes, comma's etc)
		 * check if any of the accepted dateformat characters are present
		 *
		 *
		 * @param   string  $format_string
		 * @return  string
		 */
		public static function validate_date_format( $format_string ) {
			return sanitize_text_field( $format_string );
		}


	} // End of class
	
	/* Add our actions and filters */
	Plugin_Notes_Manage_Settings_Option::init();
} // End of class exists wrapper