<?php

class GCApp {
	static private $db;
	static private $dataDBs = array();
	
	public static function getDB() {
		if(empty($db)) {
			$dsn = 'pgsql:dbname='.DB_NAME.';host='.DB_HOST;
			if(defined('DB_PORT')) $dsn .= ';port='.DB_PORT;
			try {
				self::$db = new PDO($dsn, DB_USER, DB_PWD);
				self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(Exception $e) {
				die('Impossibile connettersi al database');
			}
		}
		return self::$db;
	}
	
	public static function getDataDB($path) {
		if(empty(self::$dataDBs[$path])) {
			self::$dataDBs[$path] = new GCDataDB($path);
		}
		return self::$dataDBs[$path]->db;
	}
	
	public static function getDataDBSchema($path) {
		if(empty(self::$dataDBs[$path])) {
			self::$dataDBs[$path] = new GCDataDB($path);
		}
		return self::$dataDBs[$path]->schema;
	}
	
	public static function getDataDBParams($path, $param = null) {
		if(empty(self::$dataDBs[$path])) {
			self::$dataDBs[$path] = new GCDataDB($path);
		}
		if(!empty($param)) {
			return self::$dataDBs[$path]->$param;
		} else {
			return array(
				'schema'=>self::$dataDBs[$path]->schema,
				'db_name'=>self::$dataDBs[$path]->dbName,
			);
		}
	}

	public static function prepareInStatement($values) {

		$i = 0;
		$params = array();
		$inArr = array();
		foreach ($values as $v) {
			$pkey = sprintf(":key%d", $i);
			$inArr[] = $pkey;
			$params[$pkey] = $v;
			$i++;
		}
		return array('inQuery' => implode(',', $inArr), 'parameters' => $params);

	}
	
	public static function getNewPKey($dbschema, $schema, $table, $pkey, $start=null) {

	    $db = GCApp::getDB();
	    try {
		if (is_null($start)) {
		    $sql="select $dbschema.new_pkey(:scm, :tbl, :pkey);";
		    $stmt = $db->prepare($sql);
		    $stmt->execute(array('scm' => $schema, 'tbl' => $table, 'pkey' => $pkey));									
		} else {
		    $sql="select $dbschema.new_pkey(:scm, :tbl, :pkey, :start);";
		    $stmt = $db->prepare($sql);
		    $stmt->execute(array('scm' => $schema, 'tbl' => $table, 'pkey' => $pkey, 'start' => $start));							
		}
	    } catch (Exception $e) {
		GCError::registerException($e);
	    }	
	    print_debug($sql,null,"gcapp.class");
	    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	    return isset($row['new_pkey'])?$row['new_pkey']:null;
	}
	    
	
    public static function getUniqueRandomTmpFilename($dir, $prefix = '', $extension = '') {
        $letters = '1234567890qwertyuiopasdfghjklzxcvbnm';
        $lettersLength = strlen($letters) - 1;
        $filename = !empty($prefix) ? $prefix.'_' : '';
        for ($n = 0; $n < 20; $n++) {
            $filename .= $letters[rand(0, $lettersLength)];
        }
		if(!empty($extension)) $filename .= '.'.$extension;
        if (file_exists($filename))
            return self::getUniqueRandomTmpFilename($dir, $prefix, $extension);
        else
            return $filename;
    }
    
    public static function tableExists($dataDb, $schema, $tableName) {
        $sql = "select table_name from information_schema.tables ".
            " where table_schema=:schema and table_name=:table ";
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$tableName));
        return ($stmt->rowCount() > 0);
    }
    
    public static function getColumns($dataDb, $schema, $tableName) {
        $sql = "SELECT column_name from information_schema.columns " .
                "WHERE table_schema=:schema AND table_name=:table " .
                " ORDER BY ordinal_position";
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$tableName));
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

class GCDataDB {
	public $schema;
	public $db;
	public $dbName;
	
	function __construct($path) { //TODO: vedere per path diversi
		list($dbName, $schema) = explode('/', $path);
		
		$dsn = 'pgsql:dbname='.$dbName.';host='.DB_HOST;
		if(defined('DB_PORT')) $dsn .= ';port='.DB_PORT;
		try {
			$this->db = new PDO($dsn, DB_USER, DB_PWD);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(Exception $e) {
			throw $e;
		}
		$this->schema = $schema;
		$this->dbName = $dbName;
	}
}

class GCAuthor {
	static public $aInchesPerUnit = array(1=>39.3701,2=>12,3=>1,4=>39370.1,5=>39.3701,6=>63360,7=>4374754);
	static public $gMapResolutions = array(156543.0339,78271.51695,39135.758475,19567.8792375,9783.93961875,4891.969809375,2445.9849046875,1222.99245234375,611.496226171875,305.7481130859375,152.87405654296876,76.43702827148438,38.21851413574219,19.109257067871095,9.554628533935547,4.777314266967774,2.388657133483887,1.1943285667419434,0.5971642833709717,0.29858214168548586,0.14929107084274293,0.07464553542137146,0.03527776,0.01763888);
	static public $defaultScaleList = array(500000000,5000000,1000000,500000,250000,100000,50000,25000,10000,5000,2000,1000,900,800,700,600,500,400,300,200,100,50);
	
	static private $lang;
	static private $errors = array();
	
	public static function registerError($msg) {
		array_push(self::$errors, $msg);
	}
	
	public static function getErrors() {
		return self::$errors;
	}	
	
	public static function refreshMapfiles($project, $publish = false) {
		require_once ADMIN_PATH."lib/functions.php";
		require_once ADMIN_PATH.'lib/gcFeature.class.php';
		require_once ADMIN_PATH.'lib/gcMapfile.class.php';
		require_once ROOT_PATH."lib/i18n.php";
		
		$target = $publish ? 'public' : 'tmp';

		$mapfile = new gcMapfile();
		$mapfile->setTarget($target);
		$mapfile->writeMap("project",$project);
		
		$localization = new GCLocalization($project);
		$alternativeLanguages = $localization->getAlternativeLanguages();
		if($alternativeLanguages){
			foreach($alternativeLanguages as $languageId => $foo) {
				$mapfile = new gcMapfile($languageId);
				$mapfile->setTarget($target);
				$mapfile->writeMap('project', $project);
			}
		}
	}
	
	public static function refreshMapfile($project, $mapset, $publish = false) {
		require_once ADMIN_PATH."lib/functions.php";
		require_once ADMIN_PATH.'lib/gcFeature.class.php';
		require_once ADMIN_PATH.'lib/gcMapfile.class.php';
		require_once ROOT_PATH."lib/i18n.php";
		
		$target = $publish ? 'public' : 'tmp';

		$mapfile = new gcMapfile();
		$mapfile->setTarget($target);
		$mapfile->writeMap("mapset",$mapset);

		$localization = new GCLocalization($project);
		$alternativeLanguages = $localization->getAlternativeLanguages();
		if($alternativeLanguages){
			foreach($alternativeLanguages as $languageId => $foo) {
				$mapfile = new gcMapfile($languageId);
				$mapfile->setTarget($target);
				$mapfile->writeMap('mapset', $mapset);
			}
		}
	}
	
	public static function GCTypeFromDbType($dbType) {
		$typesMap = array(
			1=>array('varchar','text','char','bool','bpchar'),
			2=>array('int','int2','int4','int8','float','float4','float8','serial4','serial8'),
			3=>array('date','timestamp','timestamptz')
		);
		foreach($typesMap as $typeId => $types) {
			if(in_array($dbType, $types)) return $typeId;
		}
		return false;
	}
	
	public static function getLang() {
		if(empty(self::$lang)) {
			if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			} else {
				$langs=array('it','en','de','pt','fr','es');
			}
			
			if(!empty($_SESSION['AUTHOR_LANGUAGE'])) {
				self::$lang = $_SESSION['AUTHOR_LANGUAGE'];
			} else if(defined('FORCE_LANGUAGE')) {
				self::$lang = FORCE_LANGUAGE;
			} else {
				self::$lang = (!empty($_REQUEST["language"])) ? $_REQUEST["language"] : substr($langs[0],0,2);
			}
			$_SESSION['AUHTOR_LANGUAGE'] = self::$lang;
		}
		
		return self::$lang;
	}
	public static function getDataStore($ds=Array()){
		$rel_dir=ROOT_PATH."config/ini/";
		$db=gcApp::getDB();
		$tmp = parse_ini_file($rel_dir."datastore.ini",true);
		

		foreach($tmp as  $key=>$dataStore){
			if($ds==Array() || in_array($key,$ds)){
				$fieldList=Array();
				$otherFields="";
				if(!empty($dataStore["fields"])) for($i=0;$i<count($dataStore["fields"]);$i++) $fieldList[]='"'.$dataStore["fields"][$i].'"';
				if (@count($fieldList)) $otherFields=",".implode(",",$fieldList);
				$sql="SELECT ".$dataStore["valueField"]." as value,".$dataStore["displayField"]." as label $otherFields FROM gisclient_35.".$dataStore["table"].";";
				$stmt=$db->prepare($sql);
				$stmt->execute();
				$result[$key]=$stmt->fetchAll(PDO::FETCH_ASSOC);
			}
		}
		return json_encode($result);
	}
	
	public static function getTabDir() {
		$lang = self::getLang();
		$rel_dir="config/ini/forms/";
		/*if(!is_dir(ROOT_PATH.$rel_dir)) $rel_dir="config/tab/it/";*/
		if(defined('TAB_DIR')) $rel_dir="config/tab/".TAB_DIR."/";
		return $rel_dir;
	}
	public static function getConfDir() {
		$rel_dir=ROOT_PATH."config/ini/";
		return $rel_dir;
	}
	
	public static function getMapset($project,$mapsetName) {
		$db = GCApp::getDB();
		
		$sql = "select mapset_name, mapset_title, template, project_name from ".DB_SCHEMA.".mapset where project_name=? and mapset_name=?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($project,$mapsetName));
		$mapsets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($mapsets as &$mapset) {
			$url = defined('MAP_URL') ? MAP_URL : '';
			if(!empty($mapset['template'])) $url .= $mapset['template'];
			$url .= (strpos($url, '?') === false ? '?' : '&') . 'mapset='.$mapset['mapset_name'];
			$mapset['url'] = $url;
		}
		unset($mapset);
		
		return $mapsets[0];
	}
	
	public static function getMapsets($project) {
		$db = GCApp::getDB();
		
		$sql = "select mapset_name, mapset_title, template, project_name from ".DB_SCHEMA.".mapset where project_name=?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($project));
		$mapsets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($mapsets as &$mapset) {
			$url = defined('MAP_URL') ? MAP_URL : '';
			if(!empty($mapset['template'])) $url .= $mapset['template'];
			$url .= (strpos($url, '?') === false ? '?' : '&') . 'mapset='.$mapset['mapset_name'];
			$mapset['url'] = $url;
		}
		unset($mapset);
		
		return $mapsets;
	}
	
	public static function getTowsFeatures($project) {
		$db = GCApp::getDB();
		
		$sql = "select project_name, theme_title, layergroup_title, layer_title, layergroup_name || '.' || layer_name as feature_type from ".DB_SCHEMA.".layer ".
			" inner join ".DB_SCHEMA.".layergroup using(layergroup_id) ".
			" inner join ".DB_SCHEMA.".theme using(theme_id) ".
			" inner join ".DB_SCHEMA.".qtfield using(layer_id) ".
			" where layer.queryable=1 and qtfield.editable=1 and theme.project_name=? ".
			" group by project_name, theme_title, layergroup_title, layer_title, layer_id, feature_type ".
			" order by theme_title, layergroup_title, layer_title ";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($project));
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public static function t($key) {
		return self::translate($key);
	}
	
	public static function translate($key) {
		$dir = GCAuthor::getTabDir();
		$lang = GCAuthor::getLang();
		$words = parse_ini_file(ROOT_PATH.$dir."translations.ini",true);
		//return $words[$key];
		
		return $words[$lang][$key]; 
	}
	
	public static function getMessageTranslation() {
		$dir = GCAuthor::getTabDir();
		$lang = GCAuthor::getLang();
		$words = parse_ini_file(ROOT_PATH.$dir."translations.ini",true);
		//return json_encode($words); 
		return json_encode($words[$lang]); 
	}
	
}

class GCError {
	static private $errors = array();
	
	public static function register($msg) {
		array_push(self::$errors, $msg);
	}
	
	public static function get() {
		return self::$errors;
	}
	
	public static function registerException($e) {
	    array_push(self::$errors, $e->getMessage());
	}
}

class GCUtils {
	public static function parseBox($box) {
		$split = explode(',', str_replace(array('BOX(',')'), '', $box));
		list($l, $b) = explode(' ', $split[0]);
		list($r, $t) = explode(' ', $split[1]);
		return array($l, $b, $r, $t);
	}
}

class GCPage{
	public static function getConf($conf=null,$mode='view'){
		if (!empty($conf)){
			$dir = GCAuthor::getTabDir();
			$lang = GCAuthor::getLang();
			$arrConf= parse_ini_file($dir.'page.ini');
		}
		else{
			
		}
	}
}
