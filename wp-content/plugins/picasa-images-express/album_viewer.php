<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="height:99%">
	<head >
		<title>Picasa Image Express</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script type="text/javascript" src="swfobject.js"></script>
		<script type="text/javascript">
			var flashvars = {};
			<?php 
				// Debug
				foreach($_GET AS $key => $value){
					if(substr($key,0,3) == "pie"){
						echo "\n\t\t\tflashvars.{$key} = \"$value\" ;";
					}
				}
				
			?>
			var params = {};
			params.allowscriptaccess = "always";
			var attributes = {};
			attributes.id = "flashUI";
			swfobject.embedSWF("picasa-image-express.swf", "myAlternativeContent", "100%", "100%", "9.0.0", false, flashvars, params, attributes);
		</script>
		<script type="text/javascript">
			
			function sendToEditor(htmlContent){
				parent.send_to_editor(htmlContent);
				//alert(htmlContent);
				parent.tb_remove();
				return false;
			}
		</script>
		<style type="text/css">
		<!--
		html,body{
			padding:0;
			margin:0;
		}
		
		html{
			height:99%;
		}
		
		body{
			height:100%;
		}
		-->
		</style>
	</head>
	<body style="height:100%">
	<?php 
		//Debug
		/*foreach($_GET AS $key => $value){
			if(substr($key,0,3) == "pie"){
				echo "\n\t\t\tflashvars.{$key} = \"$value\" ;";
			}
		}*/
	?>

		<div id="myAlternativeContent" style="padding:15px;">
			<h1>Flash Player not detected</h1>
			<p>Picasa Image Express requires a more recent version of Adobe Flash Player. <a href="http://www.adobe.com/go/getflashplayer" target="_blank" >Click here to upgrade</a></p>
		</div>
	</body>
</html>
