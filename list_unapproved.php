<?php
include_once 'includes/init.php';
send_no_cache_header ();

if ( empty ( $user ) )
  $user = $login;

if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<META HTTP-EQUIV=\"refresh\" content=\"$refresh; URL=list_unapproved.php\" TARGET=\"_self\">\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

$key = 0;

// List all unapproved events for the user
// Exclude "extension" events (used when an event goes past midnight)
function list_unapproved ( $user ) {
  global $temp_fullname, $key, $login;
  //echo "Listing events for $user <BR>";

  echo "<UL>\n";
  $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
    "webcal_entry.cal_description, " .
    "webcal_entry.cal_priority, webcal_entry.cal_date, " .
    "webcal_entry.cal_time, webcal_entry.cal_duration, " .
    "webcal_entry_user.cal_status " .
    "FROM webcal_entry, webcal_entry_user " .
    "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
    "AND ( webcal_entry.cal_ext_for_id IS NULL " .
    "OR webcal_entry.cal_ext_for_id = 0 ) AND " .
    "webcal_entry_user.cal_login = '$user' AND " .
    "webcal_entry_user.cal_status = 'W' " .
    "ORDER BY webcal_entry.cal_date";
  $res = dbi_query ( $sql );
  $count = 0;
  $eventinfo = "";
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $key++;
      $id = $row[0];
      $name = $row[1];
      $description = $row[2];
      $pri = $row[3];
      $date = $row[4];
      $time = $row[5];
      $duration = $row[6];
      $status = $row[7];
      $divname = "eventinfo-$id-$key";
      echo "<LI><A CLASS=\"entry\" HREF=\"view_entry.php?id=$id&user=$user";
      echo "\" onMouseOver=\"window.status='" . translate("View this entry") .
        "'; show(event, '$divname'); return true;\" onMouseOut=\"hide('$divname'); return true;\">";
      $timestr = "";
      if ( $time > 0 ) {
        $timestr = display_time ( $time );
        if ( $duration > 0 ) {
          // calc end time
          $h = (int) ( $time / 10000 );
          $m = ( $time / 100 ) % 100;
          $m += $duration;
          $d = $duration;
          while ( $m >= 60 ) {
            $h++;
            $m -= 60;
          }
          $end_time = sprintf ( "%02d%02d00", $h, $m );
          $timestr .= " - " . display_time ( $end_time );
        }
      }
      echo htmlspecialchars ( $name );
      echo "</A>";
      echo " (" . date_to_str ($date) . ")\n";
      echo ": <A HREF=\"approve_entry.php?id=$id&ret=list&user=$user";
      if ( $user == "__public__" )
        echo "&public=1";
      echo "\" CLASS=\"navlinks\" onClick=\"return confirm('" .
        translate("Approve this entry?") .
        "');\">" . translate("Approve/Confirm") . "</A>, ";
      echo "<A HREF=\"reject_entry.php?id=$id&ret=list&user=$user";
      if ( $user == "__public__" )
        echo "&public=1";
      echo "\" CLASS=\"navlinks\" onClick=\"return confirm('" .
        translate("Reject this entry?") .
        "');\">" . translate("Reject") . "</A>";
      $eventinfo .= build_event_popup ( $divname, $user, $description,
        $timestr, $time );
      $count++;
    }
    dbi_free_result ( $res );
  }
  echo "</UL><P>\n";
  if ( $count == 0 ) {
    user_load_variables ( $user, "temp_" );
    echo translate("No unapproved events for") . " " . $temp_fullname . ".";
  } else {
    echo $eventinfo;
  }

}

?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Unapproved Events")?></FONT></H2>

<?php

// List unapproved events for this user.
list_unapproved ( ( $is_assistant || $is_nonuser_admin || $is_admin ) ? $user : $login );

// Admin users can also approve Public Access events
if ( $is_admin && $public_access == "Y" &&
  ( empty ( $user ) || $user != '__public__' ) ) {
  echo "<P><H3>" . translate ( "Public Access" ) . "</H3>\n";
  list_unapproved ( "__public__" );
}
?>

<?php print_trailer(); ?>
</BODY>
</HTML>
