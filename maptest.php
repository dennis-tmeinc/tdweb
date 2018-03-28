<!DOCTYPE html>
<html>
    <head>
        <title>loadMapAsyncHTML</title>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    </head>
    <body>
        <div id='printoutPanel'></div>
        
        <div id='myMap' style='width: 100vw; height: 100vh;'></div>
        <script type='text/javascript'>
                var map;
                function loadMapScenario() {
                    map = new Microsoft.Maps.Map(document.getElementById('myMap'), {
                        credentials: "<?php require_once "config.php" ; echo $map_credentials ?>"
                    });
                }
                
            
        </script>
        <script type='text/javascript' src='http://www.bing.com/api/maps/mapcontrol?branch=release&callback=loadMapScenario' async defer></script>
    </body>
</html>