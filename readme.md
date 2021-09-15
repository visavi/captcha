# Captcha

[![Latest Stable Version](https://poser.pugx.org/visavi/captcha/v/stable)](https://packagist.org/packages/visavi/captcha)
[![Total Downloads](https://poser.pugx.org/visavi/captcha/downloads)](https://packagist.org/packages/visavi/captcha)
[![Latest Unstable Version](https://poser.pugx.org/visavi/captcha/v/unstable)](https://packagist.org/packages/visavi/captcha)
[![License](https://poser.pugx.org/visavi/captcha/license)](https://packagist.org/packages/visavi/captcha)

## Examples

### Default
![example1](examples/captcha1.gif)
![example2](examples/captcha2.gif)
![example3](examples/captcha3.gif)

### Advanced
![example4](examples/captcha4.gif)
![example5](examples/captcha5.gif)
![example6](examples/captcha6.gif)

### Mini
![example7](examples/captcha7.gif)
![example8](examples/captcha8.gif)
![example9](examples/captcha9.gif)

## Methods

* **setPhrase** - Phrase (Required)
* **setWidth** - Image width, px (Optional, default 150px)
* **setHeight** - Image height, px  (Optional, default 40px)
* **setTextColor** - Text color  (Optional)
* **setBackgroundColor** - Background color  (Optional)
* **setFont** - Font path  (Optional)
* **setWindowWidth** - Window width, px  (Optional, default 75px)
* **setPixelPerFrame** - Window shift per frame, px  (Optional, default 15px)
* **setDelayBetweenFrames** - Time between frames, ms)  (Optional, default 20ms)

## Code

```php
use Visavi\Captcha\PhraseBuilder;
use Visavi\Captcha\CaptchaBuilder;

$phrase = new PhraseBuilder();
$phrase = $phrase->getPhrase(5, '1234567890');

$captcha = new CaptchaBuilder();
$captcha->setPhrase($phrase)
    ->setWidth(150)
    ->setHeight(50)
    ->setTextColor(0, 0, 0)
    ->setBackgroundColor(255, 255, 255)
    ->setFont('/path-to-font')
    ->setWindowWidth(60)
    ->setPixelPerFrame(15)
    ->setDelayBetweenFrames(20);

return $captcha->render();
```

## Installation

```
composer require visavi/captcha
```

## License

The class is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
