<?php
/**
 * @file
 *  The <code>CapturingDateFormatRegexGenerator</code> class, which extends
 *  <code>DateFormatRegexGenerator</code> to implement a regular expression
 *  generator for both interpreting and tokenizing date and time strings based
 *  on date format strings.
 *
 *  © 2010 Red Bottle Design, LLC. All rights reserved.
 *
 *  http://www.redbottledesign.com
 *
 *  This source code is free software: you can redistribute it and/or modify
 *  it under the terms of the Lesser GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  Lesser GNU General Public License for more details.
 *
 *  You should have received a copy of the Lesser GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Guy Paddock (guy.paddock@redbottledesign.com)
 */

require_once('DateFormatRegexGenerator.class.php');

/**
 * A <code>DateFormatRegexGenerator</code> that generates regular expressions
 * that will separately capture each part of the date format string that is
 * matched.
 *
 * With this class, the following code:
 * <pre>
 *  $dateString     = "September 1st, 2010";
 *
 *  $regexGenerator = CapturingDateFormatRegexGenerator::getInstance();
 *  $regex          = $regexGenerator->generateRegex('F jS, Y', TRUE);
 *
 *  preg_match($regex, $dateString, $matches);
 *
 *  print_r($matches);
 * </pre>
 *
 * ... produces the following output:
 * <pre>
 *   Array
 *   (
 *       [0] => September 1st, 2010
 *       [1] => September
 *       [2] => 1
 *       [3] => st
 *       [4] => 2010
 *   )
 * </pre>
 *
 * This makes it trivial to generate a regular expression that can tokenize a
 * particular date and time string using only a date format string as input.
 *
 * For performance reasons, this class is a singleton. Use the
 * <code>getInstance()</code> method to obtain an instance.
 *
 * @author Guy Paddock (guy.paddock@redbottledesign.com)
 */
class CapturingDateFormatRegexGenerator
extends DateFormatRegexGenerator
{
  /**
   * The current singleton instance of
   * <code>CapturingDateFormatRegexGenerator</code>.
   *
   * @var CapturingDateFormatRegexGenerator
   */
  protected static $instance;

  /**
   *
   * @return CapturingDateFormatRegexGenerator
   */
  public static function getInstance()
  {
    if (empty(self::$instance))
      self::$instance = new CapturingDateFormatRegexGenerator();

    return self::$instance;
  }

  /**
   * Override of <code>DateFormatRegexGenerator::getDelimitedRegexPiece()</code>
   * that uses capturing-parentheses as the delimiter, so that each part of the
   * date string can be obtained after a match.
   *
   * @see DateFormatRegexGenerator::getDelimitedRegexPiece()
   *
   * @param string  $regexPiece
   *                The regular expression piece that needs delimiters.
   *
   * @return        string
   *                The resulting, properly-delimited regular expression piece.
   */
  protected function getDelimitedRegexPiece($regex)
  {
    // Capturing
    return '(' . $regex . ')';
  }
}