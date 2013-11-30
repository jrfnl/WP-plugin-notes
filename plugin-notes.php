<?php
/*
Plugin Name:	Plugin Notes
Plugin URI:		http://wordpress.org/extend/plugins/plugin-notes/
Description:	Allows you to add notes to plugins. Simple and sweet.
Version:		2.0
Author:			Mohammad Jangda, Juliette Reinders Folmer
Author URI:		http://github.com/jrfnl/WP-plugin-notes
Author:			Mohammad Jangda
Author URI:		http://digitalize.ca/
Author:			Juliette Reinders Folmer
Author URI:		http://adviesenzo.nl/
Text Domain:	plugin-notes
Domain Path:	/languages/

Copyright 2009-2013 Mohammad Jangda, Juliette Reinders Folmer
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


/**
 * @internal
 * @todo: will using timestamps as keys really work ? What if - althought unlikely - two admins add a note at the same time ?
maybe just use normal numeric keys and then use array_multisort ? then again, in that sense, option could have changed between retrieval and writing it back. Is this really an issue ?
Also how to retrieve the key of the new note if using numerical keys? would be needed for ajax response prep
 *
 * @todo: check when jquery became included by default & which jquery version is included with which WP version and may be work around that if needed
 * @todo: add WP version check on activation ? What is minimum version required ?
 */


/**
 * @internal
 *
 * Road map / Possible future features
 *
 * - Multi-site compatibility
 * - Import notes from Plugin memorandum and possibly other plugin documentation plugins
 * - Add icons to notes
 * - Have different display options: tabbed | fold in/out accordion | show all
 * - Save several different templates and ability to choose which template to use for each new note
 * - Add icons to templates
 * - Add color to templates
 * - Manual sorting of notes
 * - Unique ID key for notes to use for import replacement
 * - Add reply to note option and show reply-notes as sub of original note
 */



/**
 * @internal
 *
 * What to do to put a new version online ?
 *
 * - Change version number of plugin in readme file (stable tag), this file at the top and in the class constant
 * - Change version numbers in the other files only if they were changed with this upgrade
 * - Change script and styles constants only if anything has changed in those files
 * - If styles/scripts have changed, also regenerated the .min versions
 * - Change db_lastchange constant if anything has changed in the way options are saved to the database
 * - Check if a new version of markdown is available at http://michelf.ca/projects/php-markdown/
 *   Current version: 1.0.1o
 * - Check if a new version of DateFormatRegexGenerator is available at
 *   http://www.redbottledesign.com/blog/generating-pcre-regular-expressions-date-format-strings-php
 *   Current version: 1.0
 * - Update Changelog and upgrade instructions in Readme file
 * - Maybe re-generate .POT file (if any language strings have changed)
 *   PLEASE NOTE: when regenerating the .pot file for the plugin, either (manually) remove
 *   the strings which are already included in the WP core or make sure that the translators note
 *   ('no need to translate') is included
 *   ALSO: Manually add the color strings!
 * - Maybe create new screenshots
 * - Create new Tag in WP SVN & copy all files to it
 */

// Avoid direct calls to this file
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( !class_exists( 'Plugin_Notes' ) ) {
	/**
	 * @package		WordPress\Plugins\Plugin_notes
	 * @version		2.0
	 * @author		Mohammad Jangda
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 * @link		http://wordpress.org/extend/plugins/plugin-notes/ Plugin Notes WordPress plugin
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 *
	 * @copyright	2009-2013 Mohammad Jangda, Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2
	 */
	class Plugin_Notes {


		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * @const string	Plugin version number
		 */
		const VERSION = '2.0';

		/**
		 * @const string	Version in which the admin styles where last changed
		 * @usedby	admin_enqueue_scripts()
		 */
		const ADMIN_STYLES_VERSION = '2.0';

		/**
		 * @const string	Version in which the admin scripts where last changed
		 * @usedby	admin_enqueue_scripts()
		 */
		const ADMIN_SCRIPTS_VERSION = '2.0';

		/**
		 * @const string	Plugin version number of last change in DB options setup
		 * @usedby upgrade_options()
		 */
		const DB_LASTCHANGE = '2.0';


		/**
		 * @const	string	Minimum required capability to see and/or update the notes
		 *					and to access the settings page and change the plugin options
		 */
		const REQUIRED_CAP = 'activate_plugins';

		/**
		 * @const	string	Page on which the plugin functions and underneath which the settings page will be hooked
		 */
		const PARENT_PAGE = 'plugins.php';
		
		
		/**
		 * @const	string	Page on which the plugin functions and underneath which the settings page will be hooked
		 */
		const PARENT_PAGE_SCREEN_BASE = 'plugins';
		
		
		/**
		 * @const	string
		 */
//		const UPDATE_PAGE = 'update_core.php';


		/**
		 * @const string	Unique prefix for use in class names and such
		 */
		const PREFIX = 'wp-pn_';




		/* *** DEFINE STATIC CLASS PROPERTIES *** */

		/**
		 * These static properties will be initialized - *before* class instantiation -
		 * by the static init() function
		 */

		/**
		 * @staticvar	string	$basename	Plugin Basename = 'dir/file.php'
		 */
		public static $basename;

		/**
		 * @staticvar	string	$name		Plugin name	  = dirname of the plugin
		 *									Also used as text domain for translation
		 */
		public static $name;

		/**
		 * @staticvar	string	$path		Full server path to the plugin directory, has trailing slash
		 */
		public static $path;

		/**
		 * @staticvar	string	$suffix		Suffix to use if scripts/styles are in debug mode
		 */
		public static $suffix;



		/* *** DEFINE CLASS PROPERTIES *** */

		/* *** Semi Static Properties *** */

		/**
		 * @todo remove # from color code
		 * @todo swap key <=> value
		 * @todo add set_properties() method which translates the color names
		 * @todo switch to jQuery color wheel
		 *
		 * @var array	Available box colors
		 */
		public static $boxcolors = array(
			'green'			=> '#EBF9E6', // light green
			'light green'	=> '#F0F8E2', // lighter green
			'yellow'		=> '#F9F7E6', // light yellow
			'light blue'	=> '#EAF2FA', // light blue
			'blue'			=> '#E6F9F9', // brighter blue
			'red'			=> '#F9E8E6', // light red
			'pink'			=> '#F9E6F4', // light pink
			'earth'			=> '#F9F0E6', // earth
			'purple'		=> '#E9E2F8', // light purple
			'grey'			=> '#D7DADD', // light grey
			'light grey'	=> '#EAECED', // very light grey
		);
		

		/**
		 * @var array	Allowed html tags for notes
		 * @todo determine where this should be placed - used in settings form and display methods
		 */
		public static $allowed_tags = array(
			'a' 		=> array(
				'href'		=> true,
				'title' 	=> true,
				'target'	=> true,
			),
			'br'		=> array(),
			'p'			=> array(),
			'b'			=> array(),
			'strong'	=> array(),
			'i'			=> array(),
			'em'		=> array(),
			'u'			=> array(),
			's'			=> array(),
			'img'		=> array(
				'src'		=> true,
				'height'	=> true,
				'width'		=> true,
				'alt'		=> true,
			),
			'hr'		=> array(),
		);


		
		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * @var object settings page class
		 */
		public $settings_page;





		/* *** PLUGIN INITIALIZATION METHODS *** */

		/**
		 * Object constructor for plugin
		 *
		 * @return Plugin_Notes
		 */
		function __construct() {

			/* Don't do anything if we're not in admin */
			if ( !is_admin() )
				return;
				
			spl_autoload_register( array( $this, 'auto_load' ) );


			/* Check if we have any activation or upgrade actions to do */
			if ( !isset( Plugin_Notes_Manage_Settings_Option::$current['version'] ) || version_compare( Plugin_Notes_Manage_Settings_Option::$current['version'], self::DB_LASTCHANGE, '<' ) ) {
				add_action( 'init', array( $this, 'upgrade_options' ), 8 );
			}
			// Make sure that an upgrade check is done on (re-)activation as well.
			// @todo double-check
			add_action( 'plugin_notes_plugin_activate', array( $this, 'upgrade_options' ) );


			/* Register the plugin initialization actions */
//			add_action( 'init', array( $this, 'load_textdomain' ) );
			add_action( 'init', array( $this, 'init' ), 7 );
			add_action( 'admin_menu', array( $this, 'setup_settings_page' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			/* Get the current notes set */
//			$this->notes = $this->_get_set_notes();
		}


		/**
		 * Set the static path and directory variables for this class
		 * Is called from the global space *before* instantiating the class to make
		 * sure the correct values are available to the object
		 *
		 * @return void
		 */
		public static function init_statics() {

			self::$basename = plugin_basename( __FILE__ );
			self::$name     = dirname( self::$basename );
			self::$path     = plugin_dir_path( __FILE__ );
			self::$suffix   = ( ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min' );
		}
		
		
		/**
		 * Auto load our class files
		 *
		 * @return void
		 */
		public function auto_load( $class ) {
			static $classes = NULL;

			if ( $classes === NULL ) {
				$classes = array(
					'plugin_notes_display_notes'			=> 'class-display-notes.php',
					'plugin_notes_display_notes_plain'		=> 'class-display-notes-plain.php',
					'plugin_notes_display_notes_accordion'	=> 'class-display-notes_accordion.php',
					'plugin_notes_display_notes_tabs'		=> 'class-display-notes_tabs.php',
					'plugin_notes_manage_settings_option'	=> 'class-manage-settings-option.php',
					'plugin_notes_manage_notes_option'		=> 'class-manage-notes-option.php',
					'plugin_notes_settings_page'			=> 'class-settings-page.php',
					'plugin_notes_date_string_to_timestamp'	=> 'inc/class-date-string-to-timestamp.php',
					// External libraries, do their own internal includes
					'capturingdateformatregexgenerator'		=> 'inc/date_format/CapturingDateFormatRegexGenerator.class.php',
					'markdown'								=> 'inc/markdown/markdown.php',
				);
			}

			$cn = strtolower( $class );

			if ( isset( $classes[$cn] ) ) {
				include_once( self::$path . $classes[$cn] );
			}
		}
		
		
		/** ******************* ADMINISTRATIVE METHODS ******************* **/


		/**
		 * Add actions which are needed for before anything else is done
		 *
		 * @return void
		 */
		public function init() {
			/* Don't do anything if user does not have the required capability */
			if ( current_user_can( self::REQUIRED_CAP ) === false ) {
				return;
			}

			/* Allow filtering of our plugin name */
			$this->filter_statics();
			
			/* Load text strings */
			load_plugin_textdomain( self::$name, false, self::$name . '/languages/' );
		}

		/**
		 * Allow filtering of the plugin name
		 * Mainly useful for non-standard directory setups
		 *
		 * @return void
		 */
		public function filter_statics() {
			self::$name = apply_filters( 'plugin_notes_plugin_name', self::$name );
		}


		/**
		 * Fill some property arrays with translated strings
		 */
		public function set_properties() {
			self::$allowed_tags = apply_filters( 'plugin_notes_allowed_tags', self::$allowed_tags );

/*			$this->alignments = array(
				'left'	   => __( 'Left', self::$name ),
				'right'    => __( 'Right', self::$name ),
			);

			$this->form_sections = array(
				'general'	=> __( 'General Settings', self::$name ),
				'images'	=> __( 'Image Settings', self::$name ),
				'advanced'	=> __( 'Advanced Settings', self::$name ),
			);*/
		}



		/**
		 * Add back-end functionality
		 *
		 * @return void
		 */
		public function admin_init() {
			/* Don't do anything if user does not have the required capability */
			if ( current_user_can( self::REQUIRED_CAP ) === false ) {
				return;
			}

			/* Property filters */
			add_filter( 'plugin_notes_allowed_tags', array( $this, 'validate_allowed_tags' ), 1000 );
			
			/* Adjust/set some properties */
			$this->set_properties();

			

			
			/* Determine display class */
			$display_class = 'Plugin_Notes_Display_Notes';
			if ( Plugin_Notes_Manage_Settings_Option::$current['display'] !== '' ) {
				$display_class .= '_' . ucfirst( Plugin_Notes_Manage_Settings_Option::$current['display'] );
			}

			/* Add notes to plugin row */
			add_filter( 'plugin_row_meta', array( $display_class, 'plugin_row_meta' ), 10, 4 );

			/* Add output filters to the note (string replacement and markdown syntax) */
			add_filter( 'plugin_notes_note_text', array( $display_class, 'filter_html' ) );

			if ( Plugin_Notes_Manage_Settings_Option::$current['allow_markdown'] === true ) {
				add_filter( 'plugin_notes_note_text', array( $display_class, 'filter_markdown' ) );
			}

			add_filter( 'plugin_notes_note_text', array( $display_class, 'filter_breaks' ) );
			add_filter( 'plugin_notes_note_text', array( $display_class, 'filter_variables_replace' ) );
			
			add_filter( 'plugin_notes_pi_versionnr', array( $display_class, 'filter_age_pi_version' ), 10, 2 );

			add_filter( 'plugin_notes_plugin_data', array( $this, 'get_plugin_data' ), 10, 2 );




			/* Add ajax action to edit/save plugin notes */
	//		add_action( 'wp_ajax_plugin_notes_edit_comment', array( $this, 'ajax_edit_plugin_note' ) );

			/* Add ajax action to get plugin notes for the update page*/
/*			if ( $this->settings['show_on_update'] === true ) {
				add_action( 'wp_ajax_plugin_notes_get_comment', array( $this, 'ajax_get_plugin_note' ) );
			}
*/



			/* Add js and css files */
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );


			/* Add contextual help for the regular plugins page
			   Add help tab *behind* existing core page help tabs
			   reason for using admin_head hook instead of load hook) */
			add_action( 'admin_head-' . self::PARENT_PAGE, array( $this, 'add_help_tab' ) );


			/* Conditionally change the admin bar updates link url */
			if ( Plugin_Notes_Manage_Settings_Option::$current['adminbar_url'] === true ) {
				add_action( 'admin_bar_menu', array( $this, 'tweak_admin_bar_updateurl' ), 1000 );
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
/*		function load_textdomain() {
//			global $pagenow;

			/* Only load the text strings where relevant */
			/* Page check removed as menu title also needs localization, so strings need always be loaded */
/*			if ( ( is_admin() && current_user_can( self::REQUIRED_CAP ) === true ) ) {
			/*&& ( in_array( $pagenow, array( self::PARENT_PAGE, $this->hook, 'admin-ajax.php' ) ) ) === true*/

/*				load_plugin_textdomain( self::$name, false, self::$name . '/languages/' );

/*				$this->buttons = array(
					'add_new_note'		=>	__( 'Add new plugin note', self::$name ),
					'edit_note'			=>	__( 'Edit note', self::$name ),
					'delete_note'		=>	__( 'Delete note', self::$name ),
					'save_note'			=>	__( 'Save' ),
					'cancel_edit'		=>	__( 'Cancel' ),
					'save_as_template'	=>	__( 'Save as template for new notes', self::$name ),
					'title_doubleclick'	=>	__( 'Double click to edit me!', self::$name ),
					'title_loading'		=>	__( 'Loading...', self::$name ),
					'label_notecolor'	=>	__( 'Note color:', self::$name ),
					'versionnr'			=>	__( 'v. %s', self::$name ),
				);
*/
//			}
//		}
		









		/**
		 * Register the settings page for all users that have the required capability
		 *
		 * @return void
		 */
		public function setup_settings_page() {
			/* Don't do anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( self::REQUIRED_CAP ) ) {
				return;
			}
			$this->settings_page = new Plugin_Notes_Settings_Page();
		}



		/**
		 * Adds necessary javascript and css files
		 * @todo also add these for plugin update page in view mode
		 * @todo add js functions to add the notes to the plugin update page via AJAX
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {
			
			$screen = get_current_screen();

			if ( property_exists( $screen, 'base' ) && ( ( isset( $this->settings_page ) && $screen->base === $this->settings_page->hook ) || $screen->base === self::PARENT_PAGE_SCREEN_BASE ) ) {

//			if ( $pagenow === self::PARENT_PAGE || ( $this->show_on_update === true && $pagenow === $this->update_page ) ) {

//				wp_enqueue_script( self::$name, self::$url . 'plugin-notes.js', array( 'jquery', 'wp-ajax-response' ), self::VERSION, true );
				wp_enqueue_script(
					self::$name, // id
					plugins_url( 'js/plugin-notes' . self::$suffix . '.js', __FILE__ ), // url
					array( 'jquery', 'jquery-effects-core', 'jquery-effects-fade', 'jquery-effects-highlight', 'wp-ajax-response' ), // dependants
					self::ADMIN_SCRIPTS_VERSION, // version
					true // load in footer
				);

				wp_enqueue_style(
					self::$name, // id
					plugins_url( 'css/plugin-notes' . self::$suffix . '.css', __FILE__ ), // url
					array(),  // not used
					self::ADMIN_STYLES_VERSION, // version
					'all'
				);
				
				wp_localize_script( self::$name, 'i18n_plugin_notes', $this->get_javascript_i18n( $screen->base ) );
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
		public function get_javascript_i18n( $screen_base ) {
			
			$strings = array(
				'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
				'prefix'			=> self::PREFIX,
			);
			$more_strings = array();

			if ( isset( $this->settings_page ) && $screen_base === $this->settings_page->hook ) {
				// Settings page specific text strings
				$more_strings = array(
					'confirm_delete_all'	=> esc_js( __( 'Are you sure you want to delete all existing plugin notes?', self::$name ) ),
					'blank_template'		=> $this->settings_page->get_template_form(),
				);
			}
			else if ( $screen_base === self::PARENT_PAGE_SCREEN_BASE ) {
				// Plugins page specific text strings
				$more_strings = array(
					'confirm_delete_selected'	=> esc_js( __( 'Are you sure you want to delete the plugin notes for the selected plugins?', self::$name ) ),
					'confirm_delete'	=> esc_js( __( 'Are you sure you want to delete this note?', self::$name ) ),
					'confirm_template'	=> sprintf( esc_js( __( 'Are you sure you want to save this note as a template?%sAny changes you made will not be saved to this particular plugin note.%s%sAlso beware: saving this note as the plugin notes template will overwrite any previously saved templates!', self::$name ) ), "\n\r", "\n\r", "\n\r" ),
					'success_save_template'	=> esc_js( __( 'New notes template saved succesfully', self::$name ) ),
					'success_editsave'	=> esc_js( __( 'Your changes have been saved succesfully', self::$name ) ),
					'success_delete'	=> esc_js( __( 'Note deleted', self::$name ) ),
					// Duplicate phrase with phrase within ajax function
					'error_nonce'		=> esc_js( __( 'Don\'t think you\'re supposed to be here...', self::$name ) ),
					// Duplicate phrase with phrase within ajax function
					'error_loggedin'	=> esc_js( sprintf( __( 'Your session seems to have expired. You need to %slog in%s again.', self::$name ), '<a href="' . wp_login_url( admin_url( self::PARENT_PAGE ) ) . '" title="' . __( 'Login', self::$name ) . '">', '</a>' ) ),
	//				'error_capacity'	=> esc_js( __( 'Sorry, you do not have permission to activate plugins.', self::$name ) ),
				);
				$templates = array();

				foreach ( Plugin_Notes_Manage_Settings_Option::$current['templates'] as $name => $template ) {
					$templates['name'] = $this->settings_page->get_template_form( $template );
				}
				$more_strings['templates'] = $templates;
			}

			return array_merge( $strings, $more_strings );
		}


		/**
		 * Adds contextual help tab to the regular plugin page
		 * @return	void
		 */
		public function add_help_tab() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'base' ) && $screen->base === self::PARENT_PAGE_SCREEN_BASE ) {
				$screen->add_help_tab(
					array(
						'id'	  => self::$name . '-help', // This should be unique for the screen.
						'title'   => __( 'Plugin Notes', self::$name ),
						'callback' => array( $this, 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => self::$name . '-variables', // This should be unique for the screen.
						'title'   => __( 'Plugin Notes Variables', self::$name ),
						'callback' => array( $this, 'get_helptext' ),
					)
				);
			}
		}



		/**
		 * Function containing the helptext string to be used on the plugins page
		 *
		 * @todo    add settings link
		 * @todo    add info about settings + import/export functionality
		 *
		 * @param	string	$screen		Screen object for the screen the user is on
		 * @param	string	$tab		Help tab being requested
		 * @return  void
		 */
		public function get_helptext( $screen, $tab ) {

			switch ( $tab['id'] ) {
				case self::$name . '-help' :
					echo '
						<p>' . sprintf( __( 'The %sPlugin Notes%s plugin let\'s you add notes for each installed plugin. This can be useful for documenting changes you made or how and where you use a plugin in your website.', self::$name ), '<em><a href="http://wordpress.org/extend/plugins/plugin-notes/" target="_blank" class="ext-link">', '</a></em>' ) . '</p>
						<p>' . sprintf( __( 'You can use %sMarkdown syntax%s in your notes as well as HTML.', self::$name ), '<a href="http://daringfireball.net/projects/markdown/syntax" target="_blank" class="ext-link">', '</a>' ) . '</p>
						<p>' . sprintf( __( 'On top of that, you can even use a %snumber of variables%s which will automagically be replaced, such as for example <code>%%WPURI_LINK%%</code> which would be replaced by a link to the WordPress plugin repository for this plugin. You can find them listed on the next help tab. Neat isn\'t it ?', self::$name ), '<a href="http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link">', '</a>' ) . '</p>
						<p>' . sprintf( __( 'Lastly, you can save a note as a template for new notes. If you use a fixed format for your plugin notes, you will probably like the efficiency of this.', self::$name ), '' ) . '</p>
						<p>' . sprintf( __( 'For more information: %1$sPlugin home%3$s | %2$sFAQ%3$s', self::$name ), '<a href="http://wordpress.org/extend/plugins/plugin-notes/" target="_blank" class="ext-link">', '<a href="http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank" class="ext-link">', '</a>' ) . '</p>';
					break;
					
				case self::$name . '-variables' :
					echo '
						<p>' . __( 'There are a number of variables you can use in the notes which will automagically be replaced. Most aren\'t that useful as the info is provided by default for the plugin, but they are included anyway for completeness.', self::$name ) . '</p>
						<p><strong>' . __( 'Example use:', self::$name ) . '</strong></p>
						<p>' . sprintf( '%1$s<br />%2$s <code>Plugin: %%WPURI_LINK%%</code>', __( 'Say you want a link to the WordPress Plugin repository for each plugin.', self::$name ), __( 'Instead of manually adding each and every link, you can just add the following note to each plugin and the link will be automagically placed:', self::$name ) ) . '</p>
						<p><strong>' . __( 'Available variables:', self::$name ) . '</strong></p>
						<table>
							<tr>
								<th><code>%PLUGIN_PATH%</code></th><td>:</td>
								<td>' . __( 'Plugin uri path on your website', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%WPURI%</code></th><td>:</td>
								<td>' . __( 'URI of the WordPress repository of the plugin (Please note: it is not tested whether the plugin is actually registered in the WP plugin repository! The URI is an educated guess.)', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%WPURI_LINK%</code></th><td>:</td>
								<td>' . __( 'A link to the above WordPress repository of the plugin', self::$name ) . '</td>
							</tr>
						</table>
						<p><strong>' . __( 'Already showing for each plugin (less useful):', self::$name ) . '</strong></p>
						<table>
							<tr>
								<th><code>%NAME%</code></th><td>:</td>
								<td>' . __( 'Plugin Name', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%URI%</code></th><td>:</td>
								<td>' . __( 'URI of the plugin website as given by the author', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%AUTHOR%</code></th><td>:</td>
								<td>' . __( 'Name of the plugin author', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%AUTHORURI%</code></th><td>:</td>
								<td>' . __( 'Website of the plugin author', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%VERSION%</code></th><td>:</td>
								<td>' . __( 'Current plugin version)', self::$name ) . '</td>
							</tr>
							<tr>
								<th><code>%DESCRIPTION%</code></th><td>:</td>
								<td>' . __( 'Description of the plugin', self::$name ) . '</td>
							</tr>
						</table>';
					break;
			}
		}






		/**
		 * Add screen options to the plugin page ?
		 */
		public function add_screen_options() {
			// @todo Add notes checkbox + start showing/hidden toggle ?
		}


		// @todo Add js method to add 'export notes' option to dropdown on plugins page
		public function add_export_option_to_dropdown() {
		}



		/** ******************* DO SOMETHING FUNCTIONS ******************* **/

		/**
		 * Change the updates link in the admin toolbar to go straight to the
		 * plugin updates screen instead of to the WP upgrades screen.
		 *
		 * @return	void
		 */
		public function tweak_admin_bar_updateurl( $wp_admin_bar ) {

			$node = $wp_admin_bar->get_node( 'updates' );

			// check if the comments node exists
			if ( $node ) {
				$args = $node;
				$args->href	= add_query_arg( 'plugin_status', 'upgrade', admin_url( self::PARENT_PAGE ) );
				$wp_admin_bar->add_node( $args );
				unset( $args );
			}
			unset( $node );
		}
		
		
		public function ajax_edit_note() {
			if ( ! is_admin() || current_user_can( Plugin_Notes_Manage_Notes_Option::REQUIRED_CAP ) === false ) {
				exit( '-1' );
				//or
				exit( __( 'Sorry, you do not have the necessary permissions.', Plugin_Notes::$name ) );
			}
			check_ajax_referer( Plugin_Notes::PREFIX . 'nonce' );
			
/*			if ( ! wp_verify_nonce( $_POST['_pluginnotes_nonce'], 'wp-plugin_notes_nonce' ) ) {
				// duplicate phrase with js i18n function
				exit( __( 'Don\'t think you\'re supposed to be here...', Plugin_Notes::$name ) );
			}
*/
			if ( ! is_user_logged_in() ) {
				// duplicate phrase with js i18n function
				$message = sprintf( __( 'Your session seems to have expired. You need to %slog in%s again.', Plugin_Notes::$name ), '<a href="' . wp_login_url( $this->plugin_options_url() ) . '" title="' . __( 'Login', Plugin_Notes::$name ) . '">', '</a>' );
				exit( $message );
			}

			// Ok, we're still here which means we have an valid user on a valid form
			
			$plugin_slug = Plugin_Notes_Manage_Notes_Option::validate_plugin_slug( $_POST['plugin_slug'] );
			
			$note_key = null;
			if ( ( isset( $_POST['note_key'] ) && $_POST['note_key'] !== '' ) && ( ( intval( $_POST['note_key'] ) == $_POST['note_key'] ) && intval( $_POST['note_key'] ) >= 0 ) ) {
				$note_key = intval( $_POST['note_key'] );
			}
/*
  			Do we really need the name ?
			$plugin_name = Plugin_Notes_Manage_Notes_Option::validate_plugin_name( $_POST['plugin_name'] );
*/

			if ( $plugin_slug === '' || ( !isset( $_POST['action'] ) || $_POST['action'] === '' ) ) {
				exit( __( 'Invalid form input received.', Plugin_Notes::$name ) );
			}
			
			$message = '';
			switch ( $_POST['action'] ) {

				case 'save_as_new_template':
					$this->save_note_as_template( $_POST );
					break;
					
				case 'save_note':
					if ( isset( $note_key ) ) {
						// update
					}
					else {
						// insert as new note
					}
					break;

				case 'delete_note':
					if ( isset( $note_key ) ) {
						$this->delete_single_note( $plugin_slug, $note_key );
					}
					else {
						// error
						$message = __( 'Unsure what note to deleted. No changes made, please try again', Plugin_Notes::$name );
					}
					break;
					
				default:
					$message = __( 'Invalid form input received.', Plugin_Notes::$name );
					break;


			}

			exit( $message );
		}

/*			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();


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
* /
			exit;
		}
*/



		public function save_note_as_template( $received_post ) {
			$settings = Plugin_Notes_Manage_Settings_Option::$current;
			
/*
					case 'name':
					case 'title':
					case 'note':
					case 'color':
					case 'timestamp':
					case 'saved_by_user':
					case 'private':
					case 'export_exclude':
					
					what else did we receive ? unset the others ?
*/
			$message = __( 'Failure to save as template, please try again', Plugin_Notes::$name );
			if ( is_array( $received_post ) && $received_post !== array() ) {
				$settings['templates'][] = $received_post;
				
				// re-sort templates on alphabetic name ?

				/* Validation is done within update */
				if ( update_option( Plugin_Notes_Manage_Settings_Option::NAME, $settings ) === true ) {
					$message = __( 'Successfully saved as template', Plugin_Notes::$name );
				}

				// if success:
				// add template to js template array ?
				// js: add template as choice to all dropdown template select boxes
				// js: change the template content for existing new notes ?
				
/*			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();


		//			$response_data = array_merge( $response_data, $note );
					$response_data['action'] = 'save_template';

					/*
					 @todo: 2x fix: if the template is cleared, clear the new 'add note'-forms
					 + if no notes exist for the plugin where they did this action, make sure that the form + add note
					 link returns
					 + make sure that succesfull template delete message is shown
					 * /
					$plugin_note_content = '';*/
			}
			exit( $message );
			//or
			echo json_encode( $message );
			die();
		}


		public function save_single_note( $plugin_slug ) {
			$plugin_slug = Plugin_Notes_Manage_Notes_Option::validate_plugin_slug( $plugin_slug );
			validate_note_key();
			validate_single_note();

			$all_notes = Plugin_Notes_Manage_Notes_Option::$current;

			// @todo - figure out the conditional if there is one
			if ( true ) {
				$all_notes[$plugin_slug][$note_key] = $note;
				
				// re-sort the notes for this plugin

				$all_notes[self::PREFIX . 'is_validated'] = true;

				$message = __( 'Successfully saved note', Plugin_Notes::$name );
				/* Validation is skipped within update */
				if ( update_option( Plugin_Notes_Manage_Notes_Option::NAME, $all_notes ) === false ) {
					// get_settings_errors() ?
					$message = __( 'Failure to save note, please try again', Plugin_Notes::$name );
				}
				
				
/*
			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();


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

					// Delete the old version so as to reset the timestamp key and removed potential import info
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
*/


				exit( $message );
				//or
				echo json_encode( $message );
				die();
			}
		}
		
		public function delete_single_note( $plugin_slug, $note_key ) {
			$all_notes = Plugin_Notes_Manage_Notes_Option::$current;

			if ( isset( $all_notes[$plugin_slug][$note_key] ) ) {

				unset( $all_notes[$plugin_slug][$note_key] );
				// If delete means there are no more notes for the plugin, delete plugin array
				if ( $all_notes[$plugin_slug] === array() ) {
					unset( $all_notes[$plugin_slug] );
				}

				$all_notes[self::PREFIX . 'is_validated'] = true;

				$message = __( 'Successfully deleted note', Plugin_Notes::$name );
				/* Validation is skipped within update */
				if ( update_option( Plugin_Notes_Manage_Notes_Option::NAME, $all_notes ) === false ) {
					// get_settings_errors() ?
					$message = __( 'Failure to delete note, please try again', Plugin_Notes::$name );
				}
				exit( $message );
				//or
				echo json_encode( $message );
				die();
			}
			

/*			$response_data = array();
			$response_data['slug'] = $plugin; // is this needed ?

			$note = array();


				$response_data['action'] = 'delete';

				$plugin_note_content = '';
*/
		}
		
		// Via bulk actions dropdown, probably needs hooking into a specific WP action hook
		public function bulk_delete_notes_for_plugins() {
		}

		public function bulk_export_notes_for_plugins() {
		}





		/** ******************* ACTIVATION AND UPGRADING ******************* **/


		/**
		 * Do activation actions
		 *
		 * @static
		 * @return void
		 */
		public static function activate() {
			/* Security check */
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$plugin = ( isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '' );
			check_admin_referer( 'activate-plugin_' . $plugin );


			/* Execute any extra actions registered */
			do_action( 'plugin_notes_plugin_activate' );
		}



		/**
		 * Function used when activating and/or upgrading the plugin
		 *
		 * Initial activate: Save default settings to options
		 * Upgrade for v2.0: Update old options structure to new
		 *
		 * @static
		 * @return void
		 */
		public static function upgrade_options() {
/*
Example saved note from v 1.1:
Array
(
    [wp-security-scan/index.php] => Array
        (
            [date] => 28 November, 2013
            [user] => 1
            [note] => test
        )

    [plugin-notes/plugin-notes.php] => Array
        (
            [date] => 28 November, 2013
            [user] => 1
            [note] => Nog een test
        )

)


Example saved notes from v 1.6:
Array
(
    [wp-security-scan/index.php] => Array
        (
            [date] => 28 November, 2013
            [user] => 1
            [note] => test
        )

    [plugin-notes/plugin-notes.php] => Array
        (
            [date] => 28 November, 2013
            [user] => 1
            [note] => Nog een test
        )

    [plugin-notes-1.6/plugin-notes.php] => Array
        (
            [date] => 28 November, 2013
            [user] => 1
            [note] => en een testje voor de nieuwe versie
            [color] => #F0F8E2
        )

    [plugin-notes_template] => en een testje voor de nieuwe versie
)

Example saved notes from v 2.0beta0 (site titia):
Array
(
    [bolcom-partnerprogramma-wordpress-plugin/bolcom-partnerprogramma-wordpress-plugin.php] => Array
        (
            [date] => 14 November 2012
            [user] => 1
            [note] => Requires PHP 5.3 ^%&^%&
        )

    [quotes-collection/quotes-collection.php] => Array
        (
            [date] => 18 December 2012
            [user] => 1
            [note] => **WP Plugin URL**: %WPURI_LINK%
            [color] => #F0F8E2
        )

    [mimetypes-link-icons/mime_type_link_images.php] => Array
        (
            [date] => 1372681809
            [user] => 1
            [note] => **WP Plugin URL**: %WPURI_LINK%
            [color] => #EBF9E6
            [pi_version] => 2.2.2.1
        )

    [better-wp-security/better-wp-security.php] => Array
        (
            [date] => 1362416959
            [user] => 1
            [note] => **WP Plugin URL**: %WPURI_LINK%
            [color] => #F9E8E6
            [pi_version] => 3.4.6
        )

*/


			/* Start of with the current values ( = default values if the options don't yet exist ) */
			$settings = Plugin_Notes_Manage_Settings_Option::$current;
			$notes    = Plugin_Notes_Manage_Notes_Option::$current;

//pr_var( $settings, 'Original settings within upgrade routine', true );
//pr_var( $notes, 'Original notes within upgrade routine', true );

			/**
			 * Upgrades for any version of this plugin lower than 2.0
			 * N.B.: Version nr has to be hard coded to be future-proof, i.e. facilitate
			 * upgrade routines for various versions
			 */
			if ( !isset( $settings['version'] ) || version_compare( $settings['version'], '2.0', '<' ) ) {
				/**
				 * Remove old settings from notes array
				 */
				$old_settings_keys = array(
					'version'				=>	'plugin-notes_version',
					'template'				=>	'plugin-notes_template',
					'sortorder'				=>	'plugin-notes_sortorder',
					'defaultcolor'			=>	'plugin-notes_defaultcolor',
					'dateformat'			=>	'plugin-notes_dateformat',
					'show_on_update'		=>	'plugin-notes_show_notes_on_update',
					'adminbar_url'			=>	'plugin-notes-change_adminbar_url',
				);

				foreach ( $old_settings_keys as $newkey => $oldkey ) {
					// Only set the settings array value if it's not the default
					if ( isset( $notes[$oldkey] ) && ( !isset( $settings[$newkey] ) || ( isset( $settings[$newkey] ) && ( $settings[$newkey] === Plugin_Notes_Manage_Settings_Option::$defaults[$newkey] && $notes[$oldkey] !== Plugin_Notes_Manage_Settings_Option::$defaults[$newkey] ) ) ) ) {
						$settings[$newkey] = $notes[$oldkey];
					}
					// Remove the old key
					if ( isset( $notes[$oldkey] ) && $notes[$oldkey] === $settings[$newkey] ) {
						unset( $notes[$oldkey] );
					}
				}
				unset( $newkey, $oldkey );
				
				/**
				 * Change old template setting from string to array
				 */
				if ( isset( $settings['template'] ) && is_string( $settings['template'] ) ) {
					$temp = $settings['template'];
					$settings['template'] = array();
					$settings['template']['default']['note'] = $temp;
					unset( $temp );
				}
				
				/**
				 * Move the default color setting to the template setting
				 */
				if ( isset( $settings['defaultcolor'] ) ) {
					if ( $settings['defaultcolor'] !== '' ) {
						$settings['template']['default']['color'] = $settings['defaultcolor'];
					}
					unset( $settings['defaultcolor'] );
				}

				/**
				 * Convert plugin notes array
				 */
				if ( is_array( $notes ) && $notes !== array() ) {
					foreach ( $notes as $key => $note ) {
						// @todo just not for our own options -> no longer needed ?
						// Check if we really have a note and if so, that it's a note still in the old format
						if ( in_array( $key, $old_settings_keys, true ) === false &&
							( ( is_array( $note ) && $note !== array() ) &&
							( isset( $note['date'] ) && isset( $note['note'] ) ) ) ) {

							// Change date from formatted date to timestamp
							if ( isset( $note['date'] ) && ( is_string( $note['date'] ) && $note['date'] !== '' ) ) {
								$timestamp = self::formatted_date_to_timestamp( $note['date'] );
								if ( !is_null( $timestamp ) && !is_null( Plugin_Notes_Manage_Notes_Option::validate_timestamp( $timestamp ) ) ) {
									$note['original_date'] = $note['date'];
									$note['date'] = $timestamp;
								}
								unset( $timestamp );
							}

							// Change plugin notes from single-dimension array to multi-array
							unset( $notes[$key] );
							$notes[$key][] = $note;
						}
					}
					unset( $key, $note );
				}
			}
			
			
			/**
			 * Always purge empty notes arrays
			 */
			if ( is_array( $notes ) && count( $notes ) > 0 ) {
				foreach ( $notes as $key => $note ) {
					if ( ! is_array( $note ) || ( is_array( $note ) && $note === array() ) ) {
						unset( $notes[$key] );
					}
				}
			}


			$settings['upgrading'] = true; // error prevention for when validation is used before settings API is loaded

			update_option( Plugin_Notes_Manage_Settings_Option::NAME, apply_filters( 'plugin_notes_save_settings_on_upgrade', $settings ) );
			update_option( Plugin_Notes_Manage_Notes_Option::NAME, apply_filters( 'plugin_notes_save_notes_on_upgrade', $notes ) );

			return;
		}


		/**
		 * Retrieve a timestamp based on a formatted date
		 *
		 * @static
		 * @usedby	upgrade_options()
		 *
		 * @param	string		$date_string		The date to get the timestamp for
		 * @return	int|null	the timestamp or null if conversion failed
		 */
		public static function formatted_date_to_timestamp( $date_string ) {
			static $str2timestamp = null;
			static $date_format   = null;
			
			if ( is_null( $date_format ) ) {
				$date_format = get_option( 'date_format' );
			}
			
			// Try simple conversions first
			$timestamp = self::strtotimestamp( $date_string, $date_format );
			if ( !is_null( $timestamp ) ) {
				return $timestamp;
			}

			// If that didn't work (very likely), do it the more complex, but pretty accurate way
			if ( is_null( $str2timestamp ) ) {
				$str2timestamp = Plugin_Notes_Date_String_To_Timestamp::getInstance();
			}
			return $str2timestamp->translate_to_timestamp( $date_string, $date_format );
		}

		
		/**
		 * Try to retrieve a timestamp from a datestring using PHP native functions
		 *
		 * @static
		 * @usedby	upgrade_options()
		 *
		 * @param	string		$date_string		The date to get the timestamp for
		 * @param	string		$date_format		The "old" date format
		 * @return	int|null	the timestamp or null if conversion failed
		 */
		public static function strtotimestamp( $date_string, $date_format ) {

			// Try strtotime()
			$timestamp = strtotime( $date_string );
			if ( ( $timestamp !== false && $timestamp !== -1 ) && ( date( $date_format, $timestamp ) === $date_string || date_i18n( $date_format, $timestamp ) === $date_string ) ) {
				return $timestamp;
			}
			unset( $timestamp );

			// Try date_parse_from_format()
			if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
				$date_obj  = date_parse_from_format( $date_format, $date_string );
				$timestamp = $date_obj->getTimestamp();
				if ( is_int( $timestamp ) && ( date( $date_format, $timestamp ) === $date_string || date_i18n( $date_format, $timestamp ) === $date_string ) ) {
					return $timestamp;
				}
				unset( $date_obj, $timestamp );
			}
			// Oh well, it didn't work
			return null;
		}




		/** ******************* SOME HELPER METHODS ******************* **/


		/**
		 * Validate an array of html tags
		 *
		 * @param	array	$tags		Tags array
		 * @return	array	Array of valid tags
		 */
		public function validate_allowed_tags( $tags = array() ) {
			$cleantags = array();
			if ( is_array( $tags ) && $tags !== array() ) {
				foreach ( $tags as $tag	=> $array ) {
					/* Validate tag */
					$tag = tag_escape( $tag );
					if ( $tag !== '' && is_array( $array ) ) {
						/* @todo Add validation for attributes ? */
						$cleantags[$tag] = $array;
					}
				}
				unset( $tag, $array );
			}
			return $cleantags;
		}

/*
		public static $allowed_tags = array(
			'a' 		=> array(
				'href'		=> true,
				'title' 	=> true,
				'target'	=> true,
			),
			'br'		=> array(),
			'p'			=> array(),
			'b'			=> array(),
			'strong'	=> array(),
			'i'			=> array(),
			'em'		=> array(),
			'u'			=> array(),
			's'			=> array(),
			'img'		=> array(
				'src'		=> true,
				'height'	=> true,
				'width'		=> true,
				'alt'		=> true,
			),
			'hr'		=> array(),
		);
*/


		/**
		 * Get/remember received/retrieved plugin data
		 * Efficiency function
		 *
		 * @param	string	$plugin_file	Plugin file name in the form: dir/filename.php
		 * @param	array	$plugin_data	(Optional) any plugin_data already available
		 * @return	array|null
		 */
		public function get_plugin_data( $plugin_data = null, $plugin_file = null ) {
			static $plugin_info = null;

			if ( ( is_string( $plugin_file ) && $plugin_file !== '' ) ) {
				if ( is_array( $plugin_data ) && $plugin_data !== array() ) {
					if ( isset( $plugin_info[$plugin_file] ) && ( is_array( $plugin_info[$plugin_file] ) && $plugin_info[$plugin_file] !== array() ) ) {
						$plugin_info[$plugin_file] = array_merge( $plugin_info[$plugin_file], $plugin_data );
					}
					else {
						$plugin_info[$plugin_file] = $plugin_data;
					}
				}

				if ( !isset( $plugin_info[$plugin_file] ) ) {
					$path_to_file = WP_PLUGIN_DIR . '/' . $plugin_file;
					if ( is_file( $path_to_file ) && is_readable( $path_to_file ) ) {
						$plugin_info[$plugin_file] = get_plugin_data( $path_to_file, false, true );
					}
					else {
						$plugin_info[$plugin_file] = null;
					}
				}

				return $plugin_info[$plugin_file];
			}
			return null;
		}
		
		
		/**
		 * Returns a cleaned up version of the plugin name, i.e. it's slug
		 *
		 * @param	string	$name	Plugin name
		 * @return	string
		 */
/*		public static function get_plugin_safe_name( $name ) {
			return sanitize_title( $name );
		}
*/


		/**
		 * Determine which date format to use for the plugin notes
		 *
		 * @todo remove the links_updated_date_format way as this will confuse with the settings page message of saying you can change on settings/general page (or is links_updated_... being auto-set within WP ? and if so, how ?)
		 *
		 * @return  string|null
		 */
		public static function get_date_format() {
			static $date_format;

			if ( ! isset( $date_format ) ) {
				if ( Plugin_Notes_Manage_Settings_Option::$current['use_wp_dateformat'] === false && ( isset( Plugin_Notes_Manage_Settings_Option::$current['dateformat'] ) && Plugin_Notes_Manage_Settings_Option::$current['dateformat'] !== '' ) ) {
					$date_format = Plugin_Notes_Manage_Settings_Option::$current['dateformat'];
				}
				else {
					$full = get_option( 'links_updated_date_format' );
					if ( $full !== false ) {
						$date_format = $full;
					}
					else {
						$date = get_option( 'date_format' );
						$time = get_option( 'time_format' );
						if ( $date !== false && $time !== false ) {
							/* TRANSLATORS: "date at time" */
							$date_format = sprintf( __( '%s at %s', self::$name ), $date, $time );
						}
						else if ( $date !== false ) {
							$date_format = $date;
						}
						unset( $date, $time );
					}
					unset( $full );
				}
			}
			return $date_format;
		}


		/**
		 * Format new style timestamp-date or leave as-is for old style formatted date
		 * @todo: check that the date we receive from the db is cast back to an int and not still a string
		 *
		 * @param	int		(optional) $timestamp - defaults to now
		 * @param	mixed	(optional) $alternative_date
		 * @return	string	Formatted date/time string
		 */
		public static function get_formatted_date( $timestamp = null, $alternative_date = null ) {
			static $date_format;

			if ( ! isset( $date_format ) ) {
				$date_format = self::get_date_format();
			}

			$formatted_date = '';
			if ( is_string( $date_format ) && $date_format !== '' ) {
				if ( !isset( $timestamp ) ) {
					// Get current date/time
					$formatted_date = date_i18n( $date_format );
				}
				else if ( is_int( $timestamp ) ) {
					$formatted_date = date_i18n( $date_format, $timestamp );
				}
				else if ( isset( $alternative_date ) ) {
					if ( is_int( $alternative_date ) ) {
						$formatted_date = date_i18n( $date_format, $alternative_date );
					}
				}
			}
			
			if ( $formatted_date === '' ) {
				// Fall back on giving straight back what we received
				$formatted_date = isset( $alternative_date ) ? $alternative_date : $timestamp;
			}

			return $formatted_date;
		}
		
		
		/**
		 * Recursively trim whitespace from a value
		 *
		 * @param   mixed   $value  Value to trim or array of values to trim
		 * @return  mixed   Trimmed value or array of trimmed values
		 */
		public static function trim_recursive( $value ) {
			if ( !is_array( $value ) && !is_object( $value ) ) {
				$value = trim( $value );
			}
			else if ( is_array( $value ) ) {
				$value = array_map( array( __CLASS__, 'trim_recursive' ), $value );
			}
			return $value;
		}

	} /* End of class */


	/**
	 * Instantiate the class
	 */
	if ( !function_exists( 'plugin_notes_init' ) ) {
		/* Only instantiate the class when we're in the backend */
		if ( is_admin() ) {
			add_action( 'plugins_loaded', 'plugin_notes_init' );
		}

		function plugin_notes_init() {
			/* Initialize the static variables */
			Plugin_Notes::init_statics();
			/* Instantiate the class */
			$GLOBALS['plugin_notes'] = new Plugin_Notes();
		}
		
		/* Set up the (de-)activation actions */
		register_activation_hook( __FILE__, array( 'Plugin_Notes', 'activate' ) );
	}
} /* End of class-exists wrapper */