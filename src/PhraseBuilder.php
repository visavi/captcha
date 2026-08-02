<?php

declare(strict_types=1);

namespace Visavi\Captcha;

use Exception;

class PhraseBuilder
{
    /**
     * Get random phrase of given length with given charset
     *
     * @throws Exception
     */
    public function getPhrase(
        int $length = 6,
        int|string $characters = 'abcdefghjkmnpqrstuvwxyz23456789'
    ): string {
        $phrase = '';
        $charset = preg_split('//u', (string) $characters, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charsetLength = count($charset);

        if (! $charsetLength) {
            return $phrase;
        }

        for ($i = 0; $i < $length; $i++) {
            $phrase .= $charset[random_int(0, $charsetLength - 1)];
        }

        return $phrase;
    }
}
