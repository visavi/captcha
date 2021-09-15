<?php

declare(strict_types=1);

namespace Visavi\Captcha;

class PhraseBuilder
{
    /**
    * @var int
    */
    public $length;

    /**
     * @var string
     */
    public $charset;

    /**
     * Get random phrase of given length with given charset
     *
     * @param int    $length
     * @param string $charset
     *
     * @return string
     */
    public function getPhrase(
        int $length = 6,
        string $charset = 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ): string {
        $phrase = '';
        $chars = str_split($charset);

        for ($i = 0; $i < $length; $i++) {
            $phrase .= $chars[array_rand($chars)];
        }

        return $phrase;
    }
}
