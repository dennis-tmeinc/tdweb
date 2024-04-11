<div id="header" style="text-align: right;"><span style="color:#006400;"><span style="font-size: 14px;"><span>Welcome </span></span></span><span style="color:#000;"><span style="font-size: 14px;margin-right:24px;">
<?php 
if( !empty($_SESSION['welcome_name'])) 
  echo $_SESSION['welcome_name'] ;
?></span></span><span><a href="logout.php" style="text-decoration:none;text-align:center;"> Logout </a></span><span  id="servertime" style="color:#0;font-size: 11px; margin-left:30px;margin-right:30px;"></span><span style="color:#B22222;"><span style="font-size: 12px;"><span>
<?php 
  echo $product_name ;
  if( !empty($_SESSION['release']) )
    echo  "  " .  $_SESSION['release'];
?></span></span></span></div>
