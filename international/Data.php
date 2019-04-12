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

namespace phpformsframework\libs\international;

use phpformsframework\libs\Error;

interface dataLang {
    public static function Format($key);
    public static function SetDateTime($oData, $value);
    public static function SetDate($oData, $value);
    public static function SetTime($oData, $value);
    public static function NormalizeDate($oData);
    public static function GetDateTime($oData);
    public static function GetDate($oData);
    public static function GetTime($oData);
    public static function SetCurrency($oData, $value);
    public static function GetCurrency($oData);
    public static function SetExtCurrency($oData, $value);
    public static function GetExtCurrency($oData);
    public static function SetNumber($oData, $value);
    public static function GetNumber($oData);
    public static function SetExtNumber($oData, $value);
    public static function GetExtNumber($oData);
    public static function GetTimestamp($oData);
    public static function SetTimestamp($oData, $value);
    public static function GetTimeToSec($oData);
    public static function SetTimeToSec($oData, $value);
    public static function CheckTime($raw_value);
    public static function CheckDate($raw_value);
    public static function CheckDateTime($raw_value);
    public static function CheckCurrency($raw_value);
    public static function CheckExtCurrency($raw_value);
    public static function CheckNumber($raw_value);
    public static function CheckExtNumber($raw_value);
    public static function GetEmptyCurrency();
    public static function GetEmptyDate();
    public static function GetEmptyDateTime();
}


class Data
{
	/**
	 * il valore originale del dato, memorizzato non modificato
	 * @var mixed
	 */
	var $ori_value 	= null;
	/**
	 * Il tipo del dato memorizzato al momento della creazione dell'oggetto.
	 * può essere: Text, Number, Date, Time, DateTime, Timestamp, Currency
	 * non tutti i tipi di dato sono permessi per tutti i locale
	 * @var string
	 */
	var $data_type 	= "Text";
	/**
	 * Il locale del dato memorizzato al momento della creazione dell'oggetto
	 * può essere uno qualsiasi dei tipi indicati nella sottodir "locale"
	 * Esistono due costanti predefinite normalmente associate al locale:
	 *  - FF_SYSTEM_LOCALE : il tipo usato dal sistema (sempre ISO9075)
	 *  - FF_LOCALE : il tipo usato per visualizzare i dati all'utente
	 * @var string
	 */
	var $locale 	= "ISO9075";	/* The default locale setting.
	
												NB.: DON'T ALTER THIS!!!!
												This will be altered on single instances, but is NOT safe to alter the default
												due to superclasses automation.
												If you want to alter the default locale of system objects, alter the settings
												in configuration file. */
	/**
	 * Se dev'essere applicata una trasformazione in modo che non venga mai restituito null come valore.
	 * Se "true", per dati di tipo testuale verrà restituita stringa nulla, per dati di tipo numerico verrà restituito 0
	 * @var string
	 */
	var $transform_null 		= false;

	/**
	 * @todo
	 * @var string
	 */
	var $format_string			= null;
	
	/**
	 * Il valore testuale del dato
	 * @var string
	 */
	var $value_text				= null;
	/**
	 * la parte intera di un valore numerico
	 * @var int
	 */
	var $value_numeric_integer	= null;
	/**
	 * la parte decimale di un valore numerico
	 * @var int
	 */
	var $value_numeric_decimal	= null;
	/**
	 * il segno di un valore numerico, true per negativo, false per positivo
	 * @var boolean
	 */
	var $value_sign				= false;
	/**
	 * La parte "giorno" di una data
	 * @var int
	 */
	var $value_date_day			= null;
	/**
	 * La parte "mese" di una data
	 * @var int
	 */
	var $value_date_month		= null;
	/**
	 * La parte "anno" di una data
	 * @var int
	 */
	var $value_date_year		= null;
	/**
	 * La parte "ora" di un orario
	 * @var int
	 */
	var $value_date_hours		= null;
	/**
	 * La parte "minuti" di un orario
	 * @var int
	 */
	var $value_date_minutes		= null;
	/**
	 * La parte "secondi" di un orario
	 * @var int
	 */
	var $value_date_seconds		= null;
	/**
	 * Se una data è precedente o successiva a mezzogiorno: true se precedente, false se successiva
	 * @var bool
	 */
	var $value_date_meridiem	= false; /* true = ante, false = post */

	/**
	 * @deprecated
	 * Se un tipo currency deve mostrare la parte decimale
	 * @var bool
	 */
	var $format_currency_showdecimals = true;


    static function getEmpty($data_type, $locale)
    {
        if (!$data_type) {
            Error::dump("You must specify a data type", E_USER_ERROR, null, get_defined_vars());
        }
        if (!$locale) {
            Error::dump("You must specify a locale settings", E_USER_ERROR, null, get_defined_vars());
        }
        if ($data_type == "Currency" && $locale == "ISO9075") {
            Error::dump("Data cowardly refuse to manage currency on ISO9075", E_USER_ERROR, null, get_defined_vars());
        }

        $dataLang = self::getClass($locale);
        $funcname = "GetEmpty" . $data_type;

        return $dataLang::$funcname();
    }
    /**
     * @param null $locale
     * @return dataLang
     */
    private static function getClass($locale = null) {
        if ($locale === null) {
            $locale = Locale::getLang("code");
        }

        if (!$locale) {
            Error::dump("You must specify a locale settings", E_USER_ERROR, null, get_defined_vars());
        }

        $class_name = "datalang_" . strtolower($locale);
        return $class_name();
    }
	/**
	 * crea un oggetto Data
	 * 
	 * @param mixed $value il valore originale del dato
	 * @param string $data_type il tipo del dato
	 * @param string $locale la localizzazione del dato originale
	 */
	function __construct($value = null, $data_type = null, $locale = null)
	{
		// embedded types
		if (is_object($value)) {
			if (get_class($value) == "DateTime") {
				if ($data_type === null) {
                    $data_type = "DateTime";
                } elseif ($data_type !== "DateTime" && $data_type !== "Date") {
                    Error::dump("DateTime object with " . $data_type . " type", E_USER_ERROR, $this, get_defined_vars());
                }
				if ($data_type == "Date") {
                    $value = $value->format("Y-m-d");
                } else {
                    $value = $value->format("Y-m-d H:i:s");
                }

				$locale = "ISO9075";
			}
		}
		
		if ($data_type !== null) {
            $this->data_type = $data_type;
        }
		if ($locale !== null) {
            $this->locale = $locale;
        }
		if ($value !== null) {
            $this->setValue($value, $data_type, $locale);
        }
	}



	/**
	 * set all the proper value fields in one shot.
	 * 
	 * @param mixed $value il valore da impostare nell'oggetto preesistente
	 * @param string $data_type il tipo del dato da memorizzare (sovrascriverà quello attuale). Se omesso viene considerato il tipo attuale.
	 * @param string $locale il locale del dato da impostare. se omesso viene utilizzato quello attuale.
	 */
	function setValue($value, $data_type = null, $locale = null)
	{
		$this->ori_value = $value;

		// alter the content of the object will reset the data_type
		if ($data_type === null) {
            $data_type = $this->data_type;
        } else {
            $this->data_type = $data_type;
        }
		if ($data_type == "Text") {
			$this->value_text = $value;
			return;
		}

		$dataLang = $this->getClass($locale);
		$funcname = "Set" . $data_type;

		$dataLang::$funcname($this, $value);
	}
		
	function getValue($data_type = null, $locale = null)
	{
		if ($this->ori_value === null/* || $this->ori_value === ""*/) {
            return null;
        }

		// it's possible to use data type different from the one stored (es.: DateTime -> Date or Time)
		if ($data_type === null) {
            $data_type = $this->data_type;
        }
		if ($data_type == "Text") {
			return $this->value_text . "";
		}
			
		if ($data_type == "Currency" && $locale == "ISO9075") {
            Error::dump("Data cowardly refuse to manage currency on ISO9075", E_USER_ERROR, $this, get_defined_vars());
        }

        $dataLang = $this->getClass($locale);
        $funcname = "Get" . $data_type;

		return $dataLang::$funcname($this);
	}
        
	function getDateTime()
	{
		if ($this->data_type === "Date") {
            return new \DateTime(
                sprintf("%'04u-%'02u-%'02uT00:00:00", $this->value_date_year, $this->value_date_month, $this->value_date_day)
            );
        } else if ($this->data_type === "DateTime") {
            return new \DateTime(
                sprintf("%'04u-%'02u-%'02uT%'02u:%'02u:%'02u", $this->value_date_year, $this->value_date_month, $this->value_date_day, $this->value_date_hours, $this->value_date_minutes, $this->value_date_seconds)
            );
        } else {
            Error::dump("tried to recover DateTime on " . $this->data_type . " type", E_USER_ERROR, $this, get_defined_vars());
        }
		return null;
	}
        
    function checkValue($raw_value = null, $data_type = null, $locale = null)
    {
        if ($raw_value === null) {
            $raw_value = $this->ori_value;
        }
        if ($raw_value === null/* || $this->ori_value === ""*/) {
            return null;
        }

        // it's possible to use data type different from the one stored (es.: DateTime -> Date or Time)
        if ($data_type === null) {
            $data_type = $this->data_type;
        }
		if ($data_type == "Text") {
            return true;
        }
            
        if ($data_type == "Currency" && $locale == "ISO9075") {
            Error::dump("Data cowardly refuse to manage currency on ISO9075", E_USER_ERROR, $this, get_defined_vars());
        }

        $dataLang = $this->getClass($locale);
        $funcname = "Check" . $data_type;

        return $dataLang::$funcname($raw_value);
    }
        
           		
	function format_value($format_string = null, $data_type = null, $locale = null)
	{
		// it's possible to use data type different from the one stored (es.: DateTime -> Date or Time)
		if ($data_type === null) {
            $data_type = $this->data_type;
        }
		if ($data_type == "Text") {
            return $this->ori_value;
        }

		if ($format_string === null) {
			if ($this->format_string !== null) {
                $format_string = $this->format_string;
            } else {
                $dataLang = $this->getClass($locale);
				$format_string = $dataLang::Format($data_type);
			}
		}
			
		switch ($data_type) {
			case "Date":
			case "Time":
			case "DateTime":
				$timestamp = mktime(
                    $this->value_date_hours,
                    $this->value_date_minutes,
                    $this->value_date_seconds,
                    $this->value_date_month,
                    $this->value_date_day,
                    $this->value_date_year
                );
					
				return date($format_string, $timestamp);
			
			case "Currency":
			case "Number":
				break;

			default: // Text
                Error::dump("Unhandled data_type", E_USER_ERROR, $this, get_defined_vars());
		}

		return null;
	}
}
