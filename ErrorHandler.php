<?php
/**
 * framework error handling
 *
 * @package FormsFramework
 * @subpackage common
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright (c) 2004-2017, Samuele Diella
 * @license https://opensource.org/licenses/LGPL-3.0
 * @link http://www.formsphpframework.com
 */
namespace phpformsframework\libs;

if (!defined("FF_ERRORS_MAXRECURSION"))           define("FF_ERRORS_MAXRECURSION", NULL);
if (!defined("FF_ERROR_TYPES"))				    define("FF_ERROR_TYPES", E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE /*| E_ERROR | E_WARNING*/);
if (!defined("FF_ERROR_HANDLER_HIDE"))			    define("FF_ERROR_HANDLER_HIDE", false);
if (!defined("FF_ERROR_HANDLER_MINIMAL"))		    define("FF_ERROR_HANDLER_MINIMAL", false);
if (!defined("FF_ERROR_HANDLER_CUSTOM_TPL"))		define("FF_ERROR_HANDLER_CUSTOM_TPL", ""); // path relativo a FF_DISK_PATH
if (!defined("FF_ERROR_HANDLER_500"))			    define("FF_ERROR_HANDLER_500", true);
if (!defined("FF_ERROR_HANDLER_LOG"))			    define("FF_ERROR_HANDLER_LOG", false);
if (!defined("FF_ERROR_HANDLER_LOG_PATH"))		    define("FF_ERROR_HANDLER_LOG_PATH", FF_DISK_PATH . "/ff_errors");

//set_error_handler("ffErrorHandler::errorHandler", FF_ERROR_TYPES);
//error_reporting(error_reporting() ^ FF_ERROR_TYPES);
//todo:: fa sistemare meglio la gestione degli errori introducendo il dump degli errori in modalita debug (notice da una parte ecc)
/**
 * Questa classe astratta contiene tutti i metodi utilizzati dalla funzione di gestione degli errori.
 * L'unico metodo accessibile dall'esterno è raise, gli altri sono ad utilizzo interno.
 *
 * @package FormsFramework
 * @subpackage common
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright (c) 2004-2017, Samuele Diella
 * @license https://opensource.org/licenses/LGPL-3.0
 * @link http://www.formsphpframework.com
 */
class ErrorHandler
{
    static $errors_objects  = array();
    static $errors_arrays   = array();
    static $errors_handled  = array();

    static $hide			= FF_ERROR_HANDLER_HIDE;
    static $minimal_report	= FF_ERROR_HANDLER_MINIMAL;
    static $whenErrorDo500	= FF_ERROR_HANDLER_500;
    static $log				= FF_ERROR_HANDLER_LOG;

    static $log_path		= FF_ERROR_HANDLER_LOG_PATH; // default to FF_DISK_PATH . /ff_errors
    private static $log_fp          = null; // private
    private static $error_types		= FF_ERROR_TYPES;

    /**
     * Questa funzione genera un errore, che verrà poi gestito dalla funzione apposita della classe (ffErrorHandler::errorHandler)
     *
     * @param string $errdes la descrizione dell'errore
     * @param int $errno il codice errore, può essere E_USER_ERROR o E_USER_WARNING
     * @param object $context l'oggetto contesto dell'errore. Se non esiste è null
     * @param array $variables le variabili definite al momento della generazione dell'errore, normalmente recuperate tramite get_defined_vars()
     */
    static function raise($errdes, $errno = E_USER_ERROR, $context = NULL, $variables = NULL)
    {
        $id = uniqid(rand(), true);
        self::$errors_objects = array();
        self::$errors_arrays = array();

        self::$errors_handled[$id]["description"]   = $errdes;
        self::$errors_handled[$id]["context"]       = $context;
        self::$errors_handled[$id]["variables"]     = $variables;
        self::$errors_handled[$id]["constants"]     = self::compactConstants(get_defined_constants());
        self::$errors_handled[$id]["classes"]       = self::compactConstants(get_declared_classes());

        set_error_handler("\phpformsframework\libs\ErrorHandler::errorHandler", self::$error_types);
        error_reporting(error_reporting() ^ self::$error_types);

        trigger_error($id, $errno);
    }

    static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        static $error_id = -1;

        if (!(error_reporting() & $errno) || error_reporting() === 0) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        $halt_script = false;
        $source = " in <b>" . $errfile . "</b> on line <b>" . $errline . "</b>";
        switch ($errno)
        {
            case E_ERROR:
                $error_description = "<b>E_ERROR - Fatal run-time error</b>" . $source . "<br />";
                $halt_script = true;
                break;

            case E_WARNING:
                $error_description = "<b>E_WARNING - Run-time warning (non-fatal errors)</b>" . $source . "<br />";
                break;

            case E_PARSE:
                $error_description = "<b>E_PARSE - Compile-time parse error</b>" . $source . "<br />";
                $halt_script = true;
                break;

            case E_NOTICE:
                $error_description = "<b>E_NOTICE - Run-time notice</b>" . $source . "<br />";
                break;

            case E_CORE_ERROR:
                $error_description = "<b>E_CORE_ERROR - Fatal error that occur during PHP's initial startup</b>" . $source . "<br />";
                $halt_script = true;
                break;

            case E_CORE_WARNING:
                $error_description = "<b>E_CORE_WARNING - Warning (non-fatal error) that occur during PHP's initial startup</b>" . $source . "<br />";
                break;

            case E_COMPILE_ERROR:
                $error_description = "<b>E_COMPILE_ERROR - Fatal compile-time error</b>" . $source . "<br />";
                $halt_script = true;
                break;

            case E_COMPILE_WARNING:
                $error_description = "<b>E_COMPILE_WARNING - Compile-time warning (non-fatal error)</b>" . $source . "<br />";
                break;

            case E_USER_ERROR:
                $error_description = "<b>E_USER_ERROR - User-generated error message</b>" . $source . "<br />";
                $halt_script = true;
                break;

            case E_USER_WARNING:
                $error_description = "<b>E_USER_WARNING - User-generated warning message</b>" . $source . "<br />";
                break;

            case E_USER_NOTICE:
                $error_description = "<b>E_USER_NOTICE - User-generated notice message</b>" . $source . "<br />";
                break;

            default:
                return false;
        }

        switch ($errno)
        {
            case E_USER_ERROR:
            case E_USER_WARNING:
            case E_USER_NOTICE:
                $id                                         = $errstr;
                break;
            default:
                $id                                         = uniqid(rand(), true);
                self::$errors_handled[$id]["description"]   = $errstr;
                self::$errors_handled[$id]["context"]       = null;
                self::$errors_handled[$id]["variables"]     = null;
                self::$errors_handled[$id]["constants"]     = self::compactConstants(get_defined_constants());
                self::$errors_handled[$id]["classes"]       = self::compactConstants(get_declared_classes());
        }

        $error_id++;

        if (self::logEnabled())
        {
            @mkdir(self::$log_path, 0777, true);
            self::$log_fp = @fopen(self::$log_path . "/" . $id . ".log.html", "a");
        }

        // if this is the first error handled, output javascript to handle grouped elements
        if (!$error_id)
        {
            self::out(<<<EOD
<!-- Forms Errors Handling Grouping Function -->
<script type="text/javascript">
	function expand(link, element_name, sub_element, element_ref)
		{
			var element = null;
			if (element_name !== null && element_name !== "") {
				element = document.getElementById(element_name);
			} else {
                element = element_ref;
            }
			if (element &&  element.style.display === "block") {
                element.style.display = "none";
                link.innerHTML = "<b>[+]</b>";
            } else {
                if (sub_element !== null && sub_element !== "" && element.innerHTML.length === 0) {
                    element.innerHTML = document.getElementById(sub_element).innerHTML;
                }
                element.style.display = "block";
                link.innerHTML = "<b>[-]</b>";
            }
		}
</script>
EOD
            );
        }

        // retrieve backtrace
        $backtrace = debug_backtrace();

        /* assume main error is at index 2 (in fact must be ever called by FormsTriggerError)
           and store temporary data used to display header */
        $errfile = $backtrace[2]["file"];
        $errline = $backtrace[2]["line"];
        $errfunc = $backtrace[3]["function"];
        $errargs = $backtrace[3]["args"];

        // DISPLAY BOX
        self::out('<div style="display: block; background-color: #AAAAAA; border: 1px solid #FF0000; padding: 10px;font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px;">
			<div style="display: block; background-color: #FF0000; border: 0; padding: 5px; color: #FFFFFF;">
				<b>-=| FORMS FRAMEWORK |=-</b> ERROR HANDLED #' . (self::logEnabled() ? $id : ($error_id + 1)) . '
			</div>');

        // DISPLAY HEADER INFORMATION
        self::out('<p>' . $error_description . self::$errors_handled[$id]["description"] . '</p>');
        self::out('<table style="font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px;">');
        self::out('<tr><td style="width: 110px; vertical-align: top;"><b>File:</b></td><td>' . $errfile . '</td></tr>');
        self::out('<tr><td style="vertical-align: top;"><b>Line:</b></td><td>' . $errline . '</td></tr>');
        if (strlen($errfunc))
            self::out('<tr><td style="vertical-align: top;"><b>Func:</b></td><td>' . $errfunc . '</td></tr>');

        // DISPLAY FUNCTION ARGUMENTS
        if (isset($errargs) && is_array($errargs) && count($errargs))
        {
            //self::out('<tr><td style="vertical-align: top;"><b>Func Args: <a href="javascript:void(0);" onclick="expand(this, \'div_args_' . $error_id . '\');">[+]</a></b></td><td><div id="div_args_' . $error_id . '" style="display: none; overflow: hidden;">');
            self::out('<tr><td style="vertical-align: top;"><b>Func Args:</b></td><td>');
            self::structPrint($errargs, 0, true);
            //self::out('</div></td></tr>');
            self::out('</td></tr>');
        }

        // DISPLAY FILE SOURCE
        /*		self::out('<tr><td style="vertical-align: top;"><b>Source: <a href="javascript:void(0);" onclick="expand(this, \'div_source_' . $error_id . '\');">[+]</a></b></td><td><div id="div_source_' . $error_id . '" style="display: none; border: 1px solid black; background-color: #FFFFFF; overflow-x: scroll;"><code>');
                $startline = $errline - 10;
                if ($startline < 0)
                    $startline = 0;
                $endline = $errline + 10;
                $code = highlight_file($errfile, true);
                for ($i = 0; $i < strlen($code); $i++)
                    {
                        $buffer .= $code[$i];
                        if (substr($buffer, -6) == '<br />')
                            {
                                $tmp = count($codeln);
                                if ($tmp + 1 == $errline)
                                    $codeln[$tmp] = '<span style="font-weight: bold; color: #000000; border-right: 1px solid black; background-color: #AAAAAA;">&nbsp;' . str_replace(" ", "&nbsp;", sprintf("%5s", $tmp + 1)) . '&nbsp;</span>';
                                else
                                    $codeln[$tmp] = '<span style="font-weight: bold; color: #000000; border-right: 1px solid black; background-color: #DDDDDD;">&nbsp;' . str_replace(" ", "&nbsp;", sprintf("%5s", $tmp + 1)) . '&nbsp;</span>';
                                $codeln[$tmp] .= $buffer;
                                $buffer = "";
                            }
                    }
                if ($code[$i] != '\n')
                    $codeln[count($codeln)] = $buffer;
                for ($i = $startline - 1; $i <= $endline - 1; $i++)
                    {
                        if ($i + 1 == $errline)
                            self::out("<div style='background-color: #ffff66; width: 100%;'>" . $codeln[$i] . "</div>");
                        else
                            self::out($codeln[$i]);
                    }
                self::out('</code></div></td></tr>');
         */
        // DISPLAY FUNCTION VARIABLES
        if (self::$errors_handled[$id]["variables"] !== NULL)
        {
            if (isset(self::$errors_handled[$id]["variables"]["GLOBALS"]))
                self::$errors_handled[$id]["variables"] = self::removeGlobals(self::$errors_handled[$id]["variables"]);
            self::out('<tr><td style="vertical-align: top;"><b>Variables: <a href="javascript:void(0);" onclick="expand(this, \'div_variables_' . $error_id . '\');">[+]</a></b></td><td><div id="div_variables_' . $error_id . '" style="display: none; overflow: hidden;">');
            self::structPrint(self::$errors_handled[$id]["variables"], 0, true);
            self::out('</div></td></tr>');
        }

        // DISPLAY GLOBAL VARIABLES
        if (!isset(self::$errors_handled[$id]["variables"]["GLOBALS"]))
        {
            $tmp_globals = self::removeGlobals($GLOBALS);
            self::out('<tr><td style="vertical-align: top;"><b>Globals: <a href="javascript:void(0);" onclick="expand(this, \'div_globals_' . $error_id . '\');">[+]</a></b></td><td><div id="div_globals_' . $error_id . '" style="display: none; overflow: hidden;">');
            self::structPrint($tmp_globals, 0, true);
            self::out('</div></td></tr>');
        }

        // DISPLAY CONSTANTS
        if (self::$errors_handled[$id]["constants"] !== NULL)
        {
            self::out('<tr><td style="vertical-align: top;"><b>Constants: <a href="javascript:void(0);" onclick="expand(this, \'div_constants_' . $error_id . '\');">[+]</a></b></td><td><div id="div_constants_' . $error_id . '" style="display: none; overflow: hidden;">');
            self::structPrint(self::$errors_handled[$id]["constants"], 0, true);
            self::out('</div></td></tr>');
        }

        // DISPLAY CLASSES
        if (self::$errors_handled[$id]["classes"] !== NULL)
        {
            self::out('<tr><td style="vertical-align: top;"><b>Classes: <a href="javascript:void(0);" onclick="expand(this, \'div_classes_' . $error_id . '\');">[+]</a></b></td><td><div id="div_classes_' . $error_id . '" style="display: none; overflow: hidden;">');
            self::structPrint(self::$errors_handled[$id]["classes"], 0, true);
            self::out('</div></td></tr>');
        }

        // DISPLAY BACKTRACE
        if (is_array($backtrace) && count($backtrace) > 3)
        {
            self::out('<tr><td style="vertical-align: top;"><b>Backtrace: <a href="javascript:void(0);" onclick="expand(this, \'div_backtrace_' . $error_id . '\');">[+]</a></b></td><td><div id="div_backtrace_' . $error_id . '" style="display: none; overflow: hidden;">');
            foreach ($backtrace as $key => $value)
            {
                if ($key > 2) // skip the 2x error handling function and the main displayed function
                {
                    self::out('<table style="border: 1px solid black; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; width: 100%; margin-bottom: 10px;">');
                    self::out('<tr><td style="width: 100px; vertical-align: top;"><b>File:</b></td><td>' . $value["file"] . '</td></tr>');
                    self::out('<tr><td style="vertical-align: top;"><b>Line:</b></td><td>' . $value["line"] . '</td></tr>');
                    self::out('<tr><td style="vertical-align: top;"><b>Func:</b></td><td>' . $backtrace[$key + 1]["function"] . '</td></tr>');
                    if (isset($backtrace[$key + 1]["args"]) && is_array($backtrace[$key + 1]["args"]) && count($backtrace[$key + 1]["args"]))
                    {
                        self::out('<tr><td style="vertical-align: top;"><b>Used Args:</b></td><td>');
                        self::structPrint($backtrace[$key + 1]["args"], 0, true);
                        self::out('</td></tr>');
                    }
                    self::out('</table>');
                }
            }
            self::out('</div></td></tr>');
        }
        self::out('</table>');
        self::out('</div>');

        if (self::logEnabled())
        {
            @fclose(self::$log_fp);
        }

        if (self::hideEnabled() && self::$minimal_report)
        {
            if (strlen(FF_ERROR_HANDLER_CUSTOM_TPL) && is_file(FF_DISK_PATH . FF_ERROR_HANDLER_CUSTOM_TPL))
            {
                readfile(FF_DISK_PATH . FF_ERROR_HANDLER_CUSTOM_TPL);
            }
            else
            {
                echo "<pre>-=| FORMS FRAMEWORK |=- ERROR CATCHED";
                if (self::logEnabled())
                {
                    echo "\nID: " . $id . "\n\n";
                    echo "This error has been logged and will be fixed as soon as possible.\n";
                    echo "If you are in a hurry, please contact support and pass this ID. Thanks in advice for your help";
                }
                else
                {
                    echo " #" . ($error_id + 1) . "\n\n";
                    echo self::$errors_handled[$id]["description"] . "\n\n";
                    echo "Please contact support and report this page. Thanks in advice for your help";
                }
                echo "</pre>";
            }
        }

        if (self::$whenErrorDo500) {
            Response::code(500);
        }

        if ($halt_script) {
            exit;
        }

        return false;
    }

    private static function removeGlobals($params)
    {
        $res = null;
        if (is_array($params) && count($params))
        {
            $res = array();
            foreach ($params as $key => $value)
            {
                if (!(
                    (isset($params["_ENV"][$key]) && $params["_ENV"][$key] == $value)
                    || (isset($params["_SERVER"][$key]) && $params["_SERVER"][$key] == $value)
                    || (isset($params["_COOKIE"][$key]) && $params["_COOKIE"][$key] == $value)
                    || (isset($params["_POST"][$key]) && $params["_POST"][$key] == $value)
                    || (isset($params["_GET"][$key]) && $params["_GET"][$key] == $value)
                    || (isset($params["_FILES"][$key]) && $params["_FILES"][$key] == $value)
                    || (isset($params["_SESSION"][$key]) && $params["_SESSION"][$key] == $value)
                    || (substr($key, 0, strlen(APPID)) == APPID)
                    || $key == "HTTP_ENV_VARS"
                    || $key == "HTTP_SERVER_VARS"
                    || $key == "HTTP_COOKIE_VARS"
                    || $key == "HTTP_POST_VARS"
                    || $key == "HTTP_GET_VARS"
                    || $key == "HTTP_FILES_VARS"
                    || $key == "HTTP_SESSION_VARS"
                    || $key == "GLOBALS"
                ))
                    $res[$key] = $value;
                //unset($params[$key]);
            }
            reset($params);
            /*unset($params["HTTP_ENV_VARS"]);
            unset($params["HTTP_SERVER_VARS"]);
            unset($params["HTTP_COOKIE_VARS"]);
            unset($params["HTTP_POST_VARS"]);
            unset($params["HTTP_GET_VARS"]);
            unset($params["HTTP_FILES_VARS"]);
            unset($params["HTTP_SESSION_VARS"]);
            unset($params["GLOBALS"]);*/
        }
        return $res;
    }

    private static function compactConstants($params)
    {
        ksort($params);
        foreach ($params as $key => $value)
        {
            if (strpos($key, "_") !== FALSE)
            {
                // underscores presents, find the right "node"
                unset($params[$key]);

                $tmp = $key;
                $node = $params;
                while(($offset = strpos($tmp, "_")) !== FALSE)
                {
                    $subkey = substr($tmp, 0, $offset) . "<b></b>";
                    $tmp = substr($tmp, $offset + 1);
                    if (!isset($node[$subkey]))
                        $node[$subkey] = array();

                    $node = $node[$subkey];
                }

                $node[$key] = $value;
            }
        }
        reset($params);
        self::compactConstantsReduce($params);

        return $params;
    }

    private static function compactConstantsReduce(&$node)
    {
        foreach ($node as $key => $value)
        {
            if (is_array($node[$key]))
            {
                if (count($node[$key]) == 1)
                {
                    $tmp_key = $key;
                    while(is_array($node[$tmp_key]) && count($node[$tmp_key]) == 1)
                    {
                        list($newkey, $newvalue) = each($node[$tmp_key]);
                        $node[$newkey] = $newvalue;
                        unset($node[$tmp_key]);
                        $tmp_key = $newkey;
                    }

                } else {
                    self::compactConstantsReduce($node[$key]);
                }
            }
        }
        reset($node);
        ksort($node);
    }

    private static function structPrint(&$arg, $recursion = 0, $display_lines = true)
    {
        if (!$recursion)
            self::out("<code>");

        // FIRST OF ALL, get members

        if (is_array($arg))
        {
            if (!$recursion && $display_lines)
                self::out('<div style="border-top: 1px dashed black; margin-top: 2px; margin-bottom: 2px;"></div>');

            $vars = $arg;
        }
        elseif (is_object($arg))
        {
            $vars = get_object_vars($arg);
            ksort($vars);
        }
        else
        {
            var_dump($arg);
            die("UNKNOWN IN STRUCT PRINT!");
        }

        foreach ($vars as $key => $value)
        {
            self::out("[$key] => ");
            if (is_object($value))
            {
                self::out("Object <b>[type = " . get_class($value) . "]</b> ");
                if (FF_ERRORS_MAXRECURSION !== NULL && $recursion >= FF_ERRORS_MAXRECURSION)
                    self::out("<b>MAX RECURSION</b>");
                elseif(get_class($value) == "com")
                {
                    self::out("<b>SKIPPED</b>");
                }
                else
                {
                    $bFind = FALSE;
                    /*$obj_id = get_class($value) . "_" . FormsCommon_get_object_id($value);
                    if (isset(self::$errors_objects[$obj_id]))
                        $bFind = self::$errors_objects[$obj_id]["id"];*/

                    foreach (self::$errors_objects as $subkey => $subvalue)
                    {
                        if (self::$errors_objects[$subkey]["ref"] === $value)
                        {
                            $obj_id = $subkey;
                            $bFind = self::$errors_objects[$subkey]["id"];
                            self::out(" ID #" . $obj_id . "");
                            break;
                        }
                    }
                    reset(self::$errors_objects);

                    if ($bFind === FALSE)
                    {
                        $bFind = uniqid(rand(), true);
                        $obj_id = count(self::$errors_objects);
                        self::$errors_objects[$obj_id] = array("id" => $bFind, "ref" => $value);
                        self::out(" ID #" . $obj_id . "");
                        self::out('<div id="obj_' . $bFind . '" style="display: none;">');
                        self::structPrint($value, $recursion + 1, true);
                        self::out('</div>');
                    }

                    self::out('<a href="javascript:void(0);" onclick="expand(this, null, \'obj_' . $bFind . '\', this.nextSibling);"><b>[+]</b></a><div style="padding-left: 40px; display: none;"></div>');
                }
                self::out("<br />");
            }
            else if (is_array($value))
            {
                self::out("Array <b>[count = " . count($value) . "]</b> ");
                if (
                    (string)$key != "FormsErrorsHandled"
                    && (string)$key != "FormsErrorsObjects"
                    && (string)$key != "FormsErrorsArrays"
                )
                {
                    $bFind = FALSE;
                    foreach (self::$errors_arrays as $subkey => $subvalue)
                    {
                        if ($subvalue === $value)
                        {
                            $bFind = $subkey;
                            break;
                        }
                    }
                    reset(self::$errors_arrays);

                    if ($bFind === FALSE)
                    {
                        if (FF_ERRORS_MAXRECURSION !== NULL && $recursion >= FF_ERRORS_MAXRECURSION)
                            self::out("<b>MAX RECURSION</b>");
                        else if (count($value))
                        {
                            $bFind = uniqid(rand(), true);
                            self::$errors_arrays[$bFind] = $value;
                            self::out('<div id="arr_' . $bFind . '" style="display: none;">');
                            self::structPrint($value, $recursion + 1, FALSE);
                            self::out('</div>');
                        }
                    }

                    if ($bFind)
                        self::out('<a href="javascript:void(0);" onclick="expand(this, null, \'arr_' . $bFind . '\', this.nextSibling);"><b>[+]</b></a><div style="padding-left: 40px; display: none;"></div>');
                }
                else
                {
                    self::out("<b>SKIPPED</b>");
                }
                self::out("<br />");
            }
            else if ($value === NULL)
                self::out("NULL<br />");
            else if ($value === FALSE)
                self::out("FALSE<br />");
            else if ($value === true)
                self::out("true<br />");
            else if (is_string($value))
            {
                if (
                    strpos(strtolower($key), "password") !== FALSE
                    || $key === "_crypt_Ku_"
                    || $key === "_crypt_KSu_"
                )
                    self::out("<b>PROTECTED</b><br />");
                else
                    self::out('"' . htmlspecialchars($value) . "\"<br />");
            }
            else if (is_resource($value))
                self::out("Resource <b>[type = " . get_resource_type($value) . "]</b><br />");
            else
                self::out($value . "<br />");

            if (!$recursion && $display_lines)
                self::out('<div style="border-top: 1px dashed black; margin-top: 2px; margin-bottom: 2px;"></div>');
        }
        reset($vars);

        if (!$recursion)
        {
            self::out("</code>");
        }
    }

    private static function logEnabled()
    {
        return self::$log;
    }

    private static function hideEnabled()
    {
        return self::$hide;
    }

    private static function out($text)
    {
        if (self::logEnabled())
            @fwrite(self::$log_fp, $text);

        if (!self::hideEnabled())
            echo $text;
    }
}
