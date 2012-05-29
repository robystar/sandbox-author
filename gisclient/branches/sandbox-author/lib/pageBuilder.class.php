<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pageBuilder
 *
 * @author marco
 */
require_once ROOT_PATH."lib/gcapp.class.php";

class pageBuilder {
	var $db;
	var $table;
	var $level;
	const arrMode=Array("view","new","edit");
	function __construct($mode="view",$level="home",$prms=null) {
		if(!in_array($mode,  pageBuilder::arrMode)){
			//Errore
		}
		$this->init($mode,$level);
	}
	function __destruct() {
		
	}
	private function init(){
		$dir= GCAuthor::getConfDir();
		if(!file_exists()){
			
		}
		try{
			$tmp=parse_ini_file($dir."page.ini");
			$pageDef=$tmp[$level][$mode];
		}
		catch(pageException $e){
			throw $e->setError();
			return;
		}
		
	}
	
}

class pageException extends Exception{
	public function __construct($message, $code=0){ 
		parent::__construct($message,$code); 
    }    

	public function setError(){
		
	}
}
?>
