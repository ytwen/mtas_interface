<?php
  set_time_limit(0);
  include_once("../../include/PostgreDB.class.php");
  include("../../include/select_draw.class.php");
  $range = $_POST['range']; // minimum_latitude, minimum_longitude, maximum_latitude, maximum_longitude
  $time_l = $_POST['time_l'];
  $time_h = $_POST['time_h'];
  $speed_l = $_POST['speed_l'];
  $speed_h = $_POST['speed_h'];
  $link = $_POST['link'];
  $db=new DB();
  $draw=new DRAW();
  if ($link == 'false') {
    if ($range!="" && is_numeric($speed_l) && is_numeric($speed_h)) {
      //  $results=$db->query("SELECT DISTINCT tid FROM mtas.ship_training LIMIT 100");
      $results=$db->query("SELECT DISTINCT tid FROM mtas.pst_table_pre_exp where pattern = 281 and next = 282 limit 50");//$results=$db->query("SELECT '14519' as tid");
        $total_tids = $db->num_rows();
        echo "<script>\n";
        for($i=0; $i< $total_tids; $i++){
          $tid = pg_result($results,$i,'tid');
          $result_mmsi=$db->query("SELECT * FROM mtas.ship_training WHERE tid=$tid AND point(latitude,longitude) @ box'$range' AND random() < 0.05 LIMIT 50");
          $total_pts = $db->num_rows();
          $color = $draw->createColor();
          $color = '"#'.$color.'"';
          //$draw->select_draw($sql,$link,120,$color);
          for($j=0; $j< $total_pts; $j++) {
            $lng=pg_result($result_mmsi,$j,'longitude');
            $lat=pg_result($result_mmsi,$j,'latitude');
            $cog=pg_result($result_mmsi,$j,'cog');  // course
            echo "var point = new google.maps.LatLng($lat, $lng);";
            echo "drawLastP(point,$cog,'#000000','$total_tids');";
          }
        }
        echo "</script>\n";
    } 
  }
  else echo "need to set specific all queries";
?>

