<?php

declare(strict_types=1);

namespace Visavi\Captcha;

class PhraseBuilder
{
    /**
     * Get random phrase of given length with given charset
     */
    public function getPhrase(
        int $length = 6,
        int|string $characters = 'abcdefghjkmnpqrstuvwxyz23456789'
    ): string {
        $phrase = '';
        $characters = (string) $characters;
        $charactersLength = strlen($characters);

        for ($i = 0; $i < $length; $i++) {
            $phrase .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $phrase;
    }
}
