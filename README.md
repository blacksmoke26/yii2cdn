[![Latest Stable Version](https://poser.pugx.org/blacksmoke26/yii2cdn/v/stable)](https://packagist.org/packages/blacksmoke26/yii2cdn) [![Total Downloads](https://poser.pugx.org/blacksmoke26/yii2cdn/downloads)](https://packagist.org/packages/blacksmoke26/yii2cdn) [![Latest Unstable Version](https://poser.pugx.org/blacksmoke26/yii2cdn/v/unstable)](https://packagist.org/packages/blacksmoke26/yii2cdn) [![License](https://poser.pugx.org/blacksmoke26/yii2cdn/license)](https://packagist.org/packages/blacksmoke26/yii2cdn)
[![GitHub issues](https://img.shields.io/github/issues/blacksmoke26/yii2cdn.svg)](https://github.com/blacksmoke26/yii2cdn/issues)
[![GitHub forks](https://img.shields.io/github/forks/blacksmoke26/yii2cdn.svg)](https://github.com/blacksmoke26/yii2cdn/network)
[![GitHub stars](https://img.shields.io/github/stars/blacksmoke26/yii2cdn.svg)](https://github.com/blacksmoke26/yii2cdn/stargazers)
[![Docs](https://img.shields.io/badge/docs-15%25-yellow.svg)](https://github.com/blacksmoke26/yii2cdn/wiki)
[![Twitter](https://img.shields.io/twitter/url/https/github.com/blacksmoke26/yii2cdn.svg?style=social)](https://twitter.com/intent/tweet?text=Yii2cdn+extension:&url=https://github.com/blacksmoke26/yii2cdn)

# yii2cdn

A Yii Framework 2 component for using assets in different environments (Local/CDNs)

**Notice:** *This is an experimental version. You may face difficulties, bugs and strange issues.* In case of any possibility, please create an [issue](https://github.com/blacksmoke26/yii2cdn/issues) and I will try to help you. :)

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

**Info:** *This tutorial will demonstrate how to use [`Font-Awesome`](https://github.com/FortAwesome/Font-Awesome) library in a <code>production</code> (online/CDN) or <code>development</code> (local/offline) environment.*

#### I. Installing a library
--------------------------
1. Create a <code>cdn</code> directory under your root folder.
2. Install or download [`FortAwesome/Font-Awesome`](https://github.com/FortAwesome/Font-Awesome) library under <code>cdn</code> directory.
  * Path should be `/cdn/font-awesome`.

#### II. Add a component
---------------------
1. Open `@app/config/main.php` in your code editor.
2. Add a new propery `cdn` under `components` section like the following code:

```php
// ...
'components' => [
	// ...
	'cdn' => [
		'class' => '\yii2cdn\Cdn',
		'baseUrl' => '/cdn',
		'basePath' => dirname(__DIR__, 2) . '/cdn',
		'components' => [
        	'font-awesome' => [
            	'css' => [
                	[
                    	'font-awesome.min.css', // offline version
                    	'@cdn' => '//cdnjs.cloudflare.com/ajax/libs/font-awesome/'
                        		. '4.5.0/css/font-awesome.min.css', // online version
                    ]
                ]
            ]
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
Yii::$app->cdn->get('font-awesome')->register();
//...
```

#### IV. Final moment
1. Browse the action url in your browser and check the view souce.

> Now it's time to play around, See ya!
