<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\international;

use ff\libs\Exception;

/**
 * Class Data
 * @package ff\libs\international
 */
class Data
{
    private const ERROR_BUCKET              = "data";
    private const FUNC_GET                  = "get";
    private const FUNC_SET                  = "set";
    private const FUNC_CHECK                = "check";
    private const FUNC_EMPTY                = self::FUNC_GET . "Empty";

    public const TYPE_TEXT                  = "Text";
    public const TYPE_DATETIME              = "DateTime";
    public const TYPE_DATE                  = "Date";
    public const TYPE_TIME                  = "Time";
    public const TYPE_CURRENCY              = "Currency";
    public const TYPE_CURRENCY_MICRO        = "ExtCurrency";
    public const TYPE_NUMBER                = "Number";
    public const TYPE_NUMBER_MICRO          = "ExtNumber";
    public const TYPE_TIMESTAMP             = "Timestamp";
    public const TYPE_TIME_TO_SECOND        = "TimeToSec";

    private const LOCALE_SYSTEM             = "ISO9075";

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
    public $locale 	                        = self::LOCALE_SYSTEM;

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
     * @param string|null $locale
     * @return string
     * @throws Exception
     */
    public static function getEmpty(string $data_type, string $locale = null) : string
    {
        if (($data_type == self::TYPE_CURRENCY || $data_type == self::TYPE_CURRENCY_MICRO) && $locale == self::LOCALE_SYSTEM) {
            throw new Exception("Data cowardly refuse to manage currency on " . self::LOCALE_SYSTEM, 500);
        }

        $dataLang = self::getAdapter(self::getLocale($locale));
        $funcname = self::FUNC_EMPTY . $data_type;

        return $dataLang->$funcname();
    }

    /**
     * @param null|string $locale
     * @return DataAdapter
     */
    private static function getAdapter(string $locale = null) : DataAdapter
    {
        if (!$locale) {
            $locale = self::LOCALE_SYSTEM;
        }

        if (!isset(self::$singleton[$locale])) {
            self::$singleton[$locale] = new DataAdapter($locale);
        }

        return self::$singleton[$locale];
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    private static function getLocale(string $locale = null) : ?string
    {
        return $locale ?? Locale::getCodeLang();
    }

    /**
     * crea un oggetto Data
     *
     * @param mixed $value il valore originale del dato
     * @param string|null $data_type il tipo del dato
     * @param string|null $locale la localizzazione del dato originale
     * @throws Exception
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
     * @param mixed $value il valore da impostare nell'oggetto preesistente
     * @param string|null $data_type il tipo del dato da memorizzare (sovrascriverà quello attuale). Se omesso viene considerato il tipo attuale.
     * @param string|null $locale il locale del dato da impostare. se omesso viene utilizzato quello attuale.
     * @return Data
     * @throws Exception
     * @todo da tipizzare
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

            $dataLang->$funcname($this, $value);
        }

        return $this;
    }

    /**
     * @param string|null $type
     * @return string
     * @throws Exception
     */
    private function getDataType(string $type = null) : string
    {
        if (!defined(__CLASS__ . "::TYPE_" . strtoupper($type))) {
            throw new Exception("Type " . $type . " not implemented in " . __CLASS__, 501);
        }

        return ($type
            ? constant(__CLASS__ . "::TYPE_" . strtoupper($type))
            : $this->data_type
        );
    }

    /**
     * @param string $prefix
     * @param string $type
     * @return string
     * @throws Exception
     */
    private function getFunc(string $prefix, string $type) : string
    {
        if (!defined(__CLASS__ . "::TYPE_" . strtoupper($type))) {
            throw new Exception("Type: " . $type . " not supported.", 501);
        }

        return $prefix . $type;
    }

    /**
     * @param string|null $data_type
     * @param string|null $locale
     * @return string|null
     * @throws Exception
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
            throw new Exception("Data cowardly refuse to manage currency on " . self::LOCALE_SYSTEM, 500);
        }

        $dataLang = $this->getAdapter($locale ?? $this->locale);
        $funcname = $this->getFunc(self::FUNC_GET, $data_type);

        return $dataLang->$funcname($this);
    }

    /**
     * @param mixed|null $raw_value
     * @param string|null $data_type
     * @param string|null $locale
     * @return bool
     * @throws Exception
     * @todo da tipizzare
     */
    public function checkValue($raw_value = null, string $data_type = null, string $locale = null) : bool
    {
        if ($raw_value === null) {
            $raw_value = $this->ori_value;
        }
        if ($raw_value === null) {
            return false;
        }
        $data_type = $this->getDataType($data_type);
        if ($data_type == self::TYPE_TEXT) {
            return true;
        }

        $dataLang = $this->getAdapter($locale ?? $this->locale);
        $funcname = $this->getFunc(self::FUNC_CHECK, $data_type);

        return $dataLang->$funcname($raw_value);
    }
}
