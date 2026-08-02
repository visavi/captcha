<?php

declare(strict_types=1);

namespace Visavi\Captcha;

use RuntimeException;

class GifEncoder
{
    /* GIF header 6 bytes */
    private string $gif = 'GIF89a';
    private array $buf = [];
    private bool $first = true;
    private int $lop;
    private int $dis;

    /**
     * GIF encoder
     *
     * @param array $frames Binary contents of the source gif images
     * @param array $delays Frame delays, in hundredths of a second
     * @param int   $lop    Loop count, 0 means forever
     * @param int   $dis    Disposal method
     *
     * @throws RuntimeException
     */
    public function __construct(array $frames, array $delays, int $lop, int $dis)
    {
        $this->lop = max($lop, 0);
        $this->dis = ($dis > -1) ? min($dis, 3) : 2;

        foreach ($frames as $i => $frame) {
            if (! str_starts_with($frame, 'GIF87a') && ! str_starts_with($frame, 'GIF89a')) {
                throw new RuntimeException(sprintf('%s (%d source)', 'Source is not a GIF image!', $i));
            }

            $this->assertNotAnimated($frame, $i);

            $this->buf[] = $frame;
        }

        $this->addHeader();

        foreach ($this->buf as $i => $frame) {
            $this->addFrames($i, $delays[$i] ?? 0);
        }

        $this->addFooter();
    }

    /**
     * Reject sources that are animated themselves
     *
     * @throws RuntimeException
     */
    private function assertNotAnimated(string $frame, int $i): void
    {
        $length = strlen($frame);

        for ($j = 13 + 3 * (2 << (ord($frame[10]) & 0x07)); $j < $length; $j++) {
            if ($frame[$j] === ';') {
                return;
            }

            if ($frame[$j] === '!' && substr($frame, $j + 3, 8) === 'NETSCAPE') {
                throw new RuntimeException(sprintf(
                    '%s (%d source)',
                    'Does not make animation from animated GIF source!',
                    $i + 1
                ));
            }
        }
    }

    /**
     * Add header
     */
    private function addHeader(): void
    {
        if (ord($this->buf[0][10]) & 0x80) {
            $cmap = 3 * (2 << (ord($this->buf[0][10]) & 0x07));
            $this->gif .= substr($this->buf[0], 6, 7);
            $this->gif .= substr($this->buf[0], 13, $cmap);
            $this->gif .= "!\377\13NETSCAPE2.0\3\1";
            $this->gif .= chr($this->lop & 0xFF) . chr(($this->lop >> 8) & 0xFF) . "\0";
        }
    }

    /**
     * Add frames
     */
    private function addFrames(int $i, int $d): void
    {
        $localStr = 13 + 3 * (2 << (ord($this->buf[$i][10]) & 0x07));
        $localEnd = strlen($this->buf[$i]) - $localStr - 1;
        $localTmp = substr($this->buf[$i], $localStr, $localEnd);
        $globalLen = 2 << (ord($this->buf[0][10]) & 0x07);
        $localLen = 2 << (ord($this->buf[$i][10]) & 0x07);
        $globalRgb = substr($this->buf[0], 13, 3 * $globalLen);
        $localRgb = substr($this->buf[$i], 13, 3 * $localLen);
        $localExt = "!\xF9\x04" . chr($this->dis << 2) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . "\x0\x0";

        switch ($localTmp[0]) {
            case '!':
                $localImg = substr($localTmp, 8, 10);
                $localTmp = substr($localTmp, 18);
                break;
            case ',':
                $localImg = substr($localTmp, 0, 10);
                $localTmp = substr($localTmp, 10);
                break;
            default:
                $localImg = $localTmp;
        }

        // The first frame is described by the global color table written in the header
        $samePalette = $globalLen === $localLen && $this->blockCompare($globalRgb, $localRgb, $globalLen);

        if ($this->first || $samePalette || ! (ord($this->buf[$i][10]) & 0x80)) {
            $this->gif .= $localExt . $localImg . $localTmp;
        } else {
            // Frame palette differs from the global one, so keep it as a local color table
            $byte = ord($localImg[9]);
            $byte |= 0x80;
            $byte &= 0xF8;
            $byte |= (ord($this->buf[$i][10]) & 0x07);
            $localImg[9] = chr($byte);

            $this->gif .= $localExt . $localImg . $localRgb . $localTmp;
        }

        $this->first = false;
    }

    /**
     * Add footer
     */
    private function addFooter(): void
    {
        $this->gif .= ';';
    }

    /**
     * Block compare
     */
    private function blockCompare(string $globalBlock, string $localBlock, int $len): bool
    {
        for ($i = 0; $i < $len; $i++) {
            if (
                $globalBlock[3 * $i + 0] !== $localBlock[3 * $i + 0]
                || $globalBlock[3 * $i + 1] !== $localBlock[3 * $i + 1]
                || $globalBlock[3 * $i + 2] !== $localBlock[3 * $i + 2]
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get animation
     */
    public function getAnimation(): string
    {
        return $this->gif;
    }
}
