var config={};
var libs = ['jquery.1.7.2.min', 'jquery.ui.1.8.20.custom.min', 'jquery.ui.layout', 'jquery.dataTables.min', 'jquery.dform-1.0.0.min'];
var libcss = ['start/jquery-ui-1.8.20.custom', 'layout', 'jquery.dataTables','styles'];

for (i in libcss) document.write('<LINK media="screen" href="css/'+libcss[i]+'.css" type="text/css" rel="stylesheet"></SCRIPT>');
for (i in libs) document.write('<SCRIPT language="javascript" src="js/'+libs[i]+'.js" type="text/javascript"></SCRIPT>');