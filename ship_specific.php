<?php
  set_time_limit(0);
  include("../../include/select_draw.class.php");
  $segment=$_POST['segment']; // 0:mmsi ; 1:tid
  $attribute = $_POST['attribute'];
  $link = $_POST['link'];  // true / false
  $rate = 60 * $_POST['rate'];  // in seconds -> min
  $draw=new DRAW();
  if ($segment == '0') { // attribute = mmsi
    $sql = "SELECT * FROM mtas.ship_evaluation WHERE tid=$attribute ORDER BY creation_time";
    $color = $draw->createColor();
    $draw->select_draw($sql,$link,$rate,'#FF0000');
  }
  else if ($segment == '1'){ // attribute = tid
    $sql = "SELECT * FROM mtas.ship_evaluation WHERE tid=$segment ORDER BY creation_time";
    $color = $draw->createColor();
    $draw->select_draw($sql,$link,$rate,$color);
  }
  else { // segment==tidLine, attribute==rangeLine
    $tidLine = explode(",",$segment);
    foreach ($tidLine AS $tid) {
      $sql = "SELECT * FROM mtas.ship_evaluation WHERE tid=$tid AND POINT(latitude,longitude)@BOX'22.525508795561,120.08492343027,22.750339197041,120.32805467166' ORDER BY creation_time";
      $color = $draw->createColor();
      $draw->select_draw($sql,$link,45,$color);
    }/**/
  }
?>

