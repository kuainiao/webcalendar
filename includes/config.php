<?php
/**
 * This file loads configuration settings from the data file settings.php and
 * sets up some needed variables.
 *
 * The settings.php file is created during installation using the web-based db
 * setup page (install/index.php).
 *
 * <b>Note:</b>
 * DO NOT EDIT THIS FILE!
 *
 * (Versions 0.9.44 and older required users to edit this file to configure
 * WebCalendar.  Version 0.9.45 and later requires editing the settings.php
 * file instead.)
 *
 * @version $Id$
 * @package WebCalendar
 */
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}


$PROGRAM_VERSION = "v1.1.0-CVS";
$PROGRAM_DATE = "?? ??? 2005";
$PROGRAM_NAME = "WebCalendar $PROGRAM_VERSION ($PROGRAM_DATE)";
$PROGRAM_URL = "http://webcalendar.sourceforge.net/";

$TROUBLE_URL = "docs/WebCalendar-SysAdmin.html#trouble";

/**
 * Prints a fatal error message to the user along with a link to the
 * Troubleshooting section of the WebCalendar System Administrator's Guide.
 *
 * Execution is aborted.
 *
 * @param string $error The error message to display
 *
 * @internal We don't normally put functions in this file.  But, since this
 *           file is included before some of the others, this function either
 *           goes here or we repeat this code in multiple files.
 */
function die_miserable_death ( $error )
{
global $TROUBLE_URL;
  echo "<html><head><title>WebCalendar: Fatal Error</title></head>\n" .
    "<body><h2>WebCalendar Error</h2>\n" .
    "<p>$error</p>\n<hr />" .
    "<p><a href=\"$TROUBLE_URL\" target=\"_blank\">" .
    "Troubleshooting Help</a></p></body></html>\n";
  exit;
}



// Open settings file to read
$settings = array ();
$fd = @fopen ( "settings.php", "rb", true );
if ( ! $fd )
  $fd = @fopen ( "includes/settings.php", "rb", true );
if ( empty ( $fd ) ) {
  // There is no settings.php file.
  // Redirect user to install page if it exists.
  if ( file_exists ( "install/index.php" ) ) {
    Header ( "Location: install/index.php" );
    exit;
  } else {
    die_miserable_death ( "Could not find settings.php file.<br />\n" .
      "Please copy settings.php.orig to settings.php and modify for your " .
      "site.\n" );
  }
}

// We don't use fgets() since it seems to have problems with Mac-formatted
// text files.  Instead, we read in the entire file, then split the lines
// manually.
$data = '';
while ( ! feof ( $fd ) ) {
  $data .= fgets ( $fd, 4096 );
}
fclose ( $fd );

// Replace any combination of carriage return (\r) and new line (\n)
// with a single new line.
$data = preg_replace ( "/[\r\n]+/", "\n", $data );

// Split the data into lines.
$configLines = explode ( "\n", $data );

for ( $n = 0; $n < count ( $configLines ); $n++ ) {
  $buffer = $configLines[$n];
  $buffer = trim ( $buffer, "\r\n " );
  if ( preg_match ( "/^#/", $buffer ) )
    continue;
  if ( preg_match ( "/^<\?/", $buffer ) ) // start php code
    continue;
  if ( preg_match ( "/^\?>/", $buffer ) ) // end php code
    continue;
  if ( preg_match ( "/(\S+):\s*(\S+)/", $buffer, $matches ) ) {
    $settings[$matches[1]] = $matches[2];
    //echo "settings $matches[1] => $matches[2] <br>";
  }
}
$configLines = $data = '';

// Extract db settings into global vars
$db_type = $settings['db_type'];
$db_host = $settings['db_host'];
$db_login = $settings['db_login'];
$db_password = $settings['db_password'];
$db_database = $settings['db_database'];
$db_persistent = preg_match ( "/(1|yes|true|on)/i",
  $settings['db_persistent'] ) ? '1' : '0';

foreach ( array ( "db_type", "db_host", "db_login", "db_password" ) as $s ) {
  if ( empty ( $settings[$s] ) ) {
    die_miserable_death ( "Could not find <tt>$s</tt> defined in " .
      "your <tt>settings.php</tt> file.\n" );
  }
}

$readonly = preg_match ( "/(1|yes|true|on)/i",
  $settings['readonly'] ) ? 'Y' : 'N';

$run_mode = preg_match ( "/(dev)/i",
  $settings['mode'] ) ? 'dev' : 'prod';

if ( $run_mode == 'dev' ) {
  $phpdbiVerbose = true;
}

$single_user = "N";
$single_user = preg_match ( "/(1|yes|true|on)/i",
  $settings['single_user'] ) ? 'Y' : 'N';
if ( $single_user == 'Y' )
  $single_user_login = $settings['single_user_login'];

if ( $single_user == 'Y' && empty ( $single_user_login ) ) {
  die_miserable_death ( "You must define <tt>single_user_login</tt> in " .
    "the settings.php file.\n" );
}


$use_http_auth = preg_match ( "/(1|yes|true|on)/i",
  $settings['use_http_auth'] ) ? true : false;

// Type of user authentication
$user_inc = $settings['user_inc'];

// We can add extra 'nonuser' calendars such as a corporate calendar,
// holiday calendar, departmental calendar, etc.  We need a unique prefix
// for these calendars as not to get mixed up with real logins.  This prefix
// should be a Maximum of 5 characters and should NOT change once set!
$NONUSER_PREFIX = '_NUC_';

// Language options  The first is the name presented to users while
// the second is the filename (without the ".txt") that must exist
// in the translations subdirectory.
$languages = array (
  "Browser-defined" =>"none",
  "English" =>"English-US",
  "Basque" => "Basque",
  "Bulgarian" => "Bulgarian",
  "Catalan" => "Catalan",
  "Chinese (Traditonal/Big5)" => "Chinese-Big5",
  "Chinese (Simplified/GB2312)" => "Chinese-GB2312",
  "Czech" => "Czech",
  "Danish" => "Danish",
  "Dutch" =>"Dutch",
  "Estonian" => "Estonian",
  "Finnish" =>"Finnish",
  "French" =>"French",
  "Galician" => "Galician",
  "German" =>"German",
  "Holo (Taiwanese)" => "Holo-Big5",
  "Hungarian" =>"Hungarian",
  "Icelandic" => "Icelandic",
  "Italian" => "Italian",
  "Japanese(SHIFT JIS)" => "Japanese",
  "Japanese(EUC-JP)" => "Japanese-eucjp",
  "Japanese(UTF-8)" => "Japanese-utf8",
  "Korean" =>"Korean",
  "Norwegian" => "Norwegian",
  "Polish" => "Polish",
  "Portuguese" =>"Portuguese",
  "Portuguese/Brazil" => "Portuguese_BR",
  "Russian" => "Russian",
  "Spanish" =>"Spanish",
  "Swedish" =>"Swedish",
  "Turkish" =>"Turkish"
  // add new languages here!  (don't forget to add a comma at the end of
  // last line above.)
);

// If the user sets "Browser-defined" as their language setting, then
// use the $HTTP_ACCEPT_LANGUAGE settings to determine the language.
// The array below translates browser language abbreviations into
// our available language files.
// NOTE: These should all be lowercase on the left side even though
// the proper listing is like "en-US"!
// Not sure what the abbreviation is?  Check out the following URL:
// http://www.geocities.com/click2speak/languages.html
$browser_languages = array (
  "eu" => "Basque",
  "bg" => "Bulgarian",
  "ca" => "Catalan",
  "zh" => "Chinese-GB2312",    // Simplified Chinese
  "zh-cn" => "Chinese-GB2312",
  "zh-tw" => "Chinese-Big5",   // Traditional Chinese
  "cs" => "Czech",
  "en" => "English-US",
  "en-us" => "English-US",
  "en-gb" => "English-US",
  "da" => "Danish",
  "nl" =>"Dutch",
  "ee" => "Estonian",
  "fi" =>"Finnish",
  "fr" =>"French",
  "fr-ch" =>"French", // French/Swiss
  "fr-ca" =>"French", // French/Canada
  "gl" => "Galician",
  "de" =>"German",
  "de-at" =>"German", // German/Austria
  "de-ch" =>"German", // German/Switzerland
  "de-de" =>"German", // German/German
  "hu" => "Hungarian",
  "zh-min-nan-tw" => "Holo-Big5",
  "is" => "Icelandic",
  "it" => "Italian",
  "it-ch" => "Italian", // Italian/Switzerland
  "ja" => "Japanese",
  "ko" =>"Korean",
  "no" => "Norwegian",
  "pl" => "Polish",
  "pt" =>"Portuguese",
  "pt-br" => "Portuguese_BR", // Portuguese/Brazil
  "ro" =>"Romanian",
  "ru" =>"Russian",
  "es" =>"Spanish",
  "sv" =>"Swedish",
  "tr" =>"Turkish",
  "cy" => "Welsh"
);

// The following comments will be picked up by update_translation.pl so
// translators will be aware that they also need to translate language names.
//
// translate("English")
// translate("Basque")
// translate("Bulgarian")
// translate("Catalan")
// translate("Chinese (Traditonal/Big5)")
// translate("Chinese (Simplified/GB2312)")
// translate("Czech")
// translate("Danish")
// translate("Dutch")
// translate("Estonian")
// translate("Finnish")
// translate("French")
// translate("Galician")
// translate("German")
// translate("Holo (Taiwanese)")
// translate("Hungarian")
// translate("Icelandic")
// translate("Italian")
// translate("Japanese")
// translate("Korean")
// translate("Norwegian")
// translate("Polish")
// translate("Portuguese")
// translate("Portuguese/Brazil")
// translate("Romanian")
// translate("Russian")
// translate("Spanish")
// translate("Swedish")
// translate("Turkish")

if ( $single_user != "Y" )
  $single_user_login = "";

// Make sure magic quotes is enabled, since this app requires it.
if ( get_magic_quotes_gpc () == 0 ) {
  ob_start ();
  phpinfo ();
  $val = ob_get_contents ();
  ob_end_clean ();
  $loc = '';
  if ( preg_match ( "/>([^<>]*php.ini)</", $val, $matches ) ) {
    $loc = "Please edit the following file and restart your server:" .
      "<br /><br />\n" .
      "<blockquote>\n<tt>" . $matches[1] . "</tt>\n</blockquote>\n";
  }
  die_miserable_death ( "You must reconfigure your <tt>php.ini</tt> file to " .
    "have <span style=\"font-weight:bold;\">magic_quotes_gpc</span> set " .
    " to <span style=\"font-weight:bold;\">ON</span>.<br /><br />\n" .
    $loc );
}

?>
