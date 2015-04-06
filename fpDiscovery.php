<?php
function lineToLink($filename) {
  $handle = @fopen($filename, "r");
  if ($handle) {
    echo "<br> <select class=\"input-large\" id=\"frequent_grids\" onChange=\"ajaxSendRequest3();\">";
    echo "<option value=''> frequent grids with support </option>";
    while (($line = fgets($handle)) !== false) {
      $tidList = fgets($handle);
      echo "<option value=\"$tidList\">$line</option>";
    }
    echo "</select> ";
    echo "Sample Rate <input class=\"input-mini\" type=\"text\" id=\"sample_t_2\" value=0> min";
    if (!feof($handle)) {
      echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
  }
}
  $support = $_POST['support_2'];
  $superset = $_POST['superset'];

  exec("output/prefixspan -min_sup $support output/ship_sequence");
  if ($superset == 'true') {
    exec("output/super_set");
    lineToLink('output/ship_super'); 
  }
  else {
    //exec("output/fpgrowth -s-$support -q-2 output/ship_sequence output/ship_output");
    lineToLink('output/ship_output');
  }
?>
