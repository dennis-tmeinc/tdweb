// a very simple timepicker
//     by Dennis
// note
//     jquery must be included before using this script

	var timepickertarget = 0 ;
	var timepicker_init = 0 ;
	
	$("#timepicker").hide();    
		
	function setTimepicker( picker, pickoptions )
	{
	    if( timepicker_init == 0 ) {
			$("#timepicker").change(function(){
				if( timepickertarget ) {
					$(timepickertarget).val($(this).val()) ;
				}
				$(this).hide("200");
				timepickertarget=0;
			});
			
			$("#timepicker").blur(function() {
				setTimeout("timepicker_afterblur();",10);
			});

		    timepicker_init = 1 ;
		}
		
		picker.data( "timepickeroptions", pickoptions );
			
		picker.focus(function(){
			if( timepickertarget == this ) {
				return ;
			}
			timepickertarget=this ;
			// init timepicker
			var timepicker = $("#timepicker") ;
			timepicker.empty();
			var options = $(this).data("timepickeroptions");
			var i;
			for(i=0 ; i<options.length;i++) {
				timepicker.append("<option>" + options[i] + "</option>") ;
			}
			if( i>8 ) { i=8 ; }
			timepicker.attr("size", i);
			timepicker.css("top",""+( $(this).position().top+$(this).outerHeight() )+"px");
			timepicker.css("left",""+$(this).position().left+"px");
			timepicker.show("200");
		});
				
		picker.blur(function(){
			setTimeout("timepicker_afterblur();",10);
		});
	}
	
	function timepicker_afterblur() {
		if( timepickertarget ) {
			var f = $(":focus");
			if( f[0] == $("#timepicker")[0] ) 
				return ;
			if( f[0] == timepickertarget) 
				return ;
			$("#timepicker").hide("200");
			timepickertarget=0;
		}
	}

