<?php

declare (strict_types=1);
namespace Laminas\Validator\Translator;

use Laminas\Translator\Translator_Interface;
interface Translator_Aware_Interface
{
    /**
     * Sets translator to use in helper
     *
     * @param  TranslatorInterface|null $translator  [optional] translator.
     *             Default is null, which sets no translator.
     * @param  string|null $textDomain  [optional] text domain
     *             Default is null, which skips setTranslatorTextDomain
     */
    public function set_translator(?Translator_Interface $translator = null, ?string $text_domain = null): void;
    /**
     * Returns translator used in object
     */
    public function get_translator(): ?Translator_Interface;
}