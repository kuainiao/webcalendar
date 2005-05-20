<?php
/*
 * $Id$
 *
 * Description:
 * This script is intended to be used outside of normal WebCalendar
 * use, typically as an RDF/RSS feed to a RSS client.
 *
 * You must have public access enabled in System Settings to use this
 * page (unless you modify the $public_must_be_enabled setting below
 * in this file).
 *
 * Simply use the URL of this file as the feed address in the client
 *
 *
 * By default (if you do not edit this file), events for the public
 * calendar will be loaded for either:
 *   - the next 30 days
 *   - the next 10 events
 *
 * Input parameters:
 * You can override settings by changing the URL parameters:
 *   - days: number of days ahead to look for events
 *   - cat_id: specify a category id to filter on
 *   - user: login name of calendar to display (instead of public
 *     user), if allowed by System Settings.  You must have the
 *     following System Settings configured for this:
 *       Allow viewing other user's calendars: Yes
 *       Public access can view others: Yes
 *
 * Security:
 * $RSS_ENABLED must be set true
 * $USER_RSS_ENABLED must be set true unless this is for the public user
 */

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/connect.php";

load_global_settings ();

include "includes/translate.php";

if ( empty ( $RSS_ENABLED ) || $RSS_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  etranslate("You are not authorized");
  exit;
}
/*
 *
 * Configurable settings for this file.  You may change the settings
 * below to change the default settings.
 * These settings will likely move into the System Settings in the
 * web admin interface in a future release.
 *
 */

// Change this to false if you still want to access this page even
// though you do not have public access enabled.
$public_must_be_enabled = true;

// Default time window of events to load
// Can override with "rss.php?days=60"
$numDays = 30;

// Max number of events to display
// Can override with "rss.php?max=20"
$maxEvents = 10;

// Login of calendar user to use
// '__public__' is the login name for the public user
$username = '__public__';

// Allow non-Public events to be fed to RSS
// This will only be used if $username is not __public__
$allow_all_access = "N";

// Allow the URL to override the user setting such as
// "rss.php?user=craig"
$allow_user_override = true;

// Load layers
$load_layers = false;

// Load just a specified category (by its id)
// Leave blank to not filter on category (unless specified in URL)
// Can override in URL with "rss.php?cat_id=4"
$cat_id = '';

// End configurable settings...

// Set for use elsewhere as a global
$login = $username;



if ( $public_must_be_enabled && $public_access != 'Y' ) {
  $error = translate ( "You are not authorized" ) . ".";
}

if ( $allow_user_override ) {
  $u = getValue ( "user", "[A-Za-z0-9_\.=@,\-]+", true );
  if ( ! empty ( $u ) ) {
    $username = $u;
    $login = $u;
    // We also set $login since some functions assume that it is set.
  }
}

load_user_preferences ();

user_load_variables ( $login, "rss_" );
$creator = ( $username == '__public__' ) ? 'Public' : $rss_fullname;

if ( $username != '__public__' && ( empty ( $USER_PUBLISH_ENABLED ) || 
  $USER_PUBLISH_ENABLED != 'Y' ) ) {
  header ( "Content-Type: text/plain" );
  etranslate("You are not authorized");
  exit;
}

$cat_id = '';
if ( $categories_enabled == 'Y' ) {
  $x = getIntValue ( "cat_id", true );
  if ( ! empty ( $x ) ) {
    $cat_id = $x;
  }
}

if ( $load_layers ) {
  load_user_layers ( $username );
}

//load_user_categories ();

// Calculate date range
$date = getIntValue ( "date", true );
if ( empty ( $date ) || strlen ( $date ) != 8 ) {
  // If no date specified, start with today
  $date = date ( "Ymd" );
}
$thisyear = substr ( $date, 0, 4 );
$thismonth = substr ( $date, 4, 2 );
$thisday = substr ( $date, 6, 2 );

$startTime = mktime ( 3, 0, 0, $thismonth, $thisday, $thisyear );

$x = getIntValue ( "days", true );
if ( ! empty ( $x ) ) {
  $numDays = $x;
}
// Don't let a malicious user specify more than 365 days
if ( $numDays > 365 ) {
  $numDays = 365;
}
$x = getIntValue ( "max", true );
if ( ! empty ( $x ) ) {
  $maxEvents = $x;
}
// Don't let a malicious user specify more than 100 events
if ( $maxEvents > 100 ) {
  $maxEvents = 100;
}

$endTime = mktime ( 3, 0, 0, $thismonth, $thisday + $numDays,
  $thisyear );
$endDate = date ( "Ymd", $endTime );


/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( $username, $cat_id, $date );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $username, $date, $endDate, $cat_id );

$charset = ( ! empty ( $LANGUAGE )?translate("charset"): "iso-8859-1" );
// This should work ok with RSS, may need to hardcode fallback value
$lang = languageToAbbrev ( ( $LANGUAGE == "Browser-defined" || 
  $LANGUAGE == "none" )? $lang : $LANGUAGE );
  
header('Content-type: application/rss+xml');
echo '<?xml version="1.0" encoding="' . $charset . '"?>';
?>

<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:cc="http://web.resource.org/cc/"
  xmlns="http://purl.org/rss/1.0/">
  
<channel rdf:about="<?php echo $server_url . $PHP_SELF; ?>">
<title><![CDATA[<?php etranslate ( $application_name ); ?>]]></title>
<link><?php echo $server_url; ?></link>
<description></description>
<dc:language><?php echo $lang; ?></dc:language>
<dc:creator><![CDATA[<?php echo $creator; ?>]]></dc:creator>
<dc:date><?php echo date ( 'm/d/Y H:i A' ); ?></dc:date>
<admin:generatorAgent rdf:resource="http://www.k5n.us/webcalendar.php?v=<?php echo $PROGRAM_VERSION; ?>" />

<?php
$numEvents = 0;
echo "\n<items>\n<rdf:Seq>\n";
for ( $i = $startTime; date ( "Ymd", $i ) <= date ( "Ymd", $endTime ) &&
  $numEvents < $maxEvents; $i += ( 24 * 3600 ) ) {
  $d = date ( "Ymd", $i );
  $entries = get_entries ( $username, $d );
  $rentries = get_repeating_entries ( $username, $d );
  if ( count ( $entries ) > 0 || count ( $rentries ) > 0 ) {
    for ( $j = 0; $j < count ( $entries ) && $numEvents < $maxEvents; $j++ ) {
      // Prevent non-Public events from feeding
      if ( $username == '__public__' || $entries[$j]['cal_access'] == "P" ||
        $allow_all_access == "Y" ) {
        echo "<rdf:li rdf:resource=\"" . $server_url . "view_entry.php?id=" . 
          $entries[$j]['cal_id'] . "&amp;date=" . $d . "&amp;friendly=1\" />\n";
        $numEvents++;
      }
    }
    for ( $j = 0; $j < count ( $rentries ) && $numEvents < $maxEvents; $j++ ) {
      // Prevent non-Public events from feeding
      if ( $username == '__public__' || $rentries[$j]['cal_access'] == "P" ||
        $allow_all_access == "Y" ) {
        echo "<rdf:li rdf:resource=\"" . $server_url . "view_entry.php?id=" . 
          $rentries[$j]['cal_id'] . "&amp;date=" . $d . "&amp;friendly=1\" />\n";
        $numEvents++;
      }
    }
  }
}
echo "</rdf:Seq>\n</items>\n</channel>\n\n";
?>
<image rdf:about="http://www.k5n.us/k5n_small.gif">
<title><![CDATA[<?php etranslate ( $application_name ); ?>]]></title>
<link><?php echo $PROGRAM_URL; ?></link>
<url>http://www.k5n.us/k5n_small.gif</url>
<width>141</width>
<height>36</height>
</image>
<?php
$numEvents = 0;
for ( $i = $startTime; date ( "Ymd", $i ) <= date ( "Ymd", $endTime ) &&
  $numEvents < $maxEvents; $i += ( 24 * 3600 ) ) {
  $d = date ( "Ymd", $i );
  $entries = get_entries ( $username, $d );
  $rentries = get_repeating_entries ( $username, $d );
  if ( count ( $entries ) > 0 || count ( $rentries ) > 0 ) {
    for ( $j = 0; $j < count ( $entries ) && $numEvents < $maxEvents; $j++ ) {
      // Prevent non-Public events from feeding
      if ( $username == '__public__' || $entries[$j]['cal_access'] == "P" ||
        $allow_all_access == "Y" ) {
        $unixtime = unixtime ( $d, $entries[$j]['cal_time'] );
        echo "\n<item rdf:about=\"" . $server_url . "view_entry.php?id=" . 
          $entries[$j]['cal_id'] . "&amp;date=" . $d . "&amp;friendly=1\">\n";
        echo "<title xml:lang=\"$lang\"><![CDATA[" . $entries[$j]['cal_name'] . "]]></title>\n";
        echo "<link>" . $server_url . "view_entry.php?id=" . 
          $entries[$j]['cal_id'] . "&amp;date=" . $d . "&amp;friendly=1</link>\n";
        echo "<description xml:lang=\"$lang\"><![CDATA[" .
          $entries[$j]['cal_description'] . "]]></description>\n";
        echo "<category xml:lang=\"$lang\"><![CDATA[" . $entries[$j]['cal_name'] .
          "]]></category>\n";
        echo "<content:encoded xml:lang=\"$lang\"><![CDATA[" .
          $entries[$j]['cal_description'] . "]]></content:encoded>\n";
        echo "<dc:creator><![CDATA[" . $creator . "]]></dc:creator>\n";
        echo "<dc:date>" . date ( 'm/d/Y H:i A', $unixtime ) .
          "</dc:date>\n";
        echo "</item>\n";
        $numEvents++;
      }
    }
    for ( $j = 0; $j < count ( $rentries ) && $numEvents < $maxEvents; $j++ ) {
      // Prevent non-Public events from feeding
      if ( $username == '__public__' || $rentries[$j]['cal_access'] == "P" ||
        $allow_all_access == "Y" ) {
        echo "\n<item rdf:about=\"" . $server_url . "view_entry.php?id=" . 
          $rentries[$j]['cal_id'] . "&amp;date=" . $d . "&amp;friendly=1\">\n";
        $unixtime = unixtime ( $d, $rentries[$j]['cal_time'] );
        echo "<title xml:lang=\"$lang\"><![CDATA[" . $rentries[$j]['cal_name'] . "]]></title>\n";
        echo "<link>" . $server_url . "view_entry.php?id=" . 
          $rentries[$j]['cal_id'] . "&amp;date=" . $d . "&amp;friendly=1</link>\n";
        echo "<description xml:lang=\"$lang\"><![CDATA[" .
          $rentries[$j]['cal_description'] . "]]></description>\n";
        echo "<category><![CDATA[" .  $rentries[$j]['cal_name']  .
          "]]></category>\n";
        echo "<content:encoded xml:lang=\"$lang\"><![CDATA[" .
          $rentries[$j]['cal_description'] . "]]></content:encoded>\n";
        echo "<dc:creator><![CDATA[" . $creator . "]]></dc:creator>\n";
        echo "<dc:date>" . date ( 'm/d/Y H:i A', $unixtime ) .
          "</dc:date>\n";
        echo "</item>\n";   
        $numEvents++;
      }
    }
  }
}
echo "</rdf:RDF>\n";
// Clear login...just in case
$login = '';
exit;


// Make a unix GMT time from a date (YYYYMMDD) and time (HHMM)
function unixtime ( $date, $time )
{
  $hour = $minute = 0;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );

  if ( $time >= 0 ) {
    $hour = substr ( $time, 0, 2 );
    $minute = substr ( $time, 2, 2 );
  }

  return mktime ( $hour, $minute, 0, $month, $day, $year );
}
?>
