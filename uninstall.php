<?php
/**
 * Plugin Notes Uninstall
 *
 * Code used when the plugin is removed (not just deactivated but actively deleted through the WordPress Admin).
 *
 * @package WordPress\Plugins\Plugin_Notes
 * @subpackage Uninstall
 * @since 1.5
 * @version 2.0
 *
 * @author Juliette Reinders Folmer
 *
 * @copyright 2009-2013 Mohammad Jangda, Juliette Reinders Folmer
 * @license http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2
 */

if ( !current_user_can( 'activate_plugins' ) || ( !defined( 'ABSPATH' ) || !defined( 'WP_UNINSTALL_PLUGIN' ) ) )
	exit();

delete_option( 'plugin_notes' );
delete_option( 'plugin_notes_settings' );