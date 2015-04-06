<?php
  session_start();
  include_once("../../include/select_draw.class.php");
  $draw=new DRAW();
  $rectBounds = $_SESSION['rectBounds'];
  $line = $_POST['line'];
  $tidList = $_POST['mmsi_draw'];
  $tidLine = '';
  $rangeLine = '';
  
  $tidShow = explode(" ",$tidList);
  $tid = $tidShow[0];
  $sql = "SELECT * FROM ship_pre WHERE tid=$tid ORDER BY creation_time";
  $draw->select_draw($sql,false,0,'#0000FF');
  $fp = fopen('output/tid_output','w'); 
  echo "TIDs in grid $line " ;
  echo "<a href='output/tid_output' target='_blank'><strong>open file</strong></a><br>";
  echo "<select class=\"input-small\" id=\"frequent_trajectories\" onChange=\"shipSpecific(1);\">";
  foreach($tidShow AS $index => $tid) { // replace by str_replace()
    echo "<option value=\"$tid\">$tid</option>";
    if ($index == 0) $tidLine = "$tid";
    else $tidLine .= ",$tid";
  }
  fwrite($fp,"$tidLine");
  echo "</select> ";
  fclose($fp);

  exec("rm output/grid*");
  // write grid files
  $gridShow = explode(" ",$line);
  $flag = true; // default
  array_pop($gridShow);  // remove last item
  echo "<script>";
  echo "var rightCoordinates = [];";
  echo "var meanCoordinates = [];";
  echo "var leftCoordinates = [];";
  // write grid files
  foreach($gridShow AS $index => $grid) {
    $outfile = "output/grid$grid";
    $south = $rectBounds[$grid]->south; $west = $rectBounds[$grid]->west; $north = $rectBounds[$grid]->north; $east = $rectBounds[$grid]->east;
    $range =  "$south,$west,$north,$east";
    $sql = "SELECT latitude,longitude,cog,tid FROM ship_pre WHERE tid in ($tidLine) AND point(latitude,longitude) @ box'$range' ORDER BY tid,creation_time";  // s,w,n,e
    $draw->select_write($sql,$outfile);
if ($index == 0) $rangeLine = "point(latitude,longitude) @ box\'$range\'";
else $rangeLine .= " OR point(latitude,longitude) @ box\'$range\'";

//    echo "grid$grid = ".$rectBounds[$grid]->south .','.$rectBounds[$grid]->west .','.$rectBounds[$grid]->north .','.$rectBounds[$grid]->east."\n";
    // run RRD
    exec("output/RRD $grid $south $west $north $east",$RRDreturn);
    echo "console.log('$grid $south $west $north $east');";
    
    $avg_cog = $RRDreturn[0];
    $mean = explode(" ",$RRDreturn[2],2);
$mod = $avg_cog - $pre_cog;
if ( $mod < 0 ) $mod += 360;
if ($mod < 180) $flag = true;
else $flag = false;
//    if ($index ==0 || $flag) {
      $right = explode(" ",$RRDreturn[1],2);
      $left = explode(" ",$RRDreturn[3],2);
//    }
 //   else {
 //     $right = explode(" ",$RRDreturn[3],2);
 //     $left = explode(" ",$RRDreturn[1],2);
//    }
echo "console.log('$grid index=$index mod=$mod $avg_cog - $pre_cog flag=$flag');";
$pre_cog = $avg_cog;
    echo "var point = new google.maps.LatLng(".$right[1].", ".$right[0].");";
    echo "rightCoordinates.push(point);";
    echo "var point = new google.maps.LatLng(".$mean[1].", ".$mean[0].");";
    echo "meanCoordinates.push(point);";
    echo "var point = new google.maps.LatLng(".$left[1].", ".$left[0].");";
    echo "leftCoordinates.push(point);";
    // draw
    unset($RRDreturn);
  }
echo "drawPath(rightCoordinates,'#FFFFFF','');
drawPath(meanCoordinates,'#FF0000','');
drawPath(leftCoordinates,'#FF0000','');
";
  echo "</script>"; 
?>
