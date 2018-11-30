<?php
/**
 * SQL database access: mysqli version
 *
 * @package FormsFramework
 * @subpackage utils
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright &copy; 2000-2007, Samuele Diella
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link http://www.formsphpframework.com
 */

if (!defined("FF_DB_MONGO_SHARELINK")) define("FF_DB_MONGO_SHARELINK", true);
if (!defined("FF_DB_MONGO_SHUTDOWNCLEAN")) define("FF_DB_MONGO_SHUTDOWNCLEAN", false);

if (FF_DB_MONGO_SHUTDOWNCLEAN)
{
	register_shutdown_function("ffDB_MongoDB::free_all");
}

/**
 * ffDB_MongoDB è la classe preposta alla gestione della connessione con database di tipo SQL
 *
 * @package FormsFramework
 * @subpackage utils
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright &copy; 2000-2007, Samuele Diella
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link http://www.formsphpframework.com
 */
class ffDB_MongoDB
{
	var $locale = "ISO9075";

	// PARAMETRI DI CONNESSIONE
	var $database = null;
	var $user     = null;
	var $password = null;
	var $host     = null;
    var $replica  = null;
    var $auth     = null;

	//var $charset			= "utf8"; //"utf8";
	//var $charset_names		= "utf8"; //"utf8";
	//var $charset_collation	= "utf8_unicode_ci"; //"utf8_unicode_ci";

	// PARAMETRI DI DEBUG
	var $halt_on_connect_error		= true;		## Setting to true will cause a HALT message on connection error
	var $debug						= false;	## Set to true for debugging messages. It also turn on error reporting
	var $on_error					= "halt";	## "halt" (halt with message), "report" (ignore error, but spit a warning), "ignore" (ignore errors quietly)
	var $HTML_reporting				= true;		## Display Messages in HTML Format

	// PARAMETRI SPECIFICI DI MYSQL
	//var $persistent					= false;	## Setting to true will cause use of mysql_pconnect instead of mysql_connect

	var $transform_null				= true;

	// -------------------
	//  VARIABILI PRIVATE

	// VARIABILI DI GESTIONE DEI RISULTATI
	var $row			= -1;
	var $record			= false;

	/* public: current error number and error text */
	var $errno    = 0;
	var $error    = "";

	var $link_id  = false;
	var $query_id = false;
    var $query_params = array();

	var $fields						= null;
	var $fields_names				= null;

	private $num_rows 				= null;
	private $useFormsFramework		= false;
	private $keyname				= "_id";
	public 	$events 				= null;
	static protected $_events		= null;

	static $_profile 				= false;
	static $_objProfile 			= array();

	static $_dbs 					= array();
	static $_sharelink 				= FF_DB_MONGO_SHARELINK;

	private $buffered_insert_id 	= null;

	// COMMON CHECKS

 	public function __set ($name, $value)
 	{
 		if ($this->useFormsFramework)
 			ffErrorHandler::raise("property \"$name\" not found on class " . __CLASS__, E_USER_ERROR, $this, get_defined_vars());
 		else
 			die("property \"$name\" not found on class " . __CLASS__);
	}

	public function __get ($name)
	{
 		if ($this->useFormsFramework)
 			ffErrorHandler::raise("property \"$name\" not found on class " . __CLASS__, E_USER_ERROR, $this, get_defined_vars());
 		else
 			die("property \"$name\" not found on class " . __CLASS__);
	}

	public function __isset ($name)
	{
 		if ($this->useFormsFramework)
 			ffErrorHandler::raise("property \"$name\" not found on class " . __CLASS__, E_USER_ERROR, $this, get_defined_vars());
 		else
 			die("property \"$name\" not found on class " . __CLASS__);
	}

	public function __unset ($name)
	{
 		if ($this->useFormsFramework)
 			ffErrorHandler::raise("property \"$name\" not found on class " . __CLASS__, E_USER_ERROR, $this, get_defined_vars());
 		else
 			die("property \"$name\" not found on class " . __CLASS__);
	}

	public function __call ($name, $arguments)
	{
 		if ($this->useFormsFramework)
 			ffErrorHandler::raise("function \"$name\" not found on class " . __CLASS__, E_USER_ERROR, $this, get_defined_vars());
 		else
 			die("function \"$name\" not found on class " . __CLASS__);
	}
/*
	static public function __callStatic ($name, $arguments)
	{
 		if ($this->useFormsFramework)
 			ffErrorHandler::raise("function \"$name\" not found on class " . get_class($this), E_USER_ERROR, $this, get_defined_vars());
 		else
 			die("function \"$name\" not found on class " . get_class($this));
	}*/

	// STATIC EVENTS MANAGEMENT

	static public function addEvent($event_name, $func_name, $priority = null, $index = 0, $break_when = null, $break_value = null, $additional_data = null)
	{
		if (!class_exists("ffCommon", false))
			die(__CLASS__ . ": " . __FUNCTION__ . " method require Forms Framework");

		static::initEvents();
		static::$_events->addEvent($event_name, $func_name, $priority, $index, $break_when, $break_value, $additional_data);
	}

	static private function doEvent($event_name, $event_params = array())
	{
		static::initEvents();
		return static::$_events->doEvent($event_name, $event_params);
	}

	static private function initEvents()
	{
		if (static::$_events === null)
			static::$_events = new ffEvents();
	}

	/**
	 * This method istantiate a ffDB_MongoDB instance. When using this
	 * function, the resulting object will deeply use Forms Framework.
	 *
	 * @param string $templates_root
	 * @return ffDB_MongoDB
	 */
	public static function factory()
	{
		if (!class_exists("ffCommon", false))
			die(__CLASS__ . ": " . __FUNCTION__ . " method require Forms Framework");

		$res = static::doEvent("on_factory", array());

		$tmp = new static();
		$tmp->useFormsFramework = true;
		$tmp->events = new ffEvents();

		$res = static::doEvent("on_factory_done", array($tmp));

		return $tmp;
	}

	public static function free_all()
	{
        static::$_dbs = array();
	}

	// CONSTRUCTOR
	function __construct()
	{
		if (!static::$_sharelink)
			static::$_dbs[] = $this;
	}

	// -------------------------------------------------
	//  FUNZIONI GENERICHE PER LA GESTIONE DELLA CLASSE

	// LIBERA LA CONNESSIONE E LA QUERY
	function cleanup($force = false)
	{
		$this->freeResult();
		if (is_object($this->link_id))
		{
			if ($force || !static::$_sharelink)
			{
				if (static::$_sharelink)
				{
					$dbkey = $this->host . "|" . $this->auth;
					unset(static::$_dbs[$dbkey]);
				}
			}
		}
		$this->link_id                  = false;
		$this->errno                    = 0;
		$this->error                    = "";
	}

	// LIBERA LA RISORSA DELLA QUERY SENZA CHIUDERE LA CONNESSIONE
	function freeResult()
	{
		$this->query_id                 = false;
        $this->query_params             = array();
		$this->row                      = -1;
		$this->record                   = false;
		$this->num_rows                 = null;
		$this->fields                   = null;
		$this->fields_names             = null;
		$this->buffered_insert_id       = null;
	}

	// -----------------------------------------------
	//  FUNZIONI PER LA GESTIONE DELLA CONNESSIONE/DB

	// GESTIONE DELLA CONNESSIONE

	/**
	 * Gestisce la connessione al DB
	 * @param String Nome del DB a cui connettersi
	 * @param String Host su cui risiede il DB
	 * @param String username
	 * @param String password
	 * @return String
	 */
	function connect($Database = null, $Host = null, $User = null, $Password = null, $replica = false, $force = false)
	{
		// ELABORA I PARAMETRI DI CONNESSIONE
		if ($Host !== null)
			$tmp_host                           = $Host;
		else if ($this->host === null)
			$tmp_host                           = MONGO_DATABASE_HOST;
		else
			$tmp_host                           = $this->host;

		if ($User !== null)
			$tmp_user                           = $User;
		else if ($this->user === null)
			$tmp_user                           = MONGO_DATABASE_USER;
		else
			$tmp_user                           = $this->user;

		if ($Password !== null)
			$tmp_pwd                            = $Password;
		else if ($this->password === null)
			$tmp_pwd                            = MONGO_DATABASE_PASSWORD;
		else
			$tmp_pwd                            = $this->password;

		if ($Database !== null)
			$this->database                     = $Database;
		else if ($this->database === null)
			$this->database                     = MONGO_DATABASE_NAME;
        if ($replica !== null)
            $this->replica                      = $replica;

		$do_connect = true;

		// SOVRASCRIVE I VALORI DI DEFAULT
		$this->host                             = (is_array($tmp_host)
                                                    ? implode(",", $tmp_host)
                                                    : $tmp_host
                                                );
		$this->user                             = $tmp_user;
		$this->password                         = $tmp_pwd;

        $this->auth                             = ($this->user && $this->password
                                                    ? $this->user . ":" . $this->password . "@"
                                                    : ""
                                                );

        if (static::$_sharelink && !$force)
		{
			$dbkey = $this->host . "|" . $this->auth;
			if (is_array(static::$_dbs) && array_key_exists($dbkey, static::$_dbs))
			{
				$this->link_id =& static::$_dbs[$dbkey];
				$do_connect = false;
			}
		}

		if ($do_connect)
		{   if(class_exists("MongoDB\Driver\Manager"))
		    {
                $this->link_id = new MongoDB\Driver\Manager("mongodb://"
                    . $this->auth
                    . $this->host
                    . "/"
                    . $this->database
                    . ($this->replica
                        ? "?replicaSet=" . $this->replica
                        : "")
                );
                $rc = is_object($this->link_id);
            }
			if (!$rc)
			{
				if ($this->halt_on_connect_error)
					$this->errorHandler("Connection failed to host " . $this->host);

                $this->cleanup();
				return false;
			}

			if (static::$_sharelink)
			{
				static::$_dbs[$dbkey] = $this->link_id;
				$this->link_id =& static::$_dbs[$dbkey];
			}
		}

        return $this->link_id;

	}

	/**
	 * Seleziona il DB su cui effettuare le operazioni
	 * @param String Nome del DB
	 * @return Boolean
	 */

	function selectDb($Database = "")
	{
		if ($Database == "")
			$Database = $this->database;

		return true;
	}

	// -------------------------------------------
	//  FUNZIONI PER LA GESTIONE DELLE OPERAZIONI

	/**
	 * Esegue una query senza restituire un recordset
	 * @param String La query da eseguire
	 * @return boolean
	 */

    function insert($query, $table = null)
	{
        if(is_array($query) && !empty($query[0]))
        {
            foreach($query AS $mongoDB)
            {
                $mongoDB["action"] = "insert";
                if($table)
                    $mongoDB["table"] = $table;

                $res = $this->execute($mongoDB);
            }
        } else {
            if(is_array($query))
                $mongoDB["insert"] = $query;
            else
                $mongoDB = $this->sql2mongoDB($query);

            if($table)
                $mongoDB["table"] = $table;

            $mongoDB["action"] = "insert";
            $res = $this->execute($mongoDB);
        }

        return $res;
    }
    function update($query, $table = null)
	{
        if(is_array($query) && !empty($query[0]))
        {
            foreach($query AS $mongoDB)
            {
                $mongoDB["action"] = "update";
                if($table)
                    $mongoDB["table"] = $table;

                $res = $this->execute($mongoDB);
            }
        } else {
            if(is_array($query))
                $mongoDB = $query;
            else
                $mongoDB = $this->sql2mongoDB($query);

            if($table)
                $mongoDB["table"] = $table;

            $mongoDB["action"] = "update";
            $res = $this->execute($mongoDB);
        }

        return $res;
    }
    function delete($query, $table = null)
	{
        if(is_array($query) && !empty($query[0]))
        {
            foreach($query AS $mongoDB)
            {
                $mongoDB["action"] = "delete";
                if($table)
                    $mongoDB["table"] = $table;

                $res = $this->execute($mongoDB);
            }
        } else {
            if(is_array($query))
                $mongoDB["where"] = $query;
            else
                $mongoDB = $this->sql2mongoDB($query);

            if($table)
                $mongoDB["table"] = $table;

            $mongoDB["action"] = "delete";
            $res = $this->execute($mongoDB);
        }

        return $res;
    }

	function execute($query)
	{
		if (empty($query))
			$this->errorHandler("Execute invoked With blank Query String");

		if (!$this->link_id)
		{
			if (!$this->connect())
				return false;
		}

        if(is_array($query))
            $mongoDB = $query;
        else
            $mongoDB = $this->sql2mongoDB($query);

        if(!$mongoDB["action"])
        {
            if(!empty($mongoDB["insert"]))
                $mongoDB["action"] = "insert";
            elseif(!empty($mongoDB["set"]))
                $mongoDB["action"] = "update";
            elseif(!empty($mongoDB["delete"]))
                $mongoDB["action"] = "delete";
            elseif(!empty($mongoDB["from"]))
                $mongoDB["action"] = "select";
        }

        $this->freeResult();
		$this->debugMessage($mongoDB["action"] . " = " . print_r($query, true));

		if($mongoDB["where"][$this->keyname])
			$mongoDB["where"][$this->keyname] = $this->id2object($mongoDB["where"][$this->keyname]);

        switch($mongoDB["action"]) {
            case "insert":
                if(!$mongoDB["table"])
                    $mongoDB["table"] = $mongoDB["into"];

                if($mongoDB["table"] && $mongoDB["insert"])
                {
                	if(!$mongoDB["insert"][$this->keyname])
                		$mongoDB["insert"][$this->keyname] = $this->createObjectID();

                    $bulk = new MongoDB\Driver\BulkWrite;
                    $bulk->insert($mongoDB["insert"]);
                    try {
						$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk);
					} catch (Exception $e) {
						$this->errorHandler("Server Error: " . $e->getMessage());
					}
					$this->buffered_insert_id = $mongoDB["insert"][$this->keyname];
                }
                break;
            case "update":
                if(!$mongoDB["table"])
                    $mongoDB["table"] = $mongoDB["update"];

                if($mongoDB["table"] && $mongoDB["set"])
                {
					$set = array();
					foreach ($mongoDB["set"] AS $key => $value) {
						if(strpos($key, '$') === 0) {
							$set[$key] = $value;
						} else {
							$set['$set'][$key] = $value;
						}
					}

                    if(!is_array($mongoDB["where"]))
                        $mongoDB["where"] = array();

					$mongoDB["options"]["multi"] = true;
					//$mongoDB["options"]["upsert"] = true;

					$bulk = new MongoDB\Driver\BulkWrite;
                    $bulk->update($mongoDB["where"], $set, $mongoDB["options"]);

					try {
						$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk);
					} catch (Exception $e) {
						$this->errorHandler("Server Error: " . $e->getMessage());
					}

					//$this->buffered_insert_id = null;
                }
                break;
            case "delete":
                if(!$mongoDB["table"])
                    $mongoDB["table"] = $mongoDB["delete"];

                if($mongoDB["table"] && $mongoDB["where"])
                {
                    if(!is_array($mongoDB["where"]))
                        $mongoDB["where"] = array();
                    if(!is_array($mongoDB["options"]))
                        $mongoDB["options"] = array();

                    $bulk = new MongoDB\Driver\BulkWrite;
                    $bulk->delete($mongoDB["where"], $mongoDB["options"]);
                    try {
                    	$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk);
					} catch (Exception $e) {
						$this->errorHandler("Server Error: " . $e->getMessage());
					}

					//$this->buffered_insert_id = null;
                }
                break;
            case "select":
            case "";
                $this->query($mongoDB);
                break;
            default;
        }

		return true;
	}

	function eachAll($callback)
	{
		if (!$this->query_id)
		{
			$this->errorHandler("eachAll called with no query pending");
			return false;
		}

		$res = $this->getRecordset();

		$last_ret = null;
		foreach ($res as $row => $record)
		{
			$last_ret = $callback($row, $record, $last_ret);
		}

		return $last_ret;
	}

	function eachNext($callback)
	{
		if (!$this->query_id)
		{
			$this->errorHandler("eachAll called with no query pending");
			return false;
		}

		$last_ret = null;
		if ($this->nextRecord())
		{
			do
			{
				$last_ret = $callback($this->row, $this->record, $last_ret);
			} while ($this->nextRecord());
		}

		return $last_ret;
	}

	function getRecordset()
	{
		if (!$this->query_id)
		{
			$this->errorHandler("eachAll called with no query pending");
			return false;
		}

		try {
			$cursor = $this->link_id->executeQuery($this->database . "." . $this->query_params["table"], new MongoDB\Driver\Query($this->query_params["where"], $this->query_params["options"]));
			if (!$cursor)
			{
				$this->errorHandler("fetch_assoc_error");
				return false;
			}
			else
			{
				$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
				$res = $cursor->toArray();
				foreach ($res AS $key => $value) {
					$res[$key]["_id"] = $res[$key]["_id"]->__toString();
				}
			}
		} catch (Exception $e) {
			$this->errorHandler("Server Error: " . $e->getMessage());
		}
		return $res;
	}

	/**
	 * Esegue una query
	 * @param String La query da eseguire
	 * @return L'id della query eseguita
	 */
	function query($query)
	{
		if (empty($query))
			$this->errorHandler("Query invoked With blank Query String");

		if (!$this->link_id)
		{
			if (!$this->connect())
				return false;
		}

		$this->freeResult();
		$this->debugMessage("query = " . print_r($query, true));

        if(is_array($query))
            $mongoDB = $query;
        else
            $mongoDB = $this->sql2mongoDB($query);

        if(!$mongoDB["action"])
        {
            if(!empty($mongoDB["from"]))
                $mongoDB["action"] = "select";
            if(!empty($mongoDB["insert"]))
                $mongoDB["action"] = "insert";
            elseif(!empty($mongoDB["set"]))
                $mongoDB["action"] = "update";
            elseif(!empty($mongoDB["delete"]))
                $mongoDB["action"] = "delete";

        }

		if($mongoDB["where"][$this->keyname])
			$mongoDB["where"][$this->keyname] = $this->id2object($mongoDB["where"][$this->keyname]);

        switch($mongoDB["action"]) {
            case "insert":
            case "update":
            case "delete":
                $this->execute($mongoDB);
                break;
            case "select":
            case "";
                if(!$mongoDB["table"])
                    $mongoDB["table"] = $mongoDB["from"];

                if($mongoDB["table"])
                {
                    $this->query_params = array(
                        "table" => $mongoDB["table"]
                        , "where" => ($mongoDB["where"]
                            ? $mongoDB["where"]
                            : array()
                        )
                        , "options" => ($mongoDB["options"]
                            ? $mongoDB["options"]
                            : array()
                        )
                    );

                    if($mongoDB["select"])
                        $this->query_params["options"]["projection"] = $mongoDB["select"];
                    if($mongoDB["sort"])
                        $this->query_params["options"]["sort"] = $mongoDB["sort"];
                    if($mongoDB["limit"])
                        $this->query_params["options"]["limit"] = $mongoDB["limit"];

                    //unset($this->query_params["where"]["uid"]);
                    //print_r($this->query_params["where"]);
                    //die();
					try {
						$cursor = $this->link_id->executeQuery($this->database . "." . $this->query_params["table"], new MongoDB\Driver\Query($this->query_params["where"], $this->query_params["options"]));
						if (!$cursor)
						{
							$this->errorHandler("Invalid SQL: " . print_r($query, true));
							return false;
						}
						else
						{
							$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

							$this->query_id = new \IteratorIterator($cursor);
							$this->query_id->rewind(); // Very important
							//$this->num_rows = iterator_count(new \IteratorIterator($this->link_id->executeQuery($this->database . "." . $this->query_params["table"], new MongoDB\Driver\Query($this->query_params["where"], $this->query_params["options"]))));
						}
					} catch (Exception $e) {
						$this->errorHandler("Server Error: " . $e->getMessage());
					}
                }
                break;
            default;
        }

        return $this->query_id;
	}



	function multiQuery($queries)
	{
	    if(iis_array($queries) && count($queries))
        {
            foreach($queries AS $query)
            {
                if (empty($query))
                    $this->errorHandler("Query invoked With blank Query String");

                if (!$this->link_id)
                {
                    if (!$this->connect())
                        return false;
                }

                $this->freeResult();
                $this->debugMessage("query = " . print_r($query, true));

                if(is_array($query)) {
                    $mongoDB = $query;
                } else {
                    $mongoDB = $this->sql2mongoDB($query);
                }

                //bulk
                //query
                //command
                switch($mongoDB["action"]) {
                    case "insert":
                        //$bulk->insert(ARRAY_DI_VALORI);
                        $bulk = new MongoDB\Driver\BulkWrite;
                        $bulk->insert($query);
                        try {
                        	$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk);
						} catch (Exception $e) {
							$this->errorHandler("Server Error: " . $e->getMessage());
						}

						break;
                    case "update":
                        //$bulk->update(CONDIZIONE, array('$set' => ARRAY_DI_VALORI), OPZIONI);
                        $bulk = new MongoDB\Driver\BulkWrite;
                        $bulk->update($query);
                        try {
							$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk);
						} catch (Exception $e) {
							$this->errorHandler("Server Error: " . $e->getMessage());
						}
						break;
                    case "delete":
                        //$bulk->delete(CONDIZIONE, OPZIONI);
                        $bulk = new MongoDB\Driver\BulkWrite;
                        $bulk->delete($query);
						try {
							$this->link_id->executeBulkWrite($this->database . "." . $mongoDB["table"], $bulk);
						} catch (Exception $e) {
							$this->errorHandler("Server Error: " . $e->getMessage());
						}
						break;
                    case "select":
                    case "";
                    	try {
							$cursor = $this->link_id->executeQuery($this->database . "." . $mongoDB["table"], new MongoDB\Driver\Query($mongoDB["sql"]));
							if (!$cursor)
							{
								$this->errorHandler("Invalid SQL: " . print_r($query, true));
								return false;
							}
							else
							{
								$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

								$this->query_id = new \IteratorIterator($cursor);
								$this->query_id->rewind(); // Very important
								$this->num_rows = iterator_count(new \IteratorIterator($this->link_id->executeQuery($this->database . "." . $mongoDB["table"], new MongoDB\Driver\Query($mongoDB["select"]))));

							}
						} catch (Exception $e) {
							$this->errorHandler("Server Error: " . $e->getMessage());
						}
                        break;
                    default;
                }

                $queryId[] = $this->query_id;
            }
        }

        return $queryId;
	}

	function lookup($tabella, $chiave = null, $valorechiave = null, $defaultvalue = null, $nomecampo = null, $tiporestituito = null, $bReturnPlain = false)
	{
		if (!$this->link_id)
		{
			if (!$this->connect())
				return false;
		}
		/*else
		{
			if (!$this->selectDb())
				return false;
		}*/

		if ($tiporestituito === null)
			$tiporestituito = "Text";

		if (strpos(strtolower(trim($tabella)), "select") !== 0)
		{
			$listacampi = "";

			if(is_array($nomecampo))
			{
				$valori = array();
				if (!count($nomecampo))
					$this->errorHandler("lookup: Nuessun campo specificato da recuperare", E_USER_ERROR, $this, get_defined_vars());

				foreach ($nomecampo as $key => $value)
				{
					if (strlen($listacampi))
						$listacampi .= ", ";
					$listacampi .= "`" . $key . "`";
				}
				reset($nomecampo);
			}
			elseif ($nomecampo !== null)
			{
				$listacampi = "`" . $nomecampo . "`";
			}
			else
				$listacampi = "*";

			$sSql = "SELECT " . $listacampi . " FROM " . $tabella . " WHERE 1 ";
		}
		else
			$sSql = $tabella;
		if(is_array($chiave))
		{
			if (!count($chiave))
				$this->errorHandler("lookup: Nuessuna chiave specificata per il lookup");

			foreach ($chiave as $key => $value)
			{
				if (is_object($value) && get_class($value) != "ffData")
						$this->errorHandler("lookup: Il valore delle chiavi dev'essere di tipo ffData od un plain value", E_USER_ERROR, $this, get_defined_vars());

				$sSql .= " AND `" . $key . "` = " . $this->toSql($value);
			}
			reset($chiave);
		}
		elseif ($chiave != null)
		{
			if (is_object($valorechiave) && get_class($valorechiave) != "ffData")
				$this->errorHandler("lookup: Il valore della chiave dev'essere un oggetto ffData od un plain value", E_USER_ERROR, $this, get_defined_vars());

			$sSql .= " AND `" . $chiave . "` = " . $this->toSql($valorechiave);
		}

		$this->query($sSql);
		if ($this->nextRecord())
		{

			if(is_array($nomecampo))
			{
				$valori = array();
				if (!count($nomecampo))
					$this->errorHandler("lookup: Nuessun campo specificato da recuperare", E_USER_ERROR, $this, get_defined_vars());

				foreach ($nomecampo as $key => $value)
				{
					$valori[$key] = $this->getField($key, $value, $bReturnPlain);
				}
				reset($nomecampo);

				return $valori;
			}
			elseif ($nomecampo !== null)
			{
				return $this->getField($nomecampo, $tiporestituito, $bReturnPlain);
			}
			else
			{
				return $this->getField($this->fields_names[0], $tiporestituito, $bReturnPlain);
			}

		}
		else
		{
			if ($defaultvalue === null)
				return false;
			else
				return $defaultvalue;
		}
	}

	/**
	 * Sposta il puntatore al DB al record successivo (va chiamato almeno una volta)
	 * @return boolean
	 */
    function getRecord() {
        $this->record                   = $this->query_id->current();
        if($this->record) {
            $this->record["_id"]        = $this->record["_id"]->__toString();
			$this->fields_names         = array_keys($this->record);
			$this->fields               = array_fill_keys($this->fields_names, "");
        } else {
			$this->fields_names         = null;
			$this->fields               = null;
        }
        return $this->record;
    }

	function nextRecord()
	{
		if (!$this->query_id)
		{
			$this->errorHandler("nextRecord called with no query pending");
			return false;
		}

		if ($this->getRecord())
		{
			$this->row += 1;
            $this->query_id->next();
			return true;
		}
		else
		{
			return false;
		}
	}

	// SI POSIZIONA AD UN RECORD SPECIFICO
	function seek($pos = 0)
	{
		if (!$this->query_id)
		{
			$this->errorHandler("Seek called with no query pending");
			return false;
		}

		if (!@mysqli_data_seek($this->query_id, $pos))
		{
			$this->errorHandler("seek($pos) failed, result has  " . $this->numRows() . " rows");
			@mysqli_data_seek($this->query_id, 0);
			$this->row = -1;
			return false;
		}
		else
		{
			$this->record 	= @mysqli_fetch_assoc($this->query_id);
			$this->row 		= $pos;
			return true;
		}
	}

	// SI POSIZIONA AL PRIMO RECORD DI UNA PAGINA IDEALE
	function jumpToPage($page, $RecPerPage)
	{
		$totpage = ceil($this->numRows() / $RecPerPage);
		if ($page > $totpage)
			$page = $totpage;

		if ($page > 1)
			if ($this->seek(($page - 1) * $RecPerPage))
				return $page;

		return false;
	}

	// -------------------------
	//  WRAPPER PER L'API MySQL
	function affectedRows($bReturnPlain = false)
	{
		return $this->getInsertID($bReturnPlain);
	}

	/**
	 * Conta il numero di righe
	 * @return Il numero di righe
	 */
	function numRows()
	{
		if (!$this->query_id)
		{
			$this->errorHandler("numRows() called with no query pending");
			return false;
		}

		if ($this->num_rows === null) {
			try {
            	$this->num_rows = iterator_count(new \IteratorIterator($this->link_id->executeQuery($this->database . "." . $this->query_params["table"], new MongoDB\Driver\Query($this->query_params["where"], $this->query_params["options"]))));
			} catch (Exception $e) {
				$this->errorHandler("Server Error: " . $e->getMessage());
			}
        }
        return $this->num_rows;
	}

	/**
	 * Conta il numero di campi
	 * @return Il numero di campi
	 */
	function numFields()
	{
		if (!$this->query_id)
		{
			$this->errorHandler("numFields() called with no query pending");
			return false;
		}

		return count($this->fields);
	}

	function isSetField($Name)
	{
		if (!$this->query_id)
		{
			$this->errorHandler("isSetField() called with no query pending");
			return false;
		}

		if(isset($this->fields[$Name]))
			return true;
		else
			return false;
	}

	/* ----------------------------------------
	    FUNZIONI PER LA GESTIONE DEI RISULTATI

	    Ogni volta che verrÃƒÆ’Ã‚Â  restituito un valore da una query il tipo di valore dipenderÃƒÆ’Ã‚Â 
	    dal settaggio globale della classe "useFormsFramework".

	    Nel caso sia abilitato, verrÃƒÆ’Ã‚Â  restituito un oggetto di tipo ffData, nel caso
	    sia disabilitato verrÃƒÆ’Ã‚Â  restituito un plain value.
	    E' possibile forzare la restituzione di un plain value usando il parametro $bReturnPlain.

	    Nel caso in cui non si utilizzi Forms Framework, i data_type accettati saranno solo
	    "Text" (il default) e "Number".
	*/
	function getInsertID($bReturnPlain = false)
	{
		if (!$this->link_id)
		{
			$this->errorHandler("insert_id() called with no DB connection");
			return false;
		}

		$id = ($this->buffered_insert_id instanceof \MongoDB\BSON\ObjectID
			? $this->buffered_insert_id->__toString()
			: null
		);

		if (!$this->useFormsFramework || $bReturnPlain)
			return $id;
		else
			return new ffData($id, "Number", $this->locale);
	}

	/**
	 *
	 * @param String Nome del campo
	 * @param String Tipo di dato inserito
	 * @param <type> $bReturnPlain
	 * @return ffData Dato recuperato dal DB
	 */
	function getField($Name, $data_type = "Text", $bReturnPlain = false, $return_error = true)
	{
		if (!$this->query_id)
		{
			$this->errorHandler("f() called with no query pending");
			return false;
		}

		if(isset($this->fields[$Name]))
			$tmp = $this->record[$Name];
		else {
            if($return_error)
			    $tmp = "NO_FIELD [" . $Name . "]";
            else
                $tmp = null;
        }
		if (!$this->useFormsFramework || $bReturnPlain)
		{
			switch ($data_type)
			{
				case "Number":
					if(strpos($tmp, ".") === false)
						return (int)$tmp;
					else
						return (double)$tmp;
				default:
					return $tmp;
			}
		}
		else
			return new ffData($tmp, $data_type, $this->locale);
	}

	// PERMETTE DI RECUPERARE IL VALORE DI UN CAMPO SPECIFICO DI UNA RIGA SPECIFICA. NB: Name puÃƒÆ’Ã‚Â² essere anche un indice numerico
	function getResult($row, $Name, $data_type = "Text", $bReturnPlain = false)
	{
		if (!$this->query_id)
		{
			$this->errorHandler("result() called with no query pending");
			return false;
		}

		if ($row === null)
			$row = $this->row;

		if ($row !== $this->row)
		{
			$rc = $this->seek($row);
			if (!$rc)
				return false;
		}

		return $this->getField((is_numeric($Name) ? $this->fields_names[$Name] : $Name), $data_type, $bReturnPlain);
	}

	// ----------------------------------------
	//  FUNZIONI PER LA FORMATTAZIONE DEI DATI

	function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null)
	{
		if (!$this->link_id)
			$this->connect();

		if (is_array($cDataValue))
		{
			$this->errorHandler("toSql: Wrong parameter, array not managed.");
		}
		elseif (!is_object($cDataValue))
		{
			$value = $cDataValue;
		}
		else if (get_class($cDataValue) == "ffData")
		{
			if ($data_type === null)
				$data_type = $cDataValue->data_type;

			$value = $cDataValue->getValue($data_type, $this->locale);
		}
		else if (get_class($cDataValue) == "DateTime")
		{
			switch ($data_type)
			{
				case "Date":
					$tmp = new ffData($cDataValue, "Date");
					$value = $tmp->getValue($data_type, $this->locale);
					break;

				case "DateTime":
				default:
					$data_type = "DateTime";
					$tmp = new ffData($cDataValue, "DateTime");
					$value = $tmp->getValue($data_type, $this->locale);
			}
		}
		else
			$this->errorHandler("toSql: Wrong parameter, unmanaged datatype");

		if ($transform_null === null)
			$transform_null = $this->transform_null;

		switch ($data_type)
		{
			case "Number":
			case "ExtNumber":
				if (!strlen($value))
				{
					if ($transform_null)
						return 0;
					else
						return "null";
				}
				return $value;

			default:
				if (!strlen($value) && !$transform_null)
					return "null";

				if (!strlen($value) && ($data_type == "Date" || $data_type == "DateTime"))
					$value = ffData::getEmpty($data_type, $this->locale);

				if ($enclose_field)
					return "'" . $value . "'";
				else
					return $value;
		}
	}

	// ----------------------------------------
	//  GESTIONE ERRORI

	function debugMessage($msg)
	{
		if ($this->debug)
		{
			if ($this->HTML_reporting)
			{
				$tmp = "ffDB_MongoDB - Debug: $msg<br />";
			}
			else
			{
				$tmp = "ffDB_MongoDB - Debug: $msg\n";
			}
		}
		if(ffDB_MongoDB::$_profile) {
			ffDB_MongoDB::$_objProfile["total"]++;
			ffDB_MongoDB::$_objProfile[substr($msg, strpos($msg, "=") + 1, 60)][] = $msg;
		}
	}

    function errorHandler($msg)
	{
		if ($this->on_error == "ignore" && !$this->debug)
			return;

		if ($this->HTML_reporting)
		{
			$tmp = "ffDB_MongoDB - Error: $msg";

			if ($this->errno)
			{
				$tmp .= "<br />MySQL - Error #" . $this->errno . ": " . $this->error;
			}

			if (!$this->useFormsFramework)
			{
				print $tmp;
				if ($this->on_error == "halt")
					die("<br />ffDB_MongoDB - Error: Script Halted.<br />");
			}
			else
			{
				if ($this->on_error == "halt")
					$err_code = E_USER_ERROR;
				else
					$err_code = E_USER_WARNING;

				ffErrorHandler::raise($tmp, $err_code, $this, get_defined_vars());
			}
		}
		else
		{
			$tmp = "ffDB_MongoDB - Error: $msg";

			if ($this->errno)
			{
				$tmp .= "\nMySQL - Error #" . $this->errno . ": " . $this->error;
			}


			print $tmp;
			if ($this->on_error == "halt")
				die("ffDB_MongoDB - Error: Script Halted.\n");
		}
		return;
	}
	function createObjectID()
	{
		return new \MongoDB\BSON\ObjectID();
	}
	function getObjectID($value)
	{
		if ($value instanceof \MongoDB\BSON\ObjectID) {
			$res = $value;
		} else {
			try {
				$res = new \MongoDB\BSON\ObjectID($value);
			} catch (\Exception $e) {
				return false;
			}
		}
		return $res;
	}
	function id2object($keys) {
		if(is_array($keys)) {
			foreach($keys AS $subkey => $subvalue) {
				if(is_array($subvalue)) {
					foreach($subvalue AS $i => $key) {
						$ID = $this->getObjectID($key);
						if($ID)
							$res[$subkey][] = $ID;
					}
				} else {
					$ID = $this->getObjectID($subvalue);
					if($ID)
						$res[] = $ID;
				}
			}
		} else {
			$ID = $this->getObjectID($keys);
			if($ID)
				$res = $ID;
		}

		return $res;
	}
	### Handle where
    function equation2mg($exp) {
        # split operator
        $exp = trim($exp);
        if (!$exp) { return(''); }


        ### check for normal
        if (preg_match ('/(\w+).*?([<>=!]+)(.*)/i',$exp,$matches)) {
            $operator = $matches[2];

        ### check for is null and such
        } else if (preg_match ('/([^ ]+) +?(is +?null|is +?not +?null|like) *?(.*)/i',$exp,$matches)) {
            $operator = strtolower($matches[2]);
            #print_r ($matches);
            #print ($exp . " $operator <br/>");
        }

        $matches[3] = trim($matches[3]);
        if ($operator == '=') {
            $mg_equation = "{ " . $matches[1] . " : " . $matches[3] . " }";
        } elseif ($operator == '<') {
            $mg_equation = "{  " . $matches[1] . " : { '\$lt' : $matches[3] } }";
        } elseif ($operator == '>') {
            $mg_equation = "{  " . $matches[1] . " : { '\$gt' : $matches[3] } }";
        } elseif ($operator == '<=') {
            $mg_equation = "{  " . $matches[1] . " : { '\$lte' : $matches[3] } }";
        } elseif ($operator == '>=') {
            $mg_equation = "{  " . $matches[1] . " : { '\$gte' : $matches[3] } }";
        } elseif ($operator == 'is null') {
            $mg_equation = "{  " . $matches[1] . " : null }";
        } elseif ($operator == '!=') {
            $mg_equation = "{  " . $matches[1] . " : { '\$ne' : $matches[3] } }";
        } elseif ($operator == 'is not null') {
            $mg_equation = "{  " . $matches[1] . " : { '\$ne' : null } }";
        } elseif ($operator == 'like') {
            $a = $matches[1];
            $b = $matches[3];
            if (!preg_match('/[%_]/',$b)) {
                $mg_equation = "{ $a : $b }";
            } else {
                $b = trim($b);
                $b = preg_replace ("/(^['\"]|['\"]$)/","",$b); # 'text' -> text  or "text" -> text
                if (!preg_match ("/^%/",$b)) { $b = "^" . $b; }  # handles like 'text%' -> /^text/
                if (!preg_match ("/%$/",$b)) { $b .= "$"; }  # handles like '%text' -> /^text$/
                $b = preg_replace ("/%/",".*",$b);
                $b = preg_replace ("/_/",".",$b);
                $mg_equation = "{ $a : /$b/}";
            }
            #print ($exp . " $operator <br/>");
            #$this->errorHandler ("unsupported");
            #$mg_equation = "{  " . $matches[1] . " : { '\$ne' : null } ";
        } else {
            $this->errorHandler("Unknown operator '$operator' :  $exp");
        }

        return ($mg_equation);
    }

    function where2mg($str) {
        # Make infix stuff to polishstuff
        $arr = preg_split ('/ *?(\() *?| *?(\)) *?| +(and) +| +(or) +| +(not) +|(not)/i',$str,null,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        #print_r ($arr);
        $stack = array();
        $polish = array();
        $null = null;

        foreach ($arr as $val) {


            $val = trim($val);
            #echo "'$val'\n<br/>";
            switch (strtolower($val)) {

                case 'not' :
                    #while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] != 'and' || $stack[sizeof($stack) - 1] != 'or') ) {
                    #	$e = array_pop($stack);
                    #	$polish[] = $e;
                    #}
                    $stack[]= strtolower($val);
                    break;
                case 'or' :
                    while ((sizeof($stack) > 0) && ( $stack[sizeof($stack) - 1] == 'and' ) ) {
                        $e = array_pop($stack);
                        $polish[] = $e;
                    }
                    $stack[]= strtolower($val);
                    break;

                case 'and' :
                    $stack[]= strtolower($val);
                    break;

                case '(' :
                    $stack[]= $val;
                    break;

                case ')' :
                    while ((sizeof($stack) > 0) && ($stack[sizeof($stack) - 1] != '(') ) {
                        $e = array_pop($stack);
                        $polish[] = $e;
                    }
                    $null .= array_pop($stack);

                    break;
                default :
                    ### handle not
                    $polish[] = $val;
                    if (1) {
                    while ( (sizeof($stack) > 0) && $stack[sizeof($stack) - 1] == 'not' ) {
                        #echo "... $val";
                        $e = array_pop($stack);
                        $polish[] = $e;
                    }
                    }


                    break;

            }
        }

        # empty stack
        while ( sizeof($stack) > 0) {
            $e = array_pop($stack);
            $polish[] = $e;
        }


        #foreach ($polish as $key) { echo $key . " "; }
        #echo "<br/>";


        #### Polish stuff to mongo
        $tmpval = array();
        $cnt=0;
        $nextoper = '';
        $popcount = 2;
        $tmparr=array();
        $rs = null;
        foreach ($polish as $val) {

            $cnt++;
            switch (strtolower($val))  {
                case 'and' :
                case 'not' :
                case 'or' :

                    if ($val == 'or') { $mgoper = '$or'; }
                    if ($val == 'and') { $mgoper = '$and'; }
                    if ($val == 'not') {
                        #echo "not found";
                        $mgoper = '$not';
                        $popcount = 1;
                    }

                    if ($val == $polish[$cnt] && $popcount > 1 ) {
                        $popcount++;
                        #echo "same oper $val\n";
                        continue;
                    }

                    $tmparr2 = array();
                    for ($i = 1; $i<=$popcount; $i++) {
                        $tmparr2[]=array_pop($tmparr);
                    }
                    $operstring = join(", ",array_reverse($tmparr2));

                    if ($popcount==1) {
                        $e = $operstring;
                        ### Rewrite 'not' stuff
                        if ($val == 'not') {
                            # fx { b : { $ne : 5 } } = { b : 5 }
                                $e = preg_replace ('/{ (\w+) : ([\w\'"]+) }/','{ $1 : { $ne : $2 } }',$e);
                            # fx:  { a : 5 }  = { a : { $ne : 5 } };
                            ### if no change
                            if ($e == $operstring) {
                                $e = preg_replace ('/{ (\w+) : { \$ne : ([\w\'"]+) } }/','{ $1 : $2 }',$e);
                            }
                            echo $e;
                        } else {
                            $e = " { $mgoper : $operstring } ..";
                        }
                        $tmparr[] = $e;
                        $popcount=2;
                        continue;

                    } else {
                        $e = " { $mgoper : [  $operstring ] } ";
                    }

                    $popcount=2;

                    if (isset($polish[$cnt])) { # if stuff is still left
                        # push it back
                        $tmparr[] = $e;
                    } else {
                        # only called once
                        $rs .= $e;
                    }

                break;

                default :
                    $tmparr[] = $this->equation2mg($val);
                    #$tmparr[] = ($val);
                    break;
            }
        }
        foreach ($tmparr as $val) {
            $rs .= $val;
        }

        return ($rs);

    }
    function sql2mongoDB($sql) {
		# make as oneline
		$sql = stripslashes(trim($sql));
		$sql = preg_replace ('/\r\n?/',' ', ($sql));
		# remove ending ;'s
		$sql = preg_replace ('/;+$/','', ($sql));



		preg_match('/^(\w+) /i',$sql,$querytype);
		$query['querytype'] = strtolower($querytype[1]);

		### If select
		if ($query['querytype'] == "insert") {
            $query['action'] = "insert";
            $this->errorHandler("insert not supported yet");
        } else if ($query['querytype'] == "update") {
            $query['action'] = "update";
            $this->errorHandler("update not supported yet");
        } else if ($query['querytype'] == "delete") {
            $query['action'] = "delete";
            $this->errorHandler("delete not supported yet");
		} else if ($query['querytype'] == "select") {
            $query['action'] = "select";
			$findcommand = "find";

			preg_match('/select *?(.*?)from/i',$sql,$fields);
			preg_match('/select.*?from(.*?)($|where|group.*?by|order.*?by|limit|$)/i',$sql,$tables);
			preg_match('/select.*?from.*?where(.*?)(group.*?by|order.*?by|limit|$)/i',$sql,$where);
			preg_match('/select.*?from.*?(where|group.*?by|order.*?by|.*?).*?limit (.*?)$/i',$sql,$limit);
			preg_match('/select.*?from.*?order.*?by(.*?)(limit|$)/i',$sql,$orderby);


			$query['fields'] = split(',',$fields[1]);
			$query['tables'] = split(',',$tables[1]);
			if ($where[1]) { $query['where'][] = $where[1]; }
			if ($limit[2]) { $query['limit'] = $limit[2]; }
			if ($orderby[1]) { $query['orderby'] = $orderby[1]; }

			#echo "<pre>" . print_r ($orderby,1) , "</pre>";
			### Handle fields
			# remove spaces
            $mg_count = null;
            $mg_where = null;
            $mg_distinct = null;
			foreach ($query['fields'] as $key => $value) {
				if (preg_match ('/count\((.*?)\)/i',$value,$countmatch)) {
					# Special fields
					$mg_count .= ".count()";
					$countfield = $countmatch[1];
					if ($countfield != "*") {
						$mg_where .= " { $countfield : { '\$exists' : true } } ";
					}
				} elseif (preg_match ('/distinct (.*?) *$/i',$value,$distinctmatch)) {
					$distinctfield = $distinctmatch[1];
					$mg_distinct .= ".distinct('$distinctfield') (not working)";

				} else {
					# normal fields
					$query['fields'][$key] = trim ($value);
					$tmpfields[]= trim ($value);
				}
			}
			$query['fields'] = $tmpfields;
			if (sizeof($query['fields'] > 1) && $query['fields'][0] != '*') {

				if (is_array($query['fields']) && sizeof($query['fields'] > 1)) {
					$mg_fields = ',{' . join (':1,',$query['fields']) . ':1}';
				}
			}

			### Handle table

			if (sizeof($query['tables']) > 1) {
				$this->errorHandler ("only one table for now");
			} else {
				$mg_collection = trim($query['tables'][0]);
			}


			### Handle where
			if (is_array($query['where'])) {
				foreach ($query['where'] as $key) {
					# split operator
					$mg_where .= $this->where2mg($key);
				}
			}

			### Handle order by

			if ($query['orderby']) {
				$orderby = trim($query['orderby']);
				$arr = preg_split("/ +/",$orderby);
				$orderfield = $arr[0];
				$ordersort = strtolower($arr[1]);
				$mg_sort = '.sort( { ' . $orderfield . ' : ';

				if ($ordersort == 'asc') {
					$mg_sort .= "1";
				} elseif ($ordersort == 'desc') {
					$mg_sort .= "-1";
				} else {
					$this->errorHandler("desc or asc missing $mg_sort $orderby");
				}
				$mg_sort .= " } )";
			}


			### Handle limit
            $skipvalue = null;
			if ($query['limit']) {
				$limits = split (',', $query['limit']);
				$limitcnt=0;
				$rowstofind = 0;
				foreach ($limits as $value) {
					$value = trim($value);
					$limitcnt++;
					if ($limitcnt == 2) {
						$mg_skip = ".skip($value)";
						$mg_limit = ".limit($skipvalue)";
						$rowstofind = $skipvalue;
					} else {
						$mg_limit = ".limit($value)";
						$skipvalue = $value;
						$rowstofind = $skipvalue;
					}
				}
				if ($rowstofind==1) {
					$findcommand = "findOne";
				} else {
					$findcommand = "find";
				}
			}
            $query["table"] = $mg_collection;
            $query["json"] = "db.$mg_collection.$findcommand( $mg_where$mg_fields )$mg_distinct$mg_count$mg_skip$mg_sort$mg_limit";
		} else {
            $query['action'] = $query['querytype'];
			$this->errorHandler ("unsupported querytype for the time being: " . $query['querytype']);
		}

        return $query;
    }

}