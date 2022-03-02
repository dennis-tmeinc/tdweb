// a very simple list picker
//     by Dennis
// note
//     jquery must be included before using this script

// target: target element 
// pickoptions: array of options
$.fn.picker=function(pickoptions, pickfunc)
{
	var target=this;
	target.attr( "autocomplete", "off");
	var pickerid=target.attr("id")+"_picker";
	var picker=$("select#"+pickerid);
	if(picker.length>0) picker.remove();
	target.after('<select size="8" style="position:absolute;z-index:1;display:none" id="'+pickerid+'"></select>');
	picker=$("select#"+pickerid);
	for(var i=0 ; i<pickoptions.length;i++) {
		picker.append("<option>" + pickoptions[i] + "</option>") ;
	}
	picker.attr("size", (i>10)?10:i);
	
	function picker_show(){
		if( picker.filter(":animated").length>0 ){
			return ;
		}
		else if( picker.filter(":visible").length>0 ){
			picker.hide("slow");
			return ;
		}	
		picker.css("top",""+( target.position().top+target.outerHeight() )+"px");
		picker.css("left",""+target.position().left+"px");
		picker.css("min-width",""+target.width()+"px");
		picker.show("slow");
	}
	function picker_hide(){
		picker.hide("slow");
	}
	
	target.click(function(e){
		e.preventDefault();
		picker_show();
	});
	target.focus(function(e){
		e.preventDefault();
		picker_show();
	});

	target.on( "blur", function(e){
		function picker_delayblur(){
			if($(":focus")[0] != picker[0]) {
				picker_hide();
			}
		}
		setTimeout(picker_delayblur,1);
	});
	
	picker.on( "blur", function(){
		picker_hide();
	});

	picker.on( "change", function(e){
		v = picker.val();
		if( pickfunc && typeof pickfunc == "function" && pickfunc(v) )  {
			picker_hide();
		}
		target.val(v);
	}) ;
}
