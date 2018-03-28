<!DOCTYPE html>
<html>
<head>
	<title>CKEditor</title>
	<meta charset="utf-8">
	<script src="/ckeditor/ckeditor.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
	<?php

$loadfilename='' ;
$file=@fopen("cksave","r");
if( $file != FALSE ) {
    $loadfilename = fread($file, 500 );
	fclose($file);
}
			
if (!empty($_POST))
{
	foreach ( $_POST as $key => $value )
	{
		if ( ( !is_string($value) && !is_numeric($value) ) || !is_string($key) )
			continue;

		if ( $key == "load_file" ) {
		    $loadfilename=$value ;
			break;
		}
	}
}

?>
	

	<script>
	$(document).ready(function(){
	
		CKEDITOR.replace( 'tdc_editor', {
			fullPage: true,
			allowedContent: true,
			uiColor :  "#0BCA44",
			autoGrow_maxHeight: 450,
			height: 450
		});
		
         $("#loadfile").click(function(){
            var fname=$("#load_file").val() ;
			$.get('ck_load.php', {load_file:fname}, function(data) {
			    loadeditor(data);
            });
		 });
		 
         $("#savefile").click(function(){
			$("#editorform").submit();
		 });

	});
	
	function loadeditor( html )
	{
		CKEDITOR.instances.tdc_editor.on( 'autogrow', function(e){
			alert('auto grow');
		});
		CKEDITOR.instances.tdc_editor.setMode( 'wysiwyg' );
	    CKEDITOR.instances.tdc_editor.setData( html );
	}
	
	function savehtml() 
	{
		var data=CKEDITOR.instances.tdc_editor.getData();
		var fname=$("#load_file").val() ;
		$.post('ck_save.php', {save_file:fname,tdc_editor:data}, function(d) {
			alert( fname+"  Saved:\n" + data);
		});
	}
	
	</script>
</head>
<body>
<h3>CKEditor - Customized for Touch Down Center</h3>
<p>
File Name: <input id="load_file" name="load_file" size="40" value="<?php echo $loadfilename; ?>" />
<button id="loadfile" type="button">Load File</button>
<button id="savefile" type="button">Save File</button>
</p>

<div id="container" >	
<form id="editorform" action="javascript:savehtml()" >
<textarea id="tdc_editor" name="tdc_editor" >
empty
</textarea>
</form>
</div>

</body>
</html>
