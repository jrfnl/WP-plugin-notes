=== Plugin Notes ===
Contributors: jrf, batmoo
Donate link: http://digitalize.ca/donate
Tags: plugin, plugin notes, memo, meta, plugins, document, documentation
Tested up to: 3.7.1
Requires at least: 3.5
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to add notes to plugins and share these across installations.

== Description ==

Allows you to add one or more notes/memorandi to plugins.

This plugin will be very useful if you:
* use lots of plugins and want to document which is used for what
* want to keep login details for external sites used by plugins handy (think: Jetpack)
* make modifications to a plugin and want to make a note of them
* found a bug in a plugin and want to link to your bug report
* work on your WordPress install with a group of people
* work for customers and need to document your work
* work on several wordpress installs and want to easily synchronize your plugin documentation between installs

= Features =
>Add/edit/delete notes for each plugin on the plugin page
>Multiple notes per plugin _(since v2.0)_

>Allows (limited) HTML in notes _(since v1.1)_
>Accepts [markdown syntax](http://daringfireball.net/projects/markdown/syntax) in notes _(since v1.5)_
>Output filters to alter the html output of the plugin _(since v1.5)_
>Magic variables: You can use [a number of variables](http://wordpress.org/extend/plugins/plugin-notes/faq/) which will be automagically replaced when the note displays _(since v1.5)_
>Color-code notes _(since v1.6)_
>Links within notes automagically have `target="_blank"` added so you won't accidentally leave your site while working with the plugins _(since v1.5)_
>Version number of the plugin is automatically registered with the note, so you can easily see if the note applies to the current version of the plugin or to an older version _(since v2.0)_

>Share notes with all other website admins or optionally keep certain notes private - for your eyes only -.

>Save a note as a template for new notes _(since v1.5)_

>Import/export notes between websites/WP installs. When you import notes they will be intelligently merged with existing notes _(since v2.0)_
>Optionally exclude certain notes from export (i.e. site specific notes)
>Purge notes for plugins which are no longer installed on a website _(since v2.0)_


** Extra:**
>Export a simple text file listing of installed plugins + versions


Please have a look at the [FAQ](http://wordpress.org/extend/plugins/plugin-notes/faq/) for more information about these features.

If you like this plugin, please [rate and/or review](http://wordpress.org/support/view/plugin-reviews/plugin-notes) it. If you have ideas on how to make the plugin even better or if you have found any bugs, please report these in the [Support Forum](http://wordpress.org/support/plugin/plugin-notes).


= Requirements =

The plugin requires PHP5+ and WP 3.5+

= Compatibility =

The plugin is not specifically well suited for multi-site installations. This is something which a future release will improve.

*****

= Credits =

This plugin was inspired by a post by [Chris Coyier](http://digwp.com): (http://digwp.com/2009/10/ideas-for-plugins/)

**Markdown script**: [PHP Markdown 1.0.1.o](http://michelf.ca/projects/php-markdown/)

**External link indicator**: liberally nicked from the [Better WP External Links](http://wordpress.org/extend/plugins/bwp-external-links/) plugin

**DateFormatRegexGenerator script**: Guy Paddock from [Red Bottle Design](http://www.redbottledesign.com/blog/generating-pcre-regular-expressions-date-format-strings-php)

**SweetiePlus icons**: [Joseph North](http://sublink.ca/)


= Localization =
* Dutch - [jrf](http://wordpress.org/support/profile/jrf)

Please help make this plugin available in more languages by translating it. The translation files are included in the download. See the [FAQ](http://wordpress.org/extend/plugins/plugin-notes/faq/) for more info.




== Frequently Asked Questions ==

= Why are the notes only shown on the plugins page and not on the update page ? That would be super-useful! =

There is no hook available to hook into on the update page, however, you can easily still see an overview of only the plugins which need updates *with* their notes.
By default, the plugins page will open to the _All_ view. At the top of the page you can however choose the following alternative views: _Active_ | _Recently active_ | _Inactive_ | _Must-Use_ | _Drop-ins_ |_Update available_ (some may not be available/visible depending on your setup). The plugin notes *will* display in those alternative views.

From the _Update available_ view (and from any other plugin page view for that matter), you can then check the checkbox for any number of the plugins which you want to update and choose the _Update_ option from the drop-down list at the top and bottom of the list to start updating.


= Where is the Plugin Notes data stored? =

The notes are stored in the `plugin_notes` option in the options table of the database.
The plugin notes settings are stored in the `plugin_notes_settings` option in the options table of the database.


= You say that the plugin will show the plugin version of when the note was made. Why don't I see it ? =

This will only work for notes saved after you upgraded to version 2.0 of this plugin.


= Which variables can I use ? =

There are a number of variables you can use in the notes which will automagically be replaced. Most aren't that useful as the info is provided by default for the plugin, but they are included anyway for completeness.

Example use: you want a link to the WordPress Plugin repository for each plugin.
Instead of manually adding each and every link, you can just add the following note to each plugin and the link will be automagically placed:

`	Plugin: %WPURI_LINK%`

**Available variables**:
`%PLUGIN_PATH%` : Plugin uri path on your website
`%WPURI%` : URI of the WordPress repository of the plugin (Please note: it is not tested whether the plugin is actually registered in the WP plugin repository! The URI is an educated guess.)
`%WPURI_LINK%` : A link to the above WordPress repository of the plugin

**Already showing for each plugin (less useful)**:
`%NAME%`: Plugin Name
`%URI%`: URI of the plugin website
`%AUTHOR%`: Name of the plugin author
`%AUTHORURI%`: Website of the plugin author
`%VERSION%`: Current plugin version
`%DESCRIPTION%`: Description of the plugin


= Can I use markdown syntax in the notes ? =

Yes, you can use markdown.
The markdown syntax conversion is done on the fly. The notes are saved to the database without conversion.

Don't like markdown ?
You can disable it on the Plugin Notes settings page.
Alternatively just add the following line to your theme's functions.php file:
`	remove_filter( 'plugin_notes_note', array(&$plugin_notes, 'filter_markdown' );`


= How do I use Markdown syntax? =

Please refer to [markdown syntax](http://daringfireball.net/projects/markdown/syntax).


= Can I use html in the notes ? =

Yes, you can use html in the notes. The following tags are allowed: `a, br, p, b, strong, i, em, u, s, img, hr`.
The html is saved to the database with the note.


= Can I change the allowed html tags ? =

Yes, you can, just add the following lines to your theme's functions.php file:
`function my_allowed_tags_filter( $tags ) {
	// do something to the tags
	return $tags
}
add_filter( 'plugin_notes_allowed_tags', 'my_allowed_tags_filter' );`

If you want to turn off the ability to use html in plugin notes completely, just uncheck the 'allow html' checkbox on the settings page.


= Can I change the output of the plugin ? =

Yes, you can. There are filters provided at three points:
1. The actual note to be displayed -> `plugin_notes_note`
1. The title of the note to be displayed -> `plugin_notes_title`
1. The html for the note including the surrounding box -> `plugin_notes_row`
1. The html for the input form -> `plugin_notes_form`

Hook into those filters to change the output before it's send to the screen.

`add_filter( 'plugin_notes_note', 'your_function', 10, 3 );
function your_function( $note, $plugin_data, $plugin_file ) {
	//do something
	return $output;
}`

`add_filter( 'plugin_notes_title', 'your_function', 10, 1 );
function your_function( $title ) {
	//do something
	return $output;
}`

`add_filter( 'plugin_notes_row', 'your_function', 10, 3 );
function your_function( $output, $plugin_data, $plugin_file ) {
	//do something
	return $output;
}`

`add_filter( 'plugin_notes_form', 'your_function', 10, 2 );
function your_function( $output, $plugin_safe_name ) {
	//do something
	return $output;
}`

If you want to filter the note output before the variable replacements are made and markdown syntax is applied, set the priority for your `plugin_notes_note` filter to lower than 10.

Example:
`	add_filter( 'plugin_notes_note', 'your_function', 8, 3 );`


= How can I translate the plugin? =

The plugin is translation ready, though there is not much to translate. Use the `/languages/plugin-notes.pot` file which is included in the download to create a new .po file for your language. If you would like to offer your translation to other users, please open a thread in the [support forum](http://wordpress.org/support/plugin/plugin-notes) to contact us.


== Changelog ==


= 2013-02-28 / 2.0 by jrf =
PLEASE NOTE: Once you upgrade, you can no longer downgrade without losing your notes!

* General improvements in code, query efficiency and security + code documentation
* [_Bug fix_] 'busy' image not loading when on a multi-site setup
* [_Bug fix_] displayed save date is now properly localized (for dates upgraded to/saved in the timestamp format - upgrade routine included)
* [_Bug fix_] Fixed: AJAX localization
* [_Clean code_] Cleaned up the HTML output
* [_Clean code_] Unobtrusified the javascript handlers
* [_New feature_] Enabled multiple notes per plugin
* [_New feature_] Added auto-save of version number of the plugin a note applies to
//* [_New feature_] Added option to keep certain notes private: for your eyes only
* [_New feature_] Added admin screen for import/export/purge functionality and override of some option defaults
* [_New feature_] Added import/export functionality
//* [_New feature_] Added option to selectively exclude certain notes from export
* [_New feature_] Added purge functionality
//* [_New feature_] Added export plugin list as text file functionality
//* Added screen options with js show/hide mechanism for notes
* [_Usability improvement_] Change note date saving from formatted date to timestamp to facilitate changes in the date formatting options and date compare on import of notes
* [_Usability improvement_] Added an easy way to change the allowed html tags list
* [_Usability improvement_] Updated the FAQ information in the readme file
* [_Compatibility_] Added plugin notes version number to options to enable upgrade routine
* [_Compatibility_] Added upgrade routine for the new array structure of the plugin options
* [_Compatibility_] Added upgrade routine for new way of saving note date
* [_Compatibility_] Made contextual help available for WP < 3.3
* [_l18n_] Updated the .POT file for new strings & updated the Dutch translation


= 2012-12-18 / 1.6 by jrf (not publicly released) =
* [_New feature_] Added ability to change the background color of notes

= 2012-12-16 / 1.5 by jrf (not publicly released) =

* General code review
* [_Security_] Improved output escaping
* [_Bug fix_] Fixed AJAX delete bug (kept 'waiting')
* [_Bug fix_] Fixed note edit capability bug for when 'edit_plugins' capability has been removed for security reasons
* [_Bug fix_] Fixed localization which wasn't working
* [_New feature_] Added output filters for html output (`plugin_notes_row` and `plugin_notes_form`) and the note itself (`plugin_notes_note`)
* [_New feature_] Added ability to use a number of variables in notes which will automagically be replaced - see [FAQ](http://wordpress.org/extend/plugins/plugin-notes/faq/) for more info
* [_New feature_] Added ability to use markdown syntax in notes - see [FAQ](http://wordpress.org/extend/plugins/plugin-notes/faq/) for more info
* [_Usability improvement_] Added `<hr />` to allowed tags list
* [_Usability improvement_] Made the default text area for adding a note larger
* [_Usability improvement_] Added automagical target="_blank" to all links in plugin notes including external link indicator
* [_Usability improvement_] Added contextual help for WP 3.3+
* [_Usability improvement_] Added FAQ section and plugin license info to the readme file ;-)
* [_Usability improvement_] Added uninstall script for clean uninstall of the plugin
* [_I18n_] Created .POT file and added Dutch translation


= 2010-10-15 / 1.1 =

* Certain HTML tags are now allowed in notes: `<p> <a> <b> <strong> <i> <em> <u> <img>`. Thanks to [Dave Abrahams](http://www.boostpro.com) for suggesting this feature.
* Some style tweaks
* Fixed PHP Error Notices

= 2009-12-04 / 1.0 =

* Fixed a major bug that was causing fatal errors
* Added some inline code comments
* Changed around some minor styling.
* Bumping release number up to 1.0 because I feel like it

= 2009-10-24 / 0.1 =

* Initial beta release

== Upgrade Notice ==

= 2.0 =
Code efficiency improved and new features: multiple notes per plugin and import/export notes

= 1.6 =
New feature: color-code notes.

= 1.5 =
Improved security and new features: plugin notes template, markdown syntax support and variable replacement.


== Installation ==

1. Extract the .zip file and upload its contents to the `/wp-content/plugins/` directory. Alternately, you can install directly from the Plugin directory within your WordPress Install.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Add notes to your plugins from the Manage Plugins page (Plugins > Installed)
1. Party.

== Screenshots ==
1.  Easily add/edit/delete note or save as notes-template. Uses AJAX so you'll save at least a couple seconds for each note you add/edit/delete.
2.  Example of saved note using markdown syntax and variable replacement.
3.  A bunch of multi-coloured notes added to plugins.

