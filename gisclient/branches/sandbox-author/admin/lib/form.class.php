<?php 

Class Form
{
	const objectSeparator="|";
	var $type;							//
	var $name;							
	var $mode;
	var $iniFile;
	
	public $controls=Array();
	var $groupedControls=Array();
	var $language;
	var $labels;

	var $titolo;							//stringa del titolo puo essere il titolo esplicito o il nome del campo che contiene il titolo
	var $button_menu;						//pulsante da inserire nella riga di intestazione della tabella "nuovo" o "modifica"
	var $array_hidden;						//array con l'elenco dei campi nascosti
	
	var $array_dati;						//array associativo campo=>dato con i dati da visualizzare
	var $num_record;						//numero di record presenti in array_dati
	var $curr_record;						//bookmark al record corrente di array_dati
	
	var $schemadb;
	var $tabelladb;							//nome della tabella o vista sul db dalla quale estraggo i dati
	//var $campi_obb;						// array con l'elenco dei campi obbligatori (non serve qui)
	var $tab_config;						//vettore che definisce la configurazione della tabella. La dimensione corrisponde al numero di righe per le tabelle H o al numero di colonne per le tabelle V
											//ogni elemento Ã¨ un vettore con un elemento per la tabella V e un numero di elementi pari al numero di campi sulla stessa riga per le tabelle H 
	var $num_col;							// numero di colonne di tab_config
	var $elenco_campi;						//elenco dei campi per la select 
	var $pkeys;								//elenco delle primary keys
	var $pkeys_value;						//elenco dei valori delle chiavi primarie
	var $db;								//puntatore a connessione a db da vedere se usare classe di interfaccia.....
	var $display_number=-1;
	var $table_length;
	var $table_height;
	
	public function __construct($name,$type="form",$mode="view"){
		$this->language = GCAuthor::getLang();
		$rel_dir = GCAuthor::getTabDir();
		$name=(pathinfo($name, PATHINFO_EXTENSION)=='ini')?($name):($name.".ini");
		$this->name=$name;
		$this->iniFile=ROOT_PATH.$rel_dir.$name;
		$this->type=($type=="grid")?("grid"):("form");
		$this->mode=($mode=="view")?("view"):($mode);
		//$this->mode=$mode;
		try{
			$tmp=parse_ini_file($this->iniFile,true);
		}
		catch(Exception $e){
			echo "<p>Errore di configurazione, file $name non esistente!</p>";
			return;
		}	
		
		
		
		
		
		$data=$tmp[$this->type."-mode"];
		$dataLang=$tmp["language-".$this->language];
		
		//$this->FileTitle=(!empty($tmp[$mylang]["title"][$mode]))?$tmp[$mylang]["title"][$mode]:0;
		
		//ACQUISIZIONE DELLA TABELLA E DELLO SCHEMA 
		if(preg_match("|([\w]+)[.]{1}([\w()]+)|i",trim($data["table"]),$tmp)){
			$this->tabelladb=$tmp[2];
			$this->schemadb=$tmp[1];
		}
		else{
			$this->tabelladb=trim($data["table"]);
			$this->schemadb=DB_SCHEMA;		
		}
		
		$pkeys=(trim($data["pkey"]))?(explode(",",trim($data["pkey"]))):(Array("id"));
		
		$campi=$pkeys;
		for($i=0;$i<count($pkeys);$i++) $this->pkeys[$pkeys[$i]]="";
		$this->init($data,$dataLang);
		//$ncol=count($data["fields"]);
		/*for ($i=0;$i<$ncol;$i++){//comincio da 1 perchÃ¨ sulla prima riga ho il nome della tabella e i campi obbligatori
			$d=$data["fields"][$i];
			if (strtoupper(CHAR_SET) != 'UTF-8') $d=utf8_decode($d);
			$row[]=explode('|',$d);//array di configurazione delle tabelle
		}
		$tmp_ncol=$ncol;
		$tmp_nrow=0;
		for ($i=0;$i<$tmp_ncol;$i++){
			$tmp_nrow=max($tmp_nrow,count($row[$i]));
			for ($j=0;$j<count($row[$i]);$j++){ //ogni elemento puÃ² avere un numero di elementi arbitrario
				list(,$campo,,$tipo)=array_pad(explode(';',$row[$i][$j]), 4, null);
				$tipo=trim($tipo);
				if (($tipo!="submit") && ($tipo!="button"))
					if (!in_array($campo,$campi) && $campo) $campi[]=$campo;
			}
		}
		$this->function_param=(!empty($data["fun_prm"]))?explode("#",$data["fun_prm"]):array();
		$this->num_col=$ncol;
		$this->colspan=$tmp_nrow;
		$this->elenco_campi=implode(",",$campi);
		$this->tab_config=$row;
		$this->config_file=$config_file;
		$this->order_fld=(!empty($data["order_fld"]))?implode(",",explode("#",$data["order_fld"])):array();
		$this->icons_view=(!empty($data["icons_view"]))?($data["icons_view"]):"";
		$this->buttons=(!empty($data["buttons"]))?($this->get_buttons($data["buttons"])):"";
		if (!empty($data["table_length"]) && is_numeric($data["table_length"])){
			$this->table_length=(int)$data["table_length"];
		}
		if (!empty($data["table_height"]) && is_numeric($data["table_height"])){
			$this->table_height=(int)$data["table_height"];
		}
		//$this->getLabels($data_mode);*/
	}
	public function __destruct(){
		
	}
	private function init($data,$dataLang){
		$ctrs=$this->setControls($data["fields"]);
		$this->setButtons($data["buttons"]);
		$this->setTitle($dataLang["title"]);
	}
	
	
	private function setTitle($arr){
		$this->title=$arr[$this->mode];
	}
	private function setGroupedControls($arr){
		for($i=0;$i<count($arr);$i++){
			$group=$arr[$i];
			list($id,$fields)=explode(",",$group);
			$fieldList=explode(self::objectSeparator,$fields);
			$this->groupedControls[$id]=$fieldList;
			
		}
	}
	private function setControls($arr) {
		$ctr=Array();
		for($i=0;$i<count($arr);$i++){
			$row=$arr[$i];
			$cols=explode(self::objectSeparator,trim($row));
			for($j=0;$j<count($cols);$j++){
				$params=explode(",",trim($cols[$j]));
				for($k=0;$k<count($params);$k++){
					list($key,$value)=explode(":",trim($params[$k]));
					$ctr[$i][$j][trim($key)]=trim($value);
				}
				//print_array($ctr[$i][$j]);
				try{
					$ctr[$i][$j]["label"]=(in_array("label",array_keys($ctr[$i][$j])))?($ctr[$i][$j]["label"]):(self::getLabel($ctr[$i][$j]["id"]));
				}
				catch(Exception $e){
					echo "<p>".$ctr[$i][$j]["id"]."</p>";
				}
				$this->controls[$ctr[$i][$j]["id"]]=$ctr[$i][$j];
				$this->ctrPos[$i][$j]=$ctr[$i][$j];
			}
		}
		return $ctr;
	}
	
	private function setButtons($arr){
		if(is_array($arr) && count($arr)){
			for($i=0;$i<count($arr);$i++){
				$btns=$arr[$i];
				$params=explode(",",$btns);
				for($k=0;$k<count($params);$k++){
					list($key,$value)=explode(":",$params[$k]);
					$btn[$key]=$value;
				}
				$this->buttons[$btn["id"]]=$btn;
				
			}
		}
	}
	
	private function getListeners($arr){
		
	}
	private function getLabel($key){
		$result=(empty($this->labels[$key]))?(''):($this->labels[$key]);
		return $result;
	}
	private function setLabels($arr){
		$this->labels=$arr["language-".self::language];
	}
	public function setData($mode,$data){
		if (strtolower($mode)=='data'){
			$this->array_dati=array(0=>$data);
			$this->num_record=count($data);
			$this->curr_record=0;
		}
		else{
			
		}
	}
	public function getForm(){
		$result=Array(
			"type"=>$this->type,
			"mode"=>$this->mode,
			"buttons"=>$this->buttons,
			"fields"=>$this->ctrPos,
			"title"=>$this->title,
			"action"=>Array()
		);
		return json_encode($result);
	}
}
?>