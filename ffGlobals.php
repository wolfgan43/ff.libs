<?php
/**
 * namespace emulation
 *
 * Questa classe simula l'utilizzo dei namespace.
 * Non può essere istanziata direttamente, è necessario usare il metodo getInstance()
 *
 * @package FormsFramework
 * @subpackage base
 * @author Samuele Diella <samuele.diella@gmail.com>
 * @copyright Copyright (c) 2004-2017, Samuele Diella
 * @license https://opensource.org/licenses/LGPL-3.0
 * @link http://www.formsphpframework.com
 */
class ffGlobals
{
	private static $instances =  array();

    public function __call($method, $args)
    {
        if (isset($this->$method)) {
            $func = $this->$method;
            return call_user_func_array($func, $args);
        }
    }

	/**
	 * Questa funzione restituisce un "finto" namespace sotto forma di oggetto attraverso il quale è possibile definire
	 * variabili ed oggetti in modo implicito (magic).
	 * 
	 * @param string $bucket il nome del namespace desiderato.
	 * @return ffGlobals
	 */
	public static function getInstance($bucket = null)
	{
		if (!isset(ffGlobals::$instances[$bucket]))
			ffGlobals::$instances[$bucket] = new ffGlobals();
			
		return ffGlobals::$instances[$bucket];
	}

    public static function set($name, $value = null, $bucket = null) {
        if (!isset(ffGlobals::$instances[$bucket]))
            ffGlobals::$instances[$bucket] = new ffGlobals();

	    self::$instances[$bucket]->$name = $value;

	    return true;
    }

    public static function get($name, $bucket = null) {
        if (!isset(ffGlobals::$instances[$bucket]))
            ffGlobals::$instances[$bucket] = new ffGlobals();

        return (isset(self::$instances[$bucket]->$name)
            ? self::$instances[$bucket]->$name
            : null
        ) ;
    }

    public static function del($name, $bucket = null) {
        if(isset(self::$instances[$bucket]->$name))
            unset(self::$instances[$bucket]->$name);

        return true;
    }
    public static function clear($bucket = null) {
        if(self::$instances[$bucket])
            unset(self::$instances[$bucket]);

        return true;
    }

    private function __construct()
    {
    }
}