<!DOCTYPE html>
<html>
<head><?php 
require_once "config.php" ;

// clear map filter
// session_save('mapfilter', array() );

?>
<title>Touch Down Center</title>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<meta name="description" content="Touch Down Center by TME">
<meta name="author" content="Dennis Chen @ TME, 2013-05-15">		
<link href="tdclayout.css" rel="stylesheet" type="text/css" /><script src="https://code.jquery.com/jquery-<?php echo $jqver; ?>.js"></script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" /> <script src="jq/jquery-ui.js"></script><script> if(window['jQuery']==undefined)document.write('<script src="jq/jquery.js"><\/script><link href="jq/jquery-ui.css" rel="stylesheet" type="text/css" \/><script src="jq/jquery-ui.js"><\/script>');</script><script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol'></script><script src="picker.js"></script>
<script src="jq/live.js"></script>
<style type="text/css"><?php echo "#rcontainer { display:none }" ?>
</style>
<script>
$( document ).ready(function() {
	$("button#play").click(function(){
		dashplay( $("video#videoarea")[0], "99999", 1 );
		
	});
	$("button#stop").click(function(){
		dashstop( $("video#videoarea")[0] );
		
	});
});
</script>
</head> 

<body> 

<p id="txt">text</p>

<video id="videoarea" poster="res/247logo.jpg" width="480" height="360" src="" type="video/mp4" controls>
Your browser does not support the video tag.
</video> 
<p/>
<button id="play" > Play </button>
<button id="stop" > Stop </button>


</body>
</html>