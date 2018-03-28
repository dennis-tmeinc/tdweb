<?php

header("X-livesetup-id: 10341234124\r\n\r\n" );

if( empty( $_SERVER["CONTENT_LENGTH"] ) ) {
   
}

echo "<pre>" ;

if( $_SERVER["CONTENT_LENGTH"] > 0 ) {
   echo "PUT --start\r\n" ;
   
	$putdata = fopen("php://input", "r");
	/* Read the data 1 KB at a time
		and write to the file */
	$put = '' ;
	while ($data = fread($putdata, 1024)) {
		$put .=$data ;
	}
	fclose($putdata);

   echo "PUT --end\r\n" ;

	echo "PUT: == $put ==\r\n" ;
}

echo "VAR: _SERVER:\r\n" ;
var_dump($_SERVER);

echo "VAR: _REQUEST:\r\n" ;
var_dump($_REQUEST);

echo "VAR: _POST:\r\n" ;
var_dump($_POST);

echo "VAR: _FILES:\r\n" ;
var_dump($_FILES);

echo "</pre>" ;


?>