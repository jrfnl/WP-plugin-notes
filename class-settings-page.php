<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


/**
 * @todo add function to load this file when import/export link is clicked in main plugin file
 * @todo add function to add import/export settings link on plugin page
 * @todo adjust readme file + help tab text of main plugin file
 * @todo check for any wisdom to be found in export settings functions of si-contact-form and link-library
 *
 * @todo determine whether any methods should be made private or protected
 *
 *
 * @todo add multisite options page ? or keep them in normal options page, but only visible to super-admins and save these to separate multi-site network option
 * -> multi-site behaviour:
 * -> show notes from super-admin in all blogs ?
 * -> show notes from other blogs to super-admin ?
 */



/* Only load this class if plugin_notes class exists, is instantiated and version >= 2.0 */
if ( ( ! class_exists( 'Plugin_Notes_Settings_Page' ) && class_exists( 'Plugin_Notes' ) ) && version_compare( Plugin_Notes::VERSION, 2.0, '>=' ) === true ) {
	/**
	 * Plugin Notes Settings Page
	 *
	 * Code used to create and handle the settings page for the plugin
	 *
	 * @package		WordPress\Plugins\Plugin Notes
	 * @subpackage	Settings_Page
	 * @version		2.0
	 * @since		2.0
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 *
	 * @copyright	2013 Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Plugin_Notes_Settings_Page {


		/* *** DEFINE CLASS CONSTANTS *** */



		/* *** DEFINE CLASS PROPERTIES *** */

		/**
		 * @var string	Parent page to hook our settings page under
		 */
		public $parent_page = Plugin_Notes::PARENT_PAGE;

		/**
		 * @var string	Menu slug for our settings page
		 */
		public $menu_slug = '%s-settings';
		
		/**
		 * @var string	Unique prefix for use in class names and such
		 */
		public $setting_prefix = Plugin_Notes::PREFIX;
		
		/**
		 * @var array   array of option form sections and fields
		 *				Will be set by set_properties() as the section (and field) labels need translating
		 */
		public $form_sections = array();
		
		/**
		 * @var	array	array of setting sections which do not get saved to an option
		 */
		public $non_setting_form_sections = array(
			'import',
			'export',
			'purge',
			'reset',
		);


		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * @var string settings page registration hook suffix
		 */
		public $hook;
		




		/**
		 * Constructor
		 * Runs on admin_menu hook
		 *
		 * @return \Plugin_Notes_Settings_Page
		 */
		public function __construct() {
			
			/* Translate a number of strings */
			$this->menu_slug = sprintf( $this->menu_slug, Plugin_Notes::$name );
			
			/* Register the settings (import/export/purge) page */
			$this->add_submenu_page();
			// @todo - figure out multisite (network_admin_menu)

			/* Add option page related actions */
			add_action( 'admin_init', array( $this, 'set_properties' ), 3 );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}


		/**
		 * Fill some property arrays with translated strings
		 * Enrich some others
		 *
		 * @return void
		 */
		public function set_properties() {
			
			/* Exclude sorting on author when there is only one user */
			$sort_options = Plugin_Notes_Manage_Settings_Option::$sort_options;
			$users = count_users();
			if ( $users['total_users'] === 1 ) {
				unset( $sort_options['author_asc'], $sort_options['author_desc'] );
			}
			unset( $users );


			$this->form_sections = array(
				'template'		=> array(
					'title'			=> __( 'Note templates', Plugin_Notes::$name ),
				),
				'preferences'	=> array(
					'title'			=> __( 'Display prefences', Plugin_Notes::$name ),
					'fields'	=> array(

						array(
							'title'			 => __( 'Display style:', Plugin_Notes::$name ),
							'field'			 => 'display',
							'type'			 => 'radio',
							'options'		 => Plugin_Notes_Manage_Settings_Option::$display_options,
							'inline'		 => true,
							'classes'		 => array(),
						),
						
						/* @todo: add two more fields:
						   - who can view notes ?
						   - who can add/edit notes ?
						*/
						
						array(
							'title'		 	 => sprintf( __( 'Allow %sMarkdown syntax%s in notes ?', Plugin_Notes::$name ), '<a href="http://daringfireball.net/projects/markdown/syntax" target="_blank" class="ext-link">', '</a>' ),
							'field'		 	 => 'allow_markdown',
							'type'		 	 => 'checkbox',
							'label'		 	 => __( 'Yes please!', Plugin_Notes::$name ),
							'explain'	 	 => __( 'Please Note: Unchecking this option will not remove any existing markdown from your notes, but will only stop parsing the markdown syntax in the note.', Plugin_Notes::$name ),
						),

						array(
							'title'		 	 => __( 'Allow HTML in notes ?', Plugin_Notes::$name ),
							'field'		 	 => 'allow_html',
							'type'		 	 => 'checkbox',
							'label'		 	 => __( 'Yes please!', Plugin_Notes::$name ),
							'explain'	 	 => array(
								__( 'If you choose to allow HTML, the following tags will be allowed:', Plugin_Notes::$name ) . ' <code>' . implode( ', ',  array_keys( Plugin_Notes::$allowed_tags ) ) . '</code>',
								__( 'Please Note: Unchecking this option will not remove any existing HTML from your notes until the next time you save the note. It will, however, stop the HTML from being used to display the note.', Plugin_Notes::$name ),
								sprintf( __( 'Also Note: You can change the list of allowed tags using the %s filter', Plugin_Notes::$name ), '<code>plugin_notes_allowed_tags</code>' ),
							),
						),

						array(
							'title'			 => __( 'Notes display order:', Plugin_Notes::$name ),
							'field'			 => 'sortorder',
							'type'			 => 'radio',
							'options'		 => $sort_options,
							'legend'		 => __( 'Order the plugin notes based on:', Plugin_Notes::$name ),
							'legend_visible' => true,
						),

						// @todo check whether I may need to use get_admin_url() or netwerk_admin_url() instead for the correct link
						array(
							'title'		 	 => __( 'Date / Time Format', Plugin_Notes::$name ),
							'field'		 	 => 'use_wp_dateformat',
							'type'		 	 => 'checkbox',
							'label'		 	 => __( 'Use the date/time format as set for this WordPress site', Plugin_Notes::$name ),
							'explain'	 	 => sprintf( __( 'The current date/time format is : %s.', Plugin_Notes::$name ), Plugin_Notes::get_formatted_date() ) . '<br />' . sprintf( __( 'You can change the date/time format on the %sSettings -> General%s page.', Plugin_Notes::$name ), '<a href="' . admin_url( 'options-general.php' ) . '">', '</a>' ),
						),

						array(
							'title'			 => '&nbsp;',
							'field'			 => 'dateformat',
							'type'			 => 'text',
							'surround_txt'	 => __( 'Custom date format: %s', Plugin_Notes::$name ),
							/* TRANSLATORS: no need to translate - standard WP core translation will be used */
							'explain'		 => __( '<a href="http://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.' ),
							'small_txt'		 => true,
						),


/*						array(
							'title'		 	 => __( 'Show plugin notes on update page ? (experimental):', Plugin_Notes::$name ),
							'field'	 	 	=> 'show_on_update',
							'type'	 	 	=> 'checkbox',
							'label'	 	 	=> __( 'Yes please!', Plugin_Notes::$name ),
							'explain' 	 	=> array(
							),
						),
*/

						// @todo check whether I may need to use get_admin_url() or netwerk_admin_url() instead for the correct link

						array(
							'title'		 	 => __( 'Change Update URL in Admin toolbar ?', Plugin_Notes::$name ),
							'field'		 	 => 'adminbar_url',
							'type'		 	 => 'checkbox',
							'label'		 	 => __( 'Yes please!', Plugin_Notes::$name ),
							'explain'	 	 => array(
								'<img src="' . plugins_url( 'images/update-link-button.png', __FILE__ ) . '" width="68" height="27" alt="' . __( 'Example Admin Bar update button', Plugin_Notes::$name ) . '" class="' . $this->setting_prefix . 'adminbar-img" />' .
									__( 'If any WP core, theme or plugin updates are available, the Admin toolbar displayed at the top of the page, will show this. Clicking on the button in the Admin toolbar will - by default - take you to the "Update WordPress" page where you can update all three from one screen.', Plugin_Notes::$name ) . ' ' .
									__( 'However, this "Update WordPress" screen does not show you the relevant plugin notes.', Plugin_Notes::$name ),
								__( 'With this option, you can change this default behaviour so that the Admin toolbar button will refer you to the "Plugin Updates" page instead.', Plugin_Notes::$name ),
								sprintf( __( 'Please Note: for WP / theme updates, you will still need to go to the "Upgrade Wordpress" screen to updates those. You will be able to reach this screen via %sDashboard -> Updates%s.', Plugin_Notes::$name ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ),
							),
						),
					),
				),
			);
		}


		/**
		 * Register the settings page for all users that have the required capability
		 *
		 * @return void
		 */
		public function add_submenu_page() {

			$this->hook = add_submenu_page(
				$this->parent_page, /* parent slug */
				__( 'Plugin Notes Settings', Plugin_Notes::$name ), /* page title */
				__( 'Plugin Notes Settings', Plugin_Notes::$name ), /* menu title */
				Plugin_Notes_Manage_Settings_Option::REQUIRED_CAP, /* capability */
				$this->menu_slug, /* menu slug */
				array( $this, 'display_options_page' ) /* function for subpanel */
			);
		}
		

		/**
		 * Set up our settings page
		 *
		 * @return void
		 */
		public function admin_init() {

			/* Don't do anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( Plugin_Notes_Manage_Settings_Option::REQUIRED_CAP ) ) {
				return;
			}

			/* Register the settings sections and their callbacks */
			foreach ( $this->form_sections as $section => $section_info ) {
				add_settings_section(
					$this->setting_prefix . '-' . $section . '-settings', // id
					$section_info['title'], // title
					array( $this, 'do_settings_section_' . $section ), // callback for this section
					$this->menu_slug // page menu_slug
				);

				/* Register settings fields for the section */
				if ( isset( $section_info['fields'] ) && ( is_array( $section_info['fields'] ) && $section_info['fields'] !== array() ) ) {

					foreach ( $section_info['fields'] as $field_def ) {
						
						$field_def['setting_name'] = Plugin_Notes_Manage_Settings_Option::NAME;
						if ( ( ! isset( $field_def['label'] ) && ! isset( $field_def['surround_txt'] ) ) && ! $field_def['type'] === 'radio' ) {
							// Let WP add the label around the title in the first column in certain cases
							$field_def['label_for'] = Plugin_Notes_Manage_Settings_Option::NAME . '_' . $field_def['field'];
						}

						add_settings_field(
							// @todo - change the id to accommodate the templates option (multiple templates) ?
							Plugin_Notes_Manage_Settings_Option::NAME . '_' . $field_def['field'], // field id
							$field_def['title'], // field title
							array( $this, 'do_' . $field_def['type'] . '_field' ), // callback for this field
							$this->menu_slug, // page menu slug
							$this->setting_prefix . '-' . $section . '-settings', // section id
							$field_def // array of arguments which will be passed to the callback
						);
					}
				}
			}


			/* Register the 'validation' (handling) functions for the non-settings sections */
			$this->register_non_settings();


			/* Add our settings link on plugin page */
			add_filter( 'plugin_action_links_' . Plugin_Notes::$basename , array( $this, 'add_settings_link' ), 10, 2 );

			/* Add help tabs for our settings page */
			add_action( 'load-' . $this->hook, array( $this, 'add_help_tab' ) );
		}
		
		
		/**
		 * Register the handling functions for the non-setting sections and add the settings boxes to the form
		 *
		 * @return	void
		 */
		public function register_non_settings() {

			foreach ( $this->non_setting_form_sections as $section ) {
				register_setting(
					Plugin_Notes::$name . '_' . $section . '-group',
					Plugin_Notes::$name . '_' . $section, // "Fake" option name
					array( $this, 'do_' . $section ) // Handling / validation callback
				);
				add_action( 'plugin_notes_settings_box', array( $this, 'do_settings_' . $section . '_box' ) );
			}
		}


		/**
		 * Add settings/maintenance link to plugin-notes row for import/export/purge page
		 *
		 * @param	array	$links	Current links for the current plugin
		 * @param	string	$file	The file for the current plugin
		 * @return	array
		 */
		public function add_settings_link( $links, $file ) {

			if ( Plugin_Notes::$basename === $file && current_user_can( Plugin_Notes_Manage_Settings_Option::REQUIRED_CAP ) ) {
				$links[] = '<a href="' . esc_url( $this->plugin_options_url() ) .
					'" alt="' . esc_attr__( 'Plugin Notes Settings', Plugin_Notes::$name ) . '">' .
					/* TRANSLATORS: no need to translate - standard WP core translation will be used */
					esc_html__( 'Settings', Plugin_Notes::$name ) . '</a>';
			}
			return $links;
		}


		/**
		 * Return absolute URL of options page
		 *
		 * @return string
		 */
		public function plugin_options_url() {
			return add_query_arg( 'page', $this->menu_slug, admin_url( $this->parent_page ) );
		}


		/**
		 * Adds contextual help tab to the plugin settings page
		 *
		 * @return void
		 */
		public function add_help_tab() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'base' ) && $screen->base === $this->hook ) {
				$screen->add_help_tab(
					array(
						'id'	  => Plugin_Notes::$name . '-main', // This should be unique for the screen.
						'title'   => __( 'About Plugin Notes', Plugin_Notes::$name ),
						'callback' => array( $this, 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Plugin_Notes::$name . '-templates', // This should be unique for the screen.
						'title'   => __( 'Note Templates', Plugin_Notes::$name ),
						'callback' => array( $this, 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Plugin_Notes::$name . '-settings', // This should be unique for the screen.
						'title'   => __( 'Settings', Plugin_Notes::$name ),
						'callback' => array( $this, 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Plugin_Notes::$name . '-import-export', // This should be unique for the screen.
						'title'   => __( 'Import/Export', Plugin_Notes::$name ),
						'callback' => array( $this, 'get_helptext' ),
					)
				);

				$screen->set_help_sidebar( $this->get_help_sidebar() );
			}
		}


		/**
		 * Echo out the relevant helptext strings
		 *
		 * @todo Add proper help texts
		 *
		 * @param 	object	$screen		Screen object for the screen the user is on
		 * @param 	array	$tab		Help tab being requested
		 * @return  void
		 */
		public function get_helptext( $screen, $tab ) {

			switch ( $tab['id'] ) {
				case Plugin_Notes::$name . '-main' :
					echo '
								<p>' . esc_html__( 'Here comes a helpful help text ;-)', Plugin_Notes::$name ) . '</p>
								<p>' . esc_html__( 'And some more help.', Plugin_Notes::$name ) . '</p>';
					break;


				case Plugin_Notes::$name . '-settings' :
					echo '
								<p>' . esc_html__( 'Some information on the effect of the settings', Plugin_Notes::$name ) . '</p>';
					break;
			}
		}



		/**
		 * Generate the links for the help sidebar
		 * Of course in a real plugin, we'd have proper links here
		 *
		 * @return	string
		 */
		public function get_help_sidebar() {
			return '
				   <p><strong>' . /* TRANSLATORS: no need to translate - standard WP core translation will be used */ __( 'For more information:' ) . '</strong></p>
				   <p>
						<a href="http://wordpress.org/extend/plugins/plugin-notes/" target="_blank">' . __( 'Official plugin page', Plugin_Notes::$name ) . '</a>
					</p>
					<p>
						<a href="http://wordpress.org/extend/plugins/plugin-notes/faq/" target="_blank">' . __( 'FAQ', Plugin_Notes::$name ) . '</a> |
						<a href="http://wordpress.org/extend/plugins/plugin-notes/changelog/" target="_blank">' . __( 'Changelog', Plugin_Notes::$name ) . '</a> |
						<a href="http://wordpress.org/support/plugin/plugin-notes" target="_blank">' . __( 'Support&nbsp;Forum', Plugin_Notes::$name ) . '</a>
					</p>
					<p>
						<a href="https://github.com/jrfnl/plugin_notes" target="_blank">' . __( 'GitHub Repository', Plugin_Notes::$name ) . '</a> |
						<a href="https://github.com/jrfnl/plugin_notes/issues" target="_blank">' . __( 'Report issues', Plugin_Notes::$name ) . '</a>
					</p>
				   <p>' . sprintf( __( 'Created by %1$sAdvies en zo%3$s.<br />Original version by %2$sMohammad Jangda%3$s', Plugin_Notes::$name ), '<a href="http://adviesenzo.nl/" target="_blank">', '<a href="http://digitalize.ca/" target="_blank">', '</a>' ) . '</p>';
		}




		/* *** SETTINGS PAGE DISPLAY METHODS *** */

		/**
		 * Display our options page using the Settings API
		 *
		 * Useful functions available to get access to the parameters you used in add_submenu_page():
		 * - $parent_slug: get_admin_page_parent()
		 * - $page_title: get_admin_page_title(), or simply global $title
		 * - $menu_slug: global $plugin_page
		 *
		 * @todo: change display to a tabbed based interface ?
		 *
		 * @return void
		 */
		public function display_options_page() {

			if ( !current_user_can( Plugin_Notes_Manage_Settings_Option::REQUIRED_CAP ) ) {
				/* TRANSLATORS: no need to translate - standard WP core translation will be used */
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			/**
			 * Display the updated/error messages
			 * Only needed if our settings page is not under options, otherwise it will automatically be included
			 * @see settings_errors()
			 */
			include_once( ABSPATH . 'wp-admin/options-head.php' );


			/* Display the settings page */
			echo '
		<div class="wrap">';
			screen_icon();

			echo '
			<h2>' . get_admin_page_title() . '</h2>';
		
		
			$this->form_start( 'settings' );

			do_settings_sections( $this->menu_slug );

			/* @api allow other plugins to add to our settings page */
			do_action( 'plugin_notes_settings_page_sections' );

			$this->submit_button();
			$this->form_end();



			/* @api add more form boxes to the settings page */
			do_action( 'plugin_notes_settings_box' );
			
			
			/* Add our current settings array to the page for debugging purposes */
			$this->debug_info();
			
			echo '
		</div>'; // end of wrap


		}


		/**
		 * Can be used to add additional info to the form at the top of the section
		 * @return	void
		 */
		public function do_settings_section_template() {
			
			$section = 'template';
			
			$templates = Plugin_Notes_Manage_Settings_Option::$current['templates'];

			if ( ! is_array( $templates ) || $templates === array() ) {
				// No templates found, show 'create template' button
				echo '
					<a href="" id="" class="">' . __( 'Add new template', Plugin_Notes::$name ) . '</a>
				';
			}
			else {
				foreach ( $templates as $key => $template ) {
					// generate template tab code
					// generate template form code for each template

				}
			}


			/*
			set a js string with an empty template form for jQuery to use
			*/

/*			'template'			=> array(
				'blank'				=>	array(
					'name'				=>
					'color'				=> 'EAF2FA',
					'title'				=> '',
					'content'			=> '',
					'private'			=> '',
					'export_exclude'	=> '',
				),
			),
			
			$dropdown = '';
			foreach ( $templates as $template ) {
				// add to dropdown as option or as tabs ?
				// use last_selected_template setting to select which template is selected

				// use notes form display function to display the template forms
				
			}
*/

		}
		
		/**
		 * Generate html for the template form fields
		 *
		 * @param	array	$template	Template settings - defaults to a new empty form
		 * @return  string
		 */
		public function get_template_form( $template = array() ) {
			$form = '';
			// include 'make this template the default' button
			return $form;
		}


		/**
		 * Can be used to add additional info to the form at the top of the section
		 * @return	void
		 */
		public function do_settings_section_preferences() {
			return;
		}


		/**
		 * Display the import form
		 *
		 * @return void
		 */
		public function do_settings_import_box() {

			/* Exit early if not called from the right screen */
			$screen = get_current_screen();
			if ( ! property_exists( $screen, 'base' ) || $screen->base !== $this->hook ) {
				return;
			}

			$section = 'import';

			$this->form_start( $section, __( 'Import notes', Plugin_Notes::$name ), true );


			// Detect settings from other notes plugins and if found, offer to import those

			// import from other plugins
				// -> offer to delete the option(s) from the old plugin

			// import from file
				// -> either a export file from this plugin
				// -> or a CSV file - needs definition
				// upload file box


/*			Import notes:

			If already template in place: Replace, discard or merge template ?

			Use import settings found in the import file ? (if available)
			Import template ? checkbox


			How to merge ?
			Short explanation of the basics
*/
/*			Set author to:
			author of the note found on this blog
			me (the merger's user id)
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

			// Note: notes which are indicated as being 'private' will become private to the importing users


*/

			$this->submit_button( __( 'Import Notes', Plugin_Notes::$name ) );
			$this->form_end();
		}


		/**
		 * Display the export form
		 *
		 * @return void
		 */
		public function do_settings_export_box() {
			
			/* Exit early if not called from the right screen */
			$screen = get_current_screen();
			if ( ! property_exists( $screen, 'base' ) || $screen->base !== $this->hook ) {
				return;
			}

			$section      = 'export';
			$fake_setting = Plugin_Notes::$name . '_' . $section;
			$checkboxes   = array(
				array(
					'title'		=> __( 'What would you like to export ?', Plugin_Notes::$name ),
					'fields'	=> array(
						'notes'		=> array(
							'label'		=> __( 'the plugin notes', Plugin_Notes::$name ),
							'value'		=> true,
						),
						'private'	=> array(
							'label'		=> __( 'including private notes', Plugin_Notes::$name ),
							'classes'	=> array( $this->setting_prefix . 'indent' ),
							'value'		=> false,
							'explain'	=> __( 'Notes which have explicitely been set to "Exclude from export" will not be exported', Plugin_Notes::$name ),
						),
						'settings'	=> array(
							'label'		=> __( 'the settings for the plugin notes plugin', Plugin_Notes::$name ),
							'value'		=> false,
						),
						'template'	=> array(
							'label'		=> __( 'including plugin note template(s)', Plugin_Notes::$name ),
							'classes'	=> array( $this->setting_prefix . 'indent' ),
							'value'		=> false,
						),
					),
				),
			);

			$this->form_start( $section, __( 'Export notes', Plugin_Notes::$name ) );
			$this->checkbox_table( $checkboxes, $fake_setting, __( 'Export', Plugin_Notes::$name ) );
			$this->form_end();
		}


		/**
		 * Display the purge form
		 *
		 * @return void
		 */
		public function do_settings_purge_box() {
			
			/* Exit early if not called from the right screen */
			$screen = get_current_screen();
			if ( ! property_exists( $screen, 'base' ) || $screen->base !== $this->hook ) {
				return;
			}

			// purge what ?
				// notes about plugins no longer installed ?
				// notes about plugin versions older than current version ? (use with caution!)
				// notes older than x days ?
				

			/* NEW BLOCK */

/*			Purge notes button

			Delete notes:
			* all notes
			* only public notes
			* only private notes
			* only export excluded notes
*/

/* 			Also remove empty arrays */


			$section = 'purge';

			$this->form_start( $section, __( 'Purge notes', Plugin_Notes::$name ) );
			
			echo '
					<p>' . __( 'Purging notes is just a form of regular maintenance. Nothing exciting.', Plugin_Notes::$name ) . '</p>
					<p>' . __( 'When you uninstall a plugin, the accompanying notes are <strong><em>not</em></strong> automatically deleted. If you have uninstalled plugins, you may want to purge the redundant notes.', Plugin_Notes::$name ) . '</p>
					<p>' . __( 'Note: on upgrading of this plugin, an auto-purge is done.', Plugin_Notes::$name ) . '</p>';
//					<p>' . __( 'Purging notes will also remove imported notes for plugins not installed and any empty notes which exists.', Plugin_Notes::$name ) . '</p>';
					
			echo '
					<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">&nbsp;</th>
							<td>';

			$this->submit_button( __( 'Purge notes', Plugin_Notes::$name ) );

			echo '
							</td>
						</tr>
					</tbody>
					</table>';

			$this->form_end();
		}


		/**
		 * Display the reset form
		 *
		 * @return void
		 */
		public function do_settings_reset_box() {
			
			/* Exit early if not called from the right screen */
			$screen = get_current_screen();
			if ( ! property_exists( $screen, 'base' ) || $screen->base !== $this->hook ) {
				return;
			}

			$section      = 'reset';
			$fake_setting = Plugin_Notes::$name . '_' . $section;
			$checkboxes   = array(
				array(
					'title'		=> __( 'What would you like to reset ?', Plugin_Notes::$name ),
					'fields'	=> array(
						'notes'		=> array(
							'label'		=> __( 'the plugin notes', Plugin_Notes::$name ),
							'value'		=> false,
							'explain'	=> __( 'Beware: this will delete all your saved plugin notes!', Plugin_Notes::$name ),
						),
						'settings'	=> array(
							'label'		=> __( 'the settings for the plugin notes plugin', Plugin_Notes::$name ),
							'value'		=> false,
							'explain'	=> __( 'This will reset the settings to their default values.<br />Beware: this will also delete any saved plugin note templates!', Plugin_Notes::$name ),
						),
					),
				),
			);

			$this->form_start( $section, __( 'Reset', Plugin_Notes::$name ) );
			$this->checkbox_table( $checkboxes, $fake_setting, __( 'Reset', Plugin_Notes::$name ) );
			$this->form_end();
		}




		/* *** SETTINGS PAGE FORM PROCESSING METHODS FOR NON-OPTION FORMS *** */


		/**
		 * @param $received
		 *
		 * @return	bool    false (to avoid the option being added)
		 */
		public function do_import( $received ) {
			$section = 'import';
			$setting = Plugin_Notes::$name . '_' . $section;

pr_var( $received );
exit;
			return false;
/*			is file?
			read file
			validate file contents
			merge
*/
		//	make sure you don't override existing notes with the same timestamp!
		
			// if set: do purge -> run do_purge()
		}


		/**
		 * @param $received
		 *
		 * @return	bool    false (to avoid the option being added)
		 */
		public function do_export( $received ) {

pr_var( $received );
exit;
			return false;
			

			$notes = $this->notes_object->_getset_notes();
			
			// Remove notes which are to be excluded from export
			// Include template ?

			if ( !is_serialized( $notes ) ) {
				$notes = maybe_serialize( $notes );
			}

//			save to file / force download
		}


		/**
		 * Purge notes for no-longer-installed plugins and prevent the fake option being added to the options table
		 * Also purges empty (blank) notes and imported notes about plugins not in this installation.
		 *
		 * @todo TEST
		 *
		 * @param	null	$received	Empty $_POST array as there are no settings
		 * @return	bool    false (to avoid the option being added)
		 */
		public function do_purge( $received ) {
			$section = 'purge';
			$setting = Plugin_Notes::$name . '_' . $section;
			
			/**
			 * @todo: is this whole logic not superfluous ? would just resaving the option (including validation)
			 * not get rid of any notes for non-existing plugins ? (as they just wouldn't be added again)
			 */


			/* Get list of installed plugins
			   Key is the plugin file path and the value is an array of the plugin data. */
			$plugins = get_plugins();
			
			$clean = Plugin_Notes_Manage_Notes_Option::$current;
			
			if ( is_array( $clean ) && $clean !== array() ) {

				foreach ( $clean as $key => $value ) {
					//@todo: is this needed ?
					//$key = validate_plugin_slug( $key );

					/* Match notes versus installed plugins & delete notes if plugin is not/no longer installed */
					if ( array_key_exists( $key, $plugins ) === false ) {
						unset( $clean[$key] );
					}
					else {
						// Delete any empty notes which may exist
						foreach ( $value as $k => $note ) {
							if ( ( !isset( $note['note'] ) || $note['note'] === '' ) && ( !isset( $note['title'] ) || $note['title'] === '' ) ) {
								unset( $clean[$key][$k] );
							}
						}
						unset( $k, $note );
						
						// Check if any notes remain:
						if ( $clean[$key] === array() ) {
							unset( $clean[$key] );
						}
					}
				}
				unset( $key, $value );
	
				if ( update_option( Plugin_Notes_Manage_Notes_Option::NAME, $clean ) === true ) {
					$message = __( 'Successfully purged Plugin notes.', Plugin_Notes::$name );
					$type    = 'updated';
				}
				else {
					$message = __( 'Failed to purge plugin notes.', Plugin_Notes::$name );
					$type    = 'error';
				}
			}
			else {
				$message = __( 'No notes found, purge not executed', Plugin_Notes::$name );
				$type    = 'updated';
			}
			add_settings_error( $setting, $section, $message, $type );
			unset( $message, $type );

			return false;
		}



		/**
		 * Reset the Plugin's options and prevent the fake option being added to the options table
		 *
		 * @param	array	$received	$_POST variables from the Reset form
		 * @return	bool    false (to avoid the option being added)
		 */
		public function do_reset( $received ) {
			$section = 'reset';
			$setting = Plugin_Notes::$name . '_' . $section;

			if ( isset( $received['notes'] ) && filter_var( $received['notes'], FILTER_VALIDATE_BOOLEAN ) === true ) {
				if ( get_option( Plugin_Notes_Manage_Notes_Option::NAME ) === false || delete_option( Plugin_Notes_Manage_Notes_Option::NAME ) === true ) {
					$message = __( 'Plugin notes successfully reset (deleted).', Plugin_Notes::$name );
					$type    = 'updated';
				}
				else {
					$message = __( 'Failed to delete existing plugin notes.', Plugin_Notes::$name );
					$type    = 'error';
				}
				add_settings_error( $setting, $section . '-notes', $message, $type );
				unset( $message, $type );
			}

			if ( isset( $received['settings'] ) && filter_var( $received['settings'], FILTER_VALIDATE_BOOLEAN ) === true ) {
				Plugin_Notes_Manage_Settings_Option::remove_default_filter();

				if ( get_option( Plugin_Notes_Manage_Settings_Option::NAME ) === false || delete_option( Plugin_Notes_Manage_Settings_Option::NAME ) === true ) {
					$message = __( 'Plugin Notes settings successfully reset.', Plugin_Notes::$name );
					$type    = 'updated';
				}
				else {
					$message = __( 'Failed to reset Plugin Notes settings.', Plugin_Notes::$name );
					$type    = 'error';
				}
				Plugin_Notes_Manage_Settings_Option::add_default_filter();

				add_settings_error( $setting, $section . '-settings', $message, $type );
				unset( $message, $type );
			}

			if ( ! is_array( $received ) ) {
				$message = __( 'WARNING: nothing was selected, so nothing was reset.', Plugin_Notes::$name );
				$type    = 'updated';
				add_settings_error( $setting, $section, $message, $type );
			}
			return false;
		}
		



		public function validate_import_file() {
/*			is_file()
			is_readable()
			etc
*/		}

		public function validate_file_contents() {
/*			is_serialized() & can be unserialized
			are the notes arrays from the same plugin-notes version ? if not, allow for difficulties with comparisons (i.e. old formatted dates versus new timestamp type dates
			check name of option
			check contents of options
*/		}


		// @todo verify & determine if needed & whether this should be done here or in the notes option class
		public function merge_notes() {

/*			if note_is_same() ->
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
*/
		}
		

		// @todo verify & determine if needed & whether this should be done here or in the notes option class
		public function note_is_same( $note, $imported_note ) {

			// Strip all html and strip all whitespace
			$text = wp_filter_nohtml_kses( $note['note'] );
			$text = preg_replace( '/\s+/', '', $text );

			$imported_text = wp_filter_nohtml_kses( $imported_note['note'] );
			$imported_text = preg_replace( '/\s+/', '', $imported_text );

			if ( $text === $imported_text ) {
				return true;
			}
			else {
				return false;
			}
		}
		
		// Only relevant if notes are the same
		// @todo verify & determine if needed & whether this should be done here or in the notes option class
		public function compare_colors( $note, $imported_note ) {
			if ( $note['color'] === $imported_note['color'] ) {
				return $note['color'];
			}
			if ( $note['date'] === $imported_note['date'] ) {
				if ( $note['color'] !== $this->notes_object->settings['defaultcolor'] ) {
					return $note['color'];
				}
				else {
					return $imported_note['color'];
				}
			}
			else {
				$newest = ( $note['date'] > $imported_note['date'] ) ? $note : $imported_note;
				$oldest = ( $note['date'] < $imported_note['date'] ) ? $note : $imported_note;

				if ( $newest['color'] !== $this->notes_object->settings['defaultcolor'] ) {
					return $newest['color'];
				}
				else {
					return $oldest['color'];
				}
			}
		}










		/** ******************* SOME FORM HELPER METHODS ******************* **/

		/**
		 * Generate start of form html
		 *
		 * @param   string  $form_id
		 * @param   string  $section_title
		 * @param   bool    $contains_files
		 * @return void
		 */
		public function form_start( $form_id, $section_title = '', $contains_files = false ) {
			echo '
			<div id="' . $this->setting_prefix . $form_id . '-form">
				' . ( $section_title !== '' ? '<h2>' . esc_html( $section_title ) . '</h2>' : '' ) . '
				<form action="' . admin_url( 'options.php' ) . '" method="post"' .
					( $contains_files ? ' enctype="multipart/form-data"' : '' ) .
					' accept-charset="' . get_bloginfo( 'charset' ) . '">';

			settings_fields( Plugin_Notes::$name . '_' . $form_id . '-group' );
		}


		/**
		 * Generate custom submit button
		 *
		 * @param   string  $submit_label
		 * @return void
		 */
		public function submit_button( $submit_label = '' ) {
			if ( $submit_label === '' ) {
				submit_button();
			}
			else {
				echo '
					<div class="submitbox">
						<input type="submit" name="submit" class="button-primary" value="' . esc_attr( $submit_label ) . '" />
					</div>';
			}
		}


		/**
		 * Generate end of form html
		 * @return	void
		 */
		public function form_end() {
			echo '
				</form>
			</div>';
		}


		/**
		 * Display debug info
		 * @return	void
		 */
		public function debug_info() {
			/* Add our current settings array to the page for debugging purposes */
			if ( WP_DEBUG === true || defined( 'WPPN_DEBUG' ) && WPPN_DEBUG === true ) {
				echo '
			<div id="poststuff">
				<div id="' . $this->setting_prefix . '-debug-info" class="postbox">
		
					<h3 class="hndle"><span>' . __( 'Debug Information', Plugin_Notes::$name ) . '</span></h3>
					<div class="inside">
						<h4>' . __( 'Current Settings', Plugin_Notes::$name ) . '</h4>
						' . ( ! extension_loaded( 'xdebug' ) ? '<pre>' : '' );

				var_dump( Plugin_Notes_Manage_Settings_Option::$current );

				echo ( ! extension_loaded( 'xdebug' ) ? '</pre>' : '' ) . '

						<h4>' . __( 'Currently Saved Notes', Plugin_Notes::$name ) . '</h4>
						' . ( ! extension_loaded( 'xdebug' ) ? '<pre>' : '' );

				var_dump( Plugin_Notes_Manage_Notes_Option::$current );

				echo ( ! extension_loaded( 'xdebug' ) ? '</pre>' : '' ) . '
					</div>
				</div>
			</div>';
			}
		}

		
		/**
		 * Display a typical WP form table with one or more checkboxes as settings
		 *
		 * @todo change this function to enable it to display several different types of fields
		 *
		 * @param	array		$checkboxes				Array with field definitions
		 * @param	string		$setting_name			Current setting
		 * @param	string|bool	$submit_button_label	Label or false if the submit button should not
		 *												be added to the form
		 * @return	void
		 */
		public function checkbox_table( $checkboxes, $setting_name, $submit_button_label = false ) {

			echo '
					<table class="form-table">
					<tbody>';

			foreach ( $checkboxes as $row ) {
				echo '
						<tr>
							<th scope="row">' . $row['title'] . '</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
									<span>' . $row['title'] . '</span>
									</legend>';


				/* Create the checkboxes */
				foreach ( $row['fields'] as $field => $settings ) {
					$settings['setting_name'] = $setting_name;
					$settings['field']        = $field;
					$this->do_checkbox_field( $settings );
				}
				unset( $field, $settings );
	
	
				echo '
								</fieldset>
							</td>
						</tr>';
			}
			unset( $row );
			
			if ( is_string( $submit_button_label ) ) {
				echo '
						<tr>
							<td>&nbsp;</td>
							<td>';

				$this->submit_button( $submit_button_label );

				echo '
							</td>
						</tr>';
			}

			echo '
					</tbody>
					</table>';
		}



		/**
		 * Generate a text form field
		 *
		 * @param	array	$args
		 *					['setting_name']	string		parent setting name (might be fake)
		 *					['field']			string		field name
		 *					['surround_txt']	string		(optional) sprintf surround text with one %s delimiting
		 * 													where the input field should be
		 *					['value']			string		(optional) current field value - defaults to ''
		 *					['classes']			array		(optional) array of class names to add to the field /
		 *													surrounding paragraph
		 *					['explain']			string		(optional) additional help text
		 *					['no_autocomplete']	bool		(optional) whether the allow autocomplete in the field
		 *					['code']			bool		(optional) whether the input is code
		 *					['force_ltr']		bool		(optional) whether the input *has to* be ltr (text direction)
		 *					['small_txt']		bool		(optional) display setting for the input field
		 * @return	void
		 */
		public function do_text_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : '' ) );

			$field_id     = $args['setting_name'] . '_' . $args['field'];
			$field_name   = $args['setting_name'] . '[' . $args['field'] . ']';
			$surround_txt = ( isset( $args['surround_txt'] ) ? $args['surround_txt'] : '' );
			$autocomplete = ( isset( $args['no_autocomplete'] ) && $args['no_autocomplete'] === true ) ? ' autocomplete="off"' : '';
			$code         = ( isset( $args['code'] ) && $args['code'] === true ) ? ' code' : '';
			$ltr          = ( isset( $args['force_ltr'] ) && $args['force_ltr'] === true ) ? ' ltr' : '';
			$class        = ( isset( $args['small_txt'] ) && $args['small_txt'] === true ) ? 'small-text' : 'regular-text';
			$field_class  = '';
			$label_class  = '';
			if ( isset( $args['classes'] ) && ( is_array( $args['classes'] ) && $args['classes'] !== array() ) ) {
				if ( $surround_txt !== '' ) {
					$field_class = ' class="' . $class . $code . $ltr . '"';
					$label_class = ' class="' . esc_attr( implode( ' ', $args['classes'] ) ) . '"';
				}
				else {
					$field_class = ' class="' . $class . $code . $ltr . ' ' . esc_attr( implode( ' ', $args['classes'] ) ) . '"';
				}
			}

			$input_html = '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) .'" value="' . esc_attr( $value ) . '" ' . $field_class . $autocomplete . '>';


			if ( $surround_txt !== '' ) {
				echo '<label for="' . esc_attr( $field_id ) . '"' . $label_class . '>' . sprintf( $surround_txt, $input_html ) . '</label><br />';
			}
			else {
				echo $input_html . '<br />';
			}

			if ( isset( $args['explain'] ) ) {
				$this->do_explain( $args['explain'] );
			}
		}
		
/*
@todo add method for number field ?
<input id="posts_per_page" class="small-text" type="number" value="10" min="1" step="1" name="posts_per_page">
berichten
*/



		/**
		 * Generate a text area field
		 *
		 * @todo fill in function
		 * @return	void
		 */
		public function do_textarea_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : '' ) );

/*
<p>
<label for="moderation_keys">
Wanneer een reactie één of meer van deze woorden bevat in de inhoud, naam, URL, e-mail- of IP-adres, wordt het in de
<a href="edit-comments.php?comment_status=moderated">moderatiewachtrij</a>
gehouden. Eén woord of IP-adres per regel. Er wordt gezocht in woorden, dus ?vis? zal worden gevonden in ?Walvis?.
</label>
</p>
<p>
<textarea id="moderation_keys" class="large-text code" cols="50" rows="10" name="moderation_keys"></textarea>
</p>
*/

			if ( isset( $args['explain'] ) ) {
				$this->do_explain( $args['explain'] );
			}
		}


		/**
		 * Generate a hidden form field.
		 *
		 * @param	array	$args
		 *					['setting_name']	string		parent setting name (might be fake)
		 *					['field']			string		field name
		 *					['value']			string		(optional) current field value - defaults to ''
		 * @return	void
		 */
		public function do_hidden_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : '' ) );

			$field_id   = $args['setting_name'] . '_' . $args['field'];
			$field_name = $args['setting_name'] . '[' . $args['field'] . ']';

			echo '<input type="hidden" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) .'" value="' . esc_attr( $value ) . '" />';
		}



		/**
		 * Generate a checkbox form field
		 *
		 * @param	array	$args
		 *					['setting_name']	string		parent setting name (might be fake)
		 *					['field']			string		field name
		 *					['label']			string		field label
		 *					['value']			bool		(optional) current field value - defaults to false
		 *					['classes']			array		(optional) array of class names to add to the label
		 *					['explain']			string		(optional) additional help text
		 * @return	void
		 */
		public function do_checkbox_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : false ) );

			$field_id    = $args['setting_name'] . '_' . $args['field'];
			$field_name  = $args['setting_name'] . '[' . $args['field'] . ']';
			$field_label = ( isset( $args['label'] ) ? $args['label'] : '' );
			$checked     = checked( true, $value, false );
			$field_class = '';
			if ( isset( $args['classes'] ) && ( is_array( $args['classes'] ) && $args['classes'] !== array() ) ) {
				$field_class = ' class="' . esc_attr( implode( ' ', $args['classes'] ) ) . '"';
			}


			echo '
									<label for="' . esc_attr( $field_id ) . '"' . $field_class . '>
										<input type="checkbox" name="' . esc_attr( $field_name ) .'" id="' . esc_attr( $field_id ) . '" value="on"' . $checked . ' />
										' . esc_html( $field_label ) . '
									</label>
									<br />';

			if ( isset( $args['explain'] ) ) {
				$this->do_explain( $args['explain'] );
			}
		}

		/**
		 * Generate a radio-button form field.
		 *
		 * @param	array	$args
		 *					['setting_name']	string		parent setting name (might be fake)
		 *					['field']			string		field name
		 *					['options']			array		array with options to use
		 *					['legend']			string		(optional) field legend
		 *					['legend_visible']	bool		(optional) whether the label is only for screen readers
		 *													or also for other users - defaults to false (= screen readers)
		 *					['value']			bool		(optional) current field value - defaults to null
		 *					['classes']			array		(optional) array of class names to add to the radio button set
		 *					['explain']			string		(optional) additional help text
		 *					['inline']			bool		(optional) whether to display the radio buttons on one line
		 *													- defaults to false (underneath each other)
		 * @return void
		 */
		public function do_radio_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : null ) );

			$fieldset_id    = $args['setting_name'] . '_' . $args['field'];
			$field_name     = $args['setting_name'] . '[' . $args['field'] . ']';
			$field_legend   = ( isset( $args['legend'] ) ? $args['legend'] : '' );
			$legend_class   = ( isset( $args['legend_visible'] ) && $args['legend_visible'] === true ? '' : ' class="screen-reader-text"' );
			$label_class    = ( isset( $args['inline'] ) && $args['inline'] === true ? ' class="' . $this->setting_prefix . 'radio-inline"' : '' );
			$inline         = ( isset( $args['inline'] ) && $args['inline'] === true ? '' : '<br />' );
			$fieldset_class = '';
			if ( isset( $args['classes'] ) && ( is_array( $args['classes'] ) && $args['classes'] !== array() ) ) {
				$fieldset_class = ' class="' . esc_attr( implode( ' ', $args['classes'] ) ) . '"';
			}
			
			if ( is_array( $args['options'] ) && $args['options'] !== array() ) {
				echo '
								<fieldset id="' . $fieldset_id . '"' . $fieldset_class . '>';
								
				if ( $field_legend !== '' ) {
					echo '
								<legend' . $legend_class . '>
									<span>' . $field_legend . '</span>
								</legend>';
				}

				foreach ( $args['options'] as $option_key => $option_value ) {
					$field_id = $fieldset_id . '_' . $option_key;
					$checked  = checked( $option_key, $value, false );
					$title    = strip_tags( $option_value );
					$title    = ( $title !== '' ? ' title="' . esc_attr( $title ) . '"' : '' );
					
					echo '
									<label for="' . esc_attr( $field_id ) . '"' . $title . $label_class . '>
										<input type="radio" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $option_key ) . '"' . $checked . '/> ' . $option_value . '
									</label>'. $inline;
				}
				unset( $option_key, $option_value );

				echo '
								</fieldset>';
			}
			
			if ( isset( $args['explain'] ) ) {
				$this->do_explain( $args['explain'] );
			}
		}


		/**
		 * Generate a Select Box form field
		 *
		 * @param	array	$args
		 *					['setting_name']	string		parent setting name (might be fake)
		 *					['field']			string		field name
		 *					['options']			array		(one or two dimensional) array with options to use
		 *					['label']			string		(optional) field label
		 *					['value']			bool		(optional) current field value - defaults to false
		 *					['classes']			array		(optional) array of class names to add to the radio button set
		 *					['explain']			string		(optional) additional help text
		 * @return void
		 */
		public function do_select_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : null ) );
				
			$field_id    = $args['setting_name'] . '_' . $args['field'];
			$field_name  = $args['setting_name'] . '[' . $args['field'] . ']';
			$field_label = ( isset( $args['label'] ) ? $args['label'] : '' );
			$field_class = '';
			if ( isset( $args['classes'] ) && ( is_array( $args['classes'] ) && $args['classes'] !== array() ) ) {
				$field_class = ' class="' . esc_attr( implode( ' ', $args['classes'] ) ) . '"';
			}


			if ( is_array( $args['options'] ) && $args['options'] !== array() ) {
				if ( $field_label !== '' ) {
					echo '
								<label for="' . esc_attr( $field_id ) . '">
									' . $field_label . '
								<br />';
				}

				echo '
								<select name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '"' . $field_class . '>';


				foreach ( $args['options'] as $option_key => $option_value ) {
					if ( is_string( $option_value ) ) {
						$selected = selected( $option_value, $value, false );

						echo '
									<option value="' . esc_attr( $option_key ) . '"' . $selected. '>' . esc_html( $option_value ) . '</option>';
					}
					else if ( is_array( $option_value ) && $option_value !== array() ) {
						echo '
							  		<optgroup label="' . esc_attr( $option_key ) . '">';

						foreach ( $option_value as $k => $v ) {
							$selected = selected( $k, $value, false );
	
							echo '
										<option value="' . esc_attr( $option_key . '_' . $k ) . '"' . $selected. '>' . esc_html( $v ) . '</option>';
						}
						unset( $k, $v );

						echo '
							  		</optgroup>';

					}
				}
				unset( $option_key, $option_value );

				echo '
								</select>';
								
				if ( $field_label !== '' ) {
					echo '
								</label>';
				}
				
				echo '<br />';
			}
			
			if ( isset( $args['explain'] ) ) {
				$this->do_explain( $args['explain'] );
			}

		}

		/**
		 * Create a File upload field.
		 *
		 * @todo rewrite method to suit my needs for the import section
		 *
		 * @param string $var    The variable within the option to create the file upload field for.
		 * @param string $label  The label to show for the variable.
		 * @param string $option The option the variable belongs to.
		 * @return string
		 */
		public function do_file_field( $args ) {
			$current = get_option( $args['setting_name'] );
			$value   = ( isset( $args['value'] ) ? $args['value'] :
				( $current !== false && isset( $current[$args['field']] ) ? $current[$args['field']] : '' ) );
				
			$this->do_hidden_field( array(
				'setting_name'	=> $args['setting_name'],
				'field'			=> 'max_file_size',
				'value'			=> '100000',
				)
			);


/*			if ( empty( $option ) )
				$option = $this->currentoption;
	
			$options = $this->get_option( $option );
	
			$val = '';
			if ( isset( $options[$var] ) && strtolower( gettype( $options[$var] ) ) == 'array' ) {
				$val = $options[$var]['url'];
			}
	
			$var_esc = esc_attr( $var );
			$output  = '<label class="select" for="' . $var_esc . '">' . esc_html( $label ) . ':</label>';
			$output .= '<input type="file" value="' . $val . '" class="textinput" name="' . esc_attr( $option ) . '[' . $var_esc . ']" id="' . $var_esc . '"/>';
	
			// Need to save separate array items in hidden inputs, because empty file inputs type will be deleted by settings API.
			if ( !empty( $options[$var] ) ) {
				$output .= '<input class="hidden" type="hidden" id="' . $var_esc . '_file" name="wpseo_local[' . $var_esc . '][file]" value="' . esc_attr( $options[$var]['file'] ) . '"/>';
				$output .= '<input class="hidden" type="hidden" id="' . $var_esc . '_url" name="wpseo_local[' . $var_esc . '][url]" value="' . esc_attr( $options[$var]['url'] ) . '"/>';
				$output .= '<input class="hidden" type="hidden" id="' . $var_esc . '_type" name="wpseo_local[' . $var_esc . '][type]" value="' . esc_attr( $options[$var]['type'] ) . '"/>';
			}
			$output .= '<br class="clear"/>';
	
			return $output;
*/
		}


		/**
		 * Generate the additional explanation paragraph
		 *
		 * @param	string|array	$explain	Explanation text or array of explanation texts
		 * @return	void
		 */
		public function do_explain( $explain ) {
			if ( is_string( $explain ) && $explain !== '' ) {
				echo '
									<p class="description">' . $explain . '</p>';
			}
			else if ( is_array( $explain ) && $explain !== array() ) {
				foreach ( $explain as $string ) {
					$this->do_explain( $string );
				}
			}
		}
	} // End of class
} // End of class exists wrapper