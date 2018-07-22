[![Latest Stable Version](https://poser.pugx.org/blacksmoke26/yii2cdn/v/stable)](https://packagist.org/packages/blacksmoke26/yii2cdn) [![Total Downloads](https://poser.pugx.org/blacksmoke26/yii2cdn/downloads)](https://packagist.org/packages/blacksmoke26/yii2cdn) [![Latest Unstable Version](https://poser.pugx.org/blacksmoke26/yii2cdn/v/unstable)](https://packagist.org/packages/blacksmoke26/yii2cdn) [![License](https://poser.pugx.org/blacksmoke26/yii2cdn/license)](https://packagist.org/packages/blacksmoke26/yii2cdn)
[![GitHub issues](https://img.shields.io/github/issues/blacksmoke26/yii2cdn.svg)](https://github.com/blacksmoke26/yii2cdn/issues)
[![GitHub forks](https://img.shields.io/github/forks/blacksmoke26/yii2cdn.svg)](https://github.com/blacksmoke26/yii2cdn/network)
[![GitHub stars](https://img.shields.io/github/stars/blacksmoke26/yii2cdn.svg)](https://github.com/blacksmoke26/yii2cdn/stargazers)
[![Docs](https://img.shields.io/badge/docs-15%25-yellow.svg)](https://github.com/blacksmoke26/yii2cdn/wiki)
[![Twitter](https://img.shields.io/twitter/url/https/github.com/blacksmoke26/yii2cdn.svg?style=social)](https://twitter.com/intent/tweet?text=Yii2cdn+extension:&url=https://github.com/blacksmoke26/yii2cdn)

# yii2cdn

A Yii Framework 2 component for using assets in different environments (Local/CDNs)

**Production Ready**: Used in several real projects is enough to prove its stability.

**Minimumn requirements:**: PHP 7.0+ / Yii2 Framework 2.0.12+

**Bugs / Feature Request?:** Create your [issue here](https://github.com/blacksmoke26/yii2cdn/issues).

## Resources

* **Wiki**: [https://github.com/blacksmoke26/yii2cdn/wiki](https://github.com/blacksmoke26/yii2cdn/wiki)
* **Class Reference**: [http://blacksmoke26.github.io/yii2cdn/api/](http://blacksmoke26.github.io/yii2cdn/api/)

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist blacksmoke26/yii2cdn "*"
```

or add

```
"blacksmoke26/yii2cdn": "*"
```

to the require section of your `composer.json` file.


## Usage

**Info:** *This tutorial will demonstrate how to use [`FancyBox3`](http://fancyapps.com/fancybox/3/) library in a <code>production</code> (online/CDN) or <code>development</code> (local/offline) environment.*

#### I. Installing a library
--------------------------
1. Create a <code>cdn</code> directory under the `/root/web` folder.
2. Install or download [`FancyBox3`](http://fancyapps.com/fancybox/3/) library under <code>cdn</code> directory.
  * Path should be `/root/web/cdn/jquery-fancybox`.
  * CDN URLs: https://cdnjs.com/libraries/fancybox/3.3.5

#### II. Add a component
---------------------
1. Open `@app/config/main.php` in your code editor.
2. Add a new property `cdn` under `components` section like the following code:

```php
// ...
'components' => [
	// ...
	'cdn' => [
		'class' => '\yii2cdn\Cdn',
		'baseUrl' => '/cdn',
		'basePath' => dirname(__DIR__) . '/web/cdn',
		'components' => [
        	'jquery-fancybox' => [
                'css' => [
                    '@attributes' => [
                        'noNameInPathUrls' => true, // Hide /css in urls
                    ],
                    [
                        'dist/jquery.fancybox.css', // offline version
                        '@cdn' => '//cdnjs.cloudflare.com/ajax/libs/fancybox/3.3.5/jquery.fancybox.min.css', // online version
                    ],
                ],
                'js' => [
                    '@attributes' => [
                        'noNameInPathUrls' => true, // Hide /js in urls
                    ],
                    [
                        'dist/jquery.fancybox.js', // offline version
                        '@cdn' => '//cdnjs.cloudflare.com/ajax/libs/fancybox/3.3.5/jquery.fancybox.min.js', // online version
                    ],
                ],
            ],
		],
	],
  // ...
],
// ...
```

#### III. Registering assets
-------------------
1. Open any view file and paste the following line:

```php
//...
Yii::$app->cdn->get('jquery.fancybox')->register();
//...
```

#### IV. Final moment
1. Browse the action url in your browser and check the view souce.

> Now it's time to play around, See ya!
