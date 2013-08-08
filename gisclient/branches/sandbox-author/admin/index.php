<?php
if (!file_exists("../config/config.php")) die ("Manca setup");
include_once "../config/config.php";
//include_once ROOT_PATH."lib/i18n.php";
header("Content-Type: text/html; Charset=".CHAR_SET);
header("Content-Type: X-UA-Compatible; content='IE=Edge'");
header("Cache-Control: no-cache, must-revalidate, private, pre-check=0, post-check=0, max-age=0");
header("Expires: " . gmdate('D, d M Y H:i:s', time()) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Pragma: no-cache");
require_once ADMIN_PATH."lib/form.class.php";

$frm=new Form("layer","form","view");

?>
<html>
<head>
	<!--<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge" />-->
	<SCRIPT language="javascript" src="./js/LoadLibs.js" type="text/javascript"></SCRIPT>
	<script>
		var config=<?php print $frm->getForm();?>;
	</script>
	
	<SCRIPT language="javascript" src="./js/formViewer.js" type="text/javascript"></SCRIPT>
	<script>
		console.log(config);
		var x = new formViewer(config);
		console.log(x);
	</script>

</head>
<body>
	<!--<div id="container">
		<div class="ui-layout-north">
			
		</div>
		<div class="ui-layout-center">
			<div id="myform"></div>
			
		</div>
		<div class="ui-layout-south">
			GisClient<span class="color">Author</span> - &copy; 2012
		</div>
	</div>
	</div>
	<div id="error_dialog" style="display:none;color:red;"></div>-->
</body>
</html>