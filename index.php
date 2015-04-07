<?php
  session_start();
  include("../../include/JSmap.class.php");
  include_once("../../include/PostgreDB.class.php");

  if( isset($_SESSION['centerLon']) && isset($_SESSION['centerLat']) ){
    $centerLat=$_SESSION['centerLat'];
    $centerLon=$_SESSION['centerLon'];
    $mapScale=$_SESSION['mapScale'];
  }
  else{
    $maxLon=121.0118293762207;
    $maxLat=24.801422252016984121;
    $minLon=120.98608016967773;
    $minLat=24.77804576764012120;
    $centerLat=23.0;
    $centerLon=120.0;
    $mapScale=10;
  }
?>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>Maritime Trajectory Analysis System</title>
<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
<link href="../css/ship.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?v=3.8&libraries=geometry&sensor=false&language=en"></script>
<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/infobox/src/infobox.js"></script>
<script type="text/javascript" src="../scripts/prototype.js"></script>
<script type="text/javascript" src="../scripts/gmap_draw.js"></script>
<script type="text/javascript" src="../scripts/json2.js"></script>
<script type="text/javascript">
  var map;
  var overlayList=[]; var rectList=[]; var rectBounds=[]; var overlayCache=[];
  var showRec=[]; var FPrect=[];
  function initialize() {
    window.status="Running...";
    var latlng = new google.maps.LatLng(<?php echo "$centerLat";?>,<?php echo "$centerLon";?>);
    var myOptions = {
      zoom: <?php echo "$mapScale";?>,
      center: latlng,
      zoomControl: true,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map01"),
      myOptions);
    google.maps.event.addDomListener(map, 'rightclick', function(event){
      var infobox = new InfoBox({
        content: event.latLng.lat()+","+event.latLng.lng(),
        position: event.latLng,
        boxStyle: {
          border: "1px solid black",
          background: "yellow",
          opacity: 0.75,
          textAlign: "center",
          fontSize: "5pt",
        },
        disableAutoPan: false,
        maxWidth: 0,
        pane: "overlayImage",
        enableEventPropagation: false,
        closeBoxURL: "http://www.google.com/intl/en_us/mapfiles/close.gif",
        infoBoxClearance: new google.maps.Size(1, 1)
      });
      infobox.open(map);
    });         
    window.status="Complete";
  }  

  function shipSpecific(segment){  // Show ship trajectory 
    var url='ship_specific.php?'; 
    if (segment == 0) { // : tid
      var _index=document.getElementById("tid_num").selectedIndex;
      var attribute=document.getElementById("tid_num").options[_index].value; 
      var link = document.getElementById('link').checked;  // true / false
      var rate = $F('sample_t');
    }
    else { // segment == 1 : tid
      var _index=document.getElementById("frequent_trajectories").selectedIndex;
      var attribute = document.getElementById("frequent_trajectories").options[_index].value;
      var link = 'false';
      var rate = $F('sample_t_2');
      clearArray(overlayList);
    }
    var qstr = "segment="+segment+"&attribute="+attribute+"&link="+link+"&rate="+rate;
    Element.update('content1',"Running...");
    var ajax=new Ajax.Request(url,{
      method: 'post', parameters: qstr,
      onSuccess: function(reqObj){
        Element.update('content1',reqObj.responseText);
      }
    });
  }
  
  function shipRange(tid,range){  // Show ship trajectory 
    var url='ship_specific.php?';
    // segment != 0/1 : segment==tid, attribute==range
    var segment = tid;
    var attribute = range;
    var link = 'false';
    var rate = $F('sample_t_2');
    clearArray(overlayList);
    var qstr = "segment="+segment+"&attribute="+attribute+"&link="+link+"&rate="+rate;
    Element.update('content1',"Running...");
    var ajax=new Ajax.Request(url,{
      method: 'post', parameters: qstr,
      onSuccess: function(reqObj){
        Element.update('content1',reqObj.responseText);
      }
    });/**/
  }	
  
  function ajaxSendRequest1(){  // Show ship trajectory 
    var url='ship_traj.php?'; 
    var link = document.getElementById('link').checked; // true / false
    var qstr = "range="+$F('range')+"&speed_l="+$F('speed_l')+"&speed_h="+$F('speed_h')+"&link="+link+"&rate="+$F('sample_t')+"&time_l="+$F('time_l')+"&time_h="+$F('time_h');
    Element.update('content1',"Running...");
    var ajax=new Ajax.Request(url,{
      method: 'post', parameters: qstr,
      onSuccess: function(reqObj){
        Element.update('content1',reqObj.responseText);
      }
    });
  }
  function ajaxSendRequest2(){  // Find out frequent grids 
    var url='frequent_sequence.php?';
    var ragne=$F('range').split(",");
    var size=$F('size');
	  clearArray(rectList); rectBounds = [];
    if (size >0 && range!="") {
      var qstr = "range="+$F('range')+"&support_1="+$F('support_1')+"&speed_l="+$F('speed_l')+"&speed_h="+$F('speed_h')+"&time_l="+$F('time_l')+"&time_h="+$F('time_h')+"&size="+size;
      Element.update('content2',"Running...");
      Element.update('content3',"");
      var ajax=new Ajax.Request(url,{
        method: 'post', parameters: qstr,
        onSuccess: function(reqObj){
          Element.update('content2',reqObj.responseText);
        }
      });
    }
    else alert("need to set specific range and grid size should larger than 0");
  }
  function ajaxSendRequest3(){  // draw frequent grids and list traj
    var _index=document.getElementById("frequent_grids").selectedIndex;
    var line = document.getElementById("frequent_grids").options[_index].text;
    var mmsi = document.getElementById("frequent_grids").options[_index].value;
    var grids = line.split(" ");
    var url='frequent_trajectories.php?';
    var qstr = "line="+line+"&mmsi_draw="+mmsi;
    grids.pop();
    if (line != '') {  // draw frequent grids
    drawFP(grids);
    Element.update('content4',"Running...");
    var ajax=new Ajax.Request(url,{
        method: 'post', parameters: qstr,
        onSuccess: function(reqObj){
          Element.update('content4',reqObj.responseText);
        }
      });
    }
  }
  function clearOverlay() {
    for (var i = 0; i < overlayList.length; i++ ) {
      overlayList[i].setMap(null);
    }
//    for (var i = 0; i < rectList.length; i++ ) {
//      rectList[i].setMap(null);
//rectBound[i].setMap(null);
//    }
    overlayList = []; //rectList=[];
    clearArray(FPrect); //showRec=[];
  }
  function clearArray(Arr) {
    if (typeof Arr !== 'undefined' && Arr != null) {  // check isset
      for (var i = 0; i < Arr.length; i++ ) {
        if (typeof Arr[i] !== 'undefined' && Arr[i] != null) Arr[i].setMap(null);
      }
      Arr=[];
    }
  }
  function drawGrid(){  // experiment, given pattern and draw on map 
    var url='drawGrids.php?'; 
    var qstr = "range="+$F('range')+"&grid_size="+$F('size_test')+"&pattern="+$F('pattern');
    Element.update('content1',"Running...");
    var ajax=new Ajax.Request(url,{
      method: 'post', parameters: qstr,
      onSuccess: function(reqObj){
        Element.update('content1',reqObj.responseText);
      }
    });
  }
  function testing(){  // Find out frequent grids 
    var url='anomalyDet.php?';
    var ragne=$F('range').split(",");
    var size=$F('size');
	  clearArray(rectList); rectBounds = [];
    if (size >0 && range!="") {
      var qstr = "range="+$F('range')+"&support_1="+$F('support_1')+"&speed_l="+$F('speed_l')+"&speed_h="+$F('speed_h')+"&time_l="+$F('time_l')+"&time_h="+$F('time_h')+"&size="+size;
      Element.update('content2',"Running...");
      Element.update('content3',"");
      var ajax=new Ajax.Request(url,{
        method: 'post', parameters: qstr,
        onSuccess: function(reqObj){
          Element.update('content2',reqObj.responseText);
        }
      });
    }
    else alert("need to set specific range and grid size should larger than 0");
  }	
</script>
</head>

<body onload="initialize()">
  <div id='outer2'>
  <div id='sidebar2'>
  <div id="query1" style="margin-top: 10px; margin-left: 10px;overflow:auto; ">
  <h4>DataSet</h4>
  <strong>Time</strong>
  <input class="form-control" type="text" id="time_l" value="2013-03-11 07:44:32"> to 
  <input class="form-control" type="text" id="time_h" value="2013-09-05 19:02:25"> </br>
  <strong>Area</strong>
  <input class="input-xlarge " type="text" id="range" value="22.33,119.5,23.2,120.4" placeholder="min_lat, min_lng, max_lat, max_lng">
  <p class="help-block text-right">Right click the map to get coordinates.</p> 
  <strong>Speed</strong>
  <input class="input-mini" type="text" id="speed_l" value=5> to 
  <input class="input-mini" type="text" id="speed_h" value=30> Nm / Hr  </br>
  <div class="span3"><input type="checkbox" id="link"> Link Points  </div>
  <div class="span3">Sample Rate <input class="input-mini" type="text" id="sample_t" value=3> min(s)  </div>
  <div class="span3"><select class="input-small" id="tid_num" onChange="shipSpecific(0);">
  <option> - TID - </option>
<?php
  $db=new DB();
  $results=$db->query("SELECT DISTINCT tid FROM mtas.ship_evaluation LIMIT 100");
  for($i=0; $i< $db->num_rows(); $i++){
    $tid=pg_result($results,$i,'tid');
    echo "<option value=\"$tid\">$tid</option>";
  }
?>
  </select> (optional) </div>
  <div class="span3">
  <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" onclick="ajaxSendRequest1();"> Search Trajectory </button>
  </div>
  <div id = "content1"></div>
  </div>

  <div id="query2" style="margin-top: 30px; margin-left: 10px; overflow:auto; ">
  <h4>RouteMiner</h4>
  <div>
  grid size: <input class="input-mini" type="text" id="size_test" value=5> km <br>
  pattern: <input class="input" type="text" id="pattern" placeholder="grid1,grid2,..."> <br>
  <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" onclick="drawGrid();"> Draw Grid </button>
  </div>
  <br>
  <strong>Step I</strong> <br>
  grid size: <input class="input-mini" type="text" id="size" value=5> km <br>
  minimum support threshold: <input class="input-mini" type="text" id="support_1" value=10> <br>
  <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" onclick="ajaxSendRequest2();"> Build PST </button>
  <a href="output/ship_sequence" target="_blank"><strong>open file</strong></a>
  
  <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" onclick="testing();"> Testing </button>
  
  <div id="content2"></div> <br> 
  
  <strong>Step II</strong> <br>
  minimum support threshold: <input class="input-mini" type="text" id="support_2" value=50><br>
  <input type="checkbox" id="superset" checked> superset <br>
  <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" onclick="fpdiscovery();"> Frequent Pattern Discovery </button>
  <br>
  <div id="content3"></div>
  <div id="content4"></div>
  </div>
  <div id="query3" style="margin-top: 30px; margin-left: 10px;"><a href="#" id="clear" onclick="clearOverlay();"><strong>Clear ALL</strong></a>
  </div>
  </div>
  <div id="map01"></div>
  </div>
</body>
</html>

