<?php

/**
 * This is a concatenated copy of the following files:
 *   Horde/String.php, Horde/iCalendar.php, Horde/iCalendar/*.php
 */

if (!class_exists('Horde_Date'))
    require_once(dirname(__FILE__) . '/Horde_Date.php');


$GLOBALS['_HORDE_STRING_CHARSET'] = 'iso-8859-1';

/**
 * The String:: class provides static methods for charset and locale safe
 * string manipulation.
 *
 * $Horde: framework/Util/String.php,v 1.43.6.38 2009-09-15 16:36:14 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Horde 3.0
 * @package Horde_Util
 */
class String {

    /**
     * Caches the result of extension_loaded() calls.
     *
     * @param string $ext  The extension name.
     *
     * @return boolean  Is the extension loaded?
     *
     * @see Util::extensionExists()
     */
    function extensionExists($ext)
    {
        static $cache = array();

        if (!isset($cache[$ext])) {
            $cache[$ext] = extension_loaded($ext);
        }

        return $cache[$ext];
    }

    /**
     * Sets a default charset that the String:: methods will use if none is
     * explicitly specified.
     *
     * @param string $charset  The charset to use as the default one.
     */
    function setDefaultCharset($charset)
    {
        $GLOBALS['_HORDE_STRING_CHARSET'] = $charset;
        if (String::extensionExists('mbstring') &&
            function_exists('mb_regex_encoding')) {
            $old_error = error_reporting(0);
            mb_regex_encoding(String::_mbstringCharset($charset));
            error_reporting($old_error);
        }
    }

    /**
     * Converts a string from one charset to another.
     *
     * Works only if either the iconv or the mbstring extension
     * are present and best if both are available.
     * The original string is returned if conversion failed or none
     * of the extensions were available.
     *
     * @param mixed $input  The data to be converted. If $input is an an array,
     *                      the array's values get converted recursively.
     * @param string $from  The string's current charset.
     * @param string $to    The charset to convert the string to. If not
     *                      specified, the global variable
     *                      $_HORDE_STRING_CHARSET will be used.
     *
     * @return mixed  The converted input data.
     */
    function convertCharset($input, $from, $to = null)
    {
        /* Don't bother converting numbers. */
        if (is_numeric($input)) {
            return $input;
        }

        /* Get the user's default character set if none passed in. */
        if (is_null($to)) {
            $to = $GLOBALS['_HORDE_STRING_CHARSET'];
        }

        /* If the from and to character sets are identical, return now. */
        if ($from == $to) {
            return $input;
        }
        $from = String::lower($from);
        $to = String::lower($to);
        if ($from == $to) {
            return $input;
        }

        if (is_array($input)) {
            $tmp = array();
            reset($input);
            while (list($key, $val) = each($input)) {
                $tmp[String::_convertCharset($key, $from, $to)] = String::convertCharset($val, $from, $to);
            }
            return $tmp;
        }
        if (is_object($input)) {
            // PEAR_Error objects are almost guaranteed to contain recursion,
            // which will cause a segfault in PHP.  We should never reach
            // this line, but add a check and a log message to help the devs
            // track down and fix this issue.
            if (is_a($input, 'PEAR_Error')) {
                Horde::logMessage('Called convertCharset() on a PEAR_Error object. ' . print_r($input, true), __FILE__, __LINE__, PEAR_LOG_DEBUG);
                return '';
            }
            $vars = get_object_vars($input);
            while (list($key, $val) = each($vars)) {
                $input->$key = String::convertCharset($val, $from, $to);
            }
            return $input;
        }

        if (!is_string($input)) {
            return $input;
        }

        return String::_convertCharset($input, $from, $to);
    }

    /**
     * Internal function used to do charset conversion.
     *
     * @access private
     *
     * @param string $input  See String::convertCharset().
     * @param string $from   See String::convertCharset().
     * @param string $to     See String::convertCharset().
     *
     * @return string  The converted string.
     */
    function _convertCharset($input, $from, $to)
    {
        $output = '';
        $from_check = (($from == 'iso-8859-1') || ($from == 'us-ascii'));
        $to_check = (($to == 'iso-8859-1') || ($to == 'us-ascii'));

        /* Use utf8_[en|de]code() if possible and if the string isn't too
         * large (less than 16 MB = 16 * 1024 * 1024 = 16777216 bytes) - these
         * functions use more memory. */
        if (strlen($input) < 16777216 || !(String::extensionExists('iconv') || String::extensionExists('mbstring'))) {
            if ($from_check && ($to == 'utf-8')) {
                return utf8_encode($input);
            }

            if (($from == 'utf-8') && $to_check) {
                return utf8_decode($input);
            }
        }

        /* First try iconv with transliteration. */
        if (($from != 'utf7-imap') &&
            ($to != 'utf7-imap') &&
            String::extensionExists('iconv')) {
            /* We need to tack an extra character temporarily because of a bug
             * in iconv() if the last character is not a 7 bit ASCII
             * character. */
            $oldTrackErrors = ini_set('track_errors', 1);
            unset($php_errormsg);
            $output = @iconv($from, $to . '//TRANSLIT', $input . 'x');
            $output = (isset($php_errormsg)) ? false : String::substr($output, 0, -1, $to);
            ini_set('track_errors', $oldTrackErrors);
        }

        /* Next try mbstring. */
        if (!$output && String::extensionExists('mbstring')) {
            $old_error = error_reporting(0);
            $output = mb_convert_encoding($input, $to, String::_mbstringCharset($from));
            error_reporting($old_error);
        }

        /* At last try imap_utf7_[en|de]code if appropriate. */
        if (!$output && String::extensionExists('imap')) {
            if ($from_check && ($to == 'utf7-imap')) {
                return @imap_utf7_encode($input);
            }
            if (($from == 'utf7-imap') && $to_check) {
                return @imap_utf7_decode($input);
            }
        }

        return (!$output) ? $input : $output;
    }

    /**
     * Makes a string lowercase.
     *
     * @param string  $string   The string to be converted.
     * @param boolean $locale   If true the string will be converted based on a
     *                          given charset, locale independent else.
     * @param string  $charset  If $locale is true, the charset to use when
     *                          converting. If not provided the current charset.
     *
     * @return string  The string with lowercase characters
     */
    function lower($string, $locale = false, $charset = null)
    {
        static $lowers;

        if ($locale) {
            /* The existence of mb_strtolower() depends on the platform. */
            if (String::extensionExists('mbstring') &&
                function_exists('mb_strtolower')) {
                if (is_null($charset)) {
                    $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
                }
                $old_error = error_reporting(0);
                $ret = mb_strtolower($string, String::_mbstringCharset($charset));
                error_reporting($old_error);
                if (!empty($ret)) {
                    return $ret;
                }
            }
            return strtolower($string);
        }

        if (!isset($lowers)) {
            $lowers = array();
        }
        if (!isset($lowers[$string])) {
            $language = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, 'C');
            $lowers[$string] = strtolower($string);
            setlocale(LC_CTYPE, $language);
        }

        return $lowers[$string];
    }

    /**
     * Makes a string uppercase.
     *
     * @param string  $string   The string to be converted.
     * @param boolean $locale   If true the string will be converted based on a
     *                          given charset, locale independent else.
     * @param string  $charset  If $locale is true, the charset to use when
     *                          converting. If not provided the current charset.
     *
     * @return string  The string with uppercase characters
     */
    function upper($string, $locale = false, $charset = null)
    {
        static $uppers;

        if ($locale) {
            /* The existence of mb_strtoupper() depends on the
             * platform. */
            if (function_exists('mb_strtoupper')) {
                if (is_null($charset)) {
                    $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
                }
                $old_error = error_reporting(0);
                $ret = mb_strtoupper($string, String::_mbstringCharset($charset));
                error_reporting($old_error);
                if (!empty($ret)) {
                    return $ret;
                }
            }
            return strtoupper($string);
        }

        if (!isset($uppers)) {
            $uppers = array();
        }
        if (!isset($uppers[$string])) {
            $language = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, 'C');
            $uppers[$string] = strtoupper($string);
            setlocale(LC_CTYPE, $language);
        }

        return $uppers[$string];
    }

    /**
     * Returns a string with the first letter capitalized if it is
     * alphabetic.
     *
     * @param string  $string   The string to be capitalized.
     * @param boolean $locale   If true the string will be converted based on a
     *                          given charset, locale independent else.
     * @param string  $charset  The charset to use, defaults to current charset.
     *
     * @return string  The capitalized string.
     */
    function ucfirst($string, $locale = false, $charset = null)
    {
        if ($locale) {
            $first = String::substr($string, 0, 1, $charset);
            if (String::isAlpha($first, $charset)) {
                $string = String::upper($first, true, $charset) . String::substr($string, 1, null, $charset);
            }
        } else {
            $string = String::upper(substr($string, 0, 1), false) . substr($string, 1);
        }
        return $string;
    }

    /**
     * Returns part of a string.
     *
     * @param string $string   The string to be converted.
     * @param integer $start   The part's start position, zero based.
     * @param integer $length  The part's length.
     * @param string $charset  The charset to use when calculating the part's
     *                         position and length, defaults to current
     *                         charset.
     *
     * @return string  The string's part.
     */
    function substr($string, $start, $length = null, $charset = null)
    {
        if (is_null($length)) {
            $length = String::length($string, $charset) - $start;
        }

        if ($length == 0) {
            return '';
        }

        /* Try iconv. */
        if (function_exists('iconv_substr')) {
            if (is_null($charset)) {
                $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
            }

            $old_error = error_reporting(0);
            $ret = iconv_substr($string, $start, $length, $charset);
            error_reporting($old_error);
            /* iconv_substr() returns false on failure. */
            if ($ret !== false) {
                return $ret;
            }
        }

        /* Try mbstring. */
        if (String::extensionExists('mbstring')) {
            if (is_null($charset)) {
                $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
            }
            $old_error = error_reporting(0);
            $ret = mb_substr($string, $start, $length, String::_mbstringCharset($charset));
            error_reporting($old_error);
            /* mb_substr() returns empty string on failure. */
            if (strlen($ret)) {
                return $ret;
            }
        }

        return substr($string, $start, $length);
    }

    /**
     * Returns the character (not byte) length of a string.
     *
     * @param string $string  The string to return the length of.
     * @param string $charset The charset to use when calculating the string's
     *                        length.
     *
     * @return string  The string's part.
     */
    function length($string, $charset = null)
    {
        if (is_null($charset)) {
            $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
        }
        $charset = String::lower($charset);
        if ($charset == 'utf-8' || $charset == 'utf8') {
            return strlen(utf8_decode($string));
        }
        if (String::extensionExists('mbstring')) {
            $old_error = error_reporting(0);
            $ret = mb_strlen($string, String::_mbstringCharset($charset));
            error_reporting($old_error);
            if (!empty($ret)) {
                return $ret;
            }
        }
        return strlen($string);
    }

    /**
     * Returns the numeric position of the first occurrence of $needle
     * in the $haystack string.
     *
     * @param string $haystack  The string to search through.
     * @param string $needle    The string to search for.
     * @param integer $offset   Allows to specify which character in haystack
     *                          to start searching.
     * @param string $charset   The charset to use when searching for the
     *                          $needle string.
     *
     * @return integer  The position of first occurrence.
     */
    function pos($haystack, $needle, $offset = 0, $charset = null)
    {
        if (String::extensionExists('mbstring')) {
            if (is_null($charset)) {
                $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
            }
            $track_errors = ini_set('track_errors', 1);
            $old_error = error_reporting(0);
            $ret = mb_strpos($haystack, $needle, $offset, String::_mbstringCharset($charset));
            error_reporting($old_error);
            ini_set('track_errors', $track_errors);
            if (!isset($php_errormsg)) {
                return $ret;
            }
        }
        return strpos($haystack, $needle, $offset);
    }

    /**
     * Returns a string padded to a certain length with another string.
     *
     * This method behaves exactly like str_pad but is multibyte safe.
     *
     * @param string $input    The string to be padded.
     * @param integer $length  The length of the resulting string.
     * @param string $pad      The string to pad the input string with. Must
     *                         be in the same charset like the input string.
     * @param const $type      The padding type. One of STR_PAD_LEFT,
     *                         STR_PAD_RIGHT, or STR_PAD_BOTH.
     * @param string $charset  The charset of the input and the padding
     *                         strings.
     *
     * @return string  The padded string.
     */
    function pad($input, $length, $pad = ' ', $type = STR_PAD_RIGHT,
                 $charset = null)
    {
        $mb_length = String::length($input, $charset);
        $sb_length = strlen($input);
        $pad_length = String::length($pad, $charset);

        /* Return if we already have the length. */
        if ($mb_length >= $length) {
            return $input;
        }

        /* Shortcut for single byte strings. */
        if ($mb_length == $sb_length && $pad_length == strlen($pad)) {
            return str_pad($input, $length, $pad, $type);
        }

        switch ($type) {
        case STR_PAD_LEFT:
            $left = $length - $mb_length;
            $output = String::substr(str_repeat($pad, ceil($left / $pad_length)), 0, $left, $charset) . $input;
            break;
        case STR_PAD_BOTH:
            $left = floor(($length - $mb_length) / 2);
            $right = ceil(($length - $mb_length) / 2);
            $output = String::substr(str_repeat($pad, ceil($left / $pad_length)), 0, $left, $charset) .
                $input .
                String::substr(str_repeat($pad, ceil($right / $pad_length)), 0, $right, $charset);
            break;
        case STR_PAD_RIGHT:
            $right = $length - $mb_length;
            $output = $input . String::substr(str_repeat($pad, ceil($right / $pad_length)), 0, $right, $charset);
            break;
        }

        return $output;
    }

    /**
     * Wraps the text of a message.
     *
     * @since Horde 3.2
     *
     * @param string $string         String containing the text to wrap.
     * @param integer $width         Wrap the string at this number of
     *                               characters.
     * @param string $break          Character(s) to use when breaking lines.
     * @param boolean $cut           Whether to cut inside words if a line
     *                               can't be wrapped.
     * @param string $charset        Character set to use when breaking lines.
     * @param boolean $line_folding  Whether to apply line folding rules per
     *                               RFC 822 or similar. The correct break
     *                               characters including leading whitespace
     *                               have to be specified too.
     *
     * @return string  String containing the wrapped text.
     */
    function wordwrap($string, $width = 75, $break = "\n", $cut = false,
                      $charset = null, $line_folding = false)
    {
        /* Get the user's default character set if none passed in. */
        if (is_null($charset)) {
            $charset = $GLOBALS['_HORDE_STRING_CHARSET'];
        }
        $charset = String::_mbstringCharset($charset);
        $string = String::convertCharset($string, $charset, 'utf-8');
        $wrapped = '';

        while (String::length($string, 'utf-8') > $width) {
            $line = String::substr($string, 0, $width, 'utf-8');
            $string = String::substr($string, String::length($line, 'utf-8'), null, 'utf-8');
            // Make sure didn't cut a word, unless we want hard breaks anyway.
            if (!$cut && preg_match('/^(.+?)((\s|\r?\n).*)/us', $string, $match)) {
                $line .= $match[1];
                $string = $match[2];
            }
            // Wrap at existing line breaks.
            if (preg_match('/^(.*?)(\r?\n)(.*)$/u', $line, $match)) {
                $wrapped .= $match[1] . $match[2];
                $string = $match[3] . $string;
                continue;
            }
            // Wrap at the last colon or semicolon followed by a whitespace if
            // doing line folding.
            if ($line_folding &&
                preg_match('/^(.*?)(;|:)(\s+.*)$/u', $line, $match)) {
                $wrapped .= $match[1] . $match[2] . $break;
                $string = $match[3] . $string;
                continue;
            }
            // Wrap at the last whitespace of $line.
            if ($line_folding) {
                $sub = '(.+[^\s])';
            } else {
                $sub = '(.*)';
            }
            if (preg_match('/^' . $sub . '(\s+)(.*)$/u', $line, $match)) {
                $wrapped .= $match[1] . $break;
                $string = ($line_folding ? $match[2] : '') . $match[3] . $string;
                continue;
            }
            // Hard wrap if necessary.
            if ($cut) {
                $wrapped .= $line . $break;
                continue;
            }
            $wrapped .= $line;
        }

        return String::convertCharset($wrapped . $string, 'utf-8', $charset);
    }

    /**
     * Wraps the text of a message.
     *
     * @param string $text        String containing the text to wrap.
     * @param integer $length     Wrap $text at this number of characters.
     * @param string $break_char  Character(s) to use when breaking lines.
     * @param string $charset     Character set to use when breaking lines.
     * @param boolean $quote      Ignore lines that are wrapped with the '>'
     *                            character (RFC 2646)? If true, we don't
     *                            remove any padding whitespace at the end of
     *                            the string.
     *
     * @return string  String containing the wrapped text.
     */
    function wrap($text, $length = 80, $break_char = "\n", $charset = null,
                  $quote = false)
    {
        $paragraphs = array();

        foreach (preg_split('/\r?\n/', $text) as $input) {
            if ($quote && (strpos($input, '>') === 0)) {
                $line = $input;
            } else {
                /* We need to handle the Usenet-style signature line
                 * separately; since the space after the two dashes is
                 * REQUIRED, we don't want to trim the line. */
                if ($input != '-- ') {
                    $input = rtrim($input);
                }
                $line = String::wordwrap($input, $length, $break_char, false, $charset);
            }

            $paragraphs[] = $line;
        }

        return implode($break_char, $paragraphs);
    }

    /**
     * Returns true if the every character in the parameter is an alphabetic
     * character.
     *
     * @param $string   The string to test.
     * @param $charset  The charset to use when testing the string.
     *
     * @return boolean  True if the parameter was alphabetic only.
     */
    function isAlpha($string, $charset = null)
    {
        if (!String::extensionExists('mbstring')) {
            return ctype_alpha($string);
        }

        $charset = String::_mbstringCharset($charset);
        $old_charset = mb_regex_encoding();
        $old_error = error_reporting(0);

        if ($charset != $old_charset) {
            mb_regex_encoding($charset);
        }
        $alpha = !mb_ereg_match('[^[:alpha:]]', $string);
        if ($charset != $old_charset) {
            mb_regex_encoding($old_charset);
        }

        error_reporting($old_error);

        return $alpha;
    }

    /**
     * Returns true if ever character in the parameter is a lowercase letter in
     * the current locale.
     *
     * @param $string   The string to test.
     * @param $charset  The charset to use when testing the string.
     *
     * @return boolean  True if the parameter was lowercase.
     */
    function isLower($string, $charset = null)
    {
        return ((String::lower($string, true, $charset) === $string) &&
                String::isAlpha($string, $charset));
    }

    /**
     * Returns true if every character in the parameter is an uppercase letter
     * in the current locale.
     *
     * @param string $string   The string to test.
     * @param string $charset  The charset to use when testing the string.
     *
     * @return boolean  True if the parameter was uppercase.
     */
    function isUpper($string, $charset = null)
    {
        return ((String::upper($string, true, $charset) === $string) &&
                String::isAlpha($string, $charset));
    }

    /**
     * Performs a multibyte safe regex match search on the text provided.
     *
     * @since Horde 3.1
     *
     * @param string $text     The text to search.
     * @param array $regex     The regular expressions to use, without perl
     *                         regex delimiters (e.g. '/' or '|').
     * @param string $charset  The character set of the text.
     *
     * @return array  The matches array from the first regex that matches.
     */
    function regexMatch($text, $regex, $charset = null)
    {
        if (!empty($charset)) {
            $regex = String::convertCharset($regex, $charset, 'utf-8');
            $text = String::convertCharset($text, $charset, 'utf-8');
        }

        $matches = array();
        foreach ($regex as $val) {
            if (preg_match('/' . $val . '/u', $text, $matches)) {
                break;
            }
        }

        if (!empty($charset)) {
            $matches = String::convertCharset($matches, 'utf-8', $charset);
        }

        return $matches;
    }

    /**
     * Workaround charsets that don't work with mbstring functions.
     *
     * @access private
     *
     * @param string $charset  The original charset.
     *
     * @return string  The charset to use with mbstring functions.
     */
    function _mbstringCharset($charset)
    {
        /* mbstring functions do not handle the 'ks_c_5601-1987' &
         * 'ks_c_5601-1989' charsets. However, these charsets are used, for
         * example, by various versions of Outlook to send Korean characters.
         * Use UHC (CP949) encoding instead. See, e.g.,
         * http://lists.w3.org/Archives/Public/ietf-charsets/2001AprJun/0030.html */
        if (in_array(String::lower($charset), array('ks_c_5601-1987', 'ks_c_5601-1989'))) {
            $charset = 'UHC';
        }

        return $charset;
    }

}



/**
 * @package Horde_iCalendar
 */

/**
 * String package
 */



/**
 * Class representing iCalendar files.
 *
 * $Horde: framework/iCalendar/iCalendar.php,v 1.57.4.81 2010-11-10 14:34:25 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar {

    /**
     * The parent (containing) iCalendar object.
     *
     * @var Horde_iCalendar
     */
    var $_container = false;

    /**
     * The name/value pairs of attributes for this object (UID,
     * DTSTART, etc.). Which are present depends on the object and on
     * what kind of component it is.
     *
     * @var array
     */
    var $_attributes = array();

    /**
     * Any children (contained) iCalendar components of this object.
     *
     * @var array
     */
    var $_components = array();

    /**
     * According to RFC 2425, we should always use CRLF-terminated lines.
     *
     * @var string
     */
    var $_newline = "\r\n";

    /**
     * iCalendar format version (different behavior for 1.0 and 2.0
     * especially with recurring events).
     *
     * @var string
     */
    var $_version;

    function Horde_iCalendar($version = '2.0')
    {
        $this->_version = $version;
        $this->setAttribute('VERSION', $version);
    }

    /**
     * Return a reference to a new component.
     *
     * @param string          $type       The type of component to return
     * @param Horde_iCalendar $container  A container that this component
     *                                    will be associated with.
     *
     * @return object  Reference to a Horde_iCalendar_* object as specified.
     *
     * @static
     */
    function &newComponent($type, &$container)
    {
        $type = String::lower($type);
        $class = 'Horde_iCalendar_' . $type;
        if (!class_exists($class)) {
            include 'Horde/iCalendar/' . $type . '.php';
        }
        if (class_exists($class)) {
            $component = new $class();
            if ($container !== false) {
                $component->_container = &$container;
                // Use version of container, not default set by component
                // constructor.
                $component->_version = $container->_version;
            }
        } else {
            // Should return an dummy x-unknown type class here.
            $component = false;
        }

        return $component;
    }

    /**
     * Sets the value of an attribute.
     *
     * @param string $name     The name of the attribute.
     * @param string $value    The value of the attribute.
     * @param array $params    Array containing any addition parameters for
     *                         this attribute.
     * @param boolean $append  True to append the attribute, False to replace
     *                         the first matching attribute found.
     * @param array $values    Array representation of $value.  For
     *                         comma/semicolon seperated lists of values.  If
     *                         not set use $value as single array element.
     */
    function setAttribute($name, $value, $params = array(), $append = true,
                          $values = false)
    {
        // Make sure we update the internal format version if
        // setAttribute('VERSION', ...) is called.
        if ($name == 'VERSION') {
            $this->_version = $value;
            if ($this->_container !== false) {
                $this->_container->_version = $value;
            }
        }

        if (!$values) {
            $values = array($value);
        }
        $found = false;
        if (!$append) {
            foreach (array_keys($this->_attributes) as $key) {
                if ($this->_attributes[$key]['name'] == String::upper($name)) {
                    $this->_attributes[$key]['params'] = $params;
                    $this->_attributes[$key]['value'] = $value;
                    $this->_attributes[$key]['values'] = $values;
                    $found = true;
                    break;
                }
            }
        }

        if ($append || !$found) {
            $this->_attributes[] = array(
                'name'      => String::upper($name),
                'params'    => $params,
                'value'     => $value,
                'values'    => $values
            );
        }
    }

    /**
     * Sets parameter(s) for an (already existing) attribute.  The
     * parameter set is merged into the existing set.
     *
     * @param string $name   The name of the attribute.
     * @param array $params  Array containing any additional parameters for
     *                       this attribute.
     * @return boolean  True on success, false if no attribute $name exists.
     */
    function setParameter($name, $params = array())
    {
        $keys = array_keys($this->_attributes);
        foreach ($keys as $key) {
            if ($this->_attributes[$key]['name'] == $name) {
                $this->_attributes[$key]['params'] =
                    array_merge($this->_attributes[$key]['params'], $params);
                return true;
            }
        }

        return false;
    }

    /**
     * Get the value of an attribute.
     *
     * @param string $name     The name of the attribute.
     * @param boolean $params  Return the parameters for this attribute instead
     *                         of its value.
     *
     * @return mixed (object)  PEAR_Error if the attribute does not exist.
     *               (string)  The value of the attribute.
     *               (array)   The parameters for the attribute or
     *                         multiple values for an attribute.
     */
    function getAttribute($name, $params = false)
    {
        $result = array();
        foreach ($this->_attributes as $attribute) {
            if ($attribute['name'] == $name) {
                if ($params) {
                    $result[] = $attribute['params'];
                } else {
                    $result[] = $attribute['value'];
                }
            }
        }
        if (!count($result)) {
            require_once 'PEAR.php';
            return PEAR::raiseError('Attribute "' . $name . '" Not Found');
        } if (count($result) == 1 && !$params) {
            return $result[0];
        } else {
            return $result;
        }
    }

    /**
     * Gets the values of an attribute as an array.  Multiple values
     * are possible due to:
     *
     *  a) multiplce occurences of 'name'
     *  b) (unsecapd) comma seperated lists.
     *
     * So for a vcard like "KEY:a,b\nKEY:c" getAttributesValues('KEY')
     * will return array('a', 'b', 'c').
     *
     * @param string  $name    The name of the attribute.
     * @return mixed (object)  PEAR_Error if the attribute does not exist.
     *               (array)   Multiple values for an attribute.
     */
    function getAttributeValues($name)
    {
        $result = array();
        foreach ($this->_attributes as $attribute) {
            if ($attribute['name'] == $name) {
                $result = array_merge($attribute['values'], $result);
            }
        }
        if (!count($result)) {
            return PEAR::raiseError('Attribute "' . $name . '" Not Found');
        }
        return $result;
    }

    /**
     * Returns the value of an attribute, or a specified default value
     * if the attribute does not exist.
     *
     * @param string $name    The name of the attribute.
     * @param mixed $default  What to return if the attribute specified by
     *                        $name does not exist.
     *
     * @return mixed (string) The value of $name.
     *               (mixed)  $default if $name does not exist.
     */
    function getAttributeDefault($name, $default = '')
    {
        $value = $this->getAttribute($name);
        return is_a($value, 'PEAR_Error') ? $default : $value;
    }

    /**
     * Remove all occurences of an attribute.
     *
     * @param string $name  The name of the attribute.
     */
    function removeAttribute($name)
    {
        $keys = array_keys($this->_attributes);
        foreach ($keys as $key) {
            if ($this->_attributes[$key]['name'] == $name) {
                unset($this->_attributes[$key]);
            }
        }
    }

    /**
     * Get attributes for all tags or for a given tag.
     *
     * @param string $tag  Return attributes for this tag, or all attributes if
     *                     not given.
     *
     * @return array  An array containing all the attributes and their types.
     */
    function getAllAttributes($tag = false)
    {
        if ($tag === false) {
            return $this->_attributes;
        }
        $result = array();
        foreach ($this->_attributes as $attribute) {
            if ($attribute['name'] == $tag) {
                $result[] = $attribute;
            }
        }
        return $result;
    }

    /**
     * Add a vCalendar component (eg vEvent, vTimezone, etc.).
     *
     * @param Horde_iCalendar $component  Component (subclass) to add.
     */
    function addComponent($component)
    {
        if (is_a($component, 'Horde_iCalendar')) {
            $component->_container = &$this;
            $this->_components[] = &$component;
        }
    }

    /**
     * Retrieve all the components.
     *
     * @return array  Array of Horde_iCalendar objects.
     */
    function getComponents()
    {
        return $this->_components;
    }

    function getType()
    {
        return 'vcalendar';
    }

    /**
     * Return the classes (entry types) we have.
     *
     * @return array  Hash with class names Horde_iCalendar_xxx as keys
     *                and number of components of this class as value.
     */
    function getComponentClasses()
    {
        $r = array();
        foreach ($this->_components as $c) {
            $cn = strtolower(get_class($c));
            if (empty($r[$cn])) {
                $r[$cn] = 1;
            } else {
                $r[$cn]++;
            }
        }

        return $r;
    }

    /**
     * Number of components in this container.
     *
     * @return integer  Number of components in this container.
     */
    function getComponentCount()
    {
        return count($this->_components);
    }

    /**
     * Retrieve a specific component.
     *
     * @param integer $idx  The index of the object to retrieve.
     *
     * @return mixed    (boolean) False if the index does not exist.
     *                  (Horde_iCalendar_*) The requested component.
     */
    function getComponent($idx)
    {
        if (isset($this->_components[$idx])) {
            return $this->_components[$idx];
        } else {
            return false;
        }
    }

    /**
     * Locates the first child component of the specified class, and returns a
     * reference to it.
     *
     * @param string $type  The type of component to find.
     *
     * @return boolean|Horde_iCalendar_*  False if no subcomponent of the
     *                                    specified class exists or a reference
     *                                    to the requested component.
     */
    function &findComponent($childclass)
    {
        $childclass = 'Horde_iCalendar_' . String::lower($childclass);
        $keys = array_keys($this->_components);
        foreach ($keys as $key) {
            if (is_a($this->_components[$key], $childclass)) {
                return $this->_components[$key];
            }
        }

        $component = false;
        return $component;
    }

    /**
     * Locates the first matching child component of the specified class, and
     * returns a reference to it.
     *
     * @param string $childclass  The type of component to find.
     * @param string $attribute   This attribute must be set in the component
     *                            for it to match.
     * @param string $value       Optional value that $attribute must match.
     *
     * @return boolean|Horde_iCalendar_*  False if no matching subcomponent of
     *                                    the specified class exists, or a
     *                                    reference to the requested component.
     */
    function &findComponentByAttribute($childclass, $attribute, $value = null)
    {
        $childclass = 'Horde_iCalendar_' . String::lower($childclass);
        $keys = array_keys($this->_components);
        foreach ($keys as $key) {
            if (is_a($this->_components[$key], $childclass)) {
                $attr = $this->_components[$key]->getAttribute($attribute);
                if (is_a($attr, 'PEAR_Error')) {
                    continue;
                }
                if ($value !== null && $value != $attr) {
                    continue;
                }
                return $this->_components[$key];
            }
        }

        $component = false;
        return $component;
    }

    /**
     * Clears the iCalendar object (resets the components and attributes
     * arrays).
     */
    function clear()
    {
        $this->_components = array();
        $this->_attributes = array();
    }

    /**
     * Checks if entry is vcalendar 1.0, vcard 2.1 or vnote 1.1.
     *
     * These 'old' formats are defined by www.imc.org. The 'new' (non-old)
     * formats icalendar 2.0 and vcard 3.0 are defined in rfc2426 and rfc2445
     * respectively.
     *
     * @since Horde 3.1.2
     */
    function isOldFormat()
    {
        if ($this->getType() == 'vcard') {
            return ($this->_version < 3);
        }
        if ($this->getType() == 'vNote') {
            return ($this->_version < 2);
        }
        if ($this->_version >= 2) {
            return false;
        }
        return true;
    }

    /**
     * Export as vCalendar format.
     */
    function exportvCalendar()
    {
        // Default values.
        $requiredAttributes['PRODID'] = '-//The Horde Project//Horde_iCalendar Library' . (defined('HORDE_VERSION') ? ', Horde ' . constant('HORDE_VERSION') : '') . '//EN';
        $requiredAttributes['METHOD'] = 'PUBLISH';

        foreach ($requiredAttributes as $name => $default_value) {
            if (is_a($this->getattribute($name), 'PEAR_Error')) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VCALENDAR');
    }

    /**
     * Export this entry as a hash array with tag names as keys.
     *
     * @param boolean $paramsInKeys
     *                If false, the operation can be quite lossy as the
     *                parameters are ignored when building the array keys.
     *                So if you export a vcard with
     *                LABEL;TYPE=WORK:foo
     *                LABEL;TYPE=HOME:bar
     *                the resulting hash contains only one label field!
     *                If set to true, array keys look like 'LABEL;TYPE=WORK'
     * @return array  A hash array with tag names as keys.
     */
    function toHash($paramsInKeys = false)
    {
        $hash = array();
        foreach ($this->_attributes as $a)  {
            $k = $a['name'];
            if ($paramsInKeys && is_array($a['params'])) {
                foreach ($a['params'] as $p => $v) {
                    $k .= ";$p=$v";
                }
            }
            $hash[$k] = $a['value'];
        }

        return $hash;
    }

    /**
     * Parses a string containing vCalendar data.
     *
     * @todo This method doesn't work well at all, if $base is VCARD.
     *
     * @param string $text     The data to parse.
     * @param string $base     The type of the base object.
     * @param string $charset  The encoding charset for $text. Defaults to
     *                         utf-8 for new format, iso-8859-1 for old format.
     * @param boolean $clear   If true clears the iCal object before parsing.
     *
     * @return boolean  True on successful import, false otherwise.
     */
    function parsevCalendar($text, $base = 'VCALENDAR', $charset = null,
                            $clear = true)
    {
        if ($clear) {
            $this->clear();
        }
        if (preg_match('/^BEGIN:' . $base . '(.*)^END:' . $base . '/ism', $text, $matches)) {
            $container = true;
            $vCal = $matches[1];
        } else {
            // Text isn't enclosed in BEGIN:VCALENDAR
            // .. END:VCALENDAR. We'll try to parse it anyway.
            $container = false;
            $vCal = $text;
        }
        $vCal = trim($vCal);

        // Extract all subcomponents.
        $matches = $components = null;
        if (preg_match_all('/^BEGIN:(.*)(\r\n|\r|\n)(.*)^END:\1/Uims', $vCal, $components)) {
            foreach ($components[0] as $key => $data) {
                // Remove from the vCalendar data.
                $vCal = str_replace($data, '', $vCal);
            }
        } elseif (!$container) {
            return false;
        }

        // Unfold "quoted printable" folded lines like:
        //  BODY;ENCODING=QUOTED-PRINTABLE:=
        //  another=20line=
        //  last=20line
        while (preg_match_all('/^([^:]+;\s*(ENCODING=)?QUOTED-PRINTABLE(.*=\r?\n)+(.*[^=])?\r?\n)/mU', $vCal, $matches)) {
            foreach ($matches[1] as $s) {
                $r = preg_replace('/=\r?\n/', '', $s);
                $vCal = str_replace($s, $r, $vCal);
            }
        }

        // Unfold any folded lines.
        if ($this->isOldFormat()) {
            $vCal = preg_replace('/[\r\n]+([ \t])/', '$1', $vCal);
        } else {
            $vCal = preg_replace('/[\r\n]+[ \t]/', '', $vCal);
        }

        // Parse the remaining attributes.
        if (preg_match_all('/^((?:[^":]+|(?:"[^"]*")+)*):([^\r\n]*)\r?$/m', $vCal, $matches)) {
            foreach ($matches[0] as $attribute) {
                preg_match('/([^;^:]*)((;(?:[^":]+|(?:"[^"]*")+)*)?):([^\r\n]*)[\r\n]*/', $attribute, $parts);
                $tag = trim(String::upper($parts[1]));
                $value = $parts[4];
                $params = array();

                // Parse parameters.
                if (!empty($parts[2])) {
                    preg_match_all('/;(([^;=]*)(=("[^"]*"|[^;]*))?)/', $parts[2], $param_parts);
                    foreach ($param_parts[2] as $key => $paramName) {
                        $paramName = String::upper($paramName);
                        $paramValue = $param_parts[4][$key];
                        if ($paramName == 'TYPE') {
                            $paramValue = preg_split('/(?<!\\\\),/', $paramValue);
                            if (count($paramValue) == 1) {
                                $paramValue = $paramValue[0];
                            }
                        }
                        if (is_string($paramValue)) {
                            if (preg_match('/"([^"]*)"/', $paramValue, $parts)) {
                                $paramValue = $parts[1];
                            }
                        } else {
                            foreach ($paramValue as $k => $tmp) {
                                if (preg_match('/"([^"]*)"/', $tmp, $parts)) {
                                    $paramValue[$k] = $parts[1];
                                }
                            }
                        }
                        $params[$paramName] = $paramValue;
                    }
                }

                // Charset and encoding handling.
                if ((isset($params['ENCODING']) &&
                     String::upper($params['ENCODING']) == 'QUOTED-PRINTABLE') ||
                    isset($params['QUOTED-PRINTABLE'])) {

                    $value = quoted_printable_decode($value);
                    if (isset($params['CHARSET'])) {
                        $value = String::convertCharset($value, $params['CHARSET']);
                    } else {
                        $value = String::convertCharset($value, empty($charset) ? ($this->isOldFormat() ? 'iso-8859-1' : 'utf-8') : $charset);
                    }
                } elseif (isset($params['CHARSET'])) {
                    $value = String::convertCharset($value, $params['CHARSET']);
                } else {
                    // As per RFC 2279, assume UTF8 if we don't have an
                    // explicit charset parameter.
                    $value = String::convertCharset($value, empty($charset) ? ($this->isOldFormat() ? 'iso-8859-1' : 'utf-8') : $charset);
                }

                // Get timezone info for date fields from $params.
                $tzid = isset($params['TZID']) ? trim($params['TZID'], '\"') : false;

                switch ($tag) {
                // Date fields.
                case 'COMPLETED':
                case 'CREATED':
                case 'LAST-MODIFIED':
                case 'X-MOZ-LASTACK': 
                case 'X-MOZ-SNOOZE-TIME': 
                    $this->setAttribute($tag, $this->_parseDateTime($value, $tzid), $params);
                    break;

                case 'BDAY':
                case 'X-SYNCJE-ANNIVERSARY':
                case 'X-ANNIVERSARY':
                    $this->setAttribute($tag, $this->_parseDate($value), $params);
                    break;

                case 'DTEND':
                case 'DTSTART':
                case 'DTSTAMP':
                case 'DUE':
                case 'AALARM':
                case 'RECURRENCE-ID':
                    // types like AALARM may contain additional data after a ;
                    // ignore these.
                    $ts = explode(';', $value);
                    if (isset($params['VALUE']) && $params['VALUE'] == 'DATE') {
                        $this->setAttribute($tag, $this->_parseDate($ts[0]), $params);
                    } else {
                        $this->setAttribute($tag, $this->_parseDateTime($ts[0], $tzid), $params);
                    }
                    break;

                case 'TRIGGER':
                    if (isset($params['VALUE']) &&
                        $params['VALUE'] == 'DATE-TIME') {
                            $this->setAttribute($tag, $this->_parseDateTime($value, $tzid), $params);
                    } else {
                        $this->setAttribute($tag, $this->_parseDuration($value), $params);
                    }
                    break;

                // Comma seperated dates.
                case 'EXDATE':
                case 'RDATE':
                    if (!strlen($value)) {
                        break;
                    }
                    $dates = array();
                    $separator = $this->isOldFormat() ? ';' : ',';
                    preg_match_all('/' . $separator . '([^' . $separator . ']*)/', $separator . $value, $values);

                    foreach ($values[1] as $value) {
                        $dates[] = $this->_parseDate($value);
                    }
                    $this->setAttribute($tag, isset($dates[0]) ? $dates[0] : null, $params, true, $dates);
                    break;

                // Duration fields.
                case 'DURATION':
                    $this->setAttribute($tag, $this->_parseDuration($value), $params);
                    break;

                // Period of time fields.
                case 'FREEBUSY':
                    $periods = array();
                    preg_match_all('/,([^,]*)/', ',' . $value, $values);
                    foreach ($values[1] as $value) {
                        $periods[] = $this->_parsePeriod($value);
                    }

                    $this->setAttribute($tag, isset($periods[0]) ? $periods[0] : null, $params, true, $periods);
                    break;

                // UTC offset fields.
                case 'TZOFFSETFROM':
                case 'TZOFFSETTO':
                    $this->setAttribute($tag, $this->_parseUtcOffset($value), $params);
                    break;

                // Integer fields.
                case 'PERCENT-COMPLETE':
                case 'PRIORITY':
                case 'REPEAT':
                case 'SEQUENCE':
                    $this->setAttribute($tag, intval($value), $params);
                    break;

                // Geo fields.
                case 'GEO':
                    if ($this->isOldFormat()) {
                        $floats = explode(',', $value);
                        $value = array('latitude' => floatval($floats[1]),
                                       'longitude' => floatval($floats[0]));
                    } else {
                        $floats = explode(';', $value);
                        $value = array('latitude' => floatval($floats[0]),
                                       'longitude' => floatval($floats[1]));
                    }
                    $this->setAttribute($tag, $value, $params);
                    break;

                // Recursion fields.
                case 'EXRULE':
                case 'RRULE':
                    $this->setAttribute($tag, trim($value), $params);
                    break;

                // ADR, ORG and N are lists seperated by unescaped semicolons
                // with a specific number of slots.
                case 'ADR':
                case 'N':
                case 'ORG':
                    $value = trim($value);
                    // As of rfc 2426 2.4.2 semicolon, comma, and colon must
                    // be escaped (comma is unescaped after splitting below).
                    $value = str_replace(array('\\n', '\\N', '\\;', '\\:'),
                                         array($this->_newline, $this->_newline, ';', ':'),
                                         $value);

                    // Split by unescaped semicolons:
                    $values = preg_split('/(?<!\\\\);/', $value);
                    $value = str_replace('\\;', ';', $value);
                    $values = str_replace('\\;', ';', $values);
                    $this->setAttribute($tag, trim($value), $params, true, $values);
                    break;

                // String fields.
                default:
                    if ($this->isOldFormat()) {
                        // vCalendar 1.0 and vCard 2.1 only escape semicolons
                        // and use unescaped semicolons to create lists.
                        $value = trim($value);
                        // Split by unescaped semicolons:
                        $values = preg_split('/(?<!\\\\);/', $value);
                        $value = str_replace('\\;', ';', $value);
                        $values = str_replace('\\;', ';', $values);
                        $this->setAttribute($tag, trim($value), $params, true, $values);
                    } else {
                        $value = trim($value);
                        // As of rfc 2426 2.4.2 semicolon, comma, and colon
                        // must be escaped (comma is unescaped after splitting
                        // below).
                        $value = str_replace(array('\\n', '\\N', '\\;', '\\:', '\\\\'),
                                             array($this->_newline, $this->_newline, ';', ':', '\\'),
                                             $value);

                        // Split by unescaped commas.
                        $values = preg_split('/(?<!\\\\),/', $value);
                        $value = str_replace('\\,', ',', $value);
                        $values = str_replace('\\,', ',', $values);

                        $this->setAttribute($tag, trim($value), $params, true, $values);
                    }
                    break;
                }
            }
        }

        // Process all components.
        if ($components) {
            // vTimezone components are processed first. They are
            // needed to process vEvents that may use a TZID.
            foreach ($components[0] as $key => $data) {
                $type = trim($components[1][$key]);
                if ($type != 'VTIMEZONE') {
                    continue;
                }
                $component = &Horde_iCalendar::newComponent($type, $this);
                if ($component === false) {
                    return PEAR::raiseError("Unable to create object for type $type");
                }
                $component->parsevCalendar($data, $type, $charset);

                $this->addComponent($component);
            }

            // Now process the non-vTimezone components.
            foreach ($components[0] as $key => $data) {
                $type = trim($components[1][$key]);
                if ($type == 'VTIMEZONE') {
                    continue;
                }
                $component = &Horde_iCalendar::newComponent($type, $this);
                if ($component === false) {
                    return PEAR::raiseError("Unable to create object for type $type");
                }
                $component->parsevCalendar($data, $type, $charset);

                $this->addComponent($component);
            }
        }

        return true;
    }

    /**
     * Export this component in vCal format.
     *
     * @param string $base  The type of the base object.
     *
     * @return string  vCal format data.
     */
    function _exportvData($base = 'VCALENDAR')
    {
        $result = 'BEGIN:' . String::upper($base) . $this->_newline;

        // VERSION is not allowed for entries enclosed in VCALENDAR/ICALENDAR,
        // as it is part of the enclosing VCALENDAR/ICALENDAR. See rfc2445
        if ($base !== 'VEVENT' && $base !== 'VTODO' && $base !== 'VALARM' &&
            $base !== 'VJOURNAL' && $base !== 'VFREEBUSY') {
            // Ensure that version is the first attribute.
            $result .= 'VERSION:' . $this->_version . $this->_newline;
        }
        foreach ($this->_attributes as $attribute) {
            $name = $attribute['name'];
            if ($name == 'VERSION') {
                // Already done.
                continue;
            }

            $params_str = '';
            $params = $attribute['params'];
            if ($params) {
                foreach ($params as $param_name => $param_value) {
                    /* Skip CHARSET for iCalendar 2.0 data, not allowed. */
                    if ($param_name == 'CHARSET' && !$this->isOldFormat()) {
                        continue;
                    }
                    /* Skip VALUE=DATE for vCalendar 1.0 data, not allowed. */
                    if ($this->isOldFormat() &&
                        $param_name == 'VALUE' && $param_value == 'DATE') {
                        continue;
                    }

                    if ($param_value === null) {
                        $params_str .= ";$param_name";
                    } else {
                        $len = strlen($param_value);
                        $safe_value = '';
                        $quote = false;
                        for ($i = 0; $i < $len; ++$i) {
                            $ord = ord($param_value[$i]);
                            // Accept only valid characters.
                            if ($ord == 9 || $ord == 32 || $ord == 33 ||
                                ($ord >= 35 && $ord <= 126) ||
                                $ord >= 128) {
                                $safe_value .= $param_value[$i];
                                // Characters above 128 do not need to be
                                // quoted as per RFC2445 but Outlook requires
                                // this.
                                if ($ord == 44 || $ord == 58 || $ord == 59 ||
                                    $ord >= 128) {
                                    $quote = true;
                                }
                            }
                        }
                        if ($quote) {
                            $safe_value = '"' . $safe_value . '"';
                        }
                        $params_str .= ";$param_name=$safe_value";
                    }
                }
            }

            $value = $attribute['value'];
            switch ($name) {
            // Date fields.
            case 'COMPLETED':
            case 'CREATED':
            case 'DCREATED':
            case 'LAST-MODIFIED':
            case 'X-MOZ-LASTACK':
            case 'X-MOZ-SNOOZE-TIME':
                $value = $this->_exportDateTime($value);
                break;

            case 'DTEND':
            case 'DTSTART':
            case 'DTSTAMP':
            case 'DUE':
            case 'AALARM':
            case 'RECURRENCE-ID':
                if (isset($params['VALUE'])) {
                    if ($params['VALUE'] == 'DATE') {
                        // VCALENDAR 1.0 uses T000000 - T235959 for all day events:
                        if ($this->isOldFormat() && $name == 'DTEND') {
                            $d = new Horde_Date($value);
                            $value = new Horde_Date(array(
                                'year' => $d->year,
                                'month' => $d->month,
                                'mday' => $d->mday - 1));
                            $value->correct();
                            $value = $this->_exportDate($value, '235959');
                        } else {
                            $value = $this->_exportDate($value, '000000');
                        }
                    } else {
                        $value = $this->_exportDateTime($value);
                    }
                } else {
                    $value = $this->_exportDateTime($value);
                }
                break;

            // Comma seperated dates.
            case 'EXDATE':
            case 'RDATE':
                $dates = array();
                foreach ($value as $date) {
                    if (isset($params['VALUE'])) {
                        if ($params['VALUE'] == 'DATE') {
                            $dates[] = $this->_exportDate($date, '000000');
                        } elseif ($params['VALUE'] == 'PERIOD') {
                            $dates[] = $this->_exportPeriod($date);
                        } else {
                            $dates[] = $this->_exportDateTime($date);
                        }
                    } else {
                        $dates[] = $this->_exportDateTime($date);
                    }
                }
                $value = implode($this->isOldFormat() ? ';' : ',', $dates);
                break;

            case 'TRIGGER':
                if (isset($params['VALUE'])) {
                    if ($params['VALUE'] == 'DATE-TIME') {
                        $value = $this->_exportDateTime($value);
                    } elseif ($params['VALUE'] == 'DURATION') {
                        $value = $this->_exportDuration($value);
                    }
                } else {
                    $value = $this->_exportDuration($value);
                }
                break;

            // Duration fields.
            case 'DURATION':
                $value = $this->_exportDuration($value);
                break;

            // Period of time fields.
            case 'FREEBUSY':
                $value_str = '';
                foreach ($value as $period) {
                    $value_str .= empty($value_str) ? '' : ',';
                    $value_str .= $this->_exportPeriod($period);
                }
                $value = $value_str;
                break;

            // UTC offset fields.
            case 'TZOFFSETFROM':
            case 'TZOFFSETTO':
                $value = $this->_exportUtcOffset($value);
                break;

            // Integer fields.
            case 'PERCENT-COMPLETE':
            case 'PRIORITY':
            case 'REPEAT':
            case 'SEQUENCE':
                $value = "$value";
                break;

            // Geo fields.
            case 'GEO':
                if ($this->isOldFormat()) {
                    $value = $value['longitude'] . ',' . $value['latitude'];
                } else {
                    $value = $value['latitude'] . ';' . $value['longitude'];
                }
                break;

            // Recurrence fields.
            case 'EXRULE':
            case 'RRULE':
                break;

            default:
                if ($this->isOldFormat()) {
                    if (is_array($attribute['values']) &&
                        count($attribute['values']) > 1) {
                        $values = $attribute['values'];
                        if ($name == 'N' || $name == 'ADR' || $name == 'ORG') {
                            $glue = ';';
                        } else {
                            $glue = ',';
                        }
                        $values = str_replace(';', '\\;', $values);
                        $value = implode($glue, $values);
                    } else {
                        /* vcard 2.1 and vcalendar 1.0 escape only
                         * semicolons */
                        $value = str_replace(';', '\\;', $value);
                    }
                    // Text containing newlines or ASCII >= 127 must be BASE64
                    // or QUOTED-PRINTABLE encoded. Currently we use
                    // QUOTED-PRINTABLE as default.
                    if (preg_match("/[^\x20-\x7F]/", $value) &&
                        empty($params['ENCODING']))  {
                        $params['ENCODING'] = 'QUOTED-PRINTABLE';
                        $params_str .= ';ENCODING=QUOTED-PRINTABLE';
                        // Add CHARSET as well. At least the synthesis client
                        // gets confused otherwise
                        if (empty($params['CHARSET'])) {
                            $params['CHARSET'] = 'UTF-8';
                            $params_str .= ';CHARSET=' . $params['CHARSET'];
                        }
                    }
                } else {
                    if (is_array($attribute['values']) &&
                        count($attribute['values'])) {
                        $values = $attribute['values'];
                        if ($name == 'N' || $name == 'ADR' || $name == 'ORG') {
                            $glue = ';';
                        } else {
                            $glue = ',';
                        }
                        // As of rfc 2426 2.5 semicolon and comma must be
                        // escaped.
                        $values = str_replace(array('\\', ';', ','),
                                              array('\\\\', '\\;', '\\,'),
                                              $values);
                        $value = implode($glue, $values);
                    } else {
                        // As of rfc 2426 2.5 semicolon and comma must be
                        // escaped.
                        $value = str_replace(array('\\', ';', ','),
                                             array('\\\\', '\\;', '\\,'),
                                             $value);
                    }
                    $value = preg_replace('/\r?\n/', '\n', $value);
                }
                break;
            }

            $value = str_replace("\r", '', $value);
            if (!empty($params['ENCODING']) &&
                $params['ENCODING'] == 'QUOTED-PRINTABLE' &&
                strlen(trim($value))) {
                $result .= $name . $params_str . ':'
                    . str_replace('=0A', '=0D=0A',
                                  $this->_quotedPrintableEncode($value))
                    . $this->_newline;
            } else {
                $attr_string = $name . $params_str . ':' . $value;
                if (!$this->isOldFormat()) {
                    $attr_string = String::wordwrap($attr_string, 75, $this->_newline . ' ',
                                                    true, 'utf-8', true);
                }
                $result .= $attr_string . $this->_newline;
            }
        }

        foreach ($this->_components as $component) {
            $result .= $component->exportvCalendar();
        }

        return $result . 'END:' . $base . $this->_newline;
    }

    /**
     * Parse a UTC Offset field.
     */
    function _parseUtcOffset($text)
    {
        $offset = array();
        if (preg_match('/(\+|-)([0-9]{2})([0-9]{2})([0-9]{2})?/', $text, $timeParts)) {
            $offset['ahead']  = (bool)($timeParts[1] == '+');
            $offset['hour']   = intval($timeParts[2]);
            $offset['minute'] = intval($timeParts[3]);
            if (isset($timeParts[4])) {
                $offset['second'] = intval($timeParts[4]);
            }
            return $offset;
        } else {
            return false;
        }
    }

    /**
     * Export a UTC Offset field.
     */
    function _exportUtcOffset($value)
    {
        $offset = $value['ahead'] ? '+' : '-';
        $offset .= sprintf('%02d%02d',
                           $value['hour'], $value['minute']);
        if (isset($value['second'])) {
            $offset .= sprintf('%02d', $value['second']);
        }

        return $offset;
    }

    /**
     * Parse a Time Period field.
     */
    function _parsePeriod($text)
    {
        $periodParts = explode('/', $text);

        $start = $this->_parseDateTime($periodParts[0]);

        if ($duration = $this->_parseDuration($periodParts[1])) {
            return array('start' => $start, 'duration' => $duration);
        } elseif ($end = $this->_parseDateTime($periodParts[1])) {
            return array('start' => $start, 'end' => $end);
        }
    }

    /**
     * Export a Time Period field.
     */
    function _exportPeriod($value)
    {
        $period = $this->_exportDateTime($value['start']);
        $period .= '/';
        if (isset($value['duration'])) {
            $period .= $this->_exportDuration($value['duration']);
        } else {
            $period .= $this->_exportDateTime($value['end']);
        }
        return $period;
    }

    /**
     * Grok the TZID and return an offset in seconds from UTC for this
     * date and time.
     */
    function _parseTZID($date, $time, $tzid)
    {
        $vtimezone = $this->_container->findComponentByAttribute('vtimezone', 'TZID', $tzid);
        if (!$vtimezone) {
            // use PHP's standard timezone db to determine tzoffset
            try {
                $tz = new DateTimeZone($tzid);
                $dt = new DateTime('now', $tz);
                $dt->setDate($date['year'], $date['month'], $date['mday']);
                $dt->setTime($time['hour'], $time['minute'], $date['recond']);
                return $tz->getOffset($dt);
            }
            catch (Exception $e) {
                return false;
            }
        }

        $change_times = array();
        foreach ($vtimezone->getComponents() as $o) {
            $t = $vtimezone->parseChild($o, $date['year']);
            if ($t !== false) {
                $change_times[] = $t;
            }
        }

        if (!$change_times) {
            return false;
        }

        sort($change_times);

        // Time is arbitrarily based on UTC for comparison.
        $t = @gmmktime($time['hour'], $time['minute'], $time['second'],
                       $date['month'], $date['mday'], $date['year']);

        if ($t < $change_times[0]['time']) {
            return $change_times[0]['from'];
        }

        for ($i = 0, $n = count($change_times); $i < $n - 1; $i++) {
            if (($t >= $change_times[$i]['time']) &&
                ($t < $change_times[$i + 1]['time'])) {
                return $change_times[$i]['to'];
            }
        }

        if ($t >= $change_times[$n - 1]['time']) {
            return $change_times[$n - 1]['to'];
        }

        return false;
    }

    /**
     * Parses a DateTime field and returns a unix timestamp. If the
     * field cannot be parsed then the original text is returned
     * unmodified.
     *
     * @todo This function should be moved to Horde_Date and made public.
     */
    function _parseDateTime($text, $tzid = false)
    {
        $dateParts = explode('T', $text);
        if (count($dateParts) != 2 && !empty($text)) {
            // Not a datetime field but may be just a date field.
            if (!preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', $text, $match)) {
                // Or not
                return $text;
            }
            $newtext = $text.'T000000';
            $dateParts = explode('T', $newtext);
        }

        if (!$date = Horde_iCalendar::_parseDate($dateParts[0])) {
            return $text;
        }
        if (!$time = Horde_iCalendar::_parseTime($dateParts[1])) {
            return $text;
        }

        // Get timezone info for date fields from $tzid and container.
        $tzoffset = ($time['zone'] == 'Local' && $tzid && is_a($this->_container, 'Horde_iCalendar'))
            ? $this->_parseTZID($date, $time, $tzid) : false;
        if ($time['zone'] == 'UTC' || $tzoffset !== false) {
            $result = @gmmktime($time['hour'], $time['minute'], $time['second'],
                                $date['month'], $date['mday'], $date['year']);
            if ($tzoffset) {
                $result -= $tzoffset;
            }
        } else {
            // We don't know the timezone so assume local timezone.
            // FIXME: shouldn't this be based on the user's timezone
            // preference rather than the server's timezone?
            $result = @mktime($time['hour'], $time['minute'], $time['second'],
                              $date['month'], $date['mday'], $date['year']);
        }

        return ($result !== false) ? $result : $text;
    }

    /**
     * Export a DateTime field.
     */
    function _exportDateTime($value)
    {
        $temp = array();
        if (!is_object($value) && !is_array($value)) {
            $tz = date('O', $value);
            $TZOffset = (3600 * substr($tz, 0, 3)) + (60 * substr($tz, 3, 2));
            $value -= $TZOffset;

            $temp['zone']   = 'UTC';
            list($temp['year'], $temp['month'], $temp['mday'], $temp['hour'], $temp['minute'], $temp['second']) = explode('-', date('Y-n-j-G-i-s', $value));
        } else {
            $dateOb = new Horde_Date($value);
            return Horde_iCalendar::_exportDateTime($dateOb->timestamp());
        }

        return Horde_iCalendar::_exportDate($temp) . 'T' . Horde_iCalendar::_exportTime($temp);
    }

    /**
     * Parses a Time field.
     *
     * @static
     */
    function _parseTime($text)
    {
        if (preg_match('/([0-9]{2})([0-9]{2})([0-9]{2})(Z)?/', $text, $timeParts)) {
            $time['hour'] = intval($timeParts[1]);
            $time['minute'] = intval($timeParts[2]);
            $time['second'] = intval($timeParts[3]);
            if (isset($timeParts[4])) {
                $time['zone'] = 'UTC';
            } else {
                $time['zone'] = 'Local';
            }
            return $time;
        } else {
            return false;
        }
    }

    /**
     * Exports a Time field.
     */
    function _exportTime($value)
    {
        $time = sprintf('%02d%02d%02d',
                        $value['hour'], $value['minute'], $value['second']);
        if ($value['zone'] == 'UTC') {
            $time .= 'Z';
        }
        return $time;
    }

    /**
     * Parses a Date field.
     *
     * @static
     */
    function _parseDate($text)
    {
        $parts = explode('T', $text);
        if (count($parts) == 2) {
            $text = $parts[0];
        }

        if (!preg_match('/^(\d{4})-?(\d{2})-?(\d{2})$/', $text, $match)) {
            return false;
        }

        return array('year' => $match[1],
                     'month' => $match[2],
                     'mday' => $match[3]);
    }

    /**
     * Exports a date field.
     *
     * @param object|array $value  Date object or hash.
     * @param string $autoconvert  If set, use this as time part to export the
     *                             date as datetime when exporting to Vcalendar
     *                             1.0. Examples: '000000' or '235959'
     */
    function _exportDate($value, $autoconvert = false)
    {
        if (is_object($value)) {
            $value = array('year' => $value->year, 'month' => $value->month, 'mday' => $value->mday);
        }
        if ($autoconvert !== false && $this->isOldFormat()) {
            return sprintf('%04d%02d%02dT%s', $value['year'], $value['month'], $value['mday'], $autoconvert);
        } else {
            return sprintf('%04d%02d%02d', $value['year'], $value['month'], $value['mday']);
        }
    }

    /**
     * Parse a Duration Value field.
     */
    function _parseDuration($text)
    {
        if (preg_match('/([+]?|[-])P(([0-9]+W)|([0-9]+D)|)(T(([0-9]+H)|([0-9]+M)|([0-9]+S))+)?/', trim($text), $durvalue)) {
            // Weeks.
            $duration = 7 * 86400 * intval($durvalue[3]);

            if (count($durvalue) > 4) {
                // Days.
                $duration += 86400 * intval($durvalue[4]);
            }
            if (count($durvalue) > 5) {
                // Hours.
                $duration += 3600 * intval($durvalue[7]);

                // Mins.
                if (isset($durvalue[8])) {
                    $duration += 60 * intval($durvalue[8]);
                }

                // Secs.
                if (isset($durvalue[9])) {
                    $duration += intval($durvalue[9]);
                }
            }

            // Sign.
            if ($durvalue[1] == "-") {
                $duration *= -1;
            }

            return $duration;
        } else {
            return false;
        }
    }

    /**
     * Export a duration value.
     */
    function _exportDuration($value)
    {
        $duration = '';
        if ($value < 0) {
            $value *= -1;
            $duration .= '-';
        }
        $duration .= 'P';

        $weeks = floor($value / (7 * 86400));
        $value = $value % (7 * 86400);
        if ($weeks) {
            $duration .= $weeks . 'W';
        }

        $days = floor($value / (86400));
        $value = $value % (86400);
        if ($days) {
            $duration .= $days . 'D';
        }

        if ($value) {
            $duration .= 'T';

            $hours = floor($value / 3600);
            $value = $value % 3600;
            if ($hours) {
                $duration .= $hours . 'H';
            }

            $mins = floor($value / 60);
            $value = $value % 60;
            if ($mins) {
                $duration .= $mins . 'M';
            }

            if ($value) {
                $duration .= $value . 'S';
            }
        }

        return $duration;
    }

    /**
     * Converts an 8bit string to a quoted-printable string according to RFC
     * 2045, section 6.7.
     *
     * imap_8bit() does not apply all necessary rules.
     *
     * @param string $input  The string to be encoded.
     *
     * @return string  The quoted-printable encoded string.
     */
    function _quotedPrintableEncode($input = '')
    {
        $output = $line = '';
        $len = strlen($input);

        for ($i = 0; $i < $len; ++$i) {
            $ord = ord($input[$i]);
            // Encode non-printable characters (rule 2).
            if ($ord == 9 ||
                ($ord >= 32 && $ord <= 60) ||
                ($ord >= 62 && $ord <= 126)) {
                $chunk = $input[$i];
            } else {
                // Quoted printable encoding (rule 1).
                $chunk = '=' . String::upper(sprintf('%02X', $ord));
            }
            $line .= $chunk;
            // Wrap long lines (rule 5)
            if (strlen($line) + 1 > 76) {
                $line = String::wordwrap($line, 75, "=\r\n", true, 'us-ascii', true);
                $newline = strrchr($line, "\r\n");
                if ($newline !== false) {
                    $output .= substr($line, 0, -strlen($newline) + 2);
                    $line = substr($newline, 2);
                } else {
                    $output .= $line;
                }
                continue;
            }
            // Wrap at line breaks for better readability (rule 4).
            if (substr($line, -3) == '=0A') {
                $output .= $line . "=\r\n";
                $line = '';
            }
        }
        $output .= $line;

        // Trailing whitespace must be encoded (rule 3).
        $lastpos = strlen($output) - 1;
        if ($output[$lastpos] == chr(9) ||
            $output[$lastpos] == chr(32)) {
            $output[$lastpos] = '=';
            $output .= String::upper(sprintf('%02X', ord($output[$lastpos])));
        }

        return $output;
    }

}



/**
 * Class representing vAlarms.
 *
 * $Horde: framework/iCalendar/iCalendar/valarm.php,v 1.8.10.9 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar_valarm extends Horde_iCalendar {

    function getType()
    {
        return 'vAlarm';
    }

    function exportvCalendar()
    {
        return parent::_exportvData('VALARM');
    }

}

/**
 * Class representing vEvents.
 *
 * $Horde: framework/iCalendar/iCalendar/vevent.php,v 1.31.10.16 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vevent extends Horde_iCalendar {

    function getType()
    {
        return 'vEvent';
    }

    function exportvCalendar()
    {
        // Default values.
        $requiredAttributes = array();
        $requiredAttributes['DTSTAMP'] = time();
        $requiredAttributes['UID'] = $this->_exportDateTime(time())
            . substr(str_pad(base_convert(microtime(), 10, 36), 16, uniqid(mt_rand()), STR_PAD_LEFT), -16)
            . '@' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

        $method = !empty($this->_container) ?
            $this->_container->getAttribute('METHOD') : 'PUBLISH';

        switch ($method) {
        case 'PUBLISH':
            $requiredAttributes['DTSTART'] = time();
            $requiredAttributes['SUMMARY'] = '';
            break;

        case 'REQUEST':
            $requiredAttributes['ATTENDEE'] = '';
            $requiredAttributes['DTSTART'] = time();
            $requiredAttributes['SUMMARY'] = '';
            break;

        case 'REPLY':
            $requiredAttributes['ATTENDEE'] = '';
            break;

        case 'ADD':
            $requiredAttributes['DTSTART'] = time();
            $requiredAttributes['SEQUENCE'] = 1;
            $requiredAttributes['SUMMARY'] = '';
            break;

        case 'CANCEL':
            $requiredAttributes['ATTENDEE'] = '';
            $requiredAttributes['SEQUENCE'] = 1;
            break;

        case 'REFRESH':
            $requiredAttributes['ATTENDEE'] = '';
            break;
        }

        foreach ($requiredAttributes as $name => $default_value) {
            if (is_a($this->getAttribute($name), 'PEAR_Error')) {
                $this->setAttribute($name, $default_value);
            }
        }

        return parent::_exportvData('VEVENT');
    }

    /**
     * Update the status of an attendee of an event.
     *
     * @param $email    The email address of the attendee.
     * @param $status   The participant status to set.
     * @param $fullname The full name of the participant to set.
     */
    function updateAttendee($email, $status, $fullname = '')
    {
        foreach ($this->_attributes as $key => $attribute) {
            if ($attribute['name'] == 'ATTENDEE' &&
                $attribute['value'] == 'mailto:' . $email) {
                $this->_attributes[$key]['params']['PARTSTAT'] = $status;
                if (!empty($fullname)) {
                    $this->_attributes[$key]['params']['CN'] = $fullname;
                }
                unset($this->_attributes[$key]['params']['RSVP']);
                return;
            }
        }
        $params = array('PARTSTAT' => $status);
        if (!empty($fullname)) {
            $params['CN'] = $fullname;
        }
        $this->setAttribute('ATTENDEE', 'mailto:' . $email, $params);
    }

    /**
     * Return the organizer display name or email.
     *
     * @return string  The organizer name to display for this event.
     */
    function organizerName()
    {
        $organizer = $this->getAttribute('ORGANIZER', true);
        if (is_a($organizer, 'PEAR_Error')) {
            return _("An unknown person");
        }

        if (isset($organizer[0]['CN'])) {
            return $organizer[0]['CN'];
        }

        $organizer = parse_url($this->getAttribute('ORGANIZER'));

        return $organizer['path'];
    }

    /**
     * Update this event with details from another event.
     *
     * @param Horde_iCalendar_vEvent $vevent  The vEvent with latest details.
     */
    function updateFromvEvent($vevent)
    {
        $newAttributes = $vevent->getAllAttributes();
        foreach ($newAttributes as $newAttribute) {
            $currentValue = $this->getAttribute($newAttribute['name']);
            if (is_a($currentValue, 'PEAR_error')) {
                // Already exists so just add it.
                $this->setAttribute($newAttribute['name'],
                                    $newAttribute['value'],
                                    $newAttribute['params']);
            } else {
                // Already exists so locate and modify.
                $found = false;

                // Try matching the attribte name and value incase
                // only the params changed (eg attendee updating
                // status).
                foreach ($this->_attributes as $id => $attr) {
                    if ($attr['name'] == $newAttribute['name'] &&
                        $attr['value'] == $newAttribute['value']) {
                        // merge the params
                        foreach ($newAttribute['params'] as $param_id => $param_name) {
                            $this->_attributes[$id]['params'][$param_id] = $param_name;
                        }
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // Else match the first attribute with the same
                    // name (eg changing start time).
                    foreach ($this->_attributes as $id => $attr) {
                        if ($attr['name'] == $newAttribute['name']) {
                            $this->_attributes[$id]['value'] = $newAttribute['value'];
                            // Merge the params.
                            foreach ($newAttribute['params'] as $param_id => $param_name) {
                                $this->_attributes[$id]['params'][$param_id] = $param_name;
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Update just the attendess of event with details from another
     * event.
     *
     * @param Horde_iCalendar_vEvent $vevent  The vEvent with latest details
     */
    function updateAttendeesFromvEvent($vevent)
    {
        $newAttributes = $vevent->getAllAttributes();
        foreach ($newAttributes as $newAttribute) {
            if ($newAttribute['name'] != 'ATTENDEE') {
                continue;
            }
            $currentValue = $this->getAttribute($newAttribute['name']);
            if (is_a($currentValue, 'PEAR_error')) {
                // Already exists so just add it.
                $this->setAttribute($newAttribute['name'],
                                    $newAttribute['value'],
                                    $newAttribute['params']);
            } else {
                // Already exists so locate and modify.
                $found = false;
                // Try matching the attribte name and value incase
                // only the params changed (eg attendee updating
                // status).
                foreach ($this->_attributes as $id => $attr) {
                    if ($attr['name'] == $newAttribute['name'] &&
                        $attr['value'] == $newAttribute['value']) {
                        // Merge the params.
                        foreach ($newAttribute['params'] as $param_id => $param_name) {
                            $this->_attributes[$id]['params'][$param_id] = $param_name;
                        }
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    // Else match the first attribute with the same
                    // name (eg changing start time).
                    foreach ($this->_attributes as $id => $attr) {
                        if ($attr['name'] == $newAttribute['name']) {
                            $this->_attributes[$id]['value'] = $newAttribute['value'];
                            // Merge the params.
                            foreach ($newAttribute['params'] as $param_id => $param_name) {
                                $this->_attributes[$id]['params'][$param_id] = $param_name;
                            }
                            break;
                        }
                    }
                }
            }
        }
    }

}

/**
 * Class representing vFreebusy components.
 *
 * $Horde: framework/iCalendar/iCalendar/vfreebusy.php,v 1.16.10.18 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @todo Don't use timestamps
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vfreebusy extends Horde_iCalendar {

    var $_busyPeriods = array();
    var $_extraParams = array();

    /**
     * Returns the type of this calendar component.
     *
     * @return string  The type of this component.
     */
    function getType()
    {
        return 'vFreebusy';
    }

    /**
     * Parses a string containing vFreebusy data.
     *
     * @param string $data     The data to parse.
     */
    function parsevCalendar($data, $type = null, $charset = null)
    {
        parent::parsevCalendar($data, 'VFREEBUSY', $charset);

        // Do something with all the busy periods.
        foreach ($this->_attributes as $key => $attribute) {
            if ($attribute['name'] != 'FREEBUSY') {
                continue;
            }
            foreach ($attribute['values'] as $value) {
                $params = isset($attribute['params'])
                    ? $attribute['params']
                    : array();
                if (isset($value['duration'])) {
                    $this->addBusyPeriod('BUSY', $value['start'], null,
                                         $value['duration'], $params);
                } else {
                    $this->addBusyPeriod('BUSY', $value['start'],
                                         $value['end'], null, $params);
                }
            }
            unset($this->_attributes[$key]);
        }
    }

    /**
     * Returns the component exported as string.
     *
     * @return string  The exported vFreeBusy information according to the
     *                 iCalender format specification.
     */
    function exportvCalendar()
    {
        foreach ($this->_busyPeriods as $start => $end) {
            $periods = array(array('start' => $start, 'end' => $end));
            $this->setAttribute('FREEBUSY', $periods,
                                isset($this->_extraParams[$start])
                                ? $this->_extraParams[$start] : array());
        }

        $res = parent::_exportvData('VFREEBUSY');

        foreach ($this->_attributes as $key => $attribute) {
            if ($attribute['name'] == 'FREEBUSY') {
                unset($this->_attributes[$key]);
            }
        }

        return $res;
    }

    /**
     * Returns a display name for this object.
     *
     * @return string  A clear text name for displaying this object.
     */
    function getName()
    {
        $name = '';
        $method = !empty($this->_container) ?
            $this->_container->getAttribute('METHOD') : 'PUBLISH';

        if (is_a($method, 'PEAR_Error') || $method == 'PUBLISH') {
            $attr = 'ORGANIZER';
        } elseif ($method == 'REPLY') {
            $attr = 'ATTENDEE';
        }

        $name = $this->getAttribute($attr, true);
        if (!is_a($name, 'PEAR_Error') && isset($name[0]['CN'])) {
            return $name[0]['CN'];
        }

        $name = $this->getAttribute($attr);
        if (is_a($name, 'PEAR_Error')) {
            return '';
        } else {
            $name = parse_url($name);
            return $name['path'];
        }
    }

    /**
     * Returns the email address for this object.
     *
     * @return string  The email address of this object's owner.
     */
    function getEmail()
    {
        $name = '';
        $method = !empty($this->_container)
                  ? $this->_container->getAttribute('METHOD') : 'PUBLISH';

        if (is_a($method, 'PEAR_Error') || $method == 'PUBLISH') {
            $attr = 'ORGANIZER';
        } elseif ($method == 'REPLY') {
            $attr = 'ATTENDEE';
        }

        $name = $this->getAttribute($attr);
        if (is_a($name, 'PEAR_Error')) {
            return '';
        } else {
            $name = parse_url($name);
            return $name['path'];
        }
    }

    /**
     * Returns the busy periods.
     *
     * @return array  All busy periods.
     */
    function getBusyPeriods()
    {
        return $this->_busyPeriods;
    }

    /**
     * Returns any additional freebusy parameters.
     *
     * @return array  Additional parameters of the freebusy periods.
     */
    function getExtraParams()
    {
        return $this->_extraParams;
    }

    /**
     * Returns all the free periods of time in a given period.
     *
     * @param integer $startStamp  The start timestamp.
     * @param integer $endStamp    The end timestamp.
     *
     * @return array  A hash with free time periods, the start times as the
     *                keys and the end times as the values.
     */
    function getFreePeriods($startStamp, $endStamp)
    {
        $this->simplify();
        $periods = array();

        // Check that we have data for some part of this period.
        if ($this->getEnd() < $startStamp || $this->getStart() > $endStamp) {
            return $periods;
        }

        // Locate the first time in the requested period we have data for.
        $nextstart = max($startStamp, $this->getStart());

        // Check each busy period and add free periods in between.
        foreach ($this->_busyPeriods as $start => $end) {
            if ($start <= $endStamp && $end >= $nextstart) {
                if ($nextstart <= $start) {
                    $periods[$nextstart] = min($start, $endStamp);
                }
                $nextstart = min($end, $endStamp);
            }
        }

        // If we didn't read the end of the requested period but still have
        // data then mark as free to the end of the period or available data.
        if ($nextstart < $endStamp && $nextstart < $this->getEnd()) {
            $periods[$nextstart] = min($this->getEnd(), $endStamp);
        }

        return $periods;
    }

    /**
     * Adds a busy period to the info.
     *
     * This function may throw away data in case you add a period with a start
     * date that already exists. The longer of the two periods will be chosen
     * (and all information associated with the shorter one will be removed).
     *
     * @param string $type       The type of the period. Either 'FREE' or
     *                           'BUSY'; only 'BUSY' supported at the moment.
     * @param integer $start     The start timestamp of the period.
     * @param integer $end       The end timestamp of the period.
     * @param integer $duration  The duration of the period. If specified, the
     *                           $end parameter will be ignored.
     * @param array   $extra     Additional parameters for this busy period.
     */
    function addBusyPeriod($type, $start, $end = null, $duration = null,
                           $extra = array())
    {
        if ($type == 'FREE') {
            // Make sure this period is not marked as busy.
            return false;
        }

        // Calculate the end time if duration was specified.
        $tempEnd = is_null($duration) ? $end : $start + $duration;

        // Make sure the period length is always positive.
        $end = max($start, $tempEnd);
        $start = min($start, $tempEnd);

        if (isset($this->_busyPeriods[$start])) {
            // Already a period starting at this time. Change the current
            // period only if the new one is longer. This might be a problem
            // if the callee assumes that there is no simplification going
            // on. But since the periods are stored using the start time of
            // the busy periods we have to throw away data here.
            if ($end > $this->_busyPeriods[$start]) {
                $this->_busyPeriods[$start] = $end;
                $this->_extraParams[$start] = $extra;
            }
        } else {
            // Add a new busy period.
            $this->_busyPeriods[$start] = $end;
            $this->_extraParams[$start] = $extra;
        }

        return true;
    }

    /**
     * Returns the timestamp of the start of the time period this free busy
     * information covers.
     *
     * @return integer  A timestamp.
     */
    function getStart()
    {
        if (!is_a($this->getAttribute('DTSTART'), 'PEAR_Error')) {
            return $this->getAttribute('DTSTART');
        } elseif (count($this->_busyPeriods)) {
            return min(array_keys($this->_busyPeriods));
        } else {
            return false;
        }
    }

    /**
     * Returns the timestamp of the end of the time period this free busy
     * information covers.
     *
     * @return integer  A timestamp.
     */
    function getEnd()
    {
        if (!is_a($this->getAttribute('DTEND'), 'PEAR_Error')) {
            return $this->getAttribute('DTEND');
        } elseif (count($this->_busyPeriods)) {
            return max(array_values($this->_busyPeriods));
        } else {
            return false;
        }
    }

    /**
     * Merges the busy periods of another Horde_iCalendar_vfreebusy object
     * into this one.
     *
     * This might lead to simplification no matter what you specify for the
     * "simplify" flag since periods with the same start date will lead to the
     * shorter period being removed (see addBusyPeriod).
     *
     * @param Horde_iCalendar_vfreebusy $freebusy  A freebusy object.
     * @param boolean $simplify                    If true, simplify() will
     *                                             called after the merge.
     */
    function merge($freebusy, $simplify = true)
    {
        if (!is_a($freebusy, 'Horde_iCalendar_vfreebusy')) {
            return false;
        }

        $extra = $freebusy->getExtraParams();
        foreach ($freebusy->getBusyPeriods() as $start => $end) {
            // This might simplify the busy periods without taking the
            // "simplify" flag into account.
            $this->addBusyPeriod('BUSY', $start, $end, null,
                                 isset($extra[$start])
                                 ? $extra[$start] : array());
        }

        $thisattr = $this->getAttribute('DTSTART');
        $thatattr = $freebusy->getAttribute('DTSTART');
        if (is_a($thisattr, 'PEAR_Error') && !is_a($thatattr, 'PEAR_Error')) {
            $this->setAttribute('DTSTART', $thatattr, array(), false);
        } elseif (!is_a($thatattr, 'PEAR_Error')) {
            if ($thatattr < $thisattr) {
                $this->setAttribute('DTSTART', $thatattr, array(), false);
            }
        }

        $thisattr = $this->getAttribute('DTEND');
        $thatattr = $freebusy->getAttribute('DTEND');
        if (is_a($thisattr, 'PEAR_Error') && !is_a($thatattr, 'PEAR_Error')) {
            $this->setAttribute('DTEND', $thatattr, array(), false);
        } elseif (!is_a($thatattr, 'PEAR_Error')) {
            if ($thatattr > $thisattr) {
                $this->setAttribute('DTEND', $thatattr, array(), false);
            }
        }

        if ($simplify) {
            $this->simplify();
        }

        return true;
    }

    /**
     * Removes all overlaps and simplifies the busy periods array as much as
     * possible.
     */
    function simplify()
    {
        $clean = false;
        $busy  = array($this->_busyPeriods, $this->_extraParams);
        while (!$clean) {
            $result = $this->_simplify($busy[0], $busy[1]);
            $clean = $result === $busy;
            $busy = $result;
        }

        ksort($result[1], SORT_NUMERIC);
        $this->_extraParams = $result[1];

        ksort($result[0], SORT_NUMERIC);
        $this->_busyPeriods = $result[0];
    }

    function _simplify($busyPeriods, $extraParams = array())
    {
        $checked = array();
        $checkedExtra = array();
        $checkedEmpty = true;

        foreach ($busyPeriods as $start => $end) {
            if ($checkedEmpty) {
                $checked[$start] = $end;
                $checkedExtra[$start] = isset($extraParams[$start])
                    ? $extraParams[$start] : array();
                $checkedEmpty = false;
            } else {
                $added = false;
                foreach ($checked as $testStart => $testEnd) {
                    // Replace old period if the new period lies around the
                    // old period.
                    if ($start <= $testStart && $end >= $testEnd) {
                        // Remove old period entry.
                        unset($checked[$testStart]);
                        unset($checkedExtra[$testStart]);
                        // Add replacing entry.
                        $checked[$start] = $end;
                        $checkedExtra[$start] = isset($extraParams[$start])
                            ? $extraParams[$start] : array();
                        $added = true;
                    } elseif ($start >= $testStart && $end <= $testEnd) {
                        // The new period lies fully within the old
                        // period. Just forget about it.
                        $added = true;
                    } elseif (($end <= $testEnd && $end >= $testStart) ||
                              ($start >= $testStart && $start <= $testEnd)) {
                        // Now we are in trouble: Overlapping time periods. If
                        // we allow for additional parameters we cannot simply
                        // choose one of the two parameter sets. It's better
                        // to leave two separated time periods.
                        $extra = isset($extraParams[$start])
                            ? $extraParams[$start] : array();
                        $testExtra = isset($checkedExtra[$testStart])
                            ? $checkedExtra[$testStart] : array();
                        // Remove old period entry.
                        unset($checked[$testStart]);
                        unset($checkedExtra[$testStart]);
                        // We have two periods overlapping. Are their
                        // additional parameters the same or different?
                        $newStart = min($start, $testStart);
                        $newEnd = max($end, $testEnd);
                        if ($extra === $testExtra) {
                            // Both periods have the same information. So we
                            // can just merge.
                            $checked[$newStart] = $newEnd;
                            $checkedExtra[$newStart] = $extra;
                        } else {
                            // Extra parameters are different. Create one
                            // period at the beginning with the params of the
                            // first period and create a trailing period with
                            // the params of the second period. The break
                            // point will be the end of the first period.
                            $break = min($end, $testEnd);
                            $checked[$newStart] = $break;
                            $checkedExtra[$newStart] =
                                isset($extraParams[$newStart])
                                ? $extraParams[$newStart] : array();
                            $checked[$break] = $newEnd;
                            $highStart = max($start, $testStart);
                            $checkedExtra[$break] =
                                isset($extraParams[$highStart])
                                ? $extraParams[$highStart] : array();

                            // Ensure we also have the extra data in the
                            // extraParams.
                            $extraParams[$break] =
                                isset($extraParams[$highStart])
                                ? $extraParams[$highStart] : array();
                        }
                        $added = true;
                    }

                    if ($added) {
                        break;
                    }
                }

                if (!$added) {
                    $checked[$start] = $end;
                    $checkedExtra[$start] = isset($extraParams[$start])
                        ? $extraParams[$start] : array();
                }
            }
        }

        return array($checked, $checkedExtra);
    }

}

/**
 * Class representing vJournals.
 *
 * $Horde: framework/iCalendar/iCalendar/vjournal.php,v 1.8.10.9 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vjournal extends Horde_iCalendar {

    function getType()
    {
        return 'vJournal';
    }

    function exportvCalendar()
    {
        return parent::_exportvData('VJOURNAL');
    }

}




/**
 * Class representing vNotes.
 *
 * $Horde: framework/iCalendar/iCalendar/vnote.php,v 1.3.10.10 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Karsten Fourmont <fourmont@gmx.de>
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vnote extends Horde_iCalendar {

    function Horde_iCalendar_vnote($version = '1.1')
    {
        return parent::Horde_iCalendar($version);
    }

    function getType()
    {
        return 'vNote';
    }

    /**
     * Unlike vevent and vtodo, a vnote is normally not enclosed in an
     * iCalendar container. (BEGIN..END)
     */
    function exportvCalendar()
    {
        $requiredAttributes['BODY'] = '';
        $requiredAttributes['VERSION'] = '1.1';

        foreach ($requiredAttributes as $name => $default_value) {
            if (is_a($this->getattribute($name), 'PEAR_Error')) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VNOTE');
    }

}

/**
 * Class representing vTimezones.
 *
 * $Horde: framework/iCalendar/iCalendar/vtimezone.php,v 1.8.10.10 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vtimezone extends Horde_iCalendar {

    function getType()
    {
        return 'vTimeZone';
    }

    function exportvCalendar()
    {
        return parent::_exportvData('VTIMEZONE');
    }

    /**
     * Parse child components of the vTimezone component. Returns an
     * array with the exact time of the time change as well as the
     * 'from' and 'to' offsets around the change. Time is arbitrarily
     * based on UTC for comparison.
     */
    function parseChild(&$child, $year)
    {
        // Make sure 'time' key is first for sort().
        $result['time'] = 0;

        $t = $child->getAttribute('TZOFFSETFROM');
        if (is_a($t, 'PEAR_Error')) {
            return false;
        }
        $result['from'] = ($t['hour'] * 60 * 60 + $t['minute'] * 60) * ($t['ahead'] ? 1 : -1);

        $t = $child->getAttribute('TZOFFSETTO');
        if (is_a($t, 'PEAR_Error')) {
            return false;
        }
        $result['to'] = ($t['hour'] * 60 * 60 + $t['minute'] * 60) * ($t['ahead'] ? 1 : -1);

        $switch_time = $child->getAttribute('DTSTART');
        if (is_a($switch_time, 'PEAR_Error')) {
            return false;
        }

        $rrules = $child->getAttribute('RRULE');
        if (is_a($rrules, 'PEAR_Error')) {
            if (!is_int($switch_time)) {
                return false;
            }
            // Convert this timestamp from local time to UTC for
            // comparison (All dates are compared as if they are UTC).
            $t = getdate($switch_time);
            $result['time'] = @gmmktime($t['hours'], $t['minutes'], $t['seconds'],
                                        $t['mon'], $t['mday'], $t['year']);
            return $result;
        }

        $rrules = explode(';', $rrules);
        foreach ($rrules as $rrule) {
            $t = explode('=', $rrule);
            switch ($t[0]) {
            case 'FREQ':
                if ($t[1] != 'YEARLY') {
                    return false;
                }
                break;

            case 'INTERVAL':
                if ($t[1] != '1') {
                    return false;
                }
                break;

            case 'BYMONTH':
                $month = intval($t[1]);
                break;

            case 'BYDAY':
                $len = strspn($t[1], '1234567890-+');
                if ($len == 0) {
                    return false;
                }
                $weekday = substr($t[1], $len);
                $weekdays = array(
                    'SU' => 0,
                    'MO' => 1,
                    'TU' => 2,
                    'WE' => 3,
                    'TH' => 4,
                    'FR' => 5,
                    'SA' => 6
                );
                $weekday = $weekdays[$weekday];
                $which = intval(substr($t[1], 0, $len));
                break;

            case 'UNTIL':
                if (intval($year) > intval(substr($t[1], 0, 4))) {
                    return false;
                }
                break;
            }
        }

        if (empty($month) || !isset($weekday)) {
            return false;
        }

        if (is_int($switch_time)) {
            // Was stored as localtime.
            $switch_time = strftime('%H:%M:%S', $switch_time);
            $switch_time = explode(':', $switch_time);
        } else {
            $switch_time = explode('T', $switch_time);
            if (count($switch_time) != 2) {
                return false;
            }
            $switch_time[0] = substr($switch_time[1], 0, 2);
            $switch_time[2] = substr($switch_time[1], 4, 2);
            $switch_time[1] = substr($switch_time[1], 2, 2);
        }

        // Get the timestamp for the first day of $month.
        $when = gmmktime($switch_time[0], $switch_time[1], $switch_time[2],
                         $month, 1, $year);
        // Get the day of the week for the first day of $month.
        $first_of_month_weekday = intval(gmstrftime('%w', $when));

        // Go to the first $weekday before first day of $month.
        if ($weekday >= $first_of_month_weekday) {
            $weekday -= 7;
        }
        $when -= ($first_of_month_weekday - $weekday) * 60 * 60 * 24;

        // If going backwards go to the first $weekday after last day
        // of $month.
        if ($which < 0) {
            do {
                $when += 60*60*24*7;
            } while (intval(gmstrftime('%m', $when)) == $month);
        }

        // Calculate $weekday number $which.
        $when += $which * 60 * 60 * 24 * 7;

        $result['time'] = $when;

        return $result;
    }

}

/**
 * @package Horde_iCalendar
 */
class Horde_iCalendar_standard extends Horde_iCalendar {

    function getType()
    {
        return 'standard';
    }

    function parsevCalendar($data)
    {
        parent::parsevCalendar($data, 'STANDARD');
    }

    function exportvCalendar()
    {
        return parent::_exportvData('STANDARD');
    }

}

/**
 * @package Horde_iCalendar
 */
class Horde_iCalendar_daylight extends Horde_iCalendar {

    function getType()
    {
        return 'daylight';
    }

    function parsevCalendar($data)
    {
        parent::parsevCalendar($data, 'DAYLIGHT');
    }

    function exportvCalendar()
    {
        return parent::_exportvData('DAYLIGHT');
    }

}

/**
 * Class representing vTodos.
 *
 * $Horde: framework/iCalendar/iCalendar/vtodo.php,v 1.13.10.9 2009-01-06 15:23:53 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @since   Horde 3.0
 * @package Horde_iCalendar
 */
class Horde_iCalendar_vtodo extends Horde_iCalendar {

    function getType()
    {
        return 'vTodo';
    }

    function exportvCalendar()
    {
        return parent::_exportvData('VTODO');
    }

    /**
     * Convert this todo to an array of attributes.
     *
     * @return array  Array containing the details of the todo in a hash
     *                as used by Horde applications.
     */
    function toArray()
    {
        $todo = array();

        $name = $this->getAttribute('SUMMARY');
        if (!is_array($name) && !is_a($name, 'PEAR_Error')) {
            $todo['name'] = $name;
        }
        $desc = $this->getAttribute('DESCRIPTION');
        if (!is_array($desc) && !is_a($desc, 'PEAR_Error')) {
            $todo['desc'] = $desc;
        }

        $priority = $this->getAttribute('PRIORITY');
        if (!is_array($priority) && !is_a($priority, 'PEAR_Error')) {
            $todo['priority'] = $priority;
        }

        $due = $this->getAttribute('DTSTAMP');
        if (!is_array($due) && !is_a($due, 'PEAR_Error')) {
            $todo['due'] = $due;
        }

        return $todo;
    }

    /**
     * Set the attributes for this todo item from an array.
     *
     * @param array $todo  Array containing the details of the todo in
     *                     the same format that toArray() exports.
     */
    function fromArray($todo)
    {
        if (isset($todo['name'])) {
            $this->setAttribute('SUMMARY', $todo['name']);
        }
        if (isset($todo['desc'])) {
            $this->setAttribute('DESCRIPTION', $todo['desc']);
        }

        if (isset($todo['priority'])) {
            $this->setAttribute('PRIORITY', $todo['priority']);
        }

        if (isset($todo['due'])) {
            $this->setAttribute('DTSTAMP', $todo['due']);
        }
    }

}
