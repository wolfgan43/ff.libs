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
     * @return string|null
     */
    protected function locale() : ?string
    {
        return Locale::get();
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