<?php
/*
$s="id:label,fields:[srid,maxscale,minscale],width:300";
preg_match("/\[(.+)\]/",$s,$arr);
$s=str_replace($arr[0],'@@@@@@',$s);
$params=explode(",",$s);
for($k=0;$k<count($params);$k++){
	list($key,$value)=explode(":",$params[$k]);
	$value=str_replace('@@@@@@',$arr[0],$value);
	$ctr[$key]=$value;
}

echo "<pre>";*/
/*
$tmp=explode(":",$s);
$result[0]=$tmp[0];
$result[count($tmp)-1]=$tmp[count($tmp)-1];
for($i=1;$i<count($tmp)-1;$i++){
	$t=explode(",",$tmp[$i]);
	$key=array_pop($t);
	$value=implode(",",$t);
	$result[$i-1]=$result[$i-1].":".$value;
}
$result[$i-1]=$key.":".$result[$i-1];
print_r($ctr);
echo "</pre>";*/
include_once "../config/config.php";

?>
<script>
var dataStore=<?php print GCAuthor::getDataStore(Array('seldb_conntype','seldb_catalog'));?>;
</script>