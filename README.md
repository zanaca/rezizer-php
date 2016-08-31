# RezizerUrl - a PHP Rezizer Url Generator

## Install

Add `rezizer/url` as dependency in `composer.json`.

## Usage

```php
$secretKey = 'OhMyG0shWhatASecretKey!';

// start the generator
$rezizerUrl = new Thumbor\Url('http://your.rezizer.url:port', $secretKey);


// get the Rezized url
$imageUrl = $rezizerUrl->with('http://your.domain.url/foo/bar.jpg')->resize(100, 100)->generate();
```
