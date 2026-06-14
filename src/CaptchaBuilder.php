<?php

declare(strict_types=1);

namespace Visavi\Captcha;

use GdImage;
use RuntimeException;

class CaptchaBuilder
{
    protected array $frames;
    protected array $params;
    protected string $phrase;
    protected int $width = 150;
    protected int $height = 40;
    protected string $font;
    protected ?array $textColor = null;
    protected ?array $backgroundColor = null;
    protected int $windowWidth = 75;
    protected int $pixelPerFrame = 15;
    protected int $delayBetweenFrames = 20;

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

        return $this;
    }

    /**
     * Set background color
     */
    public function setBackgroundColor(int $r, int $g, int $b): self
    {
        $this->backgroundColor = [$r, $g, $b];

        return $this;
    }

    /**
     * Set image width
     */
    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set image height
     */
    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set window width
     */
    public function setWindowWidth(int $width): self
    {
        $this->windowWidth = $width;

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

        return $this;
    }

    /**
     * Render captcha
     *
     * @throws RuntimeException
     */
    public function render(): string
    {
        $this->frames = $this->getFrames();

        $delays = [];
        for ($i = 0, $iMax = count($this->frames); $i < $iMax; $i++) {
            $delays[] = $this->delayBetweenFrames;
        }

        return (new GifEncoder($this->frames, $delays, 0, 2))->getAnimation();
    }

    /**
     * Get captcha inline
     */
    public function inline(): string
    {
        return 'data:image/gif;base64,' . base64_encode($this->render());
    }

    /**
     * Returns gif frames
     */
    public function getFrames(): array
    {
        $frames = [];
        $params = $this->getImageParams();

        for ($i = -$this->windowWidth; $i < $this->width; $i += $this->pixelPerFrame) {
            $image = $this->getBaseImage();

            $foregroundColor = $this->createColor($image, $params['backgroundColor']);

            // left foreground rectangle
            imagefilledrectangle($image, 0, 0, $i, $this->height, $foregroundColor);

            // right foreground rectangle
            imagefilledrectangle($image, $i + $this->windowWidth, 0, $this->width, $this->height, $foregroundColor);

            $this->applyEffect($image, $params);

            $frames[] = $this->getImageContent($image);
        }

        return $frames;
    }

    /**
     * Resolve the font path: a custom one if set, otherwise a random bundled font
     *
     * @throws RuntimeException
     */
    protected function resolveFont(): string
    {
        if (isset($this->font)) {
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
     */
    protected function getImageParams(): array
    {
        if (! isset($this->params)) {
            $params = [];
            $params['font'] = $this->resolveFont();
            $params['size'] = $this->width / max(strlen($this->phrase), 5);

            $box = imagettfbbox($params['size'], 0, $params['font'], $this->phrase);

            $params['textWidth'] = $box[2] - $box[0];
            $params['textHeight'] = abs($box[7] + $box[1]);

            $params['x'] = (int) (($this->width - $params['textWidth']) / 2);
            $params['y'] = (int) (($this->height + $params['textHeight']) / 2);

            $params['textColor'] = $this->textColor ?? [rand(0, 150), rand(0, 150), rand(0, 150)];
            $params['backgroundColor'] = $this->backgroundColor ?? [rand(200, 255), rand(200, 255), rand(200, 255)];

            $params['negate'] = rand(0, 1);

            $this->params = $params;
        }

        return $this->params;
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
        imagedestroy($image);

        return ob_get_clean();
    }
}
