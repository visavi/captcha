<?php

declare(strict_types=1);

namespace Visavi\Captcha;

use Exception;
use GdImage;
use RuntimeException;

class CaptchaBuilder
{
    protected ?array $params = null;
    protected string $phrase;
    protected int $width = 150;
    protected int $height = 40;
    protected ?string $font = null;
    protected ?array $textColor = null;
    protected ?array $backgroundColor = null;
    protected int $windowWidth = 75;
    protected int $pixelPerFrame = 15;
    protected int $delayBetweenFrames = 20;

    /**
     * @throws Exception
     */
    public function __construct(?string $phrase = null)
    {
        if ($phrase) {
            $this->phrase = $phrase;
        } else {
            $phraseBuilder = new PhraseBuilder();
            $this->phrase = $phraseBuilder->getPhrase(random_int(4, 6));
        }
    }

    /**
     * Get phrase
     */
    public function getPhrase(): string
    {
        return $this->phrase;
    }

    /**
     * Set text color
     */
    public function setTextColor(int $r, int $g, int $b): self
    {
        $this->textColor = [$r, $g, $b];
        $this->params = null;

        return $this;
    }

    /**
     * Set background color
     */
    public function setBackgroundColor(int $r, int $g, int $b): self
    {
        $this->backgroundColor = [$r, $g, $b];
        $this->params = null;

        return $this;
    }

    /**
     * Set image width
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;
        $this->params = null;

        return $this;
    }

    /**
     * Set image height
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;
        $this->params = null;

        return $this;
    }

    /**
     * Set window width
     */
    public function setWindowWidth(int $width): self
    {
        $this->windowWidth = $width;
        $this->params = null;

        return $this;
    }

    /**
     * Set pixel per frame
     */
    public function setPixelPerFrame(int $pixel): self
    {
        $this->pixelPerFrame = $pixel;

        return $this;
    }

    /**
     * Set delay between frames (in hundredths of a second, per GIF spec)
     */
    public function setDelayBetweenFrames(int $delay): self
    {
        $this->delayBetweenFrames = $delay;

        return $this;
    }

    /**
     * Set font
     */
    public function setFont(string $path): self
    {
        $this->font = $path;
        $this->params = null;

        return $this;
    }

    /**
     * Render captcha
     *
     * @throws RuntimeException|Exception
     */
    public function render(): string
    {
        $frames = $this->getFrames();
        $delays = array_fill(0, count($frames), $this->delayBetweenFrames);

        return (new GifEncoder($frames, $delays, 0, 2))->getAnimation();
    }

    /**
     * Get captcha inline
     *
     * @throws RuntimeException|Exception
     */
    public function inline(): string
    {
        return 'data:image/gif;base64,' . base64_encode($this->render());
    }

    /**
     * Returns gif frames
     *
     * @throws RuntimeException|Exception
     */
    public function getFrames(): array
    {
        $frames = [];
        $params = $this->getImageParams();
        $base = $this->getBaseImage();

        $window = $params['window'];

        // Even out the step so the last frame wraps back onto the first one
        $count = max((int) round($this->width / $this->pixelPerFrame), 1);

        for ($frame = 0; $frame < $count; $frame++) {
            // Start at the left edge of the text, so the first frame is never blank
            $shift = $params['x'] + (int) round($frame * $this->width / $count);
            $offset = (($shift % $this->width) + $this->width) % $this->width;

            $image = imagecreatetruecolor($this->width, $this->height);

            $backgroundColor = $this->createColor($image, $params['backgroundColor']);
            imagefilledrectangle($image, 0, 0, $this->width, $this->height, $backgroundColor);

            foreach ($this->getVisibleParts($offset, $window) as [$from, $to]) {
                imagecopy($image, $base, $from, 0, $from, 0, $to - $from, $this->height);
            }

            $this->applyEffect($image, $params);

            $frames[] = $this->getImageContent($image);
        }

        return $frames;
    }

    /**
     * Resolve the font path: a custom one if set, otherwise a random bundled font
     *
     * @throws RuntimeException|Exception
     */
    protected function resolveFont(): string
    {
        if ($this->font !== null) {
            if (! is_file($this->font)) {
                throw new RuntimeException('Font file not found: ' . $this->font);
            }

            return $this->font;
        }

        $fonts = glob(__DIR__ . '/../fonts/*.ttf') ?: [];

        if (! $fonts) {
            throw new RuntimeException('No bundled fonts found in ' . __DIR__ . '/../fonts');
        }

        return $fonts[random_int(0, count($fonts) - 1)];
    }

    /**
     * Get image params
     *
     * @throws RuntimeException|Exception
     */
    protected function getImageParams(): array
    {
        if ($this->params === null) {
            $params = [];
            $characters = $this->getCharacters();

            $params['font'] = $this->resolveFont();
            $params['size'] = $this->width / max(count($characters), 5);

            $box = imagettfbbox($params['size'], 0, $params['font'], $this->phrase);

            $params['textWidth'] = $box[2] - $box[0];
            $params['textHeight'] = abs($box[7] + $box[1]);

            $params['x'] = (int) (($this->width - $params['textWidth']) / 2);
            $params['y'] = (int) (($this->height + $params['textHeight']) / 2);

            $params['window'] = $this->resolveWindowWidth($params);

            $params['textColor'] = $this->textColor ?? [random_int(0, 150), random_int(0, 150), random_int(0, 150)];
            $params['backgroundColor'] = $this->backgroundColor ?? [random_int(200, 255), random_int(200, 255), random_int(200, 255)];

            $params['negate'] = random_int(0, 1);

            $this->params = $params;
        }

        return $this->params;
    }

    /**
     * Parts of the image the window shows at the given offset, wrapping around the right edge
     */
    protected function getVisibleParts(int $offset, int $window): array
    {
        $head = min($window, $this->width - $offset);
        $parts = [[$offset, $offset + $head]];

        if ($window > $head) {
            $parts[] = [0, $window - $head];
        }

        return $parts;
    }

    /**
     * Split the phrase into characters, multibyte aware
     */
    protected function getCharacters(): array
    {
        return preg_split('//u', $this->phrase, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Horizontal bounds of every character of the phrase
     */
    protected function getCharacterRanges(array $params): array
    {
        $characters = $this->getCharacters();
        $ranges = [];
        $start = 0;

        for ($i = 0, $length = count($characters); $i < $length; $i++) {
            $prefix = implode('', array_slice($characters, 0, $i + 1));
            $box = imagettfbbox($params['size'], 0, $params['font'], $prefix);
            $end = $box[2] - $box[0];

            $ranges[] = [$params['x'] + $start, $params['x'] + $end];
            $start = $end;
        }

        return $ranges;
    }

    /**
     * Shrink the window until no frame can show the whole phrase at once
     */
    protected function resolveWindowWidth(array $params): int
    {
        $ranges = $this->getCharacterRanges($params);
        $window = min($this->windowWidth, $this->width);

        while ($window > 1 && ! $this->hidesCharacter($window, $ranges)) {
            $window--;
        }

        return $window;
    }

    /**
     * Whether a window of the given width keeps at least one character fully hidden in every frame
     */
    protected function hidesCharacter(int $window, array $ranges): bool
    {
        for ($offset = 0; $offset < $this->width; $offset++) {
            $parts = $this->getVisibleParts($offset, $window);
            $hidden = false;

            foreach ($ranges as [$start, $end]) {
                $visible = false;

                foreach ($parts as [$from, $to]) {
                    if ($start < $to && $end > $from) {
                        $visible = true;
                        break;
                    }
                }

                if (! $visible) {
                    $hidden = true;
                    break;
                }
            }

            if (! $hidden) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply some post effects
     */
    protected function applyEffect(GdImage $image, array $params): void
    {
        if (! function_exists('imagefilter')) {
            return;
        }

        if ($this->backgroundColor || $this->textColor) {
            return;
        }

        if ($params['negate'] === 1) {
            imagefilter($image, IMG_FILTER_NEGATE);
        }
    }

    /**
     * Create a base image with the text
     *
     * @throws RuntimeException|Exception
     */
    protected function getBaseImage(): GdImage
    {
        $params = $this->getImageParams();
        $image = imagecreatetruecolor($this->width, $this->height);

        // Background
        $backgroundColor = $this->createColor($image, $params['backgroundColor']);
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $backgroundColor);

        // Text
        $textColor = $this->createColor($image, $params['textColor']);
        imagettftext($image, $params['size'], 0, $params['x'], $params['y'], $textColor, $params['font'], $this->phrase);

        return $image;
    }

    /**
     * Create color
     */
    protected function createColor(GdImage $image, array $color): bool|int
    {
        return imagecolorallocate($image, $color[0], $color[1], $color[2]);
    }

    /**
     * Get image content
     */
    protected function getImageContent(GdImage $image): bool|string
    {
        ob_start();
        imagegif($image);

        return ob_get_clean();
    }
}
