<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs\storage\drivers;

use phpformsframework\libs\international\Data;
use phpformsframework\libs\Debug;
use phpformsframework\libs\Hook;
use phpformsframework\libs\Error;
use mysqli_result;
use phpformsframework\libs\storage\DatabaseDriver;

if (!defined("FF_DB_MYSQLI_SHARELINK")) define("FF_DB_MYSQLI_SHARELINK", true);
if (!defined("FF_DB_MYSQLI_SHUTDOWNCLEAN")) define("FF_DB_MYSQLI_SHUTDOWNCLEAN", false);
if (!defined("FF_DB_MYSQLI_RECONNECT")) define("FF_DB_MYSQLI_RECONNECT", true);
if (!defined("FF_DB_MYSQLI_AVOID_REAL_CONNECT")) define("FF_DB_MYSQLI_AVOID_REAL_CONNECT", true); // ATTENZIONE!!! Usare unicamente per oggetti in sola lettura


if (FF_DB_MYSQLI_SHUTDOWNCLEAN)
{
    register_shutdown_function("ffDB_Sql::free_all");
}

/**
 * ffDB_Sql ÃƒÂ¨ la classe preposta alla gestione della connessione con database di tipo SQL
 *
 * @package FormsFramework
 * @subpackage utils
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright &copy; 2000-2007, Samuele Diella
 * @license http://opensource.org/licenses/gpl-3.0.html
 * @link http://www.formsphpframework.com
 */
class MySqli implements DatabaseDriver
{
    //const SHARE_LINK                = FF_DB_MYSQLI_SHARELINK;
    //private static $singletons      = null;

    static $_dbs = array();
    static $_sharelink = FF_DB_MYSQLI_SHARELINK;

    public $locale                  = "ISO9075";
    public $charset			        = "utf8"; //"utf8";
    public $charset_names		    = "utf8"; //"utf8";
    public $charset_collation	    = "utf8_unicode_ci"; //"utf8_unicode_ci";

    // PARAMETRI DI CONNESSIONE
    private $database               = null;
    private $user                   = null;
    private $password               = null;
    private $host                   = null;

    // PARAMETRI DI DEBUG

    // PARAMETRI SPECIFICI DI MYSQL
    var $persistent					= false;	## Setting to true will cause use of mysql_pconnect instead of mysql_connect

    var $transform_null				= true;

    // -------------------
    //  VARIABILI PRIVATE

    // VARIABILI DI GESTIONE DEI RISULTATI
    var $row			= -1;
    var $record			= false;

    /* public: current error number and error text */
    public $errno                   = 0;
    public $error                   = "";

    /**
     * @var \mysqli
     */
    private $link_id                = false;
    /**
     * @var mysqli_result
     */
    private $query_id               = false;

    var $fields			= null;
    var $fields_names	= null;
    var $field_primary  = null;

    private $num_rows               = null;

    var $avoid_real_connect         = FF_DB_MYSQLI_AVOID_REAL_CONNECT;
    var $buffered_affected_rows     = null;
    var $buffered_insert_id         = null;

    var $reconnect                  = FF_DB_MYSQLI_RECONNECT;
    var $reconnect_tryed            = false;

    static public function addEvent($event_name, $func_name, $priority = null)
    {
        Hook::register("mysqli:" . $event_name, $func_name, $priority);
    }

    static private function doEvent($event_name, $event_params = array())
    {
        Hook::handle("mysqli:" . $event_name, $event_params);
    }

    /**
     * This method istantiate a ffDb_Sql instance. When using this
     * function, the resulting object will deeply use Forms Framework.
     *
     * @return MySqli
     */
    public static function factory()
    {
        $tmp = new static();

        static::doEvent("on_factory_done", array($tmp));

        return $tmp;
    }

    public static function free_all()
    {
        if (static::$_sharelink)
        {
            foreach (static::$_dbs as $key => $link)
            {
                @mysqli_kill($link, mysqli_thread_id($link));
                @mysqli_close($link);
            }
        }
        else
        {
            $tmp_keys = array_keys(static::$_dbs);
            foreach ($tmp_keys as $key)
            {
                static::$_dbs[$key]->cleanup(true);
            }
        }
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
    private function cleanup($force = false)
    {
        $this->freeResult();
        if (is_object($this->link_id))
        {
            if ($force || (!static::$_sharelink && !$this->persistent))
            {
                @mysqli_kill($this->link_id, mysqli_thread_id($this->link_id));
                @mysqli_close($this->link_id);
                if (static::$_sharelink)
                {
                    $dbkey = $this->host . "|" . $this->user . "|" . $this->password;
                    unset(static::$_dbs[$dbkey]);
                }
            }
        }
        $this->link_id = false;
        $this->errno = 0;
        $this->error = "";
    }

    // LIBERA LA RISORSA DELLA QUERY SENZA CHIUDERE LA CONNESSIONE
    private function freeResult()
    {
        if (is_object($this->query_id)) {
            @mysqli_free_result($this->query_id);
        }
        $this->query_id		= false;
        $this->row			= -1;
        $this->record		= false;
        $this->num_rows		= null;
        $this->fields		= null;
        $this->fields_names	= null;
        $this->field_primary= null;
        $this->buffered_affected_rows = null;
        $this->buffered_insert_id = null;
    }

    // -----------------------------------------------
    //  FUNZIONI PER LA GESTIONE DELLA CONNESSIONE/DB

    // GESTIONE DELLA CONNESSIONE

    /**
     * Gestisce la connessione al DB
     * @param String $Database nome del DB a cui connettersi
     * @param String $Host su cui risiede il DB
     * @param String $User
     * @param String $Password
     * @param bool $force
     * @return bool|\mysqli
     */
    public function connect($Database = null, $Host = null, $User = null, $Password = null, $force = false)
    {
        // ELABORA I PARAMETRI DI CONNESSIONE
        if ($Host !== null) {
            $tmp_host                               = $Host;
        } else if ($this->host === null) {
            $tmp_host                               = FF_DATABASE_HOST;
        } else {
            $tmp_host                               = $this->host;
        }
        if ($User !== null) {
            $tmp_user                               = $User;
        } else if ($this->user === null) {
            $tmp_user                               = FF_DATABASE_USER;
        } else {
            $tmp_user                               = $this->user;
        }
        if ($Password !== null) {
            $tmp_pwd                                = $Password;
        } else if ($this->password === null) {
            $tmp_pwd                                = FF_DATABASE_PASSWORD;
        } else {
            $tmp_pwd                                = $this->password;
        }
        if ($Database !== null) {
            $tmp_database                           = $Database;
        } else if ($this->database === null) {
            $tmp_database                           = FF_DATABASE_NAME;
        } else {
            $tmp_database                           = $this->database;
        }

        $do_connect                                 = true;
        $dbkey                                      = null;

        // CHIUDE LA CONNESSIONE PRECEDENTE NEL CASO DI RIUTILIZZO DELL'OGGETTO
        if (is_object($this->link_id))
        {
            if (
                ($this->host !== $tmp_host || $this->user !== $tmp_user || $this->database !== $tmp_database)
                || $force
            ) {
                $this->cleanup($force);
            } else {
                $do_connect = false;
            }
        }

        // SOVRASCRIVE I VALORI DI DEFAULT
        $this->host                                 = $tmp_host;
        $this->user                                 = $tmp_user;
        $this->password                             = $tmp_pwd;
        $this->database                             = $tmp_database;

        if (static::$_sharelink && !$force) {
            $dbkey = $this->host . "|" . $this->user . "|" . $this->database;
            if (is_array(static::$_dbs) && array_key_exists($dbkey, static::$_dbs)) {
                $this->link_id =& static::$_dbs[$dbkey];
                $do_connect = false;
            }
        }

        if ($do_connect) {
            if (!$this->avoid_real_connect) {
                if (!is_object($this->link_id)) {
                    $this->link_id = @mysqli_init();
                }
                if (!is_object($this->link_id) || $this->checkError()) {
                    $this->errorHandler("mysqli::init failed");
                    $this->cleanup();
                    return false;
                }

                if ($this->persistent) {
                    $rc = @mysqli_real_connect($this->link_id, "p:" . $this->host, $this->user, $this->password, $this->database, null, null, MYSQLI_CLIENT_FOUND_ROWS);
                } else {
                    $rc = @mysqli_real_connect($this->link_id, $this->host, $this->user, $this->password, $this->database, null, null, MYSQLI_CLIENT_FOUND_ROWS);
                }
            } else {
                if ($this->persistent) {
                    $this->link_id = @mysqli_connect("p:" . $this->host, $this->user, $this->password, $this->database);
                } else {
                    $this->link_id = @mysqli_connect($this->host, $this->user, $this->password, $this->database);
                }
                $rc = is_object($this->link_id);
            }

            if (!$rc || mysqli_connect_errno()) {
                $this->errorHandler("Connection failed to host " . $this->host);
                $this->cleanup();
                return false;
            }

            if ($this->charset !== null) {
                @mysqli_set_charset($this->link_id, $this->charset);
            }
            if (static::$_sharelink) {
                static::$_dbs[$dbkey]       = $this->link_id;
                $this->link_id              =& static::$_dbs[$dbkey];
            }
        }

        return $this->link_id;
    }

    // -------------------------------------------
    //  FUNZIONI PER LA GESTIONE DELLE OPERAZIONI

    public function insert($Query_String, $table = null){
        return $this->execute($Query_String);
    }
    public function update($Query_String, $table = null){
        return $this->execute($Query_String);
    }
    public function delete($Query_String, $table = null) {
        return $this->execute($Query_String);
    }
    /**
     * Esegue una query senza restituire un recordset
     * @param String La query da eseguire
     * @return boolean
     */
    public function execute($Query_String)
    {
        if ($Query_String == "")
            $this->errorHandler("Execute invoked With blank Query String");

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
        Debug::dumpCaller($Query_String);

        $this->freeResult();

        $this->query_id = @mysqli_query($this->link_id, $Query_String);
        if ($this->checkError())
        {
            $this->errorHandler("Invalid SQL: " . $Query_String);
            return false;
        }

        if (static::$_sharelink)
        {
            $this->buffered_affected_rows = @mysqli_affected_rows($this->link_id);
            $this->buffered_insert_id = @mysqli_insert_id($this->link_id);
        }

        return true;
    }

    /**
     * @param Object|null $obj
     * @return array|bool|null|object
     */
    public function getRecordset($obj = null)
    {
        $res = null;
        if (!$this->query_id)
        {
            $this->errorHandler("eachAll called with no query pending");
            return false;
        }

        if ($obj != null) {
            $res = @mysqli_fetch_object($this->query_id, $obj);
        } else {
            $res = mysqli_fetch_all($this->query_id, MYSQLI_ASSOC);
        }
        mysqli_free_result($this->query_id);

        if ($res === null && $this->checkError())
        {
            $this->errorHandler("fetch_assoc_error");
            return false;
        }
        return $res;
    }

    public function getFieldset()
    {
        return $this->fields_names;
    }

    /**
     * Esegue una query
     * @param string|array $query
     * @return bool|mysqli_result
     */
    public function query($query)
    {
        if(is_array($query)) {
            $Query_String                                   = "SELECT "
                . (isset($query["limit"]["calc_found_rows"])
                    ? " SQL_CALC_FOUND_ROWS "
                    : ""
                ) . $query["select"] . "  
                                                                FROM " .  $query["from"] . "
                                                                WHERE " . $query["where"]
                . (isset($query["sort"])
                    ? " ORDER BY " . $query["sort"]
                    : ""
                )
                . (isset($query["limit"])
                    ? " LIMIT " . (is_array($query["limit"])
                        ? $query["limit"]["skip"] . ", " . $query["limit"]["limit"]
                        : $query["limit"]
                    )
                    : ""
                );
        } else {
            $Query_String                                   = $query;
        }


        if ($Query_String == "") {
            $this->errorHandler("Query invoked With blank Query String");
        }
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        Debug::dumpCaller($Query_String);

        $this->freeResult();

        $this->query_id = @mysqli_query($this->link_id, $Query_String);
        if (!$this->query_id || $this->checkError())
        {
            $this->errorHandler("Invalid SQL: " . $Query_String);
            return false;
        }
        else
        {
            $this->fields = array();
            $this->fields_names = array();

            $finfo = mysqli_fetch_fields($this->query_id);
            foreach ($finfo as $meta) {
                $this->fields[$meta->name] = $meta;
                $this->fields_names[] = $meta->name;
                if ($meta->flags & MYSQLI_PRI_KEY_FLAG) {
                    $this->field_primary = $meta->name;
                }
            }
        }

        return $this->query_id;
    }

    public function cmd($query, $name = "count") {
        $res = null;

        Debug::dumpCaller($query);

        switch ($name) {
            case "count":
                $query["select"] = "COUNT(ID) AS count";
                $this->query($query);
                if($this->nextRecord()) {
                    $res = $this->record["count"];
                }
                break;
            default:
                $this->errorHandler("Command not supported");

        }

        return $res;
    }


    public function multiQuery($Query_String)
    {
        if ($Query_String == "") {
            $this->errorHandler("Query invoked With blank Query String");
        }
        if (!$this->link_id) {
            if (!$this->connect())
                return false;
        }

        Debug::dumpCaller($Query_String);

        $this->freeResult();

        mysqli_multi_query($this->link_id, $Query_String);
        $i = 0;
        $rc = null;
        do
        {
            $i++;
            $extraResult = mysqli_use_result($this->link_id);
            $rc |= $this->checkError();

            if($extraResult instanceof mysqli_result) {
                $extraResult->free();
            }
        } while(mysqli_more_results($this->link_id) && (true | mysqli_next_result($this->link_id)));

        return array("rc" => !$rc, "ord" => $i);
    }

    public function lookup($tabella, $chiave = null, $valorechiave = null, $defaultvalue = null, $nomecampo = null, $tiporestituito = null, $bReturnPlain = false)
    {
        if (!$this->link_id && !$this->connect()) {
            return false;
        }

        if ($tiporestituito === null) {
            $tiporestituito = "Text";
        }

        if (strpos(strtolower(trim($tabella)), "select") !== 0) {
            $listacampi = "";

            if(is_array($nomecampo)) {
                if (!count($nomecampo)) {
                    $this->errorHandler("lookup: Nuessun campo specificato da recuperare");
                }
                foreach ($nomecampo as $key => $value) {
                    if (strlen($listacampi)) {
                        $listacampi .= ", ";
                    }
                    $listacampi .= "`" . $key . "`";
                }
                reset($nomecampo);
            } elseif ($nomecampo !== null) {
                $listacampi = "`" . $nomecampo . "`";
            } else {
                $listacampi = "*";
            }
            $sSql = "SELECT " . $listacampi . " FROM " . $tabella . " WHERE 1 ";
        } else {
            $sSql = $tabella;
        }
        if(is_array($chiave)) {
            if (!count($chiave)) {
                $this->errorHandler("lookup: Nuessuna chiave specificata per il lookup");
            }
            foreach ($chiave as $key => $value) {
                if (is_object($value) && get_class($value) != "Data") {
                    $this->errorHandler("lookup: Il valore delle chiavi dev'essere di tipo Data od un plain value");
                }
                $sSql .= " AND `" . $key . "` = " . $this->toSql($value);
            }
            reset($chiave);
        } elseif ($chiave != null) {
            if (is_object($valorechiave) && get_class($valorechiave) != "Data") {
                $this->errorHandler("lookup: Il valore della chiave dev'essere un oggetto Data od un plain value");
            }
            $sSql .= " AND `" . $chiave . "` = " . $this->toSql($valorechiave);
        }

        $this->query($sSql);
        if ($this->nextRecord()) {

            if(is_array($nomecampo)) {
                $valori = array();
                if (!count($nomecampo)) {
                    $this->errorHandler("lookup: Nuessun campo specificato da recuperare");
                }
                foreach ($nomecampo as $key => $value) {
                    $valori[$key] = $this->getField($key, $value, $bReturnPlain);
                }
                reset($nomecampo);

                return $valori;
            } elseif ($nomecampo !== null) {
                return $this->getField($nomecampo, $tiporestituito, $bReturnPlain);
            } else {
                return $this->getField($this->fields_names[0], $tiporestituito, $bReturnPlain);
            }
        } else {
            if ($defaultvalue === null) {
                return false;
            } else {
                return $defaultvalue;
            }
        }
    }


    /**
     * Sposta il puntatore al DB al record successivo (va chiamato almeno una volta)
     * @param null|object
     * @return bool|null|object
     */
    public function nextRecord($obj = null) {
        if (!$this->query_id) {
            $this->errorHandler("nextRecord called with no query pending");
            return false;
        }

        // fetch assoc bug workaround...
        if ($this->row == ($this->numRows() - 1)) {
            return false;
        }
        if ($obj === null) {
            $this->record = @mysqli_fetch_assoc($this->query_id);
        } else {
            $this->record = @mysqli_fetch_object($this->query_id, $obj);
            $this->row += 1;

            return $this->record;
        }

        if ($this->record) {
            $this->row += 1;
            return true;
        }
        else
        {
            return false;
        }
    }

    // SI POSIZIONA AD UN RECORD SPECIFICO
    private function seek($pos = 0) {
        if (!$this->query_id) {
            $this->errorHandler("Seek called with no query pending");
            return false;
        }

        if (!@mysqli_data_seek($this->query_id, $pos)) {
            $this->errorHandler("seek($pos) failed, result has  " . $this->numRows() . " rows");
            @mysqli_data_seek($this->query_id, 0);
            $this->row = -1;
            return false;
        } else {
            $this->record 	= @mysqli_fetch_assoc($this->query_id);
            $this->row 		= $pos;
            return true;
        }
    }




    /**
     * Conta il numero di righe
     * @param bool $use_found_rows
     * @return bool|int|null
     */
    public function numRows($use_found_rows = false)
    {
        if (!$this->query_id) {
            $this->errorHandler("numRows() called with no query pending");
            return false;
        }

        if ($this->num_rows === null) {
            if($use_found_rows) {
                $db = new MySqli();
                $db->query("SELECT FOUND_ROWS() AS found_rows");
                if($db->nextRecord()) {
                    $this->num_rows = $db->record["found_rows"];
                }
            } else {
                $this->num_rows = @mysqli_num_rows($this->query_id);
            }
        }
        return $this->num_rows;
    }

    public function getInsertID($bReturnPlain = false) {
        if (!$this->link_id) {
            $this->errorHandler("insert_id() called with no DB connection");
            return false;
        }

        if (static::$_sharelink) {
            if ($bReturnPlain) {
                return $this->buffered_insert_id;
            } else {
                return new Data($this->buffered_insert_id, "Number", $this->locale);
            }
        }

        if ($bReturnPlain) {
            return @mysqli_insert_id($this->link_id);
        } else {
            return new Data(@mysqli_insert_id($this->link_id), "Number", $this->locale);
        }
    }

    /**
     *
     * @param String Nome del campo
     * @param String Tipo di dato inserito
     * @param bool $bReturnPlain
     * @param bool $return_error
     * @return mixed Dato recuperato dal DB
     */
    public function getField($Name, $data_type = "Text", $bReturnPlain = false, $return_error = true) {
        if (!$this->query_id) {
            $this->errorHandler("f() called with no query pending");
            return false;
        }

        if(isset($this->fields[$Name])) {
            $tmp = $this->record[$Name];
        } else {
            if($return_error) {
                $tmp = "NO_FIELD [" . $Name . "]";
            } else {
                $tmp = null;
            }
        }

        if ($bReturnPlain) {
            switch ($data_type) {
                case "Number":
                    if(strpos($tmp, ".") === false)
                        return (int)$tmp;
                    else
                        return  (double)$tmp;
                default:
                    return $tmp;
            }
        } else {
            return new Data($tmp, $data_type, $this->locale);
        }
    }

    // ----------------------------------------
    //  FUNZIONI PER LA FORMATTAZIONE DEI DATI

    /**
     * @param string|Data $cDataValue
     * @param null|string $data_type
     * @param bool $enclose_field
     * @param null|bool $transform_null
     * @return null|string
     */
    public function toSql($cDataValue, $data_type = null, $enclose_field = true, $transform_null = null)
    {
        $value = null;
        if (!$this->link_id) {
            $this->connect();
        }
        if (is_array($cDataValue)) {
            $this->errorHandler("toSql: Wrong parameter, array not managed.");
        }
        elseif (!is_object($cDataValue)) {
            $value = mysqli_real_escape_string($this->link_id, $cDataValue);
        }
        else if (get_class($cDataValue) == "Data") {
            if ($data_type === null)
                $data_type = $cDataValue->data_type;

            $value = mysqli_real_escape_string($this->link_id, $cDataValue->getValue($data_type, $this->locale));
        }
        else if (get_class($cDataValue) == "DateTime") {
            switch ($data_type) {
                case "Date":
                    $tmp = new Data($cDataValue, "Date");
                    $value = mysqli_real_escape_string($this->link_id, $tmp->getValue($data_type, $this->locale));
                    break;

                case "DateTime":
                default:
                    $data_type = "DateTime";
                    $tmp = new Data($cDataValue, "DateTime");
                    $value = mysqli_real_escape_string($this->link_id, $tmp->getValue($data_type, $this->locale));
            }
        }
        else {
            $this->errorHandler("toSql: Wrong parameter, unmanaged datatype");
        }
        if ($transform_null === null) {
            $transform_null = $this->transform_null;
        }
        switch ($data_type) {
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
                    $value = Data::getEmpty($data_type, $this->locale);

                if ($enclose_field)
                    return "'" . $value . "'";
                else
                    return $value;
        }
    }

    // ----------------------------------------
    //  GESTIONE ERRORI

    private function checkError()
    {
        if(is_object($this->link_id)) {
            $this->error = @mysqli_error($this->link_id);
            $this->errno = @mysqli_errno($this->link_id);
            if ($this->errno)
                return true;
            else
                return false;
        } else {
            return true;
        }
    }

    private function errorHandler($msg)
    {
        $this->checkError(); // this is needed due to params order

        Error::register("MySQL(" . $this->database . ") - " . $msg . " #" . $this->errno . ": " . $this->error, "database");
    }

    public function id2object($keys) {
        return $keys;
    }
}