/*
 * Live preivew through webtun
 *
 * Copyright (c) 2016 dennis @ TME
 */
 
function dashplay( video, phone, camera )
{
	var mimeCodec = "video/mp4";
	var loading = false ;
	var sn=0 ;
	var init=1 ;
	
	var mediaSource = new MediaSource;
	
	function dashreload()
	{
		// try restart loading video
		setTimeout( function() {
			dashplay( video, phone, camera )
		}, 10000);
	} 

	function dashload() {
		if( !loading && mediaSource.readyState == "open" ) {
			loading = true ;
			var url = "mp4live.php?phone="+phone+"&camera="+camera+"&sn="+sn ;
			sn++;
			var xhr = new XMLHttpRequest;
			xhr.open('get', url);
			xhr.responseType = 'arraybuffer';
			if( init ) {
				xhr.setRequestHeader("x-mime", 1);
			}
			xhr.onreadystatechange = function (){
				if( xhr.readyState == 4 ) {
					if( xhr.status == 200 && xhr.response.byteLength > 1 )
					{
						var mimetype = xhr.getResponseHeader("x-dash-mimetype");
						if( mimetype ) {
							mimeCodec = mimetype ;
							init = 0;
						}
						var codecs = xhr.getResponseHeader("x-dash-codecs");
						if( codecs ) {
							mimeCodec = mimeCodec + "; codecs=\"" + codecs + "\"" ;
						}
						dashaddBuf(xhr.response) ;
					}
					else {
						loading = false ;
						init=1 ;
						// error? try reload after 15s
						setTimeout( dashload, 10000);
					}
				}
			};
			xhr.send(null);
		}
	};

	function dashaddBuf( vbuf ) {
		if( mediaSource.readyState == "open" ) {
			var sourceBuffer;
			if( mediaSource.sourceBuffers.length > 0 ) {
				sourceBuffer = mediaSource.sourceBuffers[0] ;
			}
			else {
				sourceBuffer = mediaSource.addSourceBuffer(mimeCodec);
				sourceBuffer.mode = "sequence" ;
				sourceBuffer.onupdateend = function() {
					if(  sourceBuffer.buffered.length>0 ) {
						var start = sourceBuffer.buffered.start(0)  ;
						var xtime = video.currentTime - 15 ;
						if( xtime > start ) {
							sourceBuffer.remove(start, xtime ) ;
						}
					}
				};
				sourceBuffer.onerror = function(e){
					mediaSource.endOfStream();
				};
			}
			
			if( !video.error && !sourceBuffer.updating ) {
				sourceBuffer.appendBuffer(vbuf);
			}
			else {
				dashreload();
			}			
			loading = false ;
		}
	};		

	function videoUpdate() 
	{
		if( mediaSource.readyState == "open" && mediaSource.sourceBuffers.length>0 && video.currentTime>=0 ) {
			var sourceBuffer = mediaSource.sourceBuffers[0] ;
			var timeleft =  video.duration - video.currentTime ;
			if( ! sourceBuffer.updating && !loading && timeleft < 3 && !video.paused ) {
				dashload() ;
			}
		}
	}
	
	mediaSource.onsourceopen = function() {
		dashload();
	};

	video.ontimeupdate = videoUpdate ;
	video.onerror = dashreload ;
	video.oncanplay = function() 
	{
		try{
			if( video.paused ) {
				video.play() ;
				video.controls=false ;
			}
		}
		catch(e){
			video.controls = true ;
		}
	};
	video.src = URL.createObjectURL(mediaSource);
}

function dashstop( video )
{
	if( !video.paused ) video.pause() ;
	video.ontimeupdate = null ;
	video.onerror = null ;
	video.src = "" ;
}
