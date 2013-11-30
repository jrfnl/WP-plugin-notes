<?php
/*
Plugin Name: Plugin Notes
Plugin URI: http://wordpress.org/extend/plugins/plugin-notes/
Description: Allows you to add notes to plugins. Simple and sweet.
Version: 2.0
Author: Mohammad Jangda
Author URI: http://digitalize.ca/
Contributor: Juliette Reinders Folmer
Contributor URI: http://adviesenzo.nl/
Text Domain: plugin-notes
Domain Path: /languages/


Copyright 2009-2010 Mohammad Jangda
*/
/*
GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
DEVELOPERS note: when regenerating a .pot file for the plugin, either (manually) removed the strings which are already included in the WP core or make sure that the translators note ('no need to translate') is included
*/


/*
@todo: will using timestamps as keys really work ? What if - althought unlikely - two admins add a note at the same time ?
maybe just use normal numeric keys and then use array_multisort ? then again, in that sense, option could have changed between retrieval and writing it back. Is this really an issue ?
Also how to retrieve the key of the new note if using numerical keys? would be needed for ajax response prep
*/

if ( !class_exists( 'plugin_notes' ) ) {

	if( !defined( 'PLUGIN_NOTES_VERSION' ) ) { define( 'PLUGIN_NOTES_VERSION', '2.0' ); }
	if( !defined( 'PLUGIN_NOTES_BASENAME' ) ) { define( 'PLUGIN_NOTES_BASENAME', plugin_basename( __FILE__ ) ); } // = dir/file.php
	if( !defined( 'PLUGIN_NOTES_NAME' ) ) { define( 'PLUGIN_NOTES_NAME', dirname( PLUGIN_NOTES_BASENAME ) ); } // = dir
	if( !defined( 'PLUGIN_NOTES_URL' ) ) { define( 'PLUGIN_NOTES_URL', plugin_dir_url( __FILE__ ) ); } // has trailing slash
	if( !defined( 'PLUGIN_NOTES_DIR' ) ) { define( 'PLUGIN_NOTES_DIR', plugin_dir_path( __FILE__ ) ); } // has trailing slash

/*pr_var( __FILE__, '__FILE__', true );
pr_var( plugin_dir_path( __FILE__ ), 'plugin_dir_path( __FILE__ )', true );
pr_var( PLUGIN_NOTES_DIR, 'PLUGIN_NOTES_DIR', true );
exit;*/

	class plugin_notes {

		/** Minimum required capability to see and/or update the notes */
		var $required_role = 'activate_plugins';

		/** Page on which the plugin functions and underneath which the settings page will be hooked */
		var $parent_page = 'plugins.php';

		/** settings page registration hook suffix */
		var $hook;


		/** Name of options variable */
		var $notes_option = 'plugin_notes';

		/** The notes array keys for settings relating to this plugin, rather than to notes about plugins */
		var $option_keys = array(
			'version'				=>	'plugin-notes_version',
			'template'				=>	'plugin-notes_template',
			'sortorder'				=>	'plugin-notes_sortorder',
			'default_note_color'	=>	'plugin-notes_defaultcolor',
			'custom_dateformat'		=>	'plugin-notes_dateformat',
		);

		/** Variable holding the current options array for this plugin */
		var $options = null;

		var $nonce_added = false;


		/** Allowed html tags for notes */
		var $allowed_tags = array(
			'a' => array(
				'href' => true,
				'title' => true,
				'target' => true,
			),
			'br' => array(),
			'p' => array(),
			'b' => array(),
			'strong' => array(),
			'i' => array(),
			'em' => array(),
			'u' => array(),
			'img' => array(
				'src' => true,
				'height' => true,
				'width' => true,
			),
			'hr' => array(),
		);

		/** Available box colors */
		var $boxcolors = array(
			'#EBF9E6', // light green
			'#F0F8E2', // lighter green
			'#F9F7E6', // light yellow
			'#EAF2FA', // light blue
			'#E6F9F9', // brighter blue
			'#F9E8E6', // light red
			'#F9E6F4', // light pink
			'#F9F0E6', // earth
			'#E9E2F8', // light purple
			'#D7DADD', // light grey
			'#EAECED', // very light grey
		);
		/** Default box color */
		var $defaultcolor	= '#EAF2FA';
		
		/** Dateformat to be used by plugin notes */
		var $dateformat = null;
		
		/** Default note sort order */
		var $sortorder = 'asc';

//		var $buttons = array();

/*		var $buttons = array(
			'add_new_note'		=>	'Add new plugin note',
			'edit_note'			=>	'Edit note',
			'delete_note'		=>	'Delete note',
			'save_note'			=>	'Save',
			'cancel_edit'		=>	'Cancel',
			'save_as_template'	=>	'Save as template for new notes',
			'title_doubleclick'	=>	'Double click to edit me!',
			'title_loading'		=>	'Loading...',
			'label_notecolor'	=>	'Note color:',
			'versionnr'			=>	'v.',
		);*/


		/**
		 * Backward compatibility for constructor
		 */
		function plugin_notes() {
			$this->__construct();
		}

		/**
		 * Object constructor for plugin
		 */
		function __construct() {

			/* Don't do anything if we're not in admin */
			if ( !is_admin() )
				return;


			/* Get the current options set */
			$this->options = $this->_getset_options();
pr_var( $this->options, 'options received from db', true );

			/* Check if we have any activation or upgrade actions to do */
			if( !isset( $this->options[$this->option_keys['version']] ) || $this->options[$this->option_keys['version']] !== PLUGIN_NOTES_VERSION ) {
				add_action( 'init', array( &$this, 'upgrade_plugin' ), 8 );
			}
			// Make sure that an upgrade check is done on (re-)activation as well.
			register_activation_hook( __FILE__, array( &$this, 'upgrade_plugin' ) );


			// Register the plugin initialization action and load localized text strings
			add_action( 'init', array( &$this, 'init' ) );
			add_action( 'init', array( &$this, 'load_textdomain' ) );
		}
		
		
		/** ******************* ADMINISTRATIVE METHODS ******************* **/


		function init() {
			
			/* Don't do anything if user does not have the required capability */
			if ( !is_admin() || current_user_can( $this->required_role ) === false )
				return;
				

			/* Register the settings (import/export/purge) page */
			add_action( 'admin_menu', array( &$this, 'add_config_page' ) );
			//network_admin_menu

			// Add settings link to plugin page
//			add_filter( 'plugin_action_links', array( &$this, 'add_settings_link' ), 10, 2 );
			add_filter( 'plugin_action_links_' . PLUGIN_NOTES_BASENAME , array( &$this, 'add_settings_link' ), 10, 2 );


			/* Register our option array */
//			register_setting( $this->notes_option, $this->notes_option, array( &$this, 'options_validate' ) );


			/* Add notes to plugin row */
			add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 4 );

			// Add output filters to the note (string replacement and markdown syntax)
			add_filter( 'plugin_notes_notetext', array( &$this, 'filter_kses' ), 10, 1 );
			add_filter( 'plugin_notes_notetext', array( &$this, 'filter_variables_replace' ), 10, 3 );
			add_filter( 'plugin_notes_notetext', array( &$this, 'filter_markdown' ), 10, 1 );
			add_filter( 'plugin_notes_notetext', array( &$this, 'filter_breaks' ), 10, 1 );
			add_filter( 'plugin_notes_pi_versionnr', array( &$this, 'filter_age_pi_version' ), 10, 2 );


			/* Add ajax action to edit/save posts */
			add_action( 'wp_ajax_plugin_notes_edit_comment', array( &$this, 'ajax_edit_plugin_note' ) );



			/* Add js and css files */
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );


			/* Add contextual help */
			if ( version_compare( $GLOBALS['wp_version'], '3.3', '>=' ) === true ) {

				// Add helptab *behind* existing core page help tabs
				// (reason for using admin_head hook instead of load hook)
				add_action( 'admin_head-' . $this->parent_page, array( &$this, 'add_help_tab' ) );
				//add_action( 'load-'. $this->parent_page, array( &$this, 'add_help_tab' ) );
			}
			else {
				// @todo check which version works (best)
//				add_action( 'admin_init', array( &$this, 'add_contextual_help_a' ) );
				add_filter( 'contextual_help', array( &$this, 'add_contextual_help_b' ), 10, 3 );
			}
		}


		/**
		 * Localization of text used in the plugin
		 * Also initialize the language variables used for the form/action buttons
		 * to make them available to both PHP and js
		 *
		 * @todo - check if button string localization now works if not in this function, but straight in
		 * the note functions and if so, remove here
		 */
		function load_textdomain() {
//			global $pagenow;

			/* Only load the text strings where relevant */
			/* Page check removed as menu title also needs localization, so strings need always be loaded */
			if ( ( is_admin() && current_user_can( $this->required_role ) === true ) /*&& ( in_array( $pagenow, array( $this->parent_page, $this->hook, 'admin-ajax.php' ) ) ) === true*/ ) {

				load_plugin_textdomain( PLUGIN_NOTES_NAME, false, PLUGIN_NOTES_NAME . '/languages/' );

/*				$this->buttons = array(
					'add_new_note'		=>	__( 'Add new plugin note', PLUGIN_NOTES_NAME ),
					'edit_note'			=>	__( 'Edit note', PLUGIN_NOTES_NAME ),
					'delete_note'		=>	__( 'Delete note', PLUGIN_NOTES_NAME ),
					'save_note'			=>	__( 'Save' ),
					'cancel_edit'		=>	__( 'Cancel' ),
					'save_as_template'	=>	__( 'Save as template for new notes', PLUGIN_NOTES_NAME ),
					'title_doubleclick'	=>	__( 'Double click to edit me!', PLUGIN_NOTES_NAME ),
					'title_loading'		=>	__( 'Loading...', PLUGIN_NOTES_NAME ),
					'label_notecolor'	=>	__( 'Note color:', PLUGIN_NOTES_NAME ),
					'versionnr'			=>	__( 'v. %s', PLUGIN_NOTES_NAME ),
				);
*/
			}
		}

		/**
		 * Register the config page for all users that have the required capability
		 *
		 * @since 3.0
		 */
		function add_config_page() {
			$this->hook = add_plugins_page( __( 'Plugin Notes Options', PLUGIN_NOTES_NAME ), __( 'Plugin Notes Options', PLUGIN_NOTES_NAME ), $this->required_role, PLUGIN_NOTES_NAME, array( &$this, 'config_page' ) );
		}

		/**
		 * Function to re-direct the config page to the settings file
		 */
		function config_page() {

			//must check that the user has the required capability
			if( !current_user_can( $this->required_role ) ) {
				/* TRANSLATORS: no need to translate - standard WP core translation will be used */
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			include_once( PLUGIN_NOTES_DIR . 'plugin_notes_settings.php' );
			$settings = new plugin_notes_settings( &$this );
//			$settings->process_page();
		}


		/**
		 * Function used when activating and/or upgrading the plugin
		 *
		 * Initial activate: Save version number to option
		 * Upgrade for v2.0: Update old options structure to new
		 *
		 * @return bool
		 */
		function upgrade_plugin() {

			$upgraded = false;

pr_var( $this->options, 'Original options within upgrade routine', true );
			// First time activation of plugin
			if( is_array( $this->options ) === false ||  count( $this->options ) === 0 ) {
				$this->options[$this->option_keys['version']] = PLUGIN_NOTES_VERSION;
				$upgraded = true;
			}

			// Upgrades for any version of this plugin lower than 2.0
			// Version nr has to be hard coded to be future-proof, i.e. facilitate upgrade routines for various versions
			if( !isset( $this->options[$this->option_keys['version']] ) || version_compare( $this->options[$this->option_keys['version']], '2.0', '<') ) {

				foreach( $this->options as $key => $note ) {

					// Change date from formatted date to timestamp
					if( isset( $note['date'] ) && $note['date'] !== '' ) {
						$timestamp = $this->_formatted_date_to_timestamp( $note['date'] );
						if( !is_null( $timestamp ) && !is_null( $this->_validate_timestamp( $timestamp ) ) ) {
							$note['original_date'] = $note['date'];
							$note['date'] = $timestamp;
						}
						unset( $timestamp );
					}

					// Change plugin notes from single-dimension array to multi-array
					// just not for our own options
					if( in_array( $key, $this->option_keys, true ) === false &&
						( is_array( $note ) && count( $note ) > 0 ) ) {

						unset( $this->options[$key] );
						$this->options[$key][$note['date']] = $note;
					}
				}
				unset( $key, $note );

				$this->options[$this->option_keys['version']] = '2.0';
				$upgraded = true;
			}

			if( $upgraded === true ) {
				$this->options = $this->_getset_options( $this->options );
pr_var( $this->options, 'Options after upgrade', true );
			}
			return $upgraded;
		}


		/**
		 * Retrieve a timestamp based on a formatted date
		 *
		 * @usedby	upgrade_plugin()
		 * @param	string		$datestring		The date to get the timestamp for
		 * @return	int|null	the timestamp or null if conversion failed
		 */
		function _formatted_date_to_timestamp( $datestring ) {
			static $str2timestamp = null;
			
			// Try simple conversions first
			$timestamp = $this->strtotimestamp( $datestring );
			if( !is_null( $timestamp ) ) {
				return $timestamp;
			}
			unset( $timestamp );

			// If that didn't work (very likely), do it the more complex, but pretty accurate way
			if( is_null( $str2timestamp ) ) {
				include_once( PLUGIN_NOTES_DIR . 'inc/plugin_notes_date_string_to_timestamp.php' );
				$str2timestamp = plugin_notes_date_string_to_timestamp::getInstance();
			}
			return $str2timestamp->translate_to_timestamp( $datestring, $this->dateformat );
		}
		
		/**
		 * Try to retrieve a timestamp from a datestring using PHP native functions
		 *
		 * @param	string		$datestring		The date to get the timestamp for
		 * @return	int|null	the timestamp or null if conversion failed
		 */
		function strtotimestamp( $datestring ) {

			// Try strtotime()
			$timestamp = strtotime( $datestring );
			if( ( $timestamp !== false && $timestamp !== -1 ) && ( date( $this->dateformat, $timestamp ) === $datestring || date_i18n( $this->dateformat, $timestamp ) === $datestring ) ) {
				return $timestamp;
			}
			unset( $timestamp );

			// Try date_parse_from_format()
			if( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {

				$date_obj = date_parse_from_format( $this->dateformat, $datestring );
				$timestamp = $date_obj->getTimestamp();
				if( is_int( $timestamp ) && ( date( $this->dateformat, $timestamp ) === $datestring || date_i18n( $this->dateformat, $timestamp ) === $datestring ) ) {
					return $timestamp;
				}
				unset( $date_obj, $timestamp );
			}
			// Oh well, it didn't work
			return null;
		}




		/**
		 * Adds necessary javascript and css files
		 * @todo also add these for plugin update page in view mode
		 * @todo add js functions to add the notes to the plugin page via AJAX
		 */
		function enqueue_scripts() {
			global $pagenow;

			if( $pagenow === $this->parent_page ) {
//				wp_enqueue_script( PLUGIN_NOTES_NAME, PLUGIN_NOTES_URL . 'plugin-notes.js', array( 'jquery', 'wp-ajax-response' ), PLUGIN_NOTES_VERSION, true );
				wp_enqueue_script( PLUGIN_NOTES_NAME, PLUGIN_NOTES_URL . 'plugin-notes.js', array( 'jquery', 'jquery-effects-core', 'jquery-effects-fade', 'jquery-effects-highlight', 'wp-ajax-response' ), PLUGIN_NOTES_VERSION, true );
				wp_enqueue_style( PLUGIN_NOTES_NAME, PLUGIN_NOTES_URL . 'plugin-notes.css', false, PLUGIN_NOTES_VERSION, 'all' );
				
				wp_localize_script( PLUGIN_NOTES_NAME, 'i18n_plugin_notes', $this->get_javascript_i18n() );
			}
		}

		/**
		 * Retrieve the strings for use in the javascript file
		 *
		 * @todo check if that this is working [Yup! working - loading in footer] 
		 * and/or necessary (did I already fix it differently for the button strings ?)
		 *
		 * @return	array
		 */
		function get_javascript_i18n() {
			$strings = array(
				'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
				'confirm_delete'	=> esc_js( __( 'Are you sure you want to delete this note?', PLUGIN_NOTES_NAME ) ),
				'confirm_template'	=> sprintf( esc_js( __( 'Are you sure you want to save this note as a template?%sAny changes you made will not be saved to this particular plugin note.%s%sAlso beware: saving this note as the plugin notes template will overwrite any previously saved templates!', PLUGIN_NOTES_NAME ) ), "\n\r", "\n\r", "\n\r" ),
				'success_save_template'	=> esc_js( __( 'New notes template saved succesfully', PLUGIN_NOTES_NAME ) ),
				'success_editsave'	=> esc_js( __( 'Your changes have been saved succesfully', PLUGIN_NOTES_NAME ) ),
				'success_delete'	=> esc_js( __( 'Note deleted', PLUGIN_NOTES_NAME ) ),
				// Duplicate phrase with phrase within ajax function
				'error_nonce'		=> esc_js( __( 'Don\'t think you\'re supposed to be here...', PLUGIN_NOTES_NAME ) ),
				// Duplicate phrase with phrase within ajax function
				'error_loggedin'	=> esc_js( sprintf( __( 'Your session seems to have expired. You need to <a href="%s" title="Login">log in</a> again.', PLUGIN_NOTES_NAME ), wp_login_url( $this->plugin_options_url() ) ) ),
//				'error_capacity'	=> esc_js( __( 'Sorry, you do not have permission to activate plugins.', PLUGIN_NOTES_NAME ) ),
			);
/*			foreach( $this->buttons as $k => $v ) {
				$strings['button_' . $k]	= esc_js( $v );
			}
			unset( $k, $v );
*/
			return $strings;
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
					'callback' => array( &$this, 'get_helptext' ),
					)
				);
			}
		}

		/**
		 * Adds contextual help text to the plugin page
		 * Backwards compatibility for WP < 3.3.
		 */
		function add_contextual_help_a( $screen, $help ) {
			add_contextual_help( 'plugins', $this->get_helptext( null, null, false ) );
		}

		/**
		 * Adds contextual help text to the plugin page
		 * Backwards compatibility for WP < 3.3.
		 */
		function add_contextual_help_b( $contextual_help, $screen_id, $screen ) {

			if( $screen_id === 'plugins' ) {
				return $this->get_helptext( null, null, false );
			}
		}

		/**
		 * Function containing the helptext string
		 *
		 * @param	bool	$echo	whether to echo or return the string
		 * @return	string			help text
		 */
		function get_helptext( $screen, $tab, $echo = true ) {
			$helptext = '
								<p>' . sprintf( __( 'The <em><a href="%s">Plugin Notes</a></em> plugin let\'s you add notes for each installed plugin. This can be useful for documenting changes you made or how and where you use a plugin in your website.', PLUGIN_NOTES_NAME ), 'http://wordpress.org/extend/plugins/plugin-notes/" target="_blank" class="ext-link') . '</p>
								<p>' . sprintf( __( 'You can use <a href="%s">Markdown syntax</a> in your notes as well as HTML.', PLUGIN_NOTES_NAME ), 'http://daringfireball.net/projects/markdown/syntax" target="_blank" class="ext-link' ) . '</p>
								<p>' . sprintf( __( 'On top of that, you can even use a <a href="%s">number of variables</a> which will automagically be replaced, such as for example <em>%%WPURI_LINK%%</em> which would be replaced by a link to the WordPress plugin repository for this plugin. Neat isn\'t it ?', PLUGIN_NOTES_NAME ), 'http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link' ) . '</p>
								<p>' . __( 'Lastly, you can save a note as a template for new notes. If you use a fixed format for your plugin notes, you will probably like the efficiency of this.', PLUGIN_NOTES_NAME ) . '</p>
								<p>' . sprintf( __( 'For more information: <a href="%1$s">Plugin home</a> | <a href="%2$s">FAQ</a>', PLUGIN_NOTES_NAME ), 'http://wordpress.org/extend/plugins/plugin-notes/" target="_blank" class="ext-link', 'http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link' ) . '</p>';

			if( $echo === true ) {
				echo $helptext;
			}
			else {
				return $helptext;
			}
		}



		/**
		 * Add screen options to the plugin page
		 */
		function add_screen_options() {
			// @todo Add notes checkbox + start showing/hidden toggle ?
		}



		/**
		 * Add settings/maintenance link to plugin-notes row for import/export/purge page
		 *
		 * @param	array	$links	Current links for the current plugin
		 * @param	string	$file	The file for the current plugin
		 * @return	array
		 */
		function add_settings_link( $links, $file ) {
			if ( $file === PLUGIN_NOTES_BASENAME ) {
				/* TRANSLATORS: no need to translate - standard WP core translation will be used */
				$links[] = '<a href="' . esc_url( $this->plugin_options_url() ) . '">' . esc_html__( 'Settings' ) . '</a>';
			}
			return $links;
		}

		/**
		 * Return absolute URL of options page
		 *
		 * @return string
		 */
		function plugin_options_url() {
			return admin_url( $this->parent_page . '?page=' . PLUGIN_NOTES_NAME );
		}


		/** ******************* SOME HELPFUL GET/SET METHODS ******************* **/


		/**
		 * Function to change the allowed html tags for notes or even remove the ability to use HTML completely
		 *
		 * @param	array	$tags - pass an empty array to disallow all html tags
		 * @returns	bool	whether the list was succesfully changed
		 */
		function set_allowed_tags( $tags = array() ) {
			$changed = false;

			if( is_array( $tags ) ) {

				if( count( $tags ) === 0 ) {
					remove_filter( 'plugin_notes_note', array( &$this, 'filter_kses' ) );
					add_filter( 'plugin_notes_note', 'wp_filter_nohtml_kses' );

					$changed = true;
				}
				else {
					$cleantags = array();
					foreach( $tags as $tag ) {
						$cleantags[] = tag_escape( $tag );
					}
					$this->allowed_tags = $cleantags;
					unset( $cleantags, $tag );

					$changed = true;
				}
			}
			return $changed;
		}


		/**
		 * Intelligently set/get the plugin notes options and override the default if needed
		 *
		 * @param	array|null	$update		Optional: changed $this->options array - make sure the new array is validated first!
		 */
		function _getset_options( $update = null ) {
			static $original_options = false;
			static $color_prop_set = false;
			static $dateformat_prop_set = false;
			static $sortorder_prop_set = false;

			// Do we have something to update ?
			if( !is_null( $update ) && $update !== $original_options ) {
				
				$update = $this->sort_notes( $update );

				// Ok, nobody else updated in the mean time
				if( $original_options === get_option( $this->notes_option ) ) {
					update_option( $this->notes_option, $update );
					$this->options = $original_options = $update;

					$color_prop_set = false;
					$dateformat_prop_set = false;
					$sortorder_prop_set = false;
				}
				else {
					// @todo:
					// do diff between original and db
					// merge diffs with update array
					//update
				}
			}

			if( ( is_null( $this->options ) || $this->options === false ) || is_array( $this->options ) === false ||  count( $this->options ) === 0 ) {
				// returns either the option array or false if option not found
				$this->options = $original_options = get_option( $this->notes_option );
			}
			
			// Modify the default note color if necessary
			if( $color_prop_set === false && isset( $this->options[$this->option_keys['default_note_color']] ) ) {
				$this->defaultcolor = $this->options[$this->option_keys['default_note_color']];
				$color_prop_set = true;
			}
			// Modify the default sortorder if necessary
			if( $sortorder_prop_set === false && isset( $this->options[$this->option_keys['sortorder']] ) ) {
				$this->sortorder = $this->options[$this->option_keys['sortorder']];
				$sortorder_prop_set = true;
			}
			// Modify the date format if necessary
			if( $dateformat_prop_set === false || is_null( $this->dateformat ) ) {
				if( isset( $this->options[$this->option_keys['custom_dateformat']] ) ) {
					$this->dateformat = $this->options[$this->option_keys['custom_dateformat']];
				}
				else {
					$this->dateformat = get_option( 'date_format' );
				}
				$dateformat_prop_set = true;
			}
			return $this->options;
		}

		/**
		 * Sort the notes per plugin based on the note timestamp (=key)
		 *
		 * @param	array	$options	complete options array for this plugin
		 * @return	array	sorted options array
		 */
		function sort_notes( $options ) {

			$sortorder = ( isset( $options[$this->option_keys['sortorder']] ) ? $options[$this->option_keys['sortorder']] : $this->sortorder );

			foreach( $options as $key => $notes ) {

				// Exclude our own options and only sort if more than one note found
				if( in_array( $key, $this->option_keys ) === false && ( is_array( $notes ) && count( $notes ) > 1 ) ) {
			
					// Sort the notes by key (=timestamp)
					if( $sortorder === 'asc' ) {
						ksort( $notes );
					}
					else {
						krsort( $notes );
					}
					$options[$key] = $notes;
				}
			}
			unset( $sortorder );
			
			return $options;
		}

		
		/**
		 * Get/remember the plugin data
		 * Efficiency function
		 *
		 * @param	string	$plugin_file	dir/filename.php of plugin
		 * @param	array	$plugin_data	Optional: any plugin_data already available
		 * @return	array|null
		 */
		function _getremember_plugin_data( $plugin_file, $plugin_data = null ) {
			static $plugin_info = null;

			if( ( isset( $plugin_file ) && $plugin_file !== '' ) ) {

				if( is_array( $plugin_data ) && count( $plugin_data ) > 0 ) {

					if( isset( $plugin_info[$plugin_file] ) && ( is_array( $plugin_info[$plugin_file] ) && count( $plugin_info[$plugin_file] ) > 0 ) ) {
						$plugin_info[$plugin_file] = array_merge( $plugin_info[$plugin_file], $plugin_data );
					}
					else {
						$plugin_info[$plugin_file] = $plugin_data;
					}
				}

				if( !isset( $plugin_info[$plugin_file] ) ) {

					if( is_file( WP_PLUGIN_DIR . '/' . $plugin_file ) && is_readable( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {

						$plugin_info[$plugin_file] = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, true );
					}
					else {
						return null;
					}
				}

				return $plugin_info[$plugin_file];
			}
			return null;
		}


		/** ******************* OUTPUT GENERATION METHODS ******************* **/


		/**
		 * Adds a nonce to the plugin page so we don't get nasty people doing nasty things
		 * and start adding the notes
		 */
		function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $context ) {
/*pr_var( array(
 'plugin_meta' => $plugin_meta,
 'plugin_file'	=> $plugin_file,
 'plugin_data'	=> $plugin_data,
 'context'		=> $context,
), 'received vars', true );*/
			$notes = isset( $this->options[$plugin_file] ) ? $this->options[$plugin_file] : null;
			$plugin_data = $this->_getremember_plugin_data( $plugin_file, $plugin_data );

			if( !$this->nonce_added ) {
				wp_nonce_field( 'wp-plugin_notes_nonce', 'wp-plugin_notes_nonce', true, true );
//				echo '<input type="hidden" name="wp-plugin_notes_nonce" value="' . wp_create_nonce( 'wp-plugin_notes_nonce' ) . '" />';
				$this->nonce_added = true;
			}
			
			$this->_add_plugin_notes( $notes, $plugin_data, $plugin_file, true );

			return $plugin_meta;
		}
/*


Retrieve or display nonce hidden field for forms.

The nonce field is used to validate that the contents of the form came from the location on the current site and not somewhere else. The nonce does not offer absolute protection, but should protect against most cases. It is very important to use nonce fields in forms.

If you set $echo to false and set $referer to true, then you will need to retrieve the referer field using wp_referer_field(). If you have the $referer set to true and are echoing the nonce field, it will also echo the referer field.

The $action and $name are optional, but if you want to have better security, it is strongly suggested to set those two parameters. It is easier to just call the function without any parameters, because validation of the nonce doesn't require any parameters, but since crackers know what the default is it won't be difficult for them to find a way around your nonce and cause damage.

The input name will be whatever $name value you gave. The input value will be the nonce creation value.
Usage

<?php wp_nonce_field( $action, $name, $referer, $echo ) ?>
Parameters

$action
    (string) (optional) Action name. This is the unique identifier for this nonce. Optional but recommended.

        Default: -1 

$name
    (string) (optional) Nonce name. This is the name of the hidden field form variable (once submitted you can access the nonce via $_POST[$name]).

        Default: "_wpnonce" 

$referer
    (boolean) (optional) default true. Whether to set the referer field for validation.

        Default: true 

$echo
    (boolean) (optional) default true. Whether to display or return hidden form field.

        Default: true 





*/

		function _add_plugin_notes( $notes = null, $plugin_data, $plugin_file, $echo = true ) {
			
			$plugin_data = $this->_getremember_plugin_data( $plugin_file, $plugin_data );
			
			$output = '';
			
			if( !is_null( $notes ) && ( is_array( $notes ) && count( $notes ) > 0 ) ) {

				// Add note blocks
				foreach( $notes as $key => $note ) {
					// Only add block if we actually have a note
					if( is_array( $note ) && count( $note ) > 0 && ! empty( $note['note'] ) ) {
						$output .= $this->_add_plugin_note( $note, $plugin_data, $plugin_file, false );
					}
				}
			}
			// Always add 'add new note link' + 'empty' form (with template)
			$output .= $this->_add_plugin_note( null, $plugin_data, $plugin_file, false );
			
			if( $echo === true ) {
				echo $output;
			}
			else {
				return $output;
			}
		}

		/**
		 * Outputs pluging note for the specified plugin
		 */
		function _add_plugin_note( $note = null, $plugin_data, $plugin_file, $echo = true ) {

			$plugin_data = $this->_getremember_plugin_data( $plugin_file, $plugin_data );

//pr_var( $note, 'note as received from db', true );
			// Merge defaults with received note
			$note = wp_parse_args( $note, array(
//				'new'				=> true,
				'name'				=> $this->_get_plugin_safe_name( $plugin_data['Name'] ),
//				'class'				=> 'wp-plugin_note_box_blank',
				'class'				=> 'wp-pn_note_box blank',
				'note'				=> null,
//				'filtered_note'		=> null,
				'user'				=> null,
//				'username'			=> null,
				'date'				=> null,
//				'formatted_date'	=> null,
				'original_date'		=> null,
				'color'				=> $this->defaultcolor,
//				'style'				=> null,
				'pi_version'		=> null,
//				'actions'			=> array(),
				'import_date'		=> null,
//				'import_formatted_date'	=> null,
				'import_user'		=> null,
//				'import_username'	=> null,
				)
			);
			
			$attr_key = ( is_null( $note['date'] ) ? '' : '_' . esc_attr( $note['date'] ) );

			$filtered_note = null;
			$actions = array();
//			$author = null;
//			$formatted_date = null;
//			$importer = null;
//			$formatted_import_date = null;

			$credits = array();


//pr_var( $note, 'note after data merge', true );
			if( !is_array( $note ) || count( $note ) === 0 || empty( $note['note'] ) ) {
//				$note['actions'][] = 'add';
				$actions[] = 'add';
			}
			// Prep some data for display
			else {
//				$note['new'] = false;
				$note['class'] = 'wp-pn_note_box';
				$filtered_note = apply_filters( 'plugin_notes_notetext', $note['note'], $plugin_data, $plugin_file );

				if( !is_null( $note['user'] ) ) {
					$author = get_userdata( $note['user'] );
//					$author = get_user_by( 'id', $note['user'] );
//					$author = $author->display_name;
					// Allow for notes made by removed users
					if( is_object( $author ) ) {
						$credits[] = esc_html( $author->display_name );
					}
					unset( $author );
				}
				// Only show import info if the note hasn't been changed since
				else if( !is_null( $note['import_user'] ) && !is_null( $note['import_date'] ) ) {
					$importer = get_userdata( $note['import_user'] );
//					$importer = get_user_by( 'id', $note['import_user'] );
//					$importer = $importer->display_name;
					$formatted_import_date = ( is_int( $note['import_date'] ) ? date_i18n( $this->dateformat, $note['import_date'] ) : null );

					// Allow for notes imported by removed users
					if( is_object( $importer ) && !is_null( $formatted_import_date ) ) {
						$credits[] = sprintf( esc_html__( 'Imported by %1$s on %2$s', PLUGIN_NOTES_NAME ), esc_html( $importer->display_name ), esc_html( $formatted_import_date ) );
					}
					else if( is_object( $importer ) ) {
						$credits[] = sprintf( esc_html__( 'Imported by %s', PLUGIN_NOTES_NAME ), esc_html( $importer->display_name ) );
					}
					unset( $importer, $formatted_import_date );
				}

				// Format new style timestamp-date or leave as-is for old style formatted date
				// @todo: check that the date we receive from the db is cast back to an int and not still a string
				$credits[] = esc_html( ( is_int( $note['date'] ) ? date_i18n( $this->dateformat, $note['date'] ) : ( isset( $note['original_date'] ) ? $note['original_date'] : $note['date'] ) ) );

				if( !is_null( $note['pi_version'] ) ) {
					$credits[] = sprintf( __( 'v. %s', PLUGIN_NOTES_NAME ), apply_filters( 'plugin_notes_pi_versionnr', $note['pi_version'], $plugin_data['Version'] ) );
				}
	
				if( WP_DEBUG ) {
					$nr = strval( $note['date'] );
					$len = strlen( $nr );
					$credits[] = substr( $nr, ($len-6 ) );
					unset( $nr, $len );
				}


/*				$note['actions'][] = 'edit';
				$note['actions'][] = 'delete';*/
				$actions[] = 'edit';
				$actions[] = 'delete';

			}


/*			$credits = array();
			if( !is_null( $author ) ) {
				$credits[] = esc_html( $author );
			}
			else if( !is_null( $importer ) ) {
				if( !is_null( $formatted_import_date ) ) {
					$credits[] = sprintf( esc_html__( 'Imported by %1$s on %2$s', PLUGIN_NOTES_NAME ), esc_html( $importer ), esc_html( $formatted_import_date ) );
				}
				else {
					$credits[] = sprintf( esc_html__( 'Imported by %s', PLUGIN_NOTES_NAME ), esc_html( $importer ) );
				}
			}
			if( !is_null( $formatted_date ) ) { $credits[] = esc_html( $formatted_date ); }
			// Color versionnr depending on age
//			if( !is_null( $note['pi_version'] ) ) { $credits[] = sprintf( $this->buttons['versionnr'], apply_filters( 'plugin_notes_pi_versionnr', $note['pi_version'], $plugin_data['Version'] ) ); }
			if( !is_null( $note['pi_version'] ) ) { $credits[] = sprintf( __( 'v. %s', PLUGIN_NOTES_NAME ), apply_filters( 'plugin_notes_pi_versionnr', $note['pi_version'], $plugin_data['Version'] ) ); }

			if( WP_DEBUG ) {
				$nr = strval( $note['date'] );
				$len = strlen( $nr );
				$credits[] = substr( $nr, ($len-6 ) );
				unset( $nr, $len );
			}
*/

			$note_color_style = ( ( $note['color'] !== $this->defaultcolor ) ? ' style="background-color: ' . $note['color'] . ';"' : '' );




//pr_var( $note, 'note after data alter', true );
			// Generate html to display the note
			$output = '
							<div class="wp-pn_note"' .( !is_null( $filtered_note ) ? ' title="' . esc_attr( __( 'Double click to edit me!', PLUGIN_NOTES_NAME ) ) . '"' : '' ) . '>';

//							<div id="wp-pn_note_' . esc_attr( $note['name'] ) . $attr_key . '" ondblclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');" class="wp-pn_note" title="' . esc_attr( __( 'Double click to edit me!', PLUGIN_NOTES_NAME ) ) . '">';
//							<div id="wp-plugin_note_' . esc_attr( $note['name'] ) . $attr_key . '" ondblclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');" title="' . esc_attr( $this->buttons['title_doubleclick'] ) . '">';

			if( !is_null( $filtered_note ) ) {
				$output .= '
								<div class="wp-pn_text">' . $filtered_note . '
								</div>';
			}
			if( count( $credits ) > 0 ) {
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
			for( $i = 0; $i < $total; $i++ ) {

//				switch( $note['actions'][$i] ) {
				switch( $actions[$i] ) {

					case 'edit':
						$output .= '
									<a href="#" class="edit">' . __( 'Edit note', PLUGIN_NOTES_NAME ) . '</a>';

//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-pn_edit_'. esc_attr( $note['name'] ) . $attr_key . '" class="edit">' . __( 'Edit note', PLUGIN_NOTES_NAME ) . '</a>';

//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-plugin_note_edit'. esc_attr( $note['name'] ) . $attr_key . '" class="edit">' . $this->buttons['edit_note'] . '</a>';
						break;

					case 'delete':
						$output .= '
									<a href="#" class="delete">' . __( 'Delete note', PLUGIN_NOTES_NAME ) . '</a>';

//									<a href="#" onclick="delete_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-pn_delete_'. esc_attr( $note['name'] ) . $attr_key . '" class="delete">' . __( 'Delete note', PLUGIN_NOTES_NAME ) . '</a>';

//									<a href="#" onclick="delete_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\'); return false;" id="wp-plugin_note_delete'. esc_attr( $note['name'] ) . $attr_key . '" class="delete">' . $this->buttons['delete_note'] . '</a>';
						break;

					case 'add':
						$output .= '
									<a href="#" class="add">'. __( 'Add new plugin note', PLUGIN_NOTES_NAME ) .'</a>';

//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . '\'); return false;" id="wp-pn_add_' . esc_attr( $note['name'] ) . '" class="edit">'. __( 'Add new plugin note', PLUGIN_NOTES_NAME ) .'</a>';
//									<a href="#" onclick="edit_plugin_note(\'' . esc_attr( $note['name'] ) . '\'); return false;">'. $this->buttons['add_new_note'] .'</a>';
						break;
				}
				$output .= ( ( $i === ( $total - 1 ) ) ? '' : ' | ' );
			}
			unset( $i, $total, $actions );

			$output .= '
									<span class="waiting"><img alt="' . __( 'Loading...', PLUGIN_NOTES_NAME ) . '" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" /></span>
								</p>
							</div>';


			$output = apply_filters( 'plugin_notes_row', $output, $plugin_data, $plugin_file );


			// Add the form to the note
			$output = '
						<div id="wp-pn_' . esc_attr( $note['name'] ) . $attr_key . '" class="' . $note['class'] . '"' . $note_color_style . '>
							' . $this->_add_plugin_form( $note, $plugin_file, true, false ) . '
							' . $output . '
							<p class="wp-pn_result">test</p>
						</div>';

			unset( $note_color_style );


			if( $echo === true ) {
				echo $output;
			}
			else {
				return $output;
			}
		}
		



		/**
		 * Outputs form to add/edit/delete a plugin note
		 *
		 * @todo allow for multiple notes per plugin
		 * @todo unobtrusify the js onclick events
		 * @todo check if html can be cleaned up more including getting rid of most ids
		 */
		function _add_plugin_form ( $note, $plugin_file, $hidden = true, $echo = true ) {

			$plugin_form_style = ( $hidden === true ) ? 'style="display:none"' : '';

			$new_note_class = '';
			if( is_null( $note['note'] ) || empty( $note['note'] ) ) {
				$note['note'] = ( isset( $this->options['plugin-notes_template'] ) ? $this->options['plugin-notes_template'] : '' );
				$new_note_class = ' class="new_note"';
			}
			
			$attr_key = ( is_null( $note['date'] ) ? '' : '_' . esc_attr( $note['date'] ) );
			


			$output = '
							<div class="wp-pn_form" ' . $plugin_form_style . '>
								<label for="wp-pn_color_' . esc_attr( $note['name'] ) . $attr_key . '">' . /*$this->buttons['label_notecolor']*/ __( 'Note color:', PLUGIN_NOTES_NAME ) . '
								<select name="wp-pn_color_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_color_' . esc_attr( $note['name'] ) . $attr_key . '">';

			// Add color options
			foreach( $this->boxcolors as $color ){
				$output .= '
									<option value="' . esc_attr( $color ) . '" style="background-color: ' . esc_attr( $color ) . '; color: ' . esc_attr( $color ) . ';"' .
					( ( $color === $note['color'] ) ? ' selected="selected"' : '' ) .
					'>' . esc_attr( $color ) . '</option>';
			}

			// @todo see if we can merge the error and success spans
			// @todo unobtrusify the javascript / add onclick handler via js
			// @todo change the code to proper submit buttons ?
			$output .= '
								</select></label>
								<textarea name="wp-pn_text_' . esc_attr( $note['name'] ) . $attr_key . '" cols="90" rows="10"' . $new_note_class . '>' . esc_textarea( $note['note'] ) . '</textarea>
' /*								<span class="wp-plugin_note_error error" style="display: none;"></span>
								<span class="wp-plugin_note_success success" style="display: none;"></span>
*/ . '
								<span class="wp-pn_edit_actions">';

			/* TRANSLATORS: no need to translate - standard WP core translation will be used */
			$output .= '
									<input type="button" value="' . esc_html__( 'Save' ) . '" name="save" class="button-primary" />
									<input type="button" value="' . esc_html__( 'Cancel' ) . '" name="cancel" class="button" />
									<input type="button" value="' . esc_html__( 'Save as template for new notes', PLUGIN_NOTES_NAME ) . '" name="templatesave" class="button-secondary" />';

/*									<a href="#" onclick="save_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');return false;" class="button-primary">' . /*$this->buttons['save_note']* / /* TRANSLATORS: no need to translate - standard WP core translation will be used * / esc_html__( 'Save' ) . '</a>
									<a href="#" onclick="cancel_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');return false;" class="button">' . /*$this->buttons['cancel_edit']* / /* TRANSLATORS: no need to translate - standard WP core translation will be used * / esc_html__( 'Cancel' ) . '</a>
									<a href="#" onclick="templatesave_plugin_note(\'' . esc_attr( $note['name'] ) . $attr_key . '\');return false;" class="button-secondary">' . /*$this->buttons['save_as_template']* / esc_html__( 'Save as template for new notes', PLUGIN_NOTES_NAME ) . '</a>
*/
			$output .= '
									<span class="waiting"><img alt="' . esc_attr( /*$this->buttons['title_loading']*/ __( 'Loading...', PLUGIN_NOTES_NAME ) ) . '" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" /></span>
								</span>
								<input type="hidden" name="wp-pn_slug_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $plugin_file ) . '" />
								<input type="hidden" name="wp-pn_name_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $note['name'] ) . '" />
								<input type="hidden" name="wp-pn_key_' . esc_attr( $note['name'] ) . $attr_key . '" value="' . esc_attr( $note['date'] ) . '" />
								<input type="hidden" name="wp-pn_new_template_' . esc_attr( $note['name'] ) . $attr_key . '" id="wp-pn_new_template_' . esc_attr( $note['name'] ) . $attr_key . '" value="n" />
							</div>';

			if( $echo === true ) {
				echo apply_filters( 'plugin_notes_form', $output, $note['name'] );
			}
			else {
				return apply_filters( 'plugin_notes_form', $output, $note['name'] );
			}
		}


		/**
		 * Returns a cleaned up version of the plugin name, i.e. it's slug
		 *
		 * @param	string	$name	Plugin name
		 * @return	string
		 */
		function _get_plugin_safe_name ( $name ) {
			return sanitize_title( $name );
		}


		/**
		 *
		 * Older is only possible is someone has downgraded a plugin or for imported notes
		 */
		function filter_age_pi_version( $pi_version, $real_version ) {

			$version_age = version_compare( $pi_version, $real_version );
			$version_class = ( ( $version_age === 0 ) ? 'same_version' : ( ( $version_age === -1 ) ? 'older_version' : 'newer_version' ) );

			return '<span class="wp-pn_' . $version_class . '">' . esc_html( $pi_version ) . '</span>';
		}


		/**
		 * Applies the wp_kses html filter to the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string
		 */
		function filter_kses( $pluginnote ) {
			return wp_kses( $pluginnote, $this->allowed_tags );
		}


		/**
		 * Adds additional line breaks to the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string
		 */
		function filter_breaks( $pluginnote) {
			return wpautop( $pluginnote );
		}


		/**
		 * Applies markdown syntax filter to the note string
		 *
		 * @param		string	$pluginnote
		 * @return		string
		 */
		function filter_markdown( $pluginnote ) {
			include_once( PLUGIN_NOTES_DIR . 'inc/markdown/markdown.php' );
			
			return Markdown( $pluginnote );
		}


		/**
		 * Replaces a number of variables in the note string
		 *
		 * @param		string	$pluginnote
		 * @param		mixed	$plugin_data
		 * @param		string	$plugin_file
		 * @return		string
		 */
		function filter_variables_replace( $pluginnote, $plugin_data = null, $plugin_file ) {

			if( preg_match( '/[%][A-Z_]+[%]/u', $pluginnote ) > 0 ) {

				$plugin_data = $this->_getremember_plugin_data( $plugin_file, $plugin_data );

				$find = array(
					'%NAME%', // Name of the plugin
					'%PLUGIN_PATH%', // Path to the plugin on this webserver
					'%URI%', // URI of the plugin website
					'%WPURI%', // URI of the WordPress repository of the plugin (might not exist)
					'%WPURI_LINK%', // HTML-string link for the WPURI
					'%AUTHOR%', // Author name
					'%AUTHORURI%', // URI of the Author's website
					'%VERSION%', // Plugin version number
					'%DESCRIPTION%', // Plugin description
				);
				// Available plugindata variables are always set, but sometimes empty
				$replace = array(
					esc_html( $plugin_data['Name'] ),
					esc_html( plugins_url() . '/' . plugin_dir_path( $plugin_file ) ),
					( !empty( $plugin_data['PluginURI'] ) ? esc_url( $plugin_data['PluginURI'] ) : '' ),
					esc_url( 'http://wordpress.org/extend/plugins/' . substr( $plugin_file, 0, strpos( $plugin_file, '/') ) ),
					'<a href="' . esc_url( 'http://wordpress.org/extend/plugins/' . substr( $plugin_file, 0, strpos( $plugin_file, '/') ) ) . '" target="_blank">' . esc_html( $plugin_data['Name'] ) . '</a>',
					( !empty( $plugin_data['Author'] ) ? esc_html( wp_kses( $plugin_data['Author'], array() ) ) : '' ),
					( !empty( $plugin_data['AuthorURI'] ) ? esc_html( $plugin_data['AuthorURI'] ) : '' ),
					( !empty( $plugin_data['Version'] ) ? esc_html( $plugin_data['Version'] ) : '' ),
					( !empty( $plugin_data['Description'] ) ? esc_html( $plugin_data['Description'] ) : '' ),
				);

				return str_replace( $find, $replace, $pluginnote );
			}
			else {
				return $pluginnote;
			}
		}


		/** ******************* INPUT PROCESSING METHODS ******************* **/


		/**
		 * Function that handles editing of the plugin via AJAX
		 *
		 * @todo allow for multiple notes per plugin -> add key
		 * @todo check if localization can be enabled
		 */
		function ajax_edit_plugin_note ( ) {
			global $current_user;

//			if( !defined( 'WPLANG' ) ) { define('WPLANG', get_option( 'WPLANG' ) ); }

			// @todo Localization of notes with AJAX edit does not seem to work....
//			$this->load_textdomain();
//			load_textdomain( PLUGIN_NOTES_NAME, PLUGIN_NOTES_DIR . 'languages/' );

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

			if( empty($_POST) ) {
				//return ?
			}

			// Verify nonce
//			check_ajax_referer( 'wp-plugin_notes_nonce', '_pluginnotes_nonce' );

			if ( ! wp_verify_nonce( $_POST['_pluginnotes_nonce'], 'wp-plugin_notes_nonce' ) ) {
				// duplicate phrase with js i18n function
				exit( __( 'Don\'t think you\'re supposed to be here...', PLUGIN_NOTES_NAME ) );
			}

			if( ! is_user_logged_in() ) {
				// duplicate phrase with js i18n function
				$error = sprintf( __( 'Your session seems to have expired. You need to <a href="%s" title="Login">log in</a> again.', PLUGIN_NOTES_NAME ), wp_login_url( $this->plugin_options_url() ) );
				exit( $error );
			}

//			$current_user = wp_get_current_user(); // is this needed and if so, why get the global variable above ?
			// @todo: test!
			if ( ! is_admin() || current_user_can( $this->required_role ) === false ) {
				// user can't activate plugins, so throw error
				exit( __( 'Sorry, you do not have the necessary permissions.', PLUGIN_NOTES_NAME ) );
			}

			// Ok, we're still here which means we have an valid user on a valid form


// use sanitize_text_field() for text field inputs

			if( $this->_validate_plugin( sanitize_text_field( $_POST['plugin_slug'] ), true ) === true ) {
				$plugin = sanitize_text_field( $_POST['plugin_slug'] );
			}
			if( $this->_validate_plugin( $this->_get_plugin_safe_name( $_POST['plugin_name'] ), false ) === true ) {
				$plugin_name = $this->_get_plugin_safe_name( $_POST['plugin_name'] );
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
			if( ( isset( $_POST['note_key'] ) && $_POST['note_key'] !== '' ) && ( ( intval( $_POST['note_key'] ) == $_POST['note_key'] ) && intval( $_POST['note_key'] ) >= 0 ) ) {
				$key = intval( $_POST['note_key'] );
			}

			if( !isset( $plugin ) || !isset( $plugin_name ) /*|| !isset( $key ) - quite possible, i.e. new note*/ ) {
				exit( __( 'Invalid form input received.', PLUGIN_NOTES_NAME ) );
			}


//trigger_error( pr_var( $_POST, 'post vars', true ), E_USER_NOTICE );
			// Get notes array
//			$options = $this->_getset_options();

//			$original_id = 'wp-pn_' . ( isset( $key ) ? $plugin_name . '_' . $key : $plugin_name );

			$note_text = $this->_validate_note_text( $_POST['plugin_note'] );



			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();

			if( $note_text !== '' ) {
				// Are we trying to save the note as a note template ?
				if( $_POST['plugin_new_template'] === 'y' ) {

					$this->options['plugin-notes_template'] = $note_text;

		//			$response_data = array_merge( $response_data, $note );
					$response_data['action'] = 'save_template';

					/*
					 @todo: 2x fix: if the template is cleared, clear the new 'add note'-forms
					 + if no notes exist for the plugin where they did this action, make sure that the form + add note
					 link returns
					 + make sure that succesfull template delete message is shown
					 */
					$plugin_note_content = '';
				}
				// Ok, no template, but we have a note, save the note to the specific plugin
				else {

					$plugin_data = $this->_getremember_plugin_data( $plugin );
//					$date_format = $this->dateformat;

					// setup the note data
//					$note['date'] = date_i18n( $date_format );
					$note['date'] = time();
					$note['user'] = $current_user->ID;
					$note['note'] = $note_text;
					$note['color'] = $this->_validate_note_color( sanitize_text_field( $_POST['plugin_note_color'] ) );
					$note['pi_version'] = $plugin_data['Version'];

					// Delete the old version so as to reset the timestamp key and removed potential import info
					if( !empty( $key ) && !empty( $this->options[$plugin][$key] ) ) {
						unset( $this->options[$plugin][$key] );
					}

					// Add new note to notes array
					$this->options[$plugin][$note['date']] = $note;

					$response_data = array_merge( $response_data, $note ); // is this needed ?
					$response_data['action'] = 'edit';

					if( !empty( $key ) ) {
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
				if( !empty( $key ) && !empty( $this->options[$plugin][$key] ) ) {
					unset( $this->options[$plugin][$key] );
				}
				$response_data['action'] = 'delete';
				
				$plugin_note_content = '';
			}
			
			
			//							<span class="wp-plugin_note_result" style="display: none;"></span>

			// Save the new notes array
			$this->options = $this->_getset_options( $this->options );

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
*/
			exit;
		}



		function _validate_note_array( $note ) {
//			$note['text'] = $this->_validate_note_text( $note['text'] );
//			$note['color'] = $this->_validate_note_color( $note['color'] );

//			return $note;
		}

		
		/**
		 * Validate input for note text and note template
		 *
		 * @param	string	$note_text
		 * @return	string
		 */
		function _validate_note_text( $note_text = null ) {
			$note_text = stripslashes( trim( $note_text ) );
			// @todo Do some replacements which aren't meant to change live?
			if( !is_null( $note_text ) && !empty( $note_text ) )
				return wp_kses( $note_text, $this->allowed_tags );
			else
				return '';
		}

		/**
		 * Validate input for note color
		 *
		 * @param	string	$note_color
		 * @return	string
		 */
		function _validate_note_color( $note_color = null ) {
			if( !is_null( $note_color ) && in_array( $note_color, $this->boxcolors ) )
				return $note_color;
			else
				return $this->defaultcolor;
		}
		
		function _validate_timestamp( $timestamp = null ) {
			if( !is_null( $timestamp ) && ( intval( $timestamp ) == $timestamp && $timestamp <= PHP_INT_MAX ) )
				return intval( $timestamp );
			else
				return null;
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
		 */
		function _validate_plugin( $plugin = null, $is_slug = true ) {
			static $plugins = null;

			if( is_null( $plugins ) ) {
				// Get list of installed plugins
				// Key is the plugin file path and the value is an array of the plugin data.
				$plugins = get_plugins();
			}

			$valid = false;
			
			if( !is_null( $plugin ) ) {
				if( $is_slug === true ) {
					if( array_key_exists( $plugin, $plugins ) === true ) {
						$valid = true;
					}
				}
				else {
					foreach( $plugins as $data ) {
						if( $this->_get_plugin_safe_name( $data['Name'] ) === $plugin ) {
							$valid = true;
							break;
						}
					}
				}
			}
			return $valid;
		}




	} /* End of class */


	/**
	 * Only load the class when we're in the backend
	 */
	if ( is_admin() ) {
		add_action( 'plugins_loaded', 'plugin_notes_init' );
//		$plugin_notes = new plugin_notes();
	}

	function plugin_notes_init() {
		/** Let's get the plugin rolling **/
		// Create new instance of the plugin_notes object

		global $plugin_notes;
		$plugin_notes = new plugin_notes();
//		return $plugin_notes;
	}

} /* End of class-exists wrapper */