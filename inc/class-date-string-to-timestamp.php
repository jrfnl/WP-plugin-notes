<?php
// Avoid direct calls to this file, by checking whether WP core and framework exists
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/*
@todo Move unit tests to separate file
*/


if ( !class_exists( 'Plugin_Notes_Date_String_To_Timestamp' ) ) {
	/**
	 * Plugin Notes Date conversion Functions
	 *
	 * Code used to convert formatted dates to their timestamp number
	 *
	 * @package		WordPress\Plugins\Plugin Notes
	 * @subpackage	Date String To Timestamp
	 * @version		2.0
	 * @since		2.0
	 * @link		https://github.com/jrfnl/WP-plugin-notes Plugin Notes
	 * @author		Juliette Reinders Folmer <wp_pluginnotes_nospam@adviesenzo.nl>
	 *
	 * @copyright	2013 Juliette Reinders Folmer
	 * @license		http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Plugin_Notes_Date_String_To_Timestamp extends CapturingDateFormatRegexGenerator {

		/**
		 * The current singleton instance of
		 * <code>plugin_notes_date_string_to_timestamp</code>.
		 *
		 * @access protected
		 * @var object plugin_notes_date_string_to_timestamp
		 */
		protected static $instance;

		/**
		 * @access protected
		 * @var array	English full names of months
		 */
		protected $full_months = array(
				1	=>	'January',
				2	=>	'February',
				3	=>	'March',
				4	=>	'April',
				5	=>	'May',
				6	=>	'June',
				7	=>	'July',
				8	=>	'August',
				9	=>	'September',
				10	=>	'October',
				11	=>	'November',
				12	=>	'December',
		);

		/**
		 * @access protected
		 * @var array	English short names of months
		 */
		protected $short_months = array(
				1	=>	'Jan',
				2	=>	'Feb',
				3	=>	'Mar',
				4	=>	'Apr',
				5	=>	'May',
				6	=>	'Jun',
				7	=>	'Jul',
				8	=>	'Aug',
				9	=>	'Sep',
				10	=>	'Oct',
				11	=>	'Nov',
				12	=>	'Dec',
		);
		
		/** Arrays to store the localized values of the same - English language - properties **/
		/**
		 * @access protected
		 * @var array	Localized version of the date format regular expressions
		 */
		protected $i18n_formatCharToRegexMap = array();
		
		/**
		 * @access protected
		 * @var array	Localized full names of months
		 */
		protected $i18n_full_months = array();
		
		/**
		 * @access protected
		 * @var array	Localized short names of months
		 */
		protected $i18n_short_months = array();


		/* *** SET UP THE CLASS *** */

		/**
		 * Override of parent method
		 *
		 * @return Plugin_Notes_Date_String_To_Timestamp
		 */
		public static function getInstance() {
			if ( empty( self::$instance ) )
				self::$instance = new Plugin_Notes_Date_String_To_Timestamp();

			return self::$instance;
		}

		/**
		 * Override of parent method
		 *
		 * Fills the localization arrays and initializes the RegexMap
		 */
		protected function initializeMap() {
			$this->set_formatCharToRegexMap();
			parent::initializeMap();

			// Fill the localization arrays
			$this->i18n_full_months();
			$this->i18n_short_months();
			$this->i18n_formatCharToRegexMap();
		}


		/* *** LOCALIZE THE CLASS PROPERTIES *** */

		/**
		 * Copy of the parent method
		 * Sole difference is that this one used the localized RegexMap
		 *
		 * @param   string  $format
		 * @param   bool    $match_entire_string
		 * @param   bool    $escape_special_chars
		 * @return	string	localized regular expression
		 */
		public function i18n_generate_regex( $format, $match_entire_string = false, $escape_special_chars = true ) {
			$pattern_start = ( $match_entire_string ) ? '`^' : '`';
			$pattern_end   = ( $match_entire_string ) ? '$`' : '`';

			if ( $escape_special_chars ) {
				// Escape all PCRE special characters
				$format = preg_quote( $format, '`' );
			}

			$regex = $pattern_start . strtr( $format, $this->i18n_formatCharToRegexMap ) . $pattern_end;

			return $regex;
		}
		
		/**
		 * Override two parent properties to facilitate capturing of individual date properties
		 * when the chosen format is a full date format
		 */
		protected function set_formatCharToRegexMap() {
			/*** Full Date/Time ***/
			// ISO 8601 date (added in PHP 5) - 2004-02-12T15:19:21+00:00
			$this->formatCharToRegexMap['c'] = '([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])T(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])(?:(?:-|\+)(?:[0-1][0-9]|2[0-3]):(?:[0-5][0-9]))';

			// RFC 2822 formatted date
			$this->formatCharToRegexMap['r'] = '(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun), (0[1-9]|[1-2][0-9]|3[0-1]) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-9]{4}) (0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]) (?:(?:-|\+)(?:[0-1][0-9]|2[0-3])(?:[0-5][0-9]))';
		}
		
		/**
		 * Localize (translate) the full month names
		 *
		 * @uses WordPress localization methods
		 */
		protected function i18n_full_months() {
			global $wp_locale;
			foreach ( $this->full_months as $key => $value ) {
				$this->i18n_full_months[$key] = $wp_locale->get_month( $key );
			}
		}

		/**
		 * Localize (translate) the short month names
		 *
		 * @uses WordPress localization methods
		 */
		protected function i18n_short_months() {
			global $wp_locale;
			foreach ( $this->i18n_full_months as $key => $value ) {
				$this->i18n_short_months[$key] = $wp_locale->get_month_abbrev( $value );
			}
		}

		/**
		 * Create a localized version of the RegexMap
		 *
		 * @uses WordPress localization methods
		 */
		protected function i18n_formatCharToRegexMap() {
			global $wp_locale;

			$sets = array(
				array(
					'i18n_method'	=>	'weekday_abbrev',
					'i18n_method2'  =>	'weekday',
					'values'		=>	'Mon|Tue|Wed|Thu|Fri|Sat|Sun',
					'keys'		    =>	array( 1, 2, 3, 4, 5, 6, 0 ),
				),
				array(
					'i18n_method'	=>	'weekday',
					'values'		=>	'Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday',
					'keys'		    =>	array( 1, 2, 3, 4, 5, 6, 0 ),
				),
				array(
					'i18n_method'	=>	null,
					'values'		=>	'st|nd|rd|th',
				),
				array(
					'i18n_method'	=>	'month',
					'values'		=>	'January|February|March|April|May|June|July|August|September|October|November|December',
				),
				array(
					'i18n_method'	=>	'month_abbrev',
					'values'		=>	'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec',
				),
				array(
					'i18n_method'	=>	'meridiem',
					'values'		=>	'am|pm',
				),
				array(
					'i18n_method'	=>	'meridiem',
					'values'		=>	'AM|PM',
				),
			);
			
			$find    = array();
			$replace = array();

			foreach ( $sets as $set ) {
				$find[]	= $set['values'];

				$strings     = explode( '|', $set['values'] );
				$method_name = 'get_' . $set['i18n_method'];

				switch ( $set['i18n_method'] ) {
					case 'weekday':
						foreach ( $set['keys'] as $k => $v ) {
							$strings[$k] = $wp_locale->$method_name( $v );
						}
						unset( $k, $v );
						break;

					case 'weekday_abbrev':
						$method2 = 'get_' . $set['i18n_method2'];
						foreach ( $set['keys'] as $k => $v ) {
							$strings[$k] = $wp_locale->$method_name( $wp_locale->$method2( $v ) );
						}
						unset( $k, $v, $method2 );
						break;

					case 'month':
						$strings = $this->i18n_full_months;
						break;

					case 'month_abbrev':
						$strings = $this->i18n_short_months;
						break;

					case 'meridiem':
						foreach ( $strings as $k => $v ) {
							$strings[$k] = $wp_locale->$method_name( $v );
						}
						unset( $k, $v );
						break;

					default:
						foreach ( $strings as $k => $v ) {
							$strings[$k] = __( $v );
						}
						unset( $k, $v );
						break;
				}
				$replace[] = implode( '|', $strings );
			}

			$this->i18n_formatCharToRegexMap = str_replace( $find, $replace, $this->formatCharToRegexMap );
		}
		
		
		/* *** CONVERSION METHOD *** */

		/**
		 * Retrieve a timestamp based on a formatted date and a known date format
		 *
		 * @param	string	    $date			formatted date
		 * @param	string	    $date_format	PHP date() format string
		 * @return	int|null	Unix timestamp integer or null if no valid timestamp could be created
		 */
		public function translate_to_timestamp( $date, $date_format ) {
			$timestamp = $this->get_timestamp( $date, $date_format );
			if ( !is_null( $timestamp ) && is_int( $timestamp ) && $timestamp > 0 ) {
				return $timestamp;
			}
			return null;
		}
		

		/* *** CONVERSION HELPER METHODS *** */
		
		/**
		 * Gets a Unix timestamp from a formatted date based on a known dateformat
		 *
		 * @param	string	    $date			a formatted date
		 * @param	string	    $date_format	a PHP date() format string
		 * @return	int|null	Unix timestamp integer or null if no valid timestamp could be created
		 */
		protected function get_timestamp( $date, $date_format ) {
			static $last_date_format = null;
			static $regex = null;
			static $i18n_regex = null;

			if ( is_null( $regex ) || $last_date_format !== $date_format ) {
				$regex            = $this->generateRegex( $date_format,  true );
				$i18n_regex       = $this->i18n_generate_regex( $date_format,  true );
				$last_date_format = $date_format;
			}

			$matched      = preg_match( $regex, $date, $matches );
			$i18n_matched = false;
			if ( $matched !== 1 ) {
				$i18n_matched = preg_match( $i18n_regex, $date, $matches );
			}

			if ( count( $matches ) > 0 ) {
				$matches = $this->map_format_to_matches( $matches, $date_format );

				if ( $matches !== false ) {
					$timestamp = $this->make_timestamp( $matches, $date_format, ( $matched === 0 && $i18n_matched === 1) );

					if ( isset( $matches['T'] ) ) {
						// Hack to be able to check if dates are the same without the T(imezone) value getting in the way
						$date        = str_replace( $matches['T'], '', $date );
						$find        = array( '\T', 'T', '%%' );
						$replace     = array( '%%', '', '\T' );
						$date_format = str_replace( $find, $replace, $date_format );
					}

					if ( $matched === 1 ) {
						if ( date( $date_format, $timestamp ) === $date ) {
							return $timestamp;
						}
						else if ( $date_format === 'c' || $date_format === 'r' ) {
							$new_date     = date( $date_format, $timestamp );
							$len_new_date = mb_strlen( $new_date );
							$len_date     = mb_strlen( $date );
							if ( mb_substr( $new_date, 0, $len_new_date - 6 ) === mb_substr( $date, 0, $len_date - 6 ) ) {
								return $timestamp;
							}
						}
					}

					else if ( $i18n_matched === 1 ) {
						if ( date_i18n( $date_format, $timestamp ) === $date ) {
							return $timestamp;
						}
						else if ( $date_format === 'c' || $date_format === 'r' ) {
							$new_date     = date_i18n( $date_format, $timestamp );
							$len_new_date = mb_strlen( $new_date );
							$len_date     = mb_strlen( $date );
							if ( mb_substr( $new_date, 0, $len_new_date - 6 ) === mb_substr( $date, 0, $len_date - 6 ) ) {
								return $timestamp;
							}
						}
					}
				}
			}
			return null;
		}

		/**
		 * Pull the date formatting letters from the date format and add them as
		 * keys to the matches array
		 *
		 * @param	array	        $matches
		 * @param	string	        $date_format		a PHP date() format string
		 * @return	array|false		combined array or false if the combine failed
		 */
		protected function map_format_to_matches( $matches, $date_format ) {

			$keys = array();
			
			if ( $date_format === 'c' ) {
				$keys = array( 'Y', 'm', 'd', 'H', 'i', 's' );
			}
			else if ( $date_format === 'r' ) {
				$keys = array( 'd', 'M', 'Y', 'H', 'i', 's' );
			}
			else {
				$max = strlen( $date_format );
				$prev_char = null;
				for ( $i = 0; $i < $max; $i++ ) {
					$char = substr( $date_format, $i, 1 );
					if ( $prev_char !== '\\' && array_key_exists( $char, $this->formatCharToRegexMap ) === true ) {
						$keys[] = $char;
					}
					$prev_char = $char;
					unset( $char );
				}
				unset( $max, $i, $prev_char );
			}
			// remove the full date matches
			array_shift( $matches );
			if ( $date_format === 'c' || $date_format === 'r' ) {
				array_shift( $matches );
			}
			return ( array_combine( $keys, $matches ) );
		}

		/**
		 * Extrapolate individual date/time properties from a regex match array
		 * and create a timestamp based on these
		 *
		 * @param	array	    $matches	    Regex match array with date format chars as keys
		 * @param   string      $date_format a PHP date() format string
		 * @param	bool	    $i18n		    Whether $matches is based on localized data or English
		 *
		 * @return	int|false	timestamp integer or false if no valid timestamp could be created
		 */
		protected function make_timestamp( $matches, $date_format, $i18n = false ) {

			$hour   = intval( $this->get_hour( $matches, $i18n ) );
			$minute = ( isset( $matches['i'] ) ? intval( $matches['i'] ) : 0 );
			$second = ( isset( $matches['s'] ) ? intval( $matches['s'] ) : 0 );

			$month = $this->get_month( $matches, $i18n );
			$day   = ( isset( $matches['d'] ) ? intval( $matches['d'] ) :
				( isset( $matches['j'] ) ? intval( $matches['j'] ) : null ) );
			$year  = ( isset( $matches['Y'] ) ? intval( $matches['Y'] ) :
				( isset( $matches['y'] ) ? intval( $matches['y'] + 2000 ) :
				( isset( $matches['o'] ) ? intval( $matches['o'] ) : null ) ) );

			$is_dst = ( isset( $matches['I'] ) ? intval( $matches['I'] ) : -1 );

			if ( ( is_null( $day ) || is_null( $month ) ) && ( isset( $matches['z'] ) && !is_null( $year ) ) ) {
				$zvalues = $this->get_day_month_from_z( $matches['z'], $year );
				$day     = ( !is_null( $zvalues['day'] ) ? $zvalues['day'] : null );
				$month   = ( !is_null( $zvalues['month'] ) ? $zvalues['month'] : null );
				unset( $zvalues );
			}

			$timestamp = false;
			if ( !is_null( $year ) && !is_null( $month ) && !is_null( $day ) ) {
				$timestamp = mktime( $hour, $minute, $second, $month, $day, $year, $is_dst );
			}

			if ( $timestamp !== false && $timestamp !== -1 ) {
				return $timestamp;
			}
			else {
				return false;
			}
		}

		/**
		 * Get the qualified 24-hour based hour from matches
		 *
		 * @param	array	$matches	a regex matches array with PHP date() format characters as keys
		 * @param	bool	$i18n		Whether $matches is based on localized data or English
		 * @return	int		hour or 0 when no hour indicator found
		 */
		protected function get_hour( $matches, $i18n = false ) {
			
			if ( isset( $matches['H'] ) )
				return $matches['H'];
			
			if ( isset( $matches['G'] ) )
				return $matches['G'];

			if ( isset( $matches['h'] ) || isset( $matches['g'] ) ) {
				$hour = ( isset( $matches['h'] ) ? intval( $matches['h'] ) : intval( $matches['g'] ) );

				// No am/pm indicator, return what we know
				if ( ! isset( $matches['a'] ) && ! isset( $matches['A'] ) )
					return $hour;

				// Still here so we have either 'a' or 'A'
				$ampm = ( isset( $matches['a'] ) ? $matches['a'] : $matches['A'] );

				// Considered strcasecmp, but that would not allow for differences in translation
				if ( $i18n === false && ( $ampm === 'pm' || $ampm === 'PM' ) )
					return ( $hour + 12 );
					
				/* TRANSLATORS: no need to translate - standard WP core translation will be used */
				else if ( $i18n === true && ( $ampm === __( 'pm' ) || $ampm === __( 'PM' ) ) )
					return ( $hour + 12 );
				else
					return $hour;
			}
			return 0;
		}

		/**
		 * Get the month from matches
		 *
		 * @param	array	$matches	a regex matches array with PHP date() format characters as keys
		 * @param	bool	$i18n		Whether $matches is based on localized data or English
		 * @return	mixed|null		    month number as either string or int or null if no month found
		 */
		protected function get_month( $matches, $i18n = false ) {

			if ( isset( $matches['m'] ) ) {
				return intval( $matches['m'] );
			}

			if ( isset( $matches['n'] ) ) {
				return intval( $matches['n'] );
			}
			if ( isset( $matches['F'] ) ) {
				$months     = ( $i18n ? $this->i18n_full_months : $this->full_months );
				$full_month = array_search( $matches['F'], $months, true );

				if ( !is_null( $full_month ) && $full_month !== false ) {
					return intval( $full_month );
				}
				unset( $months, $full_month );
			}
			if ( isset( $matches['M'] ) ) {
				$months      = ( $i18n ? $this->i18n_short_months : $this->short_months );
				$short_month = array_search( $matches['M'], $months, true );

				if ( !is_null( $short_month ) && $short_month !== false ) {
					return intval( $short_month );
				}
				unset( $months, $short_month );
			}
			return null;
		}
		
		/**
		 * Get the day number and month from a date format 'z' (day in the year) match
		 *
		 * @param	string|int	$z_days		Value for 'z'
		 * @param	int			$year
		 * @return	array		array with day and month numbers or null values if not matched
		 */
		protected function get_day_month_from_z( $z_days, $year ) {

			$result = array(
				'day'	=> null,
				'month'	=> null,
			);
			
			$is_leapyear = date( 'L', mktime( 0, 0, 0, 1, 1, $year ) );
			
			// z starts at 0 for Jan 1st
			$days = intval( $z_days + 1 );
			
			$months = array(
				1	=> 31, // January
				2	=> 28 + $is_leapyear, // February
				3	=> 31, // March
				4	=> 30, // April
				5	=> 31, // May
				6	=> 30, // June
				7	=> 31, // July
				8	=> 31, // August
				9	=> 30, // September
				10	=> 31, // October
				11	=> 30, // November
				12	=> 31, // December
			);
			
			$sofar = 0;
			foreach ( $months as $k => $v ) {
				if ( $days <= ( $sofar + $v ) ) {
					$result['day']   = ( $days - $sofar );
					$result['month'] = $k;
					break;
				}
				$sofar += $v;
			}
			unset( $k, $v, $sofar, $is_leapyear, $days, $months );

			return $result;
		}


	} /* End of class */


	/**
	 * Fix for bug in WP core date_i18n() function where dates with format 'r' are not being translated
	 * Reported in: http://core.trac.wordpress.org/ticket/23056
	 */
	if ( !function_exists( 'patch_date_i18n' ) ) :
		add_filter( 'date_i18n', 'patch_date_i18n', 10, 4 );

		/**
		 * Patch for WP core date_i18n() function
		 *
		 * @param   string  $formatted_date
		 * @param   string  $req_format
		 * @param   string  $timestamp
		 * @param   string  $gmt
		 * @return  string
		 */
		function patch_date_i18n( $formatted_date, $req_format, $timestamp, $gmt ) {

			if ( $req_format !== 'r' )
				return $formatted_date;

			global $wp_locale;
			$datefunc = $gmt? 'gmdate' : 'date';

			$find    = array(
				$datefunc( 'M', $timestamp ),
				$datefunc( 'D', $timestamp ),
			);
			$replace = array(
				$wp_locale->get_month_abbrev( $wp_locale->get_month( $datefunc( 'm', $timestamp ) ) ),
				$wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $datefunc( 'w', $timestamp ) ) ),
			);
			return str_replace( $find, $replace, $formatted_date );
		}
	endif;
	
	
	/**
	 * Simple unit test for the above class
	 */
	function plugin_notes_datestringconvert_test_it() {

		$test_date_array = array(
			array(
				'date'		=>	'24 December 2012',
				'format'	=>	'j F Y',
			),
			array(
				'date'		=>	'Thu, 21 Dec 2000 16:01:07 +0200',
				'format'	=>	'r',
			),
			array(
				'date'		=>	'March 10, 2001, 5:16 pm',
				'format'	=>	'F j, Y, g:i a',
			),
			array(
				'date'		=>	'03.10.01',
				'format'	=>	'm.d.y',
			),
			array(
				'date'		=>	'10, 3, 2001',
				'format'	=>	'j, n, Y',
			),
			array(
				'date'		=>	'20010310',
				'format'	=>	'Ymd',
			),
			array(
				'date'		=>	'05-16-18, 10-03-01, 1631 1618 6 Satpm01',
				'format'	=>	'h-i-s, j-m-y, it is w Day',
			),
			array(
				'date'		=>	'Sat Mar 10 17:16:18 MST 2001',
				'format'	=>	'D M j G:i:s T Y',
			),
			array(
				'date'		=>	'Sat Mar 10 TEST 17:16:18 MST 2001',
				'format'	=>	'D M j \TE\S\T G:i:s T Y',
			),
			array(
				'date'		=>	'2001-03-10 17:16:18',
				'format'	=>	'Y-m-d H:i:s',
			),
			array(
				'date'		=>	'2008 day 270',
				'format'	=>	'Y \d\a\y z',
			),
			array(
				'date'		=>	'2012 day 360',
				'format'	=>	'Y \d\a\y z',
			),
			array(
				'date'		=>	'2013 day 0',
				'format'	=>	'Y \d\a\y z',
			),
			array(
				'date'		=>	'2004-02-12T15:19:21+04:00',
				'format'	=>	'c',
			),

			// Dutch localized dates
			array(
				'date'		=>	'24 december 2012',
				'format'	=>	'j F Y',
			),
			array(
				'date'		=>	'24 mei 2009',
				'format'	=>	'j M Y',
			),
			array(
				'date'		=>	'za, 21 okt 2000 16:01:07 +0200',
				'format'	=>	'r',
			),
			array(
				'date'		=>	'maart 10, 2001, 5:16 pm',
				'format'	=>	'F j, Y, g:i a',
			),
			array(
				'date'		=>	'05-16-18, 10-03-01, 1631 1618 6 zapm01',
				'format'	=>	'h-i-s, j-m-y, it is w Day',
			),
			array(
				'date'		=>	'za mrt 10 17:16:18 MST 2001',
				'format'	=>	'D M j G:i:s T Y',
			),
		);

		$format = 'r';
		$regexGenerator = Plugin_Notes_Date_String_To_Timestamp::getInstance();

		foreach ( $test_date_array as $test ) {
			$timestamp = $regexGenerator->translate_to_timestamp( $test['date'], $test['format'] );

			print '<h4>Testing: ' . $test['date'] . ' with format ' . $test['format'] . '</h4>';
			print '<p>Resulting timestamp: ' . $timestamp . '</p>';
			print '<p>Which translates to: ' . date( $format, $timestamp ) . '</p>';
		}
	}
}