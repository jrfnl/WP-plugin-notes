<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Plugin_Notes' ) && ! class_exists( 'Plugin_Notes_Display_Notes_Accordion' ) ) {
	/**
	 * @package		WordPress\Plugins\Plugin Notes
	 * @subpackage	Display Notes Accordion
	 * @version		2.0
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 *
	 * @copyright	2013 Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Plugin_Notes_Display_Notes_Accordion extends Plugin_Notes_Display_Notes {
		


	} // End of class
} // End of class exists wrapper