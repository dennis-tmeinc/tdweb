<!DOCTYPE html>
<html>
<head>
<?php 
require 'config.php' ; 
require 'session.php'; 
?>
	<title>Touch Down Center</title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta name="description" content="Touch Down Center by TME">
	<meta name="author" content="Dennis Chen @ TME, 2013-06-05">
	<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
	<?php echo "<link href=\"http://code.jquery.com/ui/1.10.2/themes/$default_ui_theme/jquery-ui.css\" rel=\"stylesheet\" type=\"text/css\" />" ?><script src="http://code.jquery.com/ui/1.10.2/jquery-ui.min.js"></script><script src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script><script src="timepicker.js"></script>
	<script>
        // start up 
        var map  ;
        var timepickertarget = 0 ;
        
      	$(document).ready(function(){
					
// update TouchDown alert
function touchdownalert()
{
	$.getJSON("td_alert.php", function(td_alert){
		$("#rt_msg").empty();
		if( td_alert.length>0 ) {
			var txt="";
			for(var i=0;i<2&&i<td_alert.length;i++) {
				if( i>0 ) txt+="\n" ;
				txt+=td_alert[i].dvr_name + " : "+td_alert[i].description ;
			}
			$("#rt_msg").text(txt);
		}
	});
}
touchdownalert();

            $(window).resize(function(){
               var workarea = $("#workarea") ;
		       var nh = window.innerHeight - workarea.offset().top -$("#footer").outerHeight() - 32 ;
               if( nh != workarea.height() ) {	// height changed
        		  workarea.height( nh );
               }
			});
            $( "#datepicker" ).datepicker();
            $( "#datepicker" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
           
			setTimepicker( $("#speedlimit"), [ "10 mph", "20 mph", "30 mph"] );

            setTimeout( 'loadmap();' , 200);
		});
        
	
        function loadmap() {
          $("#workarea").css("min-height", $("#rpanel").height()+"px").css("margin-right", $("#rpanel").outerWidth()+"px"); 
          $(window).resize();
          map = new Microsoft.Maps.Map(document.getElementById("tdcmap"), 
           	{credentials: <?php echo '"'. $map_credentials . '"'; ?> ,
            enableSearchLogo: false,
            enableClickableLogo: false,
        	center: new Microsoft.Maps.Location(39.718, -104.9786),
	   		zoom: 9
            });

          Microsoft.Maps.loadModule('Microsoft.Maps.Search', { callback: searchModuleLoaded });

      }
        
      function searchModuleLoaded()
      {
        var searchManager = new Microsoft.Maps.Search.SearchManager(map);
		var where = 'Denver,CO'; 
    	var request = 
    	{ 
        	where: where, 
        	count: 1,
        	callback: onGeocodeSuccess
    	}; 
    	searchManager.geocode(request); 
      }
     
	  function onGeocodeSuccess(result, userData) 
	  { 
     	if (result) { 
        	var topResult = result.results && result.results[0]; 
        	if (topResult) { 
            	map.setView({ center: topResult.location, zoom: 12 }); 
        	} 
    	} 
	  } 

	</script>
	<style type="text/css">
      #generate {
        font-size:20px;
      }
     
      .evicon {
        width: 16px; height: 16px;
      }
	</style>
</head>
<body>
<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#2F4F4F;"><span style="font-size: 14px;margin-right:24px;"><?php echo $_SESSION['welcome_name'] ;?></span></span><span><a href="logout.php" style="background-color:#98bf21;text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#800080;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>TOUCH DOWN CENTER <?php echo $_SESSION['release']; ?></span></span></span></div>

<div id="lpanel"><img alt="index.php" src="res/side-TD-logo-clear.png" />
	<p style="text-align: center;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
<ul style="list-style-type:none;margin:0;padding:0;">
	<li><a class="lmenu" href="dashboardlive.php"><img onmouseout="this.src='res/side-dashboard-logo-clear.png'" onmouseover="this.src='res/side-dashboard-logo-fade.png'" src="res/side-dashboard-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="mapview.php"><img onmouseout="this.src='res/side-mapview-logo-clear.png'" onmouseover="this.src='res/side-mapview-logo-fade.png'" src="res/side-mapview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="reportview.php"><img onmouseout="this.src='res/side-reportview-logo-clear.png'" onmouseover="this.src='res/side-reportview-logo-fade.png'" src="res/side-reportview-logo-clear.png" /> </a></li>
	<li><a class="lmenu" href="videos.php"><img onmouseout="this.src='res/side-videos-logo-clear.png'" onmouseover="this.src='res/side-videos-logo-fade.png'" src="res/side-videos-logo-clear.png" /> </a></li>
	<li><img src="res/side-livetrack-logo-green.png" /></li>
	<li><a class="lmenu" href="settings.php"><img onmouseout="this.src='res/side-settings-logo-clear.png'" onmouseover="this.src='res/side-settings-logo-fade.png'" src="res/side-settings-logo-clear.png" /> </a></li>
</ul>
</div>

<div id="mcontainer">
<div id="title">
<div id="rt_msg_container">
<pre id="rt_msg">
 
</pre>
</div>
<strong><span style="font-size:26px;">LIVE TRACK</span></strong></div>

<div id="rcontainer">
<div id="rpanel"><!-- timepicker example --><select id="timepicker" size="8" style="position:absolute;display:none;"><option></option> </select>

<form action="" id="livetrackform">
<div><strong><span style="font-size:14px;">Select One or More Vehicles:</span></strong></div>

<div><input name="timerange" type="radio" value="0" />By Vehicle <input name="timerange" type="radio" value="1" />By Group</div>

<div style="text-align: center;"><select name="vehicles" size="8" style="width:90%;"></select><select name="groups" size="8" style="display:none;width:90%;"></select></div>

<div>&nbsp;</div>

<div style="text-align: center;"><input name="reset" type="button" value="Get Current Position" /></div>

<div>&nbsp;</div>
</form>
</div>

<div id="workarea" style="width:auto;">
<div id="tdcmap">Bing Maps</div>
</div>
</div>
<!-- mcontainer --></div>

<div id="footer">
<hr />
<div id="footerline" style="padding-left:24px;padding-right:24px">
<div style="float:left"><span style="color:#800080;font-size: 11px;"><?php
echo date("Y-m-d H:i:s") ;
?> </span></div>

<p style="text-align: right;"><span style="font-size:11px;"><a href="http://www.247securityinc.com/" style="text-decoration:none;">247 Security Inc.</a></span></p>
</div>
</div>
</body>
</html>