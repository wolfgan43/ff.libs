<?php
namespace phpformsframework\libs\international;

use Exception;

/**
 * Trait InternationalManager
 * @package phpformsframework\libs\international
 */
trait InternationalManager
{
    /**
     * @return string
     */
    protected function locale() : string
    {
        return Locale::get();
    }

    /**
     * @return array
     */
    protected function acceptLanguage() : array
    {
        return array_keys(Locale::getAll());
    }

    /**
     * @param string $code
     * @param null|string $language
     * @return string|null
     * @throws Exception
     */
    protected function translate(string $code, string $language = null) : ?string
    {
        return Translator::getWordByCode($code, $language);
    }
}