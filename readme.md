# Captcha

[![Latest Stable Version](https://poser.pugx.org/visavi/captcha/v/stable)](https://packagist.org/packages/visavi/captcha)
[![Total Downloads](https://poser.pugx.org/visavi/captcha/downloads)](https://packagist.org/packages/visavi/captcha)
[![Latest Unstable Version](https://poser.pugx.org/visavi/captcha/v/unstable)](https://packagist.org/packages/visavi/captcha)
[![License](https://poser.pugx.org/visavi/captcha/license)](https://packagist.org/packages/visavi/captcha)

Animated GIF captcha for PHP. The phrase is never shown at once: a narrow window slides over the
text, so a human reads it frame by frame while a single frame gives a bot almost nothing.

![example1](examples/captcha1.gif)
![example2](examples/captcha2.gif)
![example3](examples/captcha3.gif)

* **No dependencies** beyond `ext-gd` — no ImageMagick, no mbstring, no font files to find
* **Seven bundled fonts**, picked at random, with random colors and inversion
* **Guaranteed partial view** — the window is shrunk automatically until no frame can show the
  whole phrase, whatever the font, size or phrase length
* **Seamless loop** — the text leaves through the right edge and enters from the left one

## Installation

```
composer require visavi/captcha
```

Requires PHP 8.0+ and `ext-gd` compiled with FreeType support.

## Usage

Serve the captcha as an image:

```php
use Visavi\Captcha\CaptchaBuilder;

header('Content-Type: image/gif');

$captcha = new CaptchaBuilder();
$_SESSION['captcha'] = $captcha->getPhrase();

echo $captcha->render();
```

Or embed it straight into the page, no separate request and no headers needed:

```php
$captcha = new CaptchaBuilder();
$_SESSION['captcha'] = $captcha->getPhrase();

echo '<img src="' . $captcha->inline() . '" alt="captcha">';
```

Then check the answer, and drop the phrase so it cannot be replayed:

```php
$phrase = $_SESSION['captcha'] ?? '';
unset($_SESSION['captcha']);

if ($phrase === '' || ! hash_equals(strtolower($phrase), strtolower(trim($_POST['captcha'] ?? '')))) {
    // wrong answer
}
```

## Configuration

Every setter is optional and returns `$this`, so calls can be chained:

```php
use Visavi\Captcha\CaptchaBuilder;
use Visavi\Captcha\PhraseBuilder;

$phrase = (new PhraseBuilder())->getPhrase(5, '1234567890');

$captcha = (new CaptchaBuilder($phrase))
    ->setWidth(150)
    ->setHeight(50)
    ->setTextColor(0, 0, 0)
    ->setBackgroundColor(255, 255, 255)
    ->setFont('/path-to-font.ttf')
    ->setWindowWidth(60)
    ->setPixelPerFrame(5)
    ->setDelayBetweenFrames(10);
```

| Method | Default | Description |
|---|---|---|
| `setWidth(int $width)` | `150` | Image width, px |
| `setHeight(int $height)` | `40` | Image height, px |
| `setTextColor(int $r, int $g, int $b)` | random dark | Text color |
| `setBackgroundColor(int $r, int $g, int $b)` | random light | Background color |
| `setFont(string $path)` | random bundled font | Path to a TTF font |
| `setWindowWidth(int $width)` | `75` | Width of the sliding window, px |
| `setPixelPerFrame(int $pixel)` | `15` | Window shift per frame, px — smaller is smoother and heavier |
| `setDelayBetweenFrames(int $delay)` | `20` | Delay between frames, in hundredths of a second |

Reading the result:

| Method | Description |
|---|---|
| `getPhrase()` | The phrase the user is expected to type |
| `render()` | Binary GIF contents |
| `inline()` | Base64 `data:` URI, ready for `<img src>` |
| `getFrames()` | Raw frames, if you want to encode them yourself |

Colors and inversion are random unless set explicitly, so the same settings still give a different
looking image every time.

Both colors and the phrase are left untouched when you set them yourself; everything else —
font, inversion, and the exact window width — is decided per image.

## How the window works

`setWindowWidth()` is an upper bound, not an exact value. Before rendering, the window is shrunk
until no frame can show the whole phrase: narrow fonts, short phrases and wide windows are all
handled, and the guarantee holds for every frame, including the ones where the window wraps around
the edge of the image.

The number of frames is `width / pixelPerFrame`, evened out so the last frame lands back on the
first — the animation loops without a visible jump. So a 150 px image with the default 15 px step
is 10 frames.

## Examples

Same size and settings, different bundled fonts:

![example1](examples/captcha1.gif)
![example2](examples/captcha2.gif)
![example3](examples/captcha3.gif)

Different window width and speed — 40 px window at 4 px/frame, 75 at 10, 120 at 25:

![example4](examples/captcha4.gif)
![example5](examples/captcha5.gif)
![example6](examples/captcha6.gif)

Mini, 100×30 with a 4 character phrase:

![example7](examples/captcha7.gif)
![example8](examples/captcha8.gif)
![example9](examples/captcha9.gif)

## Phrases

`PhraseBuilder` generates the phrase with `random_int()`. The default charset skips characters that
are easy to misread — `i`, `l`, `o`, `0`, `1`:

```php
$phrase = new PhraseBuilder();

$phrase->getPhrase();                       // 6 characters, default charset
$phrase->getPhrase(5, '1234567890');        // digits only
$phrase->getPhrase(4, 'абвгдежзик');        // any UTF-8 charset
```

A custom charset is split by code points, so multibyte phrases work — provided the font has the
glyphs. Of the bundled fonts only `fonts/5.ttf` covers Cyrillic; for anything else pass your own
font via `setFont()`.

## License

The class is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
