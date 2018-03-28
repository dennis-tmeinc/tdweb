<?php
// load raw file
// load_file: <file name>

if (!empty($_GET))
{
    $filename="test.php";
	foreach ( $_GET as $key => $value )
	{
		if ( $key == "load_file" ) {
		    $filename=$value ;
		    $file=fopen("ckfname","w");
			fwrite($file,$filename);
			fclose($file);
			
			$file=fopen($filename,"r");
			$text=fread($file,500000);
			$text = str_replace( "<?php", "<!--?php", $text );
			$text = str_replace( "?>", "?-->", $text );
			fclose($file);
			echo $text ;

			break;
		}
	}
}
?>
