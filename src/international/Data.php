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

/**
 * Class Data
 * @package phpformsframework\libs\international
 */
class Data
{
    private const ERROR_BUCKET              = "data";
    private const FUNC_GET                  = "get";
    private const FUNC_SET                  = "set";
    private const FUNC_CHECK                = "check";
    private const FUNC_EMPTY                = self::FUNC_GET . "Empty";

    public const TYPE_TEXT                  = "Text";
    public const TYPE_DATE_TIME             = "DateTime";
    public const TYPE_DATE                  = "Date";
    public const TYPE_TIME                  = "Time";
    public const TYPE_CURRENCY              = "Currency";
    public const TYPE_CURRENCY_MICRO        = "ExtCurrency";
    public const TYPE_NUMBER                = "Number";
    public const TYPE_NUMBER_MICRO          = "ExtNumber";
    public const TYPE_TIMESTAMP             = "Timestamp";
    public const TYPE_TIME_TO_SECOND        = "TimeToSec";

    public const LOCALE_SYSTEM              = "ISO9075";

    private static $singleton               = null;
    /**
     * il valore originale del dato, memorizzato non modificato
     * @var mixed
     */
    public $ori_value 	                    = null;
    /**
     * Il tipo del dato memorizzato al momento della creazione dell'oggetto.
     * può essere: Text, Number, Date, Time, DateTime, Timestamp, Currency
     * non tutti i tipi di dato sono permessi per tutti i locale
     * @var string
     */
    public $data_type 	                    = self::TYPE_TEXT;
    /**
     * Il locale del dato memorizzato al momento della creazione dell'oggetto
     * può essere uno qualsiasi dei tipi indicati nella sottodir "locale"
     * Esistono due costanti predefinite normalmente associate al locale:
     *  - FF_SYSTEM_LOCALE : il tipo usato dal sistema (sempre ISO9075)
     *  - FF_LOCALE : il tipo usato per visualizzare i dati all'utente
     * @var string
     */
    public $locale 	                        = self::LOCALE_SYSTEM;	/* The default locale setting.

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
    public $transform_null 		            = false;

    /**
     * Il valore testuale del dato
     * @var string
     */
    public $value_text				        = null;
    /**
     * la parte intera di un valore numerico
     * @var int
     */
    public $value_numeric_integer	        = null;
    /**
     * la parte decimale di un valore numerico
     * @var int
     */
    public $value_numeric_decimal	        = null;
    /**
     * il segno di un valore numerico, true per negativo, false per positivo
     * @var boolean
     */
    public $value_sign				        = false;
    /**
     * La parte "giorno" di una data
     * @var int
     */
    public $value_date_day			        = null;
    /**
     * La parte "mese" di una data
     * @var int
     */
    public $value_date_month		        = null;
    /**
     * La parte "anno" di una data
     * @var int
     */
    public $value_date_year		            = null;
    /**
     * La parte "ora" di un orario
     * @var int
     */
    public $value_date_hours		        = null;
    /**
     * La parte "minuti" di un orario
     * @var int
     */
    public $value_date_minutes		        = null;
    /**
     * La parte "secondi" di un orario
     * @var int
     */
    public $value_date_seconds		        = null;
    /**
     * Se una data è precedente o successiva a mezzogiorno: true se precedente, false se successiva
     * @var bool
     */
    public $value_date_meridiem	            = false; /* true = ante, false = post */

    /**
     * @param string $data_type
     * @param string $locale
     * @return string
     */
    public static function getEmpty(string $data_type, string $locale = null) : string
    {
        if (($data_type == self::TYPE_CURRENCY || $data_type == self::TYPE_CURRENCY_MICRO) && $locale == self::LOCALE_SYSTEM) {
            Error::register("Data cowardly refuse to manage currency on " . self::LOCALE_SYSTEM, static::ERROR_BUCKET);
        }

        $dataLang = self::getAdapter(self::getLocale($locale));
        $funcname = self::FUNC_EMPTY . $data_type;

        return $dataLang->$funcname();
    }

    /**
     * @param null|string $locale
     * @return DataAdapter
     */
    private static function getAdapter(string $locale) : DataAdapter
    {
        if (!$locale) {
            Error::register("You must specify a locale settings", static::ERROR_BUCKET);
        }

        if (!isset(self::$singleton[$locale])) {
            self::$singleton[$locale] = new DataAdapter(strtolower($locale));
        }

        return self::$singleton[$locale];
    }

    /**
     * @param string|null $locale
     * @return string
     */
    private static function getLocale(string $locale = null) : string
    {
        return (
            $locale
            ? $locale
            : Locale::getLang("code")
        );
    }
    /**
     * crea un oggetto Data
     *
     * @param mixed $value il valore originale del dato
     * @param string $data_type il tipo del dato
     * @param string $locale la localizzazione del dato originale
     */
    public function __construct($value = null, string $data_type = null, string $locale = null)
    {
        if ($data_type) {
            $this->data_type = $data_type;
        }

        $this->locale = $this->getLocale($locale);

        if ($value !== null) {
            $this->setValue($value, $data_type, $this->locale);
        }
    }



    /**
     * set all the proper value fields in one shot.
     *
     * @todo da tipizzare
     * @param mixed $value il valore da impostare nell'oggetto preesistente
     * @param string $data_type il tipo del dato da memorizzare (sovrascriverà quello attuale). Se omesso viene considerato il tipo attuale.
     * @param string $locale il locale del dato da impostare. se omesso viene utilizzato quello attuale.
     * @return Data
     */
    public function setValue($value, string $data_type = null, string $locale = null) : self
    {
        $this->ori_value = $value;

        $data_type = $this->getDataType($data_type);
        if (!$locale) {
            $locale = $this->locale;
        }

        if ($data_type == self::TYPE_TEXT) {
            $this->value_text = $value;
        } else {
            $dataLang = $this->getAdapter($locale);
            $funcname = $this->getFunc(self::FUNC_SET, $data_type);

            if (!$this->checkValue($value, $data_type, $locale)) {
                Error::register($value . " is not valid " .  $data_type . " for locale " . $locale . ": " . $dataLang->getFormat($data_type), static::ERROR_BUCKET);
            }

            $dataLang->$funcname($this, $value);
        }

        return $this;
    }

    /**
     * @param string $type
     * @return string
     */
    private function getDataType(string $type = null) : string
    {
        return ($type
            ? constant(__CLASS__ . "::TYPE_" . strtoupper($type))
            : $this->data_type
        );
    }
    /**
     * @param string $prefix
     * @param string $type
     * @return string
     */
    private function getFunc(string $prefix, string $type) : string
    {
        if (!defined(__CLASS__ . "::TYPE_" . strtoupper($type))) {
            Error::register("Type: " . $type . " not supported.", static::ERROR_BUCKET);
        }

        return $prefix . $type;
    }

    /**
     * @param string|null $data_type
     * @param string|null $locale
     * @return string|null
     */
    public function getValue(string $data_type = null, string $locale = null) : ?string
    {
        if ($this->ori_value === null) {
            return null;
        }

        if (!$data_type) {
            $data_type = $this->data_type;
        }
        if ($data_type == self::TYPE_TEXT) {
            return $this->value_text;
        }
            
        if (($data_type == self::TYPE_CURRENCY || $data_type == self::TYPE_CURRENCY_MICRO) && $locale == self::LOCALE_SYSTEM) {
            Error::register("Data cowardly refuse to manage currency on " . self::LOCALE_SYSTEM, static::ERROR_BUCKET);
        }

        $dataLang = $this->getAdapter($locale);
        $funcname = $this->getFunc(self::FUNC_GET, $data_type);

        return $dataLang->$funcname($this);
    }

    /**
     * @todo da tipizzare
     * @param mixed|null $raw_value
     * @param string|null $data_type
     * @param string|null $locale
     * @return bool
     */
    public function checkValue($raw_value = null, string $data_type = null, string $locale = null) : bool
    {
        if ($raw_value === null) {
            $raw_value = $this->ori_value;
        }
        if ($raw_value === null) {
            return null;
        }
        $data_type = $this->getDataType($data_type);
        if ($data_type == self::TYPE_TEXT) {
            return true;
        }

        $dataLang = $this->getAdapter($locale);
        $funcname = $this->getFunc(self::FUNC_CHECK, $data_type);

        return $dataLang->$funcname($raw_value);
    }


}
