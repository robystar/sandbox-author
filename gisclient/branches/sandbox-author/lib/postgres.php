<?php
  /***************************************************************************
   *                               postgres7.php
   *                            -------------------
   *   begin                : Saturday, Feb 13, 2001
   *   copyright            : (C) 2001 The phpBB Group
   *   email                : supportphpbb.com
   *
   *   $Id: postgres7.php,v 1.19 2002/03/05 02:19:38 psotfx Exp $
   *
   ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/
/*
if(!defined("SQL_LAYER"))
{

define("SQL_LAYER","postgresql");
*/
define('BEGIN_TRANSACTION', 1);
define('END_TRANSACTION', 0);

class sql_db
{

	var $db_connect_id;
	var $query_result;
	var $in_transaction = 0;
	var $row = array();
	var $rowset = array();
	var $rownum = array();
	var $num_queries = 0;
	var $error_message = array();

	//
	// Constructor
	//
	function sql_db($sqlserver, $sqluser, $sqlpassword, $database, $persistency = true)
	{
		$this->connect_string = "";

		if( $sqluser )
		{
			$this->connect_string .= "user=$sqluser ";
		}

		if( $sqlpassword )
		{
			$this->connect_string .= "password=$sqlpassword ";
		}

		if( $sqlserver )
		{
			if( preg_match("/:/", $sqlserver) )
			{
				list($sqlserver, $sqlport) = preg_split("/:/", $sqlserver);
				$sqlport=($sqlport)?($sqlport):("5432");
				$this->connect_string .= "host=$sqlserver port=$sqlport ";
			}
			else
			{
				if( $sqlserver != "localhost" )
				{
					$this->connect_string .= "host=$sqlserver ";
				}
			}
		}

		if( $database )
		{
			$this->dbname = $database;
			$this->connect_string .= "dbname=$database";
		}

		$this->persistency = $persistency;
		$this->db_connect_id = ( $this->persistency ) ? pg_pconnect($this->connect_string) : pg_connect($this->connect_string);

		return ( $this->db_connect_id ) ? $this->db_connect_id : false;
	}

	//
	// Other base methods
	//
	function sql_close()
	{
		if( $this->db_connect_id )
		{
			//
			// Commit any remaining transactions
			//
			if( $this->in_transaction )
			{
				@pg_exec($this->db_connect_id, "COMMIT");
			}

			if( $this->query_result )
			{
				@pg_freeresult($this->query_result);
			}

			return @pg_close($this->db_connect_id);
		}
		else
		{
			return false;
		}
	}

	//
	// Query method
	//
	function sql_query($query = "", $transaction = false)
	{
		//
		// Remove any pre-existing queries
		//
		unset($this->query_result);
		if( $query != "" )
		{
			$this->num_queries++;

			$query = preg_replace("/LIMIT ([0-9]+),([ 0-9]+)/", "LIMIT \\2 OFFSET \\1", $query);

			if( $transaction == BEGIN_TRANSACTION && !$this->in_transaction )
			{
				$this->in_transaction = TRUE;

				if( !@pg_query($this->db_connect_id, "BEGIN") )
				{
					return false;
				}
			}
			//echo "DB" ;print_r($this);
			
			if (pg_send_query($this->db_connect_id, $query)){
				$this->query_result = pg_get_result($this->db_connect_id);
				$error_code=pg_result_error_field($this->query_result, PGSQL_DIAG_SQLSTATE);
			}
			//$this->query_result = @pg_query($this->db_connect_id, $query);
			if(!$error_code)
			{
				if( $transaction == END_TRANSACTION )
				{
					$this->in_transaction = FALSE;

					if( !@pg_query($this->db_connect_id, "COMMIT") )
					{
						@pg_query($this->db_connect_id, "ROLLBACK");
						return false;
					}
				}

				$this->last_query_text[$this->query_result] = $query;
				$this->rownum[$this->query_result] = 0;

				unset($this->row[$this->query_result]);
				unset($this->rowset[$this->query_result]);

				return $this->query_result;
			}
			else
			{
				if( $this->in_transaction )
				{
					@pg_query($this->db_connect_id, "ROLLBACK");
				}
				$this->in_transaction = FALSE;
				/*pg_send_query($this->db_connect_id, $query);
				$result = pg_get_result($this->db_connect_id);
				$code=pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);*/
				$this->error_message[]=Array("code"=>$error_code,"text"=>pg_result_error($this->query_result),"query"=>$query);
				return false;
			}
		}
		else
		{
			if( $transaction == END_TRANSACTION && $this->in_transaction )
			{
				$this->in_transaction = FALSE;

				if( !@pg_query($this->db_connect_id, "COMMIT") )
				{
					@pg_query($this->db_connect_id, "ROLLBACK");
					return false;
				}
			}

			return true;
		}
	}

	//
	// Other query methods
	//
	function sql_numrows($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? @pg_numrows($query_id) : false;
	}

	function sql_numfields($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? @pg_numfields($query_id) : false;
	}

	function sql_fieldname($offset, $query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? @pg_fieldname($query_id, $offset) : false;
	}

	function sql_fieldtype($offset, $query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? @pg_fieldtype($query_id, $offset) : false;
	}

	function sql_fetchrow($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		if($query_id)
		{
			$this->row = @pg_fetch_array($query_id, $this->rownum[$query_id]);

			if( $this->row )
			{
				$this->rownum[$query_id]++;
				return $this->row;
			}
		}

		return false;
	}

	function sql_fetchrowset($query_id = 0)
	{			
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		if( $query_id )
		{
			unset($this->rowset[$query_id]);
			unset($this->row[$query_id]);
			$this->rownum[$query_id] = 0;
			$result = array();
			while( $this->rowset = @pg_fetch_array($query_id, $this->rownum[$query_id], PGSQL_ASSOC) )
			{
				$result[] = $this->rowset;
				$this->rownum[$query_id]++;
			}

			return $result;
		}

		return false;
	}

	function sql_fetchfield($field, $row_offset=-1, $query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		if( $query_id )
		{
			if( $row_offset != -1 )
			{
				$this->row = @pg_fetch_array($query_id, $row_offset, PGSQL_ASSOC);
			}
			else
			{
				if( !empty($this->rownum[$query_id]) )
				{
					$this->row = @pg_fetch_array($query_id, $this->rownum[$query_id]-1, PGSQL_ASSOC);
				}
				else
				{
					$this->row = @pg_fetch_array($query_id, $this->rownum[$query_id], PGSQL_ASSOC);

					if( $this->row )
					{
						$this->rownum[$query_id]++;
					}
				}
			}

			return $this->row[$field];
		}

		return false;
	}

	function sql_rowseek($offset, $query_id = 0)
	{

		if(!$query_id)
		{
			$query_id = $this->query_result;
		}

		if( $query_id )
		{
			if( $offset > -1 )
			{
				$this->rownum[$query_id] = $offset;
				return true;
			}
			else
			{
				return false;
			}
		}

		return false;
	}

	function sql_nextid()
	{
		$query_id = $this->query_result;

		if($query_id && $this->last_query_text[$query_id] != "")
		{
			if( preg_match("/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-.]+)/is", $this->last_query_text[$query_id], $tablename) )
			{
				$query = "SELECT currval('". $tablename[1] . "_id_seq') AS last_value";
				$temp_q_id =  pg_exec($this->db_connect_id, $query);
				if( !$temp_q_id )
				{
					return false;
				}

				$temp_result = @pg_fetch_array($temp_q_id, 0, PGSQL_ASSOC);

				return ( $temp_result ) ? $temp_result['last_value'] : false;
			}
		}

		return false;
	}

	function sql_affectedrows($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? @pg_cmdtuples($query_id) : false;
	}

	function sql_freeresult($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		return ( $query_id ) ? @pg_freeresult($query_id) : false;
	}

	function sql_error($query_id = 0)
	{
		if( !$query_id )
		{
			$query_id = $this->query_result;
		}

		$result['message'] = @pg_errormessage($this->db_connect_id);
		//echo "<p> Errore.<br>".pg_last_error ($this->db_connect_id)."</p>";;
		//$result['message'] = @pg_result_error ($query_id);
		$result['code'] = -1;

		return $result;
	}
	
	
	//Aggiunto roby restituisce una lista relativa al campo
	function sql_fetchlist($field){
		$rowset=$this->sql_fetchrowset();
		if(!$rowset) return;
		foreach ($rowset as $row)
			//$elenco[]=intval($row[$field]);
			$elenco[]=$row[$field];
		return $elenco;
	}
	
	function test(){
	
	echo ("eccoci!!!");
	
	}
	
	
	function quote($dirtyData) {
		return "'".pg_escape_string($dirtyData)."'";
	}
} // class ... db_sql

?>
